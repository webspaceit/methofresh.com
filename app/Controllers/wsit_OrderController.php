<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Models\wsit_Order;

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
}