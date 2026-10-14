<?php

return [
    'name' => env('GYM_NAME', 'PARTA GYM'),
    'timezone' => env('GYM_TIMEZONE', 'Asia/Makassar'),
    'currency' => 'IDR',
    'duplicate_minutes' => (int) env('GYM_DUPLICATE_MINUTES', 15),
];
