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

        // AJAX (add from product cards) → return JSON with the new cart count.
        if (strtolower((string)$request->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest') {
            $this->json([
                'ok'         => true,
                'message'    => trans('cart_added'),
                'cart_count' => guest_cart_count(),
                'subtotal'   => guest_cart_subtotal(),
            ]);
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
        $this->flash('success', trans('cart_removed'));
        $this->redirect('/cart');
    }

    public function destroy(): void
    {
        $_SESSION['cart'] = [];
        $this->flash('success', trans('cart_cleared'));
        $this->redirect('/cart');
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

    public function shippingFor(float $subtotal): float
    {
        if ($subtotal >= 500) {
            return 0.0;
        }
        return 60.0;
    }
}