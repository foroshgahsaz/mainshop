<?php

namespace App\Console\Commands;

use App\Services\Shop\CreditPlanGatewaySchemaRepair;
use Illuminate\Console\Command;

class RepairCreditPlanGatewaySchemaCommand extends Command
{
    protected $signature = 'shop:repair-credit-plan-gateways';

    protected $description = 'Add missing credit_plans.credit_gateway / cash_gateway and orders.credit_plan_id columns';

    public function handle(CreditPlanGatewaySchemaRepair $repair): int
    {
        CreditPlanGatewaySchemaRepair::forgetCache();
        $actions = $repair->ensure();

        if ($actions === []) {
            $this->components->info('Credit plan gateway columns already present.');
        } else {
            $this->components->info('Repaired: '.implode(', ', $actions));
        }

        return self::SUCCESS;
    }
}
