<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Core\wsit_Validator;
use App\Models\wsit_CheckoutVisit;
use App\Models\wsit_Order;
use App\Models\wsit_OrderItem;
use App\Models\wsit_Product;
use App\Models\wsit_User;

class wsit_CheckoutController extends wsit_Controller
{
    private wsit_CartController $cartController;
    private wsit_Order $orderModel;
    private wsit_OrderItem $orderItemModel;
    private wsit_Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartController = new wsit_CartController();
        $this->orderModel = new wsit_Order();
        $this->orderItemModel = new wsit_OrderItem();
        $this->productModel = new wsit_Product();
    }

    public function show(): string
    {
        $cart = $this->cartController->hydratedCart();
        if (empty($cart)) {
            $this->flash('error', trans('empty_cart'));
            $this->redirect('/cart');
        }

        $user = current_user();
        $subtotal = guest_cart_subtotal();
        $shipping = $this->cartController->shippingFor($subtotal);

        $activeMethods = active_payment_methods();
        if (empty($activeMethods)) {
            $activeMethods = [
                'cod' => [
                    'key' => 'cod',
                    'enabled' => true,
                    'icon' => '💵',
                    'title' => trans('payment_cod'),
                    'desc' => '',
                    'require_trx' => false,
                ]
            ];
        }

        // Checkout funnel log — reaching this page is the event the admin
        // dashboard tracks ("who went as far as checkout"), with a snapshot
        // of the cart they arrived with. Best-effort: analytics must never
        // break checkout itself.
        try {
            (new wsit_CheckoutVisit())->logVisit($user, $cart);
        } catch (\Throwable) {
        }

        return $this->view('checkout/wsit_index', [
            'pageTitle'      => trans('checkout'),
            'cart'           => $cart,
            'subtotal'       => $subtotal,
            'discount'       => $this->cartController->couponDiscount($subtotal),
            'shipping'       => $shipping,
            'user'           => $user,
            'paymentMethods' => $activeMethods,
            'shippingCfg'    => shipping_config(),
            'shippingWeight' => cart_total_weight(),
        ]);
    }

    public function place(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/checkout');
        }

        $cart = $this->cartController->hydratedCart();
        if (empty($cart)) {
            $this->flash('error', trans('empty_cart'));
            $this->redirect('/cart');
        }

        $data = [
            'name'           => $request->input('name'),
            'email'          => $request->input('email'),
            'phone'          => $request->input('phone'),
            'address'        => $request->input('address'),
            'city'           => $request->input('city'),
            'postal_code'    => $request->input('postal_code'),
            'notes'          => $request->input('notes'),
            'payment_method' => $request->input('payment_method'),
            'trx_id'         => trim((string)$request->input('trx_id', '')),
        ];

        $validator = new wsit_Validator();
        $rules = [
            'name'    => 'required|min:2',
            'email'   => 'required|email',
            'phone'   => 'required|min:6',
            'address' => 'required|min:5',
            'city'    => 'required|min:2',
        ];

        if (!$validator->validate($data, $rules)) {
            $errors = $validator->errors();
            $first = reset($errors);
            $this->flash('error', is_array($first) ? reset($first) : (string)$first);
            $this->redirect('/checkout');
        }

        // Validate stock before placing order.
        foreach ($cart as $item) {
            if ((int)$item['quantity'] > (int)$item['stock']) {
                $this->flash('error', trans('stock_insufficient'));
                $this->redirect('/cart');
            }
        }

        $subtotal = guest_cart_subtotal();
        $discount = $this->cartController->couponDiscount($subtotal);

        // Shipping — authoritative server-side recomputation from the admin
        // config: the submitted method must be active AND serve the entered
        // city; the charge is base + per_kg × cart weight (free threshold
        // applied). The client-side preview is never trusted.
        $shipCfg = shipping_config();
        $shipping = 0.0;
        $shippingMethodName = null;
        if (!empty($shipCfg['enabled'])) {
            $availableMethods = shipping_available_methods($data['city']);
            if ($availableMethods === []) {
                if (shipping_available_methods(null) !== []) {
                    // Methods exist, but none of them serves this address.
                    $this->flash('error', trans('shipping_unavailable'));
                    $this->redirect('/checkout');
                }
                // No active methods at all → delivery is free, no choice.
            } else {
                $selectedId = trim((string)$request->input('shipping_method', ''));
                $chosenMethod = null;
                foreach ($availableMethods as $candidate) {
                    if ($candidate['id'] === $selectedId) {
                        $chosenMethod = $candidate;
                        break;
                    }
                }
                if ($chosenMethod === null) {
                    $this->flash('error', trans('shipping_method_invalid'));
                    $this->redirect('/checkout');
                }
                $shipping = shipping_method_charge($chosenMethod, $subtotal, cart_total_weight(), $shipCfg['free_over']);
                $shippingMethodName = shipping_method_name($chosenMethod);
            }
        }
        $total = $subtotal - $discount + $shipping;

        $activeMethods = active_payment_methods();
        $selectedKey = (string)$data['payment_method'];

        if (!isset($activeMethods[$selectedKey])) {
            $selectedKey = !empty($activeMethods) ? array_key_first($activeMethods) : 'cod';
        }

        $chosenMethodConfig = $activeMethods[$selectedKey] ?? null;
        if ($chosenMethodConfig && !empty($chosenMethodConfig['require_trx']) && $data['trx_id'] === '') {
            $this->flash('error', trans('payment_trx_id_required'));
            $this->redirect('/checkout');
        }

        $user = current_user();

        $accessToken = bin2hex(random_bytes(32));
        $orderData = [
            'user_id'          => $user ? (int)$user['id'] : null,
            'order_number'     => $this->orderModel->generateNumber(),
            'status'           => 'pending',
            'subtotal'         => $subtotal,
            'discount'         => $discount,
            'shipping'         => $shipping,
            'total'            => $total,
            'payment_method'   => $selectedKey,
            'transaction_id'   => $data['trx_id'] !== '' ? $data['trx_id'] : null,
            'access_token_hash' => hash('sha256', $accessToken),
            'shipping_name'    => $data['name'],
            'shipping_email'   => $data['email'],
            'shipping_phone'   => $data['phone'],
            'shipping_address' => $data['address'],
            'shipping_city'    => $data['city'],
            'shipping_postal'  => $data['postal_code'] !== '' ? $data['postal_code'] : null,
            'shipping_method'  => $shippingMethodName,
            'notes'            => $data['notes'] !== '' ? $data['notes'] : null,
        ];

        $this->orderModel->beginTransaction();
        try {
            $orderId = $this->orderModel->create($orderData);

            foreach ($cart as $item) {
                $this->orderItemModel->create([
                    'order_id'        => $orderId,
                    'product_id'      => (int)$item['product_id'],
                    'product_name'    => $item['name'],
                    'product_name_bn' => $this->productNameBn((int)$item['product_id']),
                    'price'           => $item['current_price'],
                    'quantity'        => (int)$item['quantity'],
                    'total'           => round($item['current_price'] * (int)$item['quantity'], 2),
                ]);
                $this->productModel->decrementStock((int)$item['product_id'], (int)$item['quantity']);
                $this->productModel->incrementSold((int)$item['product_id'], (int)$item['quantity']);
            }

            $this->orderModel->commit();
        } catch (\Throwable $e) {
            $this->orderModel->rollBack();
            throw $e;
        }

        // This session just converted — flag its checkout visit(s) so the
        // funnel can separate real orders from abandoned checkouts.
        try {
            (new wsit_CheckoutVisit())->markConverted();
        } catch (\Throwable) {
        }

        $orderNumber = $orderData['order_number'];

        $_SESSION['cart'] = [];
        unset($_SESSION['cart_coupon']);

        $this->flash('success', trans('order_placed'));
        $this->redirect('/order/success/' . $orderNumber . '?access=' . $accessToken);
    }

    public function success(wsit_Request $request, string $orderNumber): string
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Referrer-Policy: no-referrer');
        $order = $this->orderModel->findWithItemsByNumber($orderNumber);
        if ($order === null) {
            $this->flash('error', trans('order_not_found'));
            $this->redirect('/');
        }

        // The order number is public-facing and must not grant access by itself.
        $user = current_user();
        $authorized = $user && (int)($order['user_id'] ?? 0) === (int)$user['id'];
        $accessToken = trim((string)$request->query('access', ''));
        if (!$authorized && $accessToken !== '') {
            $storedHash = (string)($order['access_token_hash'] ?? '');
            $authorized = $storedHash !== '' && hash_equals($storedHash, hash('sha256', $accessToken));
        }
        if (!$authorized) {
            http_response_code(404);
            return $this->view('errors/wsit_404', ['pageTitle' => '404']);
        }

        return $this->view('checkout/wsit_success', [
            'pageTitle' => trans('order_success'),
            'order'     => $order,
        ]);
    }

    private function productNameBn(int $productId): ?string
    {
        $product = $this->productModel->find($productId);
        if ($product === null) {
            return null;
        }
        return trim((string)($product['name_bn'] ?? '')) !== '' ? $product['name_bn'] : null;
    }
}
