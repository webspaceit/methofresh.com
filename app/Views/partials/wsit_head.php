<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(trans('tagline')) ?>">
<?php $favicon = site_favicon_url(); ?>
<?php include views_path('partials/wsit_pixel.php'); ?>
<?php if ($favicon !== ''): ?>
<link rel="icon" href="<?= e($favicon) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍯</text></svg>">
<?php endif; ?>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" onload="this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap"></noscript>
<link rel="preload" as="font" href="https://fonts.gstatic.com/s/hindsiliguri/v1/K5mXaAaT8G62IuqN4rIzj1jvn6A0X-g.woff2" crossorigin>
<link rel="preload" as="font" href="https://fonts.gstatic.com/s/notosansbengali/v16/r5Yodf1kK-2LrIQaT5fC6Igp9Tprq5T6s1qng2kC.woff2" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: <?= app_locale() === 'bn' ? '[\'"Noto Sans Bengali"\', \'sans-serif\']' : '[\'ui-sans-serif\', \'system-ui\', \'-apple-system\', \'BlinkMacSystemFont\', \'"Segoe UI"\', \'Roboto\', \'"Helvetica Neue"\', \'Arial\', \'sans-serif\']' ?>,
                    bengali: ['"Noto Sans Bengali"', 'sans-serif'],
                    bangla: ['"Hind Siliguri"', '"Noto Sans Bengali"', 'sans-serif'],
                },
                colors: {
                    brand: {
                        50:  '#F4F7F4',
                        100: '#E3EBE3',
                        200: '#C9D8C6',
                        300: '#A3BCA4',
                        400: '#6F9176',
                        500: '#4C7A5C',
                        600: '#2D6A4F',
                        700: '#1B4332',
                        800: '#143626',
                        900: '#0B1F16',
                        950: '#08170F',
                    },
                    honey: {
                        50:  '#fef9ea',
                        100: '#f8dc90',
                        200: '#f5ce6c',
                        300: '#f2a635',
                        400: '#e08b1f',
                        500: '#c6711a',
                        600: '#a35715',
                        700: '#7c4213',
                        800: '#59300f',
                        900: '#3b200a',
                    },
                    cream: '#FAF7F2',
                    warmBeige: '#F3EFE6',
                    card: '#ffffff',
                }
            }
        }
    }
</script>
<?php if (app_locale() === 'bn'): ?>
<style>body{font-family:'Hind Siliguri','Noto Sans Bengali',sans-serif;}</style>
<?php endif; ?>
<style>
    /* Announcement bar — offers slide up from the bottom, one after another
       (index set by Alpine). The viewport and every slide share one fixed
       line height, so i * 100% / n lands exactly on each slide. */
    .ann-slider { transition: transform .5s ease-out; }
    @media (prefers-reduced-motion: reduce) {
        .ann-slider { transition: none; }
    }
</style>
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
            },
            // Fire a Facebook Pixel AddToCart event when an item is added.
            // Best-effort: never blocks the add-to-cart flow.
            trackAddToCart(productId, quantity = 1, name = '', price = 0, currency = 'USD') {
                if (typeof fbq !== 'function') return;
                try {
                    fbq('track', 'AddToCart', {
                        content_ids: [String(productId)],
                        content_name: String(name || ''),
                        content_type: 'product',
                        currency: String(currency || 'USD'),
                        value: Number(price || 0),
                        quantity: Number(quantity || 1),
                    });
                } catch (e) { /* ignore */ }
            }
        };
    }
    window.store = store;

    // Alpine component — live search suggestions dropdown.
    function liveSearch(initialQuery = '') {
        const suggestUrl = '<?= locale_url('/search/suggest') ?>';
        const resultsUrl = '<?= locale_url('/products') ?>';
        const logUrl = '<?= locale_url('/search/log') ?>';
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
                    // Tell the server which exact product this search led to,
                    // so the dashboard records the product name instead of
                    // the typed fragments. Best-effort: never blocks navigation.
                    try {
                        const fd = new FormData();
                        fd.append('product_id', String(target.id || 0));
                        if (navigator.sendBeacon) {
                            navigator.sendBeacon(logUrl, fd);
                        } else {
                            fetch(logUrl, { method: 'POST', body: fd, keepalive: true }).catch(() => {});
                        }
                    } catch (e) { /* ignore */ }
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