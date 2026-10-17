/*
 * Storefront runtime — extracted verbatim from the inline <script> that used to
 * live in app/Views/partials/wsit_head.php (~13 KB on every page load).
 *
 * It is now a static, cacheable file. The values that were previously injected
 * per-request by PHP (CSRF token + endpoint URLs) arrive through the tiny
 * window.MF_CONFIG object rendered in the head partial.
 *
 * Loaded with `defer` BEFORE alpine.min.js so the `alpine:init` listener below
 * is registered before Alpine boots.
 */
(function () {
    'use strict';

    var CFG = window.MF_CONFIG || {};

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
                form.append('_token', CFG.csrf || '');
                form.append('product_id', productId);
                form.append('quantity', quantity);
                fetch(CFG.cartAdd, { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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
                fetch(CFG.cartItems, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => this.applyCart(data))
                    .catch(() => {});
            },
            cartAction(url, form) {
                form.append('_token', CFG.csrf || '');
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
                this.cartAction(CFG.cartUpdate, form);
            },
            removeItem(productId) {
                const form = new FormData();
                form.append('product_id', productId);
                this.cartAction(CFG.cartRemove, form);
            },
            clearCart() {
                this.cartAction(CFG.cartClear, new FormData());
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
        const suggestUrl = CFG.searchSuggest;
        const resultsUrl = CFG.searchResults;
        const logUrl = CFG.searchLog;
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
})();
