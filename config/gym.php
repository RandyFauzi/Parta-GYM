<?php

return [
    'name' => env('GYM_NAME', 'SPARTA Gym & Pilates'),
    'timezone' => env('GYM_TIMEZONE', 'Asia/Makassar'),
    'currency' => 'IDR',
    'payment_methods' => ['cash' => 'Tunai', 'bank_transfer' => 'Transfer bank', 'qris' => 'QRIS', 'card' => 'Kartu / EDC', 'other' => 'Lainnya'],
    'preview_seed_enabled' => (bool) env('PREVIEW_SEED_ENABLED', false),
    'preview_seed_password' => env('PREVIEW_SEED_PASSWORD'),
    'duplicate_minutes' => (int) env('GYM_DUPLICATE_MINUTES', 15),
];
