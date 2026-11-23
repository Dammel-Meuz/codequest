<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class JavaScriptSandbox
{
    public function run(string $sourceCode, mixed $inputData, float $remainingSeconds): array
    {
        $payload = json_encode([
            'source_code' => $sourceCode,
            'input_data' => $inputData,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false || strlen($payload) > 131072) {
            throw new RuntimeException('Les données de correction dépassent la taille autorisée.');
        }

        if ($remainingSeconds <= 0) {
            throw new RuntimeException('Le temps maximal de correction est écoulé.');
        }

        $image = (string) config('code_execution.image');
        $dockerBinary = (string) config('code_execution.docker_binary');

        if ($image === '' || $dockerBinary === '') {
            throw new RuntimeException('Le moteur de correction isolé n’est pas configuré.');
        }

        $containerId = null;
        $deadline = microtime(true) + $remainingSeconds;

        try {
            $container = new Process([
                $dockerBinary,
                'create',
                '--pull=never',
                '--network=none',
                '--read-only',
                '--memory=128m',
                '--memory-swap=128m',
                '--cpus=0.5',
                '--pids-limit=32',
                '--security-opt=no-new-privileges',
                '--cap-drop=ALL',
                '--user=10001:10001',
                '--tmpfs',
                '/tmp:rw,noexec,nosuid,size=16m',
                '--log-driver=local',
                '--log-opt=max-size=64k',
                '--log-opt=max-file=2',
                '--env=NODE_OPTIONS=--max-old-space-size=64',
                '--entrypoint=/bin/sh',
                $image,
                '-c',
                'while [ ! -f /tmp/input.json ]; do sleep 0.05; done; exec node /runner.cjs',
            ], base_path());
            $container->setTimeout(min(
                (float) config('code_execution.process_timeout', 5),
                $this->remainingSeconds($deadline)
            ));
            $container->run();

            if (! $container->isSuccessful()) {
                throw new RuntimeException(
                    'Le conteneur de correction n’a pas pu être créé : '.trim($container->getErrorOutput())
                );
            }

            $containerId = trim($container->getOutput());

            if (! preg_match('/\A[a-f0-9]{64}\z/i', $containerId)) {
                throw new RuntimeException('Docker a renvoyé un identifiant de conteneur invalide.');
            }

            $this->startContainer($dockerBinary, $containerId, $deadline);
            $this->sendInput($dockerBinary, $payload, $containerId, $deadline);
            $exitCode = $this->waitForContainer($dockerBinary, $containerId, $deadline);

            if ($exitCode !== 0) {
                throw new RuntimeException('Le moteur isolé s’est arrêté avec une erreur.');
            }

            $logs = new Process([$dockerBinary, 'logs', $containerId], base_path());
            $logs->setTimeout(min(
                (float) config('code_execution.process_timeout', 5),
                $this->remainingSeconds($deadline)
            ));
            $logs->run();

            if (! $logs->isSuccessful()) {
                throw new RuntimeException(
                    'Impossible de récupérer le résultat du moteur isolé : '.trim($logs->getErrorOutput())
                );
            }

            $output = $logs->getOutput();

            if (strlen($output) > (int) config('code_execution.output_limit', 65536)) {
                throw new RuntimeException('Le moteur de correction a dépassé la taille de sortie autorisée.');
            }

            try {
                $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new RuntimeException('Le moteur isolé a renvoyé un résultat invalide.', previous: $exception);
            }

            if (! is_array($result) || ! array_key_exists('actual', $result)) {
                throw new RuntimeException('Le moteur isolé a renvoyé un résultat incomplet.');
            }

            return $result;
        } finally {
            if ($containerId !== null) {
                $this->removeContainer($dockerBinary, $containerId);
            }
        }
    }

    private function startContainer(string $dockerBinary, string $containerId, float $deadline): void
    {
        $start = new Process([$dockerBinary, 'start', $containerId], base_path());
        $start->setTimeout(min(
            (float) config('code_execution.process_timeout', 5),
            $this->remainingSeconds($deadline)
        ));
        $start->run();

        if (! $start->isSuccessful()) {
            throw new RuntimeException('Le conteneur de correction n’a pas pu démarrer : '.trim($start->getErrorOutput()));
        }
    }

    private function sendInput(
        string $dockerBinary,
        string $payload,
        string $containerId,
        float $deadline
    ): void {
        $input = new Process([
            $dockerBinary,
            'exec',
            '--interactive',
            '--user=10001:10001',
            $containerId,
            'sh',
            '-c',
            'cat > /tmp/input.json.tmp && mv /tmp/input.json.tmp /tmp/input.json',
        ], base_path());
        $input->setInput($payload);
        $input->setTimeout(min(
            (float) config('code_execution.process_timeout', 5),
            $this->remainingSeconds($deadline)
        ));
        $input->run();

        if (! $input->isSuccessful()) {
            throw new RuntimeException('Les données ne peuvent pas être transmises au moteur isolé : '.trim($input->getErrorOutput()));
        }
    }

    private function waitForContainer(string $dockerBinary, string $containerId, float $deadline): int
    {
        do {
            $inspect = new Process([
                $dockerBinary,
                'inspect',
                '--format',
                '{{.State.Running}}|{{.State.ExitCode}}',
                $containerId,
            ], base_path());
            $inspect->setTimeout(min(2, $this->remainingSeconds($deadline)));
            $inspect->run();

            if (! $inspect->isSuccessful()) {
                throw new RuntimeException('Impossible de vérifier l’état du conteneur de correction.');
            }

            $state = explode('|', trim($inspect->getOutput()), 2);

            if (count($state) !== 2) {
                throw new RuntimeException('Docker a renvoyé un état de conteneur invalide.');
            }

            if ($state[0] === 'false') {
                return (int) $state[1];
            }

            usleep(100000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Le conteneur de correction a dépassé son temps maximal.');
    }

    private function remainingSeconds(float $deadline): float
    {
        $remaining = $deadline - microtime(true);

        if ($remaining <= 0) {
            throw new RuntimeException('Le temps maximal de correction est écoulé.');
        }

        return $remaining;
    }

    private function removeContainer(string $dockerBinary, string $containerId): void
    {
        $cleanup = new Process([$dockerBinary, 'rm', '--force', $containerId], base_path());
        $cleanup->setTimeout((float) config('code_execution.process_timeout', 5));

        try {
            $cleanup->run();

            if (! $cleanup->isSuccessful()) {
                Log::warning('Could not remove JavaScript sandbox container', [
                    'container_id' => $containerId,
                    'error' => trim($cleanup->getErrorOutput()),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Could not remove JavaScript sandbox container', [
                'container_id' => $containerId,
                'exception' => $exception,
            ]);
        }
    }
}
