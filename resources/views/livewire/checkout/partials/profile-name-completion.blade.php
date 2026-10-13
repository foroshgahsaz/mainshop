<section class="shop-card checkout-card checkout-profile-names mb-4 border-2 border-amber-200 bg-amber-50/40">
    <h2 class="shop-section-title text-amber-950">تکمیل نام حساب</h2>
    <p class="text-sm text-amber-950/80 mb-4 leading-6">
        نام شما در سیستم هنوز «کاربر {{ substr(auth()->user()->phone ?? '', -4) }}» است. نام و نام خانوادگی را وارد کنید؛ در هدر سایت و فیلد «نام گیرنده» اعمال می‌شود.
    </p>
    <div class="checkout-address-form__row">
        <div>
            <label class="shop-label">نام</label>
            <input type="text" wire:model="profileFirstName" class="shop-input bg-white" placeholder="نام">
            @error('profileFirstName') <span class="checkout-field-error">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="shop-label">نام خانوادگی</label>
            <input type="text" wire:model="profileLastName" class="shop-input bg-white" placeholder="نام خانوادگی">
            @error('profileLastName') <span class="checkout-field-error">{{ $message }}</span> @enderror
        </div>
    </div>
    <button type="button" wire:click="saveCheckoutProfileNames" wire:loading.attr="disabled" class="shop-btn-primary mt-3">
        <span wire:loading.remove wire:target="saveCheckoutProfileNames">ذخیره نام</span>
        <span wire:loading wire:target="saveCheckoutProfileNames">در حال ذخیره...</span>
    </button>
</section>
