<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Core\wsit_Validator;
use App\Models\wsit_User;
use App\Models\wsit_Order;
use App\Models\wsit_PasswordReset;

class wsit_AuthController extends wsit_Controller
{
    private wsit_User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new wsit_User();
    }

    public function showLogin(wsit_Request $request): string
    {
        if (current_user()) {
            $this->redirect('/account');
        }
        return $this->view('auth/wsit_login', [
            'pageTitle' => trans('login'),
            // Where to send the visitor after a successful sign-in. Only
            // same-site paths are accepted (safe_redirect_path).
            'redirect'  => safe_redirect_path($request->query('redirect', '')),
            'email'     => trim((string)$request->query('email', '')),
        ]);
    }

    public function showRegister(): string
    {
        if (current_user()) {
            $this->redirect('/account');
        }
        return $this->view('auth/wsit_register', ['pageTitle' => trans('register')]);
    }

    public function showForgotPassword(): string
    {
        return $this->view('auth/wsit_forgot_password', ['pageTitle' => trans('forgot_password')]);
    }

    public function requestPasswordReset(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/forgot-password');
        }

        $email = strtolower(trim((string)$request->input('email', '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                $resets = new wsit_PasswordReset();
                if (!$resets->recentlyIssued((int)$user['id'])) {
                    $token = bin2hex(random_bytes(32));
                    $resets->issue((int)$user['id'], hash('sha256', $token));
                    $baseUrl = rtrim((string)config('app.base_url', ''), '/');
                    $link = $baseUrl . '/' . app_locale() . '/reset-password?token=' . rawurlencode($token);
                    $subject = app_locale() === 'bn' ? 'পাসওয়ার্ড রিসেট অনুরোধ' : 'Password reset request';
                    $body = app_locale() === 'bn'
                        ? "আপনার পাসওয়ার্ড পরিবর্তন করতে এই লিংকটি এক ঘণ্টার মধ্যে খুলুন:\n\n{$link}\n\nআপনি অনুরোধ না করলে এই ইমেইল উপেক্ষা করুন।"
                        : "Use this link within one hour to reset your password:\n\n{$link}\n\nIf you did not request this, you can ignore this email.";
                    mf_send_mail($email, $subject, '', $body);
                }
            }
        }

        // Keep the response identical whether or not this email has an account.
        $this->flash('success', trans('password_reset_sent'));
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(wsit_Request $request): string
    {
        $token = trim((string)$request->query('token', ''));
        $reset = $token !== '' ? (new wsit_PasswordReset())->findValid(hash('sha256', $token)) : null;
        if (!$reset) {
            $this->flash('error', trans('password_reset_invalid'));
            $this->redirect('/forgot-password');
        }
        return $this->view('auth/wsit_reset_password', [
            'pageTitle' => trans('reset_password'),
            'token' => $token,
        ]);
    }

    public function resetPassword(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/forgot-password');
        }
        $token = trim((string)$request->input('token', ''));
        $password = (string)$request->input('password', '');
        $confirmation = (string)$request->input('password_confirmation', '');
        $resetModel = new wsit_PasswordReset();
        $resetModel->beginTransaction();
        try {
            $reset = $token !== '' ? $resetModel->findValidForUpdate(hash('sha256', $token)) : null;
            if (!$reset) {
                $resetModel->rollBack();
                $this->flash('error', trans('password_reset_invalid'));
                $this->redirect('/forgot-password');
            }
            if (strlen($password) < 8 || $password !== $confirmation) {
                $resetModel->rollBack();
                $this->flash('error', trans('password_reset_password_rules'));
                $this->redirect('/reset-password?token=' . rawurlencode($token));
            }
            $this->userModel->updatePassword((int)$reset['user_id'], $password);
            $resetModel->consume((int)$reset['id']);
            $resetModel->commit();
        } catch (\Throwable $e) {
            $resetModel->rollBack();
            throw $e;
        }
        $this->flash('success', trans('password_reset_complete'));
        $this->redirect('/login');
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

        // Come back where the visitor came from (e.g. the checkout page they
        // were asked to sign in for). Admin pages stay off-limits for
        // non-admin accounts.
        $redirect = safe_redirect_path($request->input('redirect', ''))
            ?: safe_redirect_path($request->query('redirect', ''));
        if ($redirect !== null
            && !(str_starts_with($redirect, '/mf-dashboard') && (int)$user['is_admin'] !== 1)) {
            $this->redirect($redirect);
        }

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

        // Guest orders placed with this e-mail now belong to this account,
        // so the customer's earlier orders appear in their order history.
        try {
            (new wsit_Order())->linkGuestOrders($id, $data['email']);
        } catch (\Throwable) {
        }

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
