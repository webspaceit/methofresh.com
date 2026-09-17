<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Core\wsit_Validator;
use App\Models\wsit_User;

class wsit_AuthController extends wsit_Controller
{
    private wsit_User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new wsit_User();
    }

    public function showLogin(): string
    {
        if (current_user()) {
            $this->redirect('/account');
        }
        return $this->view('auth/wsit_login', ['pageTitle' => trans('login')]);
    }

    public function showRegister(): string
    {
        if (current_user()) {
            $this->redirect('/account');
        }
        return $this->view('auth/wsit_register', ['pageTitle' => trans('register')]);
    }

    public function login(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/login');
        }

        $email = $request->input('email');
        $password = $request->input('password');

        $validator = new wsit_Validator();
        if (!$validator->validate(['email' => $email, 'password' => $password], [
            'email'    => 'required|email',
            'password' => 'required|min:4',
        ])) {
            $this->flash('error', trans('login_failed'));
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);

        if ($user === null || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->flash('error', trans('login_failed'));
            $this->redirect('/login');
        }

        $_SESSION['user_id'] = (int)$user['id'];
        session_regenerate_id(true);
        $this->flash('success', trans('login_success'));

        $this->redirect((int)$user['is_admin'] === 1 ? '/mf-dashboard' : '/account');
    }

    public function register(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/register');
        }

        $data = [
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'phone'    => $request->input('phone'),
            'address'  => $request->input('address'),
            'city'     => $request->input('city'),
            'password' => $request->input('password'),
        ];
        $confirm = $request->input('password_confirmation');

        $validator = new wsit_Validator();
        $rules = [
            'name'     => 'required|min:3',
            'email'    => 'required|email|unique:' . table('users') . ',email',
            'phone'    => 'required|min:6',
            'password' => 'required|min:6|confirmed',
        ];

        if (!$validator->validate(array_merge($data, ['password_confirmation' => $confirm]), $rules)) {
            $errors = $validator->errors();
            $first = reset($errors);
            $this->flash('error', is_array($first) ? reset($first) : (string)$first);
            $this->redirect('/register');
        }

        $id = $this->userModel->create([
            'name'     => $data['name'],
            'email'    => strtolower($data['email']),
            'phone'    => $data['phone'],
            'address'  => $data['address'],
            'city'     => $data['city'],
            'password' => $data['password'],
            'is_admin' => 0,
        ]);

        $_SESSION['user_id'] = $id;
        session_regenerate_id(true);
        $this->flash('success', trans('register_success'));
        $this->redirect('/account');
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        $this->flash('success', trans('logout_success'));
        $this->redirect('/login');
    }
}