<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(trans('tagline')) ?>">
<?php $favicon = site_favicon_url(); ?>
<?php if ($favicon !== ''): ?>
<link rel="icon" href="<?= e($favicon) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥦</text></svg>">
<?php endif; ?>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: <?= app_locale() === 'bn' ? '[\'"Noto Sans Bengali"\', \'sans-serif\']' : '[\'ui-sans-serif\', \'system-ui\', \'-apple-system\', \'BlinkMacSystemFont\', \'"Segoe UI"\', \'Roboto\', \'"Helvetica Neue"\', \'Arial\', \'sans-serif\']' ?>,
                    bengali: ['"Noto Sans Bengali"', 'sans-serif'],
                },
                colors: {
                    brand: {
                        50:  '#f0fdf4',
                        100: '#dcfce7',
                        500: '#22c55e',
                        600: '#16a34a',
                        700: '#15803d',
                        800: '#166534',
                    },
                }
            }
        }
    }
</script>
<?php if (app_locale() === 'bn'): ?>
<style>body{font-family:'Noto Sans Bengali',sans-serif;}</style>
<?php endif; ?>
<script>
    // Alpine store — global cart counter that updates after add-to-cart.
    function store(initial) {
        return {
            cartCount: (initial && initial.cartCount) ? initial.cartCount : 0,
            openMobile: false,
            cartOpen: false,
            toast: { show: false, type: 'success', message: '' },
            addToCart(productId, quantity = 1) {
                const form = new FormData();
                form.append('_token', '<?= csrf_token() ?>');
                form.append('product_id', productId);
                form.append('quantity', quantity);
                fetch('<?= locale_url('/cart/add') ?>', { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        if (data.cart_count !== undefined) {
                            this.cartCount = data.cart_count;
                        }
                        if (data.message) {
                            this.toast = { show: true, type: data.ok ? 'success' : 'error', message: data.message };
                            setTimeout(() => { this.toast.show = false; }, 3500);
                        }
                    })
                    .catch(() => {
                        window.location.reload();
                    });
            }
        };
    }
    window.store = store;

    // Alpine component — live search suggestions dropdown.
    function liveSearch(initialQuery = '') {
        const suggestUrl = '<?= locale_url('/search/suggest') ?>';
        const resultsUrl = '<?= locale_url('/products') ?>';
        return {
            query: initialQuery || '',
            open: false,
            ready: false,
            active: -1,
            results: [],
            controller: null,
            resultsUrl: resultsUrl,
            runSearch() {
                if (this.controller) {
                    this.controller.abort();
                }
                const q = (this.query || '').trim();
                if (q.length < 2) {
                    this.results = [];
                    this.ready = false;
                    this.open = false;
                    this.active = -1;
                    return;
                }
                this.controller = new AbortController();
                const url = suggestUrl + '?q=' + encodeURIComponent(q);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: this.controller.signal })
                    .then(r => r.json())
                    .then(data => {
                        this.results = Array.isArray(data.results) ? data.results : [];
                        this.active = -1;
                        this.ready = true;
                        this.open = this.results.length > 0 || q.length >= 2;
                    })
                    .catch(() => {
                        if (this.controller && this.controller.signal.aborted) {
                            return;
                        }
                        this.results = [];
                        this.active = -1;
                        this.ready = true;
                        this.open = false;
                    });
            },
            move(dir) {
                if (!this.results || this.results.length === 0) {
                    return;
                }
                this.active = (this.active + dir + this.results.length) % this.results.length;
            },
            go() {
                const target = (this.open && this.active >= 0 && this.results && this.results[this.active]) ? this.results[this.active] : null;
                if (target && target.url) {
                    window.location.href = target.url;
                } else {
                    this.submit();
                }
            },
            submit() {
                const form = (this.$el && this.$el.tagName === 'FORM') ? this.$el : (this.$el ? this.$el.closest('form') : null);
                if (form) {
                    HTMLFormElement.prototype.submit.call(form);
                } else {
                    window.location.href = resultsUrl + '?q=' + encodeURIComponent((this.query || '').trim());
                }
            }
        };
    }
    window.liveSearch = liveSearch;

    document.addEventListener('alpine:init', () => {
        if (window.Alpine) {
            Alpine.data('liveSearch', liveSearch);
            Alpine.data('store', store);
        }
    });
</script>