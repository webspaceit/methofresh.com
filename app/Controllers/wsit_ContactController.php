<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Core\wsit_Validator;
use App\Models\wsit_ContactMessage;

class wsit_ContactController extends wsit_Controller
{
    private wsit_ContactMessage $contactModel;

    public function __construct()
    {
        parent::__construct();
        $this->contactModel = new wsit_ContactMessage();
    }

    public function show(): string
    {
        $captchaEnabled = contact_captcha_enabled();
        $captcha = $captchaEnabled ? $this->generateCaptcha() : null;

        return $this->view('contact/wsit_index', [
            'pageTitle'      => trans('contact') . ' | ' . trans('site_name'),
            'captcha'        => $captcha,
            'captchaEnabled' => $captchaEnabled,
        ], 'wsit_main');
    }

    public function submit(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/contact');
        }

        $redirect = $this->contactRedirect($request->input('_redirect'));
        $captchaEnabled = contact_captcha_enabled();

        $data = [
            'name'    => $request->input('name'),
            'email'   => $request->input('email'),
            'phone'   => $request->input('phone'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'captcha' => $captchaEnabled ? $request->input('captcha') : '',
        ];

        $validator = new wsit_Validator();
        $rules = [
            'name'     => 'required|min:2',
            'email'    => 'required|email',
            'subject'  => 'required|min:3',
            'message'  => 'required|min:10',
        ];
        if ($captchaEnabled) {
            $rules['captcha'] = 'required';
        }

        if (!$validator->validate($data, $rules)) {
            $errors = $validator->errors();
            $first = reset($errors);
            $this->flash('error', is_array($first) ? reset($first) : (string)$first);
            $this->redirect($redirect);
        }

        $captchaAnswer = '';
        if ($captchaEnabled) {
            $captchaAnswer = (string)($_SESSION['captcha_answer'] ?? '');
            if (trim((string)$data['captcha']) !== $captchaAnswer) {
                $this->flash('error', trans('captcha_incorrect'));
                $this->redirect($redirect);
            }
            unset($_SESSION['captcha_answer']);
            unset($_SESSION['captcha_image']);
        }

        $this->contactModel->create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] !== '' ? $data['phone'] : null,
            'subject'        => $data['subject'],
            'message'        => $data['message'],
            'captcha_answer' => $captchaAnswer,
        ]);

        send_contact_message_email($data);

        $this->flash('success', trans('contact_message_sent'));
        $this->redirect($redirect);
    }

    private function contactRedirect(string|null $path): string
    {
        $path = (string)$path;
        if ($path === '' || str_contains($path, '//') || !preg_match('#^/[A-Za-z0-9/_-]*$#', $path)) {
            return '/contact';
        }
        return $path;
    }

    public function captchaImage(): void
    {
        $captcha = $this->generateCaptcha();
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        echo $captcha['image'];
        exit;
    }

    private function generateCaptcha(): array
    {
        $num1 = random_int(1, 20);
        $num2 = random_int(1, 20);
        $operators = ['+', '-', '×'];
        $operator = $operators[array_rand($operators)];

        switch ($operator) {
            case '+': $answer = $num1 + $num2; break;
            case '-': $answer = $num1 - $num2; break;
            case '×': $answer = $num1 * $num2; break;
            default: $answer = $num1 + $num2;
        }

        $_SESSION['captcha_answer'] = (string)$answer;

        $width = 140;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);

        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 40, 40, 40);
        $lineColor = imagecolorallocate($image, 180, 180, 180);
        $noiseColor = imagecolorallocate($image, 200, 200, 200);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        for ($i = 0; $i < 5; $i++) {
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        for ($i = 0; $i < 30; $i++) {
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $noiseColor);
        }

        $text = "{$num1} {$operator} {$num2} = ?";
        $fontSize = 22;
        $textWidth = imagefontwidth($fontSize) * mb_strlen($text);
        $x = (int)(($width - $textWidth) / 2);
        $y = (int)(($height - imagefontheight($fontSize)) / 2) + imagefontheight($fontSize);

        imagestring($image, $fontSize, $x, $y - 4, $text, $textColor);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();

        return [
            'image'  => $imageData,
            'answer' => (string)$answer,
        ];
    }
}
