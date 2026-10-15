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
    /* Keep Alpine-controlled elements (cart drawer, badges, dropdowns) hidden
       until Alpine has initialised and evaluated x-show. */
    [x-cloak] { display: none !important; }

    /* Announcement bar — offers slide up from the bottom, one after another
       (index set by Alpine). The viewport and every slide share one fixed
       line height, so i * 100% / n lands exactly on each slide. */
    .ann-slider { transition: transform .5s ease-out; }
    @media (prefers-reduced-motion: reduce) {
        .ann-slider { transition: none; }
    }

    /* Cart icon bounce when a flying product lands. */
    @keyframes cartBump {
        0% { transform: scale(1); }
        35% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
    .cart-bump { animation: cartBump .45s ease; }
</style>
<script>
    // Alpine store — global cart counter that updates after add-to-cart.
    function store(initial) {
        return {
            cartCount: (initial && initial.cartCount) ? initial.cartCount : 0,
            itemsLabel: (initial && initial.itemsLabel) ? initial.itemsLabel : ':count items',
            openMobile: false,
            cartOpen: false,
            cartItems: [],
            cartSubtotal: '',
            cartLoaded: false,
            toast: { show: false, type: 'success', message: '' },

            // Merge a cart payload (from any cart endpoint) into the drawer.
            applyCart(cart) {
                if (!cart) return;
                this.cartItems = Array.isArray(cart.items) ? cart.items : [];
                this.cartSubtotal = cart.subtotal_display || '';
                if (cart.cart_count !== undefined) {
                    this.cartCount = cart.cart_count;
                }
                this.cartLoaded = true;
            },
            showToast(ok, message) {
                if (!message) return;
                this.toast = { show: true, type: ok === false ? 'error' : 'success', message: message };
                setTimeout(() => { this.toast.show = false; }, 3500);
            },

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
                        this.applyCart(data.cart);
                        this.showToast(data.ok, data.message);
                        if (data.ok) {
                            // Slide the cart in once the flying image lands.
                            setTimeout(() => { this.cartOpen = true; }, 650);
                        }
                    })
                    .catch(() => {
                        window.location.reload();
                    });
            },

            // -- Cart drawer ---------------------------------------------------
            openCart() {
                this.cartOpen = true;
                if (!this.cartLoaded) {
                    this.loadCart();
                }
            },
            closeCart() {
                this.cartOpen = false;
            },
            loadCart() {
                fetch('<?= locale_url('/cart/items') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => this.applyCart(data))
                    .catch(() => {});
            },
            cartAction(url, form) {
                form.append('_token', '<?= csrf_token() ?>');
                return fetch(url, { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        this.applyCart(data.cart);
                        this.showToast(data.ok, data.message);
                    })
                    .catch(() => {});
            },
            setQty(productId, quantity) {
                if (quantity < 1) {
                    this.removeItem(productId);
                    return;
                }
                const form = new FormData();
                form.append('quantity[' + productId + ']', quantity);
                this.cartAction('<?= locale_url('/cart/update') ?>', form);
            },
            removeItem(productId) {
                const form = new FormData();
                form.append('product_id', productId);
                this.cartAction('<?= locale_url('/cart/remove') ?>', form);
            },
            clearCart() {
                this.cartAction('<?= locale_url('/cart/clear') ?>', new FormData());
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

    // Fly a product image to the header cart icon as add-to-cart feedback.
    // Adapted from the pharmacy project's flyToCart utility.
    function flyToCart(event, imgSrc) {
        try {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            const btn = (event && (event.currentTarget || event.target)) || document.body;

            // Nearest product image inside the clicked card (or an explicit URL).
            let sourceImg = null;
            for (let node = btn; node && node !== document.body; node = node.parentElement) {
                if (node.tagName === 'IMG') { sourceImg = node; break; }
                const inner = node.querySelector ? node.querySelector('img') : null;
                if (inner) { sourceImg = inner; break; }
            }

            const startRect = (sourceImg || btn).getBoundingClientRect();

            // Land on the header cart icon; fall back to the top-right corner
            // when it is missing or scrolled out of view.
            const cartEl = document.getElementById('cart-icon');
            let cartRect = null;
            if (cartEl) {
                const r = cartEl.getBoundingClientRect();
                if (r.bottom > 0 && r.top < window.innerHeight && r.right > 0 && r.left < window.innerWidth) {
                    cartRect = r;
                }
            }
            if (!cartRect) {
                cartRect = { left: window.innerWidth - 56, top: 20, width: 44, height: 44 };
            }

            const src = (sourceImg && sourceImg.src) || imgSrc || '';
            let flyer;
            if (src) {
                flyer = document.createElement('img');
                flyer.src = src;
                flyer.alt = '';
                flyer.style.objectFit = 'cover';
            } else {
                flyer = document.createElement('div');
                flyer.style.background = 'linear-gradient(135deg, #142e1c, #2D6A4F)';
                flyer.textContent = '✓';
                flyer.style.color = '#f2a635';
                flyer.style.display = 'flex';
                flyer.style.alignItems = 'center';
                flyer.style.justifyContent = 'center';
                flyer.style.fontSize = '20px';
                flyer.style.fontWeight = '700';
            }
            Object.assign(flyer.style, {
                position: 'fixed',
                left: startRect.left + 'px',
                top: startRect.top + 'px',
                width: Math.max(startRect.width, 24) + 'px',
                height: Math.max(startRect.height, 24) + 'px',
                borderRadius: '12px',
                boxShadow: '0 10px 25px rgba(0, 0, 0, .28)',
                zIndex: '9999',
                pointerEvents: 'none',
                opacity: '0.95',
                willChange: 'transform, opacity',
                transformOrigin: 'center center',
                transform: 'translate(0, 0) scale(1)',
            });
            document.body.appendChild(flyer);

            const destX = (cartRect.left + cartRect.width / 2) - (startRect.left + startRect.width / 2);
            const destY = (cartRect.top + cartRect.height / 2) - (startRect.top + startRect.height / 2);
            const duration = 650;

            const cleanup = () => { if (flyer.parentNode) flyer.parentNode.removeChild(flyer); };

            const startTime = performance.now();
            const tick = (now) => {
                const p = Math.min((now - startTime) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                flyer.style.transform = `translate(${destX * eased}px, ${destY * eased}px) scale(${1 - eased * 0.76})`;
                flyer.style.opacity = (0.95 - eased * 0.75).toFixed(3);
                if (p < 1) {
                    requestAnimationFrame(tick);
                } else {
                    cleanup();
                    if (cartEl) {
                        cartEl.classList.remove('cart-bump');
                        void cartEl.offsetWidth;
                        cartEl.classList.add('cart-bump');
                        setTimeout(() => cartEl.classList.remove('cart-bump'), 450);
                    }
                }
            };
            requestAnimationFrame(tick);
            setTimeout(cleanup, 900);
        } catch (e) {
            console.warn('flyToCart error', e);
        }
    }
    window.flyToCart = flyToCart;

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