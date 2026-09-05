(function () {
    'use strict';

    if (typeof Swal === 'undefined') {
        return;
    }

    const iconMap = {
        success: 'success',
        danger: 'error',
        warning: 'warning',
        info: 'info',
    };

    function resolveIcon(status) {
        return iconMap[status] || 'info';
    }

    window.shopAdminAlert = function (options) {
        const opts = options || {};
        const icon = resolveIcon(opts.status || opts.icon || 'success');
        const isToast = Boolean(opts.toast);
        const defaultTimer = icon === 'success' ? 2800 : undefined;
        const timer = opts.timer !== undefined ? opts.timer : (isToast ? 2500 : defaultTimer);

        return Swal.fire({
            title: opts.title || '',
            html: opts.body || opts.text || '',
            icon: icon,
            confirmButtonText: opts.confirmButtonText || 'باشه',
            showCancelButton: Boolean(opts.showCancelButton),
            cancelButtonText: opts.cancelButtonText || 'انصراف',
            timer: timer,
            timerProgressBar: timer !== undefined && timer > 0,
            toast: isToast,
            position: opts.position || (isToast ? 'top-start' : 'center'),
            showConfirmButton: opts.showConfirmButton !== undefined
                ? opts.showConfirmButton
                : !isToast && icon !== 'success',
            customClass: {
                popup: 'shop-swal-popup',
                confirmButton: 'shop-swal-btn shop-swal-btn--confirm',
                cancelButton: 'shop-swal-btn shop-swal-btn--cancel',
                title: 'shop-swal-title',
                htmlContainer: 'shop-swal-body',
            },
            buttonsStyling: false,
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            },
        });
    };

    window.shopAdminToast = function (title, status) {
        return window.shopAdminAlert({
            title: title,
            status: status || 'success',
            toast: true,
            timer: 2500,
            showConfirmButton: false,
            position: 'top-start',
        });
    };

    window.shopAdminConfirm = function (options) {
        const opts = options || {};

        return window.shopAdminAlert({
            title: opts.title || 'آیا مطمئن هستید؟',
            body: opts.body || opts.text || '',
            status: 'warning',
            showCancelButton: true,
            confirmButtonText: opts.confirmButtonText || 'بله',
            cancelButtonText: opts.cancelButtonText || 'انصراف',
            timer: undefined,
            showConfirmButton: true,
        });
    };

    window.addEventListener('notificationSent', function (event) {
        const notification = event.detail?.notification;

        if (!notification) {
            return;
        }

        const status = notification.status || 'success';
        const isPersistent = notification.duration === 'persistent';

        window.shopAdminAlert({
            title: notification.title || 'اعلان',
            body: notification.body || '',
            status: status,
            timer: isPersistent ? undefined : (status === 'success' ? 2800 : undefined),
            showConfirmButton: status !== 'success',
        });
    });

    window.addEventListener('shop-admin-alert', function (event) {
        window.shopAdminAlert(event.detail || {});
    });
})();
