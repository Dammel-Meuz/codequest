<?php

namespace App\Jobs;


use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessExerciseSubmission implements ShouldQueue
{
    use Queueable;

    public int $submissionId;

    public function __construct(int $submissionId)
    {
        $this->submissionId = $submissionId;
    }

    public function handle(): void
    {
        $submission = Submission::find($this->submissionId);

        if (!$submission) {
            return;
        }

        // Le traitement de la correction sera ajouté plus tard.
    }
}
