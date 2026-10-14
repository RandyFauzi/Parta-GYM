<?php

return [
    'name' => env('GYM_NAME', 'Parta GYM'),
    'timezone' => env('GYM_TIMEZONE', 'Asia/Makassar'),
    'currency' => 'IDR',
    'duplicate_minutes' => (int) env('GYM_DUPLICATE_MINUTES', 15),
];
