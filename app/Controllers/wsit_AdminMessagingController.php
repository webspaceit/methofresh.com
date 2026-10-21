<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Conversation;
use App\Models\wsit_MessageLog;
use App\Models\wsit_Setting;
use App\Services\Messaging\MessagingConfig;
use App\Services\Messaging\MessengerService;
use App\Services\Messaging\WhatsAppService;

/**
 * Dashboard pages for the WhatsApp + Messenger automation system:
 * credentials, webhook URLs, test send, message log and conversations.
 */
class wsit_AdminMessagingController extends wsit_Controller
{
    private wsit_Setting $settingModel;
    private wsit_MessageLog $logModel;
    private wsit_Conversation $conversationModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel      = new wsit_Setting();
        $this->logModel          = new wsit_MessageLog();
        $this->conversationModel = new wsit_Conversation();
    }

    protected function before(): void
    {
        $this->requireAdmin();
    }

    public function index(): string
    {
        $config = new MessagingConfig();
        $base   = rtrim((string)config('app.base_url', ''), '/');

        return $this->view('admin/wsit_messaging', [
            'pageTitle'      => trans('admin_messaging') . ' | ' . trans('admin'),
            'values'         => $config->all(),
            'stats'          => $this->logModel->stats(),
            'conversationStats' => $this->conversationModel->countByStatus(),
            'waWebhookUrl'   => $base . '/api/whatsapp/webhook',
            'fbWebhookUrl'   => $base . '/api/messenger/webhook',
            'waReady'        => $config->whatsAppReady(),
            'fbReady'        => $config->messengerReady(),
        ], 'wsit_admin_material');
    }

    public function save(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/messaging');
        }

        $defaults = (new MessagingConfig())->all();
        $keys = array_keys($defaults);

        foreach ($keys as $key) {
            if ($key === 'enabled') {
                $value = $request->input('enabled') === '1' ? '1' : '0';
            } elseif ($key === 'api_version') {
                $value = trim((string)$request->input($key, 'v21.0'));
                if (!preg_match('/^v\d+\.\d+$/', $value)) {
                    $value = 'v21.0';
                }
            } elseif ($key === 'wa_template_lang') {
                $value = (string)$request->input($key, 'bn');
                if (!in_array($value, ['bn', 'en', 'en_US'], true)) {
                    $value = 'bn';
                }
            } elseif ($key === 'admin_alert_phone') {
                $value = WhatsAppService::normalizePhone((string)$request->input($key, ''));
            } else {
                $value = trim((string)$request->input($key, ''));
            }

            $this->settingModel->set('messaging_' . $key, $value);
        }

        MessagingConfig::flush();
        $this->flash('success', trans('admin_settings_saved'));
        $this->redirect('/mf-dashboard/messaging');
    }

    public function test(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/messaging');
        }

        $channel   = $request->input('channel') === 'messenger' ? 'messenger' : 'whatsapp';
        $recipient = trim((string)$request->input('recipient', ''));
        $config    = new MessagingConfig();

        if ($channel === 'whatsapp') {
            if (!$config->whatsAppReady()) {
                $this->flash('error', trans('admin_messaging_not_configured'));
                $this->redirect('/mf-dashboard/messaging');
            }
            $result = (new WhatsAppService($config))->sendText(
                $recipient,
                '✅ ' . app_name() . ' — এটি একটি টেস্ট মেসেজ। আপনার WhatsApp ইন্টিগ্রেশন কাজ করছে।'
            );
            $this->logModel->logOutbound(
                'whatsapp',
                WhatsAppService::normalizePhone($recipient) ?: $recipient,
                $result['message_id'],
                ['text' => 'test message', 'response' => $result['response']],
                $result['ok'],
                $result['error'],
                null,
                'test'
            );
        } else {
            if (!$config->messengerReady()) {
                $this->flash('error', trans('admin_messaging_not_configured'));
                $this->redirect('/mf-dashboard/messaging');
            }
            $result = (new MessengerService($config))->sendText(
                $recipient,
                '✅ ' . app_name() . ' — Messenger integration test message.'
            );
            $this->logModel->logOutbound(
                'messenger',
                $recipient,
                $result['message_id'],
                ['text' => 'test message', 'response' => $result['response']],
                $result['ok'],
                $result['error'],
                null,
                'test'
            );
        }

        if ($result['ok']) {
            $this->flash('success', trans('admin_messaging_test_ok'));
        } else {
            $this->flash('error', trans('admin_messaging_test_failed') . ' ' . (string)$result['error']);
        }

        $this->redirect('/mf-dashboard/messaging');
    }

    public function logs(wsit_Request $request): string
    {
        $filters = [
            'channel'   => (string)$request->query('channel', ''),
            'direction' => (string)$request->query('direction', ''),
            'status'    => (string)$request->query('status', ''),
            'q'         => trim((string)$request->query('q', '')),
        ];

        $perPage = 30;
        $page    = max(1, (int)$request->query('page', 1));
        $total   = $this->logModel->countAll($filters);

        return $this->view('admin/wsit_message_logs', [
            'pageTitle' => trans('admin_message_logs') . ' | ' . trans('admin'),
            'logs'      => $this->logModel->all($filters, $perPage, ($page - 1) * $perPage),
            'total'     => $total,
            'filters'   => $filters,
            'page'      => $page,
            'perPage'   => $perPage,
            'stats'     => $this->logModel->stats(),
        ], 'wsit_admin_material');
    }

    public function clearLogs(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/message-logs');
        }

        if ($request->input('mode') === 'all') {
            $deleted = $this->logModel->clearAll();
        } else {
            $deleted = $this->logModel->deleteOlderThan(30);
        }

        $this->flash('success', trans('admin_messaging_logs_cleared') . ' (' . $deleted . ')');
        $this->redirect('/mf-dashboard/message-logs');
    }

    public function conversations(wsit_Request $request): string
    {
        $status  = (string)$request->query('status', '');
        $perPage = 30;
        $page    = max(1, (int)$request->query('page', 1));
        $total   = $this->conversationModel->countAll($status);

        return $this->view('admin/wsit_conversations', [
            'pageTitle'    => trans('admin_conversations') . ' | ' . trans('admin'),
            'conversations' => $this->conversationModel->all($status, $perPage, ($page - 1) * $perPage),
            'total'        => $total,
            'statusFilter' => $status,
            'page'         => $page,
            'perPage'      => $perPage,
            'counts'       => $this->conversationModel->countByStatus(),
        ], 'wsit_admin_material');
    }

    public function updateConversationStatus(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/conversations');
        }

        $status = (string)$request->input('status', '');
        if (in_array($status, ['open', 'human', 'closed'], true)) {
            $this->conversationModel->setStatus($id, $status);
            $this->flash('success', trans('admin_updated'));
        }

        $this->redirect('/mf-dashboard/conversations');
    }
}
