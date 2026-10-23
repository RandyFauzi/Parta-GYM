<?php

namespace App\Console\Commands;

use App\Services\CheckoutService;
use Illuminate\Console\Command;

class CloseGymVisits extends Command
{
    protected $signature = 'gym:checkout-due';

    protected $description = 'Close due member visits at their recorded gym closing time';

    public function handle(CheckoutService $service): int
    {
        $this->info($service->closeDue().' kunjungan ditutup otomatis.');

        return self::SUCCESS;
    }
}
