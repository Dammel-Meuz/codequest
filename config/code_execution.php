<?php

return [
    'docker_binary' => env('CODE_EXECUTION_DOCKER_BINARY', 'docker'),
    'image' => env('CODE_EXECUTION_IMAGE', 'codequest-runner:node24'),
    'process_timeout' => 15,
    'submission_timeout' => 45,
    'output_limit' => 65536,
    'error_output_limit' => 8192,
];
