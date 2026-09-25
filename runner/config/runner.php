<?php

return [

    // Shared with the main API, which signs every request to POST /run.
    'secret' => env('RUNNER_SECRET'),

    // PHP's max_execution_time for learner code; the wall-clock limit is one second longer.
    'timeout' => (int) env('RUNNER_TIMEOUT', 5),

];
