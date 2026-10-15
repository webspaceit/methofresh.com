<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Product;

class wsit_CartController extends wsit_Controller
{
    private wsit_Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new wsit_Product();
    }

    public function index(): string
    {
        $cart = $this->hydratedCart();

        $subtotal = 0.0;
        foreach ($cart as &$item) {
            $subtotal += (float)$item['current_price'] * (int)$item['quantity'];
        }
        unset($item);

        $discount = $this->couponDiscount($subtotal);
        $shipping = $this->shippingFor($subtotal);

        return $this->view('cart/wsit_index', [
            'pageTitle' => trans('cart'),
            'cart'      => $cart,
            'subtotal'  => $subtotal,
            'discount'  => $discount,
            'shipping'  => $shipping,
            'total'     => $subtotal - $discount + $shipping,
        ]);
    }

    public function add(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token') . '');
            $this->json(['ok' => false, 'message' => trans('validate_token')]);
        }

        $productId = (int)$request->input('product_id');
        $quantity = max(1, (int)$request->input('quantity', 1));

        $product = $this->productModel->find($productId);
        if ($product === null || (int)$product['active'] !== 1) {
            $this->flash('error', trans('out_of_stock_msg'));
            $this->json(['ok' => false, 'message' => trans('out_of_stock_msg')]);
        }

        $priceInfo = price_display($product);
        $stock = (int)$product['stock'];

        $cart = guest_cart();
        $existingQty = $cart[$productId]['quantity'] ?? 0;
        $newQty = $existingQty + $quantity;

        if ($newQty > $stock) {
            $this->flash('error', trans('stock_insufficient'));
            $this->json(['ok' => false, 'message' => trans('stock_insufficient')]);
        }

        $cart[$productId] = [
            'product_id'   => $productId,
            'name'         => $this->productModel->name($product),
            'slug'         => $product['slug'],
            'image'        => $product['image'],
            'stock'        => $stock,
            'unit_price'   => (float)$product['price'],
            'current_price'=> $priceInfo['current'],
            'sale_price'   => $priceInfo['sale'],
            'quantity'     => $newQty,
        ];
        $_SESSION['cart'] = $cart;

        $this->flash('success', trans('cart_added'));

        // AJAX (add from product cards) → return JSON with the updated cart.
        if ($this->isAjax($request)) {
            $this->cartJsonResponse(trans('cart_added'));
        }

        $referrer = $request->server('HTTP_REFERER');
        $this->redirect($referrer ? str_replace(base_url_path(), '', parse_url($referrer, PHP_URL_PATH)) : '/cart');
    }

    public function update(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', 'Invalid token.');
            $this->redirect('/cart');
        }

        $cart = guest_cart();
        $quantities = $request->inputArray('quantity');

        foreach ($cart as $productId => $item) {
            $qty = (int)($quantities[$productId] ?? $item['quantity']);
            if ($qty <= 0) {
                unset($cart[$productId]);
                continue;
            }
            if ($qty > (int)$item['stock']) {
                $qty = (int)$item['stock'];
            }
            $cart[$productId]['quantity'] = $qty;
        }

        $_SESSION['cart'] = $cart;
        if ($this->isAjax($request)) {
            $this->cartJsonResponse(trans('cart_updated'));
        }
        $this->flash('success', trans('cart_updated'));
        $this->redirect('/cart');
    }

    public function remove(wsit_Request $request): void
    {
        $productId = (int)$request->input('product_id');
        $cart = guest_cart();
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;
        }
        if ($this->isAjax($request)) {
            $this->cartJsonResponse(trans('cart_removed'));
        }
        $this->flash('success', trans('cart_removed'));
        $this->redirect('/cart');
    }

    public function destroy(wsit_Request $request): void
    {
        $_SESSION['cart'] = [];
        if ($this->isAjax($request)) {
            $this->cartJsonResponse(trans('cart_cleared'));
        }
        $this->flash('success', trans('cart_cleared'));
        $this->redirect('/cart');
    }

    /**
     * GET /cart/items — JSON snapshot used by the slide-in cart drawer.
     */
    public function items(): void
    {
        $this->json($this->cartJson());
    }

    public function applyCoupon(wsit_Request $request): void
    {
        $code = strtoupper(trim($request->input('code', '')));
        $cart = guest_cart();

        if ($code === 'WELCOME10') {
            $_SESSION['cart_coupon'] = ['code' => 'WELCOME10', 'percent' => 10];
            $this->flash('success', trans('coupon_applied'));
        } else {
            unset($_SESSION['cart_coupon']);
            $this->flash('error', trans('coupon_invalid'));
        }
        $this->redirect('/cart');
    }

    // -- Helpers (used by checkout too) ------------------------------------

    /**
     * JSON-friendly snapshot of the cart for the slide-in cart drawer.
     * Images and prices are pre-formatted so the front-end needs no formatting.
     */
    public function cartJson(): array
    {
        $cart = $this->hydratedCart();
        $items = [];
        $subtotal = 0.0;

        foreach ($cart as $productId => $item) {
            $qty = (int)$item['quantity'];
            $line = (float)$item['current_price'] * $qty;
            $subtotal += $line;

            $items[] = [
                'product_id'    => (int)($item['product_id'] ?? $productId),
                'name'          => $item['name'],
                'slug'          => $item['slug'],
                'url'           => locale_url('/products/' . $item['slug']),
                'image'         => image_url($item['image'] ?? null, $item['name'], 160),
                'price'         => (float)$item['current_price'],
                'price_display' => format_price((float)$item['current_price']),
                'line_display'  => format_price($line),
                'quantity'      => $qty,
                'stock'         => (int)$item['stock'],
            ];
        }

        return [
            'items'            => $items,
            'cart_count'       => guest_cart_count(),
            'subtotal'         => $subtotal,
            'subtotal_display' => format_price($subtotal),
        ];
    }

    private function isAjax(wsit_Request $request): bool
    {
        return strtolower((string)$request->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    private function cartJsonResponse(string $message, bool $ok = true): void
    {
        $cart = $this->cartJson();
        $this->json([
            'ok'         => $ok,
            'message'    => $message,
            'cart_count' => $cart['cart_count'],
            'cart'       => $cart,
        ]);
    }

    public function hydratedCart(): array
    {
        $cart = guest_cart();
        foreach ($cart as $productId => &$item) {
            $product = $this->productModel->find((int)$productId);
            if ($product === null) {
                unset($cart[$productId]);
                continue;
            }
            $priceInfo = price_display($product);
            $item['name'] = $this->productModel->name($product);
            $item['slug'] = $product['slug'];
            $item['image'] = $product['image'];
            $item['stock'] = (int)$product['stock'];
            $item['weight'] = (float)($product['weight'] ?? 0);
            $item['current_price'] = $priceInfo['current'];
            $item['unit_price'] = (float)$product['price'];
            $item['sale_price'] = $priceInfo['sale'];
            if ((int)$item['quantity'] > (int)$product['stock']) {
                $item['quantity'] = max(1, (int)$product['stock']);
            }
        }
        unset($item);
        $_SESSION['cart'] = $cart;
        return $_SESSION['cart'];
    }

    public function coupon(): ?array
    {
        return $_SESSION['cart_coupon'] ?? null;
    }

    public function couponDiscount(float $subtotal): float
    {
        $coupon = $this->coupon();
        if ($coupon === null) {
            return 0.0;
        }
        return round($subtotal * ((int)$coupon['percent'] / 100), 2);
    }

    /**
     * Shipping estimate/charge from the admin configuration.
     *
     * $city null     → locality unknown (cart page) — first active method.
     * $methodId given → that method when it serves the city, else the first.
     *
     * Falls back to the original hardcoded behaviour (৳60, free at ৳500)
     * until a configuration is saved, and returns 0 when charges are off or
     * no method applies.
     */
    public function shippingFor(float $subtotal, ?string $city = null, ?string $methodId = null): float
    {
        $cfg = shipping_config();
        if (!$cfg['enabled']) {
            return 0.0;
        }
        $methods = shipping_available_methods($city);
        if ($methods === []) {
            return 0.0;
        }

        $method = $methods[0];
        if ($methodId !== null && $methodId !== '') {
            foreach ($methods as $candidate) {
                if ($candidate['id'] === $methodId) {
                    $method = $candidate;
                    break;
                }
            }
        }

        return shipping_method_charge($method, $subtotal, cart_total_weight(), $cfg['free_over']);
    }
}