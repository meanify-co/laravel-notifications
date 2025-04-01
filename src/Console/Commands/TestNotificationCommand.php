<?php
namespace Meanify\LaravelNotifications\Console\Commands;

namespace Meanify\LaravelNotifications\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Meanify\LaravelNotifications\Support\NotificationBuilder;

class TestNotificationCommand extends Command
{
    protected $signature = 'meanify:notifications:test
        {--template= : The notification template key}
        {--locale= : (Option) accept pt-br or en-us}
        {--user= : ID of the user who will receive the notification}
        {--emails= : (Optional) Comma-separated list of emails to send}
        {--smtp= : (Optional) ID of smtp email settings }
        {--account= : (Optional) Account ID}
        {--application= : (Optional) Application ID}
        {--session= : (Optional) Session ID}
        {--vars= : (Optional) JSON with dynamic template variables}';

    protected $description = 'Manually send a test notification using a template';

    public function handle(): void
    {
        $templateKey = $this->option('template');
        $locale = str_replace('-', '_', ($this->option('locale') ?? config('app.locale')));
        $userId = $this->option('user');

        $accountId = $this->option('account');
        $applicationId = $this->option('application');
        $sessionId = $this->option('session');

        $vars = $this->option('vars');

        $user = User::find($userId);
        if (! $user) {
            $this->error('User not found.');
            return;
        }

        $replacements = [];
        if ($vars) {
            try {
                $replacements = json_decode($vars, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $this->error('Invalid JSON in --vars');
                return;
            }
        }


        $smtp_id = $this->option('smtp');

        if($smtp_id)
        {
            $emailSettings = DB::table('emails_settings')->find($smtp_id);
            $smtpConfigs = json_decode($emailSettings->settings, true);
        }
        else
        {
            $emailSettings = DB::table('emails_settings')->first();
            $smtpConfigs = json_decode($emailSettings->settings, true);
        }

        $overrideEmails = [];
        if ($emails = $this->option('emails')) {
            $overrideEmails = array_map('trim', explode(',', $emails));
        }

        NotificationBuilder::make()
            ->to($user)
            ->locale($locale)
            ->onAccount($accountId)
            ->onApplication($applicationId)
            ->onSession($sessionId)
            ->email($smtpConfigs, $overrideEmails, $templateKey)
            ->with($replacements)
            ->send();

        $this->info("Notification sent successfully to {$user->username}");
    }
}
