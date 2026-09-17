<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Core\wsit_Validator;
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
        $shipping = $this->cartController->shippingFor(guest_cart_subtotal());

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

        return $this->view('checkout/wsit_index', [
            'pageTitle'      => trans('checkout'),
            'cart'           => $cart,
            'subtotal'       => guest_cart_subtotal(),
            'discount'       => $this->cartController->couponDiscount(guest_cart_subtotal()),
            'shipping'       => $shipping,
            'user'           => $user,
            'paymentMethods' => $activeMethods,
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
        $shipping = $this->cartController->shippingFor($subtotal);
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
            'shipping_name'    => $data['name'],
            'shipping_email'   => $data['email'],
            'shipping_phone'   => $data['phone'],
            'shipping_address' => $data['address'],
            'shipping_city'    => $data['city'],
            'shipping_postal'  => $data['postal_code'] !== '' ? $data['postal_code'] : null,
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

        $orderNumber = $orderData['order_number'];

        $_SESSION['cart'] = [];
        unset($_SESSION['cart_coupon']);

        $this->flash('success', trans('order_placed'));
        $this->redirect('/order/success/' . $orderNumber);
    }

    public function success(string $orderNumber): string
    {
        $order = $this->orderModel->findByNumber($orderNumber);
        if ($order === null) {
            $this->flash('error', trans('order_not_found'));
            $this->redirect('/');
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