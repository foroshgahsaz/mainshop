document.addEventListener('DOMContentLoaded', () => {
  initListingFilterSidebar();
  initListingPriceSliders();
  initListingSortForm();
  initSearchModal();
});

function initListingFilterSidebar() {
  const sidebar = document.getElementById('filterSidebar');
  const backdrop = document.getElementById('filterSidebarBackdrop');
  if (!sidebar) return;

  const open = () => {
    sidebar.classList.add('is-open');
    backdrop?.classList.add('is-visible');
    document.body.classList.add('listing-filters-open');
  };

  const close = () => {
    sidebar.classList.remove('is-open');
    backdrop?.classList.remove('is-visible');
    document.body.classList.remove('listing-filters-open');
  };

  document.querySelectorAll('[data-open-filters]').forEach((btn) => {
    btn.addEventListener('click', open);
  });

  document.querySelectorAll('[data-close-filters]').forEach((btn) => {
    btn.addEventListener('click', close);
  });

  backdrop?.addEventListener('click', close);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
      close();
    }
  });
}

function initListingPriceSliders() {
  const formatPrice = (value) => `${new Intl.NumberFormat('fa-IR').format(value)} تومان`;

  const bindWrap = (wrap) => {
    if (wrap.dataset.bound === '1') return;
    wrap.dataset.bound = '1';

    const max = parseInt(wrap.dataset.max || '10000000', 10);
    const minRange = wrap.querySelector('.price-range-min');
    const maxRange = wrap.querySelector('.price-range-max');
    const fill = wrap.querySelector('.price-range-fill');
    const minLabel = wrap.querySelector('.price-range-min-label');
    const maxLabel = wrap.querySelector('.price-range-max-label');
    const form = wrap.closest('form');

    if (!minRange || !maxRange || !form) return;

    const minInput = form.querySelector('.price-range-min-input');
    const maxInput = form.querySelector('.price-range-max-input');
    const minBound = parseInt(minRange.min || '0', 10);

    const syncFill = () => {
      let minVal = parseInt(minRange.value, 10);
      let maxVal = parseInt(maxRange.value, 10);

      if (minVal > maxVal) {
        if (document.activeElement === minRange) {
          maxVal = minVal;
          maxRange.value = maxVal;
        } else {
          minVal = maxVal;
          minRange.value = minVal;
        }
      }

      const left = ((minVal - minBound) / (max - minBound)) * 100;
      const right = 100 - ((maxVal - minBound) / (max - minBound)) * 100;

      if (fill) {
        fill.style.right = `${Math.max(0, right)}%`;
        fill.style.left = `${Math.max(0, left)}%`;
      }

      if (minLabel) minLabel.textContent = formatPrice(minVal);
      if (maxLabel) maxLabel.textContent = formatPrice(maxVal);

      if (minInput) {
        minInput.value = minVal > minBound ? minVal : '';
      }

      if (maxInput) {
        maxInput.value = maxVal < max ? maxVal : '';
      }
    };

    minRange.addEventListener('input', syncFill);
    maxRange.addEventListener('input', syncFill);
    syncFill();
  };

  document.querySelectorAll('[data-price-range-wrap]').forEach(bindWrap);
}

function initListingSortForm() {
  const select = document.getElementById('listingSort');
  const directionInput = document.getElementById('listingDirection');
  if (!select || !directionInput) return;

  const syncDirection = () => {
    directionInput.value = select.value === 'price' ? 'asc' : 'desc';
  };

  select.addEventListener('change', syncDirection);
  syncDirection();
}

function initSearchModal() {
  const searchModal = document.getElementById('searchModal');
  if (!searchModal) return;

  searchModal.addEventListener('click', (event) => {
    if (event.target === searchModal) {
      toggleSearchModal(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && searchModal.classList.contains('is-open')) {
      toggleSearchModal(false);
    }
  });
}

function toggleSearchModal(show) {
  const modal = document.getElementById('searchModal');
  if (!modal) return;

  const input = modal.querySelector('.search-modal__input');

  if (show) {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setTimeout(() => input?.focus(), 200);
  } else {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
}

window.toggleSearchModal = toggleSearchModal;
