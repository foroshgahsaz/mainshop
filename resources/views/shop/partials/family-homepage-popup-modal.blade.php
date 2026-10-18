<div id="familyHomepagePopupModal"
     class="family-homepage-popup"
     role="dialog"
     aria-modal="true"
     aria-labelledby="familyHomepagePopupModalTitle"
     aria-hidden="true">
    <div class="family-homepage-popup__panel">
        <div class="family-homepage-popup__header">
            <h3 id="familyHomepagePopupModalTitle" class="family-homepage-popup__title" data-family-popup-title></h3>
            <button type="button"
                    class="family-homepage-popup__close"
                    onclick="toggleFamilyHomepagePopup(false)"
                    aria-label="بستن">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <p class="family-homepage-popup__hint">مورد مورد نظر را انتخاب کنید</p>
        <ul class="family-homepage-popup__list" data-family-popup-list></ul>
    </div>
</div>
