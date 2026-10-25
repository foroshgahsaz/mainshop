<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DigipayLeasingController extends Controller
{
    /** سقف اعتبار نمایشی (میلیون تومان) */
    public const CREDIT_CAP_MILLIONS = 200;

    public function __invoke(): View
    {
        return view('plans.digipay-leasing', [
            'creditCapMillions' => self::CREDIT_CAP_MILLIONS,
        ]);
    }
}
