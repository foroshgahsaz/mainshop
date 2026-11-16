<?php

namespace App\Console\Commands;

use App\Services\Shop\EnsureDarikCreditPlan;
use Illuminate\Console\Command;

class EnsureDarikCreditPlanCommand extends Command
{
    protected $signature = 'shop:ensure-darik-credit-plan';

    protected $description = 'Ensure داریک credit plan card exists for /plans and homepage';

    public function handle(EnsureDarikCreditPlan $ensure): int
    {
        if ($ensure->ensure()) {
            $this->components->info('کارت طرح داریک ساخته شد (/plans و صفحه اصلی).');

            return self::SUCCESS;
        }

        $this->components->info('کارت طرح داریک از قبل وجود دارد.');

        return self::SUCCESS;
    }
}
