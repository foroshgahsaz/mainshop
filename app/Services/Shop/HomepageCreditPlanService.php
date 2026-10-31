<?php

namespace App\Services\Shop;

use App\Models\CreditPlan;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\ShopFormatter;

class HomepageCreditPlanService
{
    public const HOMEPAGE_DESKTOP_LIMIT = 4;

    public function __construct(
        protected PaymentGatewayCatalog $gatewayCatalog,
    ) {}

    /**
     * @return array{cards: list<array{label: string, intro: string, plan_url: string, icon: ?string}>, show_continue: bool, continue_url: string}
     */
    public function homepageWidget(): array
    {
        $continueUrl = route('plans.index');

        if (CreditPlan::query()->active()->exists()) {
            $eligible = CreditPlan::query()
                ->active()
                ->homepageDesktop()
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $cards = $eligible->take(self::HOMEPAGE_DESKTOP_LIMIT)
                ->map(fn (CreditPlan $plan) => $this->cardFromModel($plan))
                ->values()
                ->all();

            $activeCount = CreditPlan::query()->active()->count();

            return [
                'cards' => $cards,
                'show_continue' => $activeCount > count($cards),
                'continue_url' => $continueUrl,
            ];
        }

        $gatewayCards = $this->gatewayCatalog->homepagePlanCards();

        return [
            'cards' => array_map(fn (array $card) => [
                'label' => $card['label'],
                'intro' => $card['intro'],
                'plan_url' => $card['plan_url'],
                'icon' => $card['icon'],
            ], $gatewayCards),
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

        return [
            'label' => $plan->name,
            'intro' => $intro,
            'plan_url' => $planUrl,
            'icon' => $icon,
        ];
    }
}
