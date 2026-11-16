<?php

namespace App\Services\Shop;

use App\Models\CreditPlan;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\ShopFormatter;

class HomepageCreditPlanService
{
    public function __construct(
        protected PaymentGatewayCatalog $gatewayCatalog,
    ) {}

    /**
     * ویجت خانه = همان طرح‌های فعال `/plans` (بدون سقف ۴ تایی).
     *
     * @return array{cards: list<array{label: string, intro: string, plan_url: string, icon: ?string}>, show_continue: bool, continue_url: string}
     */
    public function homepageWidget(): array
    {
        $continueUrl = route('plans.index');

        if (CreditPlan::query()->active()->exists()) {
            $cards = array_map(
                static fn (array $card): array => [
                    'label' => $card['label'],
                    'intro' => $card['intro'],
                    'plan_url' => $card['plan_url'],
                    'icon' => $card['icon'],
                ],
                $this->allActiveCards(),
            );

            return [
                'cards' => $cards,
                'show_continue' => false,
                'continue_url' => $continueUrl,
            ];
        }

        $gatewayCards = $this->gatewayCatalog->homepagePlanCards();
        $mapped = array_map(fn (array $card) => [
            'label' => $card['label'],
            'intro' => $card['intro'],
            'plan_url' => $card['plan_url'],
            'icon' => $card['icon'],
        ], $gatewayCards);

        return [
            'cards' => $mapped,
            'show_continue' => false,
            'continue_url' => $continueUrl,
        ];
    }

    /**
     * @return list<array{id: int, label: string, intro: string, plan_url: string, icon: ?string}>
     */
    public function allActiveCards(): array
    {
        return CreditPlan::query()
            ->active()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (CreditPlan $plan) => [
                'id' => $plan->id,
                ...$this->cardFromModel($plan),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, intro: string, plan_url: string, icon: ?string}
     */
    protected function cardFromModel(CreditPlan $plan): array
    {
        $intro = trim((string) ($plan->short_description ?? ''));
        if ($intro === '') {
            $intro = 'مشاهده جزئیات و شرایط این طرح';
        }

        $planUrl = trim($plan->plan_url);
        if ($planUrl !== '' && ! str_starts_with($planUrl, 'http://') && ! str_starts_with($planUrl, 'https://')) {
            $planUrl = url($planUrl);
        }

        $icon = $plan->icon
            ? ShopFormatter::sectionImage('credit-plans', $plan->icon, '')
            : null;
        if ($icon === '') {
            $icon = null;
        }

        if ($icon === null && filled($plan->credit_gateway)) {
            $icon = $this->gatewayCatalog->iconUrl((string) $plan->credit_gateway);
        }

        return [
            'label' => $plan->name,
            'intro' => $intro,
            'plan_url' => $planUrl,
            'icon' => $icon,
        ];
    }
}
