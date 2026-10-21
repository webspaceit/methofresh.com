<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Order;
use App\Models\wsit_ContactMessage;

class wsit_OrderController extends wsit_Controller
{
    private wsit_Order $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new wsit_Order();
    }

    public function account(): string
    {
        $this->requireLogin();
        $user = current_user();
        $orders = $this->orderModel->history((int)$user['id']);
        return $this->view('account/wsit_index', [
            'pageTitle' => trans('nav_account'),
            'user'      => $user,
            'orders'    => $orders,
        ]);
    }

    public function show(int $id): string
    {
        $this->requireLogin();
        $user = current_user();
        $order = $this->orderModel->findWithItems($id, (int)$user['id']);

        if ($order === null) {
            $this->flash('error', trans('order_not_found'));
            $this->redirect('/account');
        }

        return $this->view('account/wsit_show', [
            'pageTitle' => trans('order_details') . ' #' . $order['order_number'],
            'order'     => $order,
            'user'      => $user,
        ]);
    }

    public function cancel(wsit_Request $request, int $id): void
    {
        $this->requireLogin();
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/account/orders/' . $id);
        }
        $user = current_user();
        $order = $this->orderModel->cancelPendingForUser($id, (int)$user['id']);
        if (!$order) {
            $this->flash('error', trans('order_cancel_unavailable'));
            $this->redirect('/account/orders/' . $id);
        }
        send_order_status_email($order, 'pending', 'cancelled');
        $this->flash('success', trans('order_cancelled'));
        $this->redirect('/account/orders/' . $id);
    }

    public function requestReturn(wsit_Request $request, int $id): void
    {
        $this->requireLogin();
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/account/orders/' . $id);
        }
        $user = current_user();
        $order = $this->orderModel->findWithItems($id, (int)$user['id']);
        $details = trim((string)$request->input('details', ''));
        if (!$order || $order['status'] !== 'delivered' || mb_strlen($details) < 10) {
            $this->flash('error', trans('return_request_invalid'));
            $this->redirect('/account/orders/' . $id);
        }
        $message = [
            'name' => (string)$order['shipping_name'],
            'email' => (string)($order['shipping_email'] ?: $user['email']),
            'phone' => (string)$order['shipping_phone'],
            'subject' => 'Return request for order ' . $order['order_number'],
            'message' => "Order: {$order['order_number']}\n\n" . $details,
        ];
        (new wsit_ContactMessage())->create($message + ['captcha_answer' => '']);
        send_contact_message_email($message);
        $this->flash('success', trans('return_request_sent'));
        $this->redirect('/account/orders/' . $id);
    }
}
