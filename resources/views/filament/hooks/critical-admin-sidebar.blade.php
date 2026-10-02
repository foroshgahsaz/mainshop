<style>
    @media (max-width: 991px) {
        html.fi-admin-compact .fi-admin-shell .sidebar-secondary,
        html.fi-admin-compact .fi-admin-shell .sidebar-primary {
            display: none !important;
        }

        html.fi-admin-compact .fi-admin-shell .main-content,
        html.fi-admin-compact .fi-admin-shell .main-content.expanded {
            margin-right: 0 !important;
        }

        html.fi-admin-compact body.fi-sidebar-menu-open .fi-admin-shell .sidebar-secondary {
            display: flex !important;
        }

        html.fi-admin-compact body.fi-sidebar-menu-open .fi-admin-shell .sidebar-primary {
            display: block !important;
        }
    }
</style>
<script>
    (function () {
        if (window.matchMedia('(max-width: 991px)').matches) {
            document.documentElement.classList.add('fi-admin-compact');
        }
    })();
</script>
