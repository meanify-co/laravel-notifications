<p align="center">
  <a href="https://www.meanify.co?from=github&lib=laravel-notifications">
    <img src="https://meanify.co/assets/core/img/logo/png/meanify_color_dark_horizontal_02.png" width="200" alt="Meanify Logo" />
  </a>
</p>

# Laravel Notifications

A complete library for centralized multichannel notifications in Laravel, supporting multiple email drivers, real-time broadcasting, dynamic templates with multi-language support, and recipient filtering.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Email Channel](#email-channel)
  - [In-App Channel](#in-app-channel)
  - [Broadcasting](#broadcasting)
  - [Recipient Filter](#recipient-filter)
- [Database Setup](#database-setup)
  - [Tables Overview](#tables-overview)
  - [Seeders](#seeders)
- [Usage](#usage)
  - [NotificationBuilder (Fluent API)](#notificationbuilder-fluent-api)
  - [Helper Function](#helper-function)
  - [Sending Emails](#sending-emails)
  - [Email Drivers](#email-drivers)
  - [Attachments](#attachments)
  - [In-App Notifications & Broadcasting](#in-app-notifications--broadcasting)
  - [Scheduling](#scheduling)
  - [Preview (Without Sending)](#preview-without-sending)
  - [Pre-rendered HTML](#pre-rendered-html)
  - [Setting Subject Override](#setting-subject-override)
- [Template System](#template-system)
  - [Template Interpolation Syntax](#template-interpolation-syntax)
  - [Email Layouts](#email-layouts)
- [Recipient Filter](#recipient-filter-1)
- [Notification Statuses](#notification-statuses)
- [Artisan Commands](#artisan-commands)
- [Models Reference](#models-reference)
- [License](#license)

---

## Features

- **Multiple email drivers**: SMTP, Mailgun, SendGrid, SendPulse
- **Email attachments**: file path or base64-encoded content
- **Real-time broadcasting**: in-app notifications via Laravel Broadcasting
- **Custom broadcast channels**: obfuscated model-based channels or simple string channels
- **Multi-language**: locale-based template translations (e.g. `pt_BR`, `en_US`)
- **Dynamic templates**: flexible interpolation engine with conditionals, loops, and isset blocks
- **Queue processing**: async dispatch via Laravel queues with retries and backoff
- **Immediate sending**: bypass queue when needed
- **Recipient filtering**: block emails by domain patterns, encrypted or base64-encoded addresses
- **Payload encryption**: sensitive notification data is encrypted at rest
- **Preview mode**: render notifications without dispatching
- **Scheduled delivery**: send notifications at a future date/time

---

## Requirements

- PHP >= 8.0
- Laravel 10.x, 11.x, or 12.x

---

## Installation

```bash
composer require meanify-co/laravel-notifications
```

The service provider is auto-discovered. Then publish the assets:

```bash
# Publish config
php artisan vendor:publish --tag=meanify-configs

# Publish models
php artisan vendor:publish --tag=meanify-models

# Publish migrations
php artisan vendor:publish --tag=meanify-migrations

# Publish seeders
php artisan vendor:publish --tag=meanify-seeders

# Run migrations
php artisan migrate
```

---

## Configuration

After publishing, the config file is at `config/meanify-laravel-notifications.php`.

```php
return [

    'default_queue_name' => 'meanify.queue.notifications',

    'user_model' => \App\Models\User::class,

    'default_email_layout' => 'default',

    'email' => [
        'enabled'            => true,
        'queue'              => 'meanify.queue.notifications.emails',
        'tries'              => 3,
        'backoff'            => 30,
        'verify_ssl'         => false,
        'send_immediately'   => false,

        'recipient_filter' => [
            'enabled'          => false,
            'blocked_domains'  => [],
            'block_encrypted'  => true,
            'block_base64'     => true,
            'on_block_status'  => 'simulated',
        ],
    ],

    'in_app' => [
        'enabled' => true,
        'queue'   => 'meanify.queue.notifications.in_app',
    ],

    'broadcast' => [
        'channel_prefix' => 'mfy_channel_',
        'enabled'        => true,
    ],
];
```

### Email Channel

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable email sending globally |
| `queue` | string | `meanify.queue.notifications.emails` | Queue name for email jobs |
| `tries` | int | `3` | Max retry attempts on failure |
| `backoff` | int | `30` | Seconds between retries |
| `verify_ssl` | bool | `false` | SSL verification for API drivers |
| `send_immediately` | bool | `false` | If `true`, bypasses the queue globally |

### In-App Channel

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable in-app notifications |
| `queue` | string | `meanify.queue.notifications.in_app` | Queue name for broadcast jobs |

### Broadcasting

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `channel_prefix` | string | `mfy_channel_` | Prefix for obfuscated broadcast channels |
| `enabled` | bool | `true` | Enable/disable broadcasting |

### Recipient Filter

Allows blocking email delivery based on recipient patterns. See [Recipient Filter](#recipient-filter-1) for details.

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | bool | `false` | Enable/disable recipient filtering |
| `blocked_domains` | array | `[]` | Domain patterns to block (supports wildcards) |
| `block_encrypted` | bool | `true` | Block emails that appear encrypted |
| `block_base64` | bool | `true` | Block emails that appear base64-encoded |
| `on_block_status` | string | `simulated` | Status when blocked: `simulated` or `skipped` |

---

## Database Setup

### Tables Overview

The migration creates the following tables:

#### `emails_layouts`
Stores reusable email layout templates (Blade-based).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Layout display name |
| `key` | string (unique) | Identifier (e.g. `default`) |
| `metadata` | json | Colors, fonts, social links, etc. |
| `blade_template` | longText | Blade template with `{{ $content }}` |

#### `notifications_templates`
Master notification template definitions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `email_layout_id` | foreignId (nullable) | Associated email layout |
| `key` | string (unique) | Template identifier (e.g. `user_registered`) |
| `available_channels` | json | Channels array: `['email', 'in_app']` |
| `force_channels` | json (nullable) | Override available channels |
| `active` | boolean | Enable/disable template |

#### `notifications_templates_translations`
Localized content for each template.

| Column | Type | Description |
|--------|------|-------------|
| `notification_template_id` | bigint | Foreign key |
| `locale` | string(10) | Language code (e.g. `pt_BR`) |
| `subject` | string | Email subject |
| `title` | string | Email title |
| `body` | text | Email body with template variables |
| `short_message` | string | In-app notification message |

#### `notifications_templates_variables`
Documents expected variables per template.

| Column | Type | Description |
|--------|------|-------------|
| `notification_template_id` | bigint | Foreign key |
| `key` | string | Variable name (e.g. `user_name`) |
| `description` | text | Human description |
| `example` | string | Example value |

#### `notifications`
Log of all dispatched notifications.

| Column | Type | Description |
|--------|------|-------------|
| `notification_template_id` | foreignId (nullable) | Template reference |
| `user_id` | bigint (nullable) | Recipient user |
| `application_id` | bigint (nullable) | Application context |
| `account_id` | bigint (nullable) | Account context |
| `session_id` | bigint (nullable) | Session context |
| `channel` | string | `email` or `in_app` |
| `payload` | json (encrypted) | Full notification data |
| `status` | enum | `pending`, `processing`, `sent`, `failed`, `simulated`, `skipped` |
| `scheduled_to` | timestamp (nullable) | Scheduled delivery time |
| `sent_at` | timestamp (nullable) | Actual send time |
| `failed_at` | timestamp (nullable) | Failure time |
| `failed_log` | json (nullable) | Exception details |

> **Note:** The `application_id`, `account_id`, and `session_id` foreign keys are conditionally added only if their corresponding tables exist in the database.

### Seeders

After publishing, run the seeders to populate default data:

```bash
# Seeds the default email layout
php artisan db:seed --class=EmailLayoutSeeder

# Seeds pre-built notification templates (pt_BR + en_US)
php artisan db:seed --class=NotificationTemplateSeeder
```

**Included templates:** `sign_in_otp`, `forgot_password_otp`, `new_login_detected`, `email_change_verification`, `password_changed_notice`, `email_changed_notice`, `invite_new_user`, `in_app_only_1`, `combined_notification`.

---

## Usage

### NotificationBuilder (Fluent API)

The `NotificationBuilder` is the primary interface for creating and sending notifications.

```php
use Meanify\LaravelNotifications\Support\NotificationBuilder;

NotificationBuilder::make('template_key', $user, 'pt_BR')
    ->onAccount($accountId)              // optional: account context
    ->onApplication($applicationId)      // optional: application context
    ->onSession($sessionId)              // optional: session context
    ->forEmail('smtp', $configs, $recipients)
    ->with(['user_name' => 'John'])      // template variables
    ->withAttachments([...])             // optional: file attachments
    ->toBroadcastChannels([...])         // optional: custom broadcast channels
    ->scheduledTo(now()->addHours(1))    // optional: delay delivery
    ->send();                            // dispatch!
```

#### Available Methods

| Method | Description |
|--------|-------------|
| `make($templateKey, $user, $locale)` | Static factory. Creates a builder instance. |
| `onAccount($id)` | Sets account context. |
| `onApplication($id)` | Sets application context. |
| `onSession($id)` | Sets session context. |
| `forEmail($driver, $configs, $recipients, $sendImmediately)` | Configures email delivery. |
| `with($data)` | Sets dynamic template variables. |
| `setSubject($subject)` | Overrides the email subject. |
| `withRenderedHtml($html, $subject, $payload)` | Uses pre-rendered HTML instead of template. |
| `withAttachments($attachments)` | Adds file attachments. |
| `toBroadcastChannels($channels)` | Sets custom broadcast channels for in-app. |
| `scheduledTo($carbon)` | Schedules delivery for a future time. |
| `preview($interpolate)` | Renders the notification without sending. Returns array. |
| `send()` | Dispatches the notification. Returns `bool`. |

### Helper Function

A global helper is available for convenience:

```php
// With arguments: returns NotificationBuilder
meanify_notifications('template_key', $user, 'pt_BR')
    ->forEmail('smtp', $configs, ['user@example.com'])
    ->with(['code' => '123456'])
    ->send();

// Without arguments: returns NotificationUtils
$email = meanify_notifications()->decryptRecipientToUnsubscribe($encryptedToken);
```

### Sending Emails

```php
NotificationBuilder::make('welcome_email', $user, 'pt_BR')
    ->forEmail('smtp', [
        'smtp_host'       => 'smtp.example.com',
        'smtp_port'       => 587,
        'smtp_encryption' => 'tls',
        'smtp_username'   => 'notifications@example.com',
        'smtp_password'   => 'your-encrypted-password',
        'from_address'    => 'noreply@example.com',
        'from_name'       => 'My App',
    ], ['recipient@example.com'])
    ->with([
        'user_name'       => 'John Doe',
        'activation_link' => 'https://example.com/activate/abc123',
    ])
    ->send();
```

To send immediately (bypassing queue):

```php
NotificationBuilder::make('otp_email', $user, 'en_US')
    ->forEmail('smtp', $configs, ['user@example.com'], sendImmediately: true)
    ->with(['otp' => '123456'])
    ->send();
```

### Email Drivers

#### SMTP

```php
->forEmail('smtp', [
    'smtp_host'       => 'smtp.example.com',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',
    'smtp_username'   => 'user@example.com',
    'smtp_password'   => 'encrypted-password',
    'from_address'    => 'noreply@example.com',
    'from_name'       => 'App Name',
], $recipients)
```

#### Mailgun

```php
->forEmail('mailgun', [
    'mailgun_domain'   => 'mg.example.com',
    'mailgun_api_key'  => 'key-xxx',
    'mailgun_endpoint' => 'https://api.mailgun.net',
    'from_address'     => 'noreply@example.com',
    'from_name'        => 'App Name',
], $recipients)
```

#### SendGrid

```php
->forEmail('sendgrid', [
    'sendgrid_api_key'  => 'SG.xxx',
    'sendgrid_endpoint' => 'https://api.sendgrid.com',
    'from_address'      => 'noreply@example.com',
    'from_name'         => 'App Name',
], $recipients)
```

#### SendPulse

```php
->forEmail('sendpulse', [
    'sendpulse_client_id'     => 'client-id',
    'sendpulse_client_secret' => 'client-secret',
    'sendpulse_endpoint'      => 'https://api.sendpulse.com',
    'from_address'            => 'noreply@example.com',
    'from_name'               => 'App Name',
], $recipients)
```

### Attachments

Supports two formats: file path and base64 content.

```php
NotificationBuilder::make('invoice_email', $user, 'pt_BR')
    ->forEmail('smtp', $configs, ['finance@example.com'])
    ->with(['invoice_number' => 'INV-001'])
    ->withAttachments([
        // From file path
        [
            'path' => '/storage/invoices/inv-001.pdf',
            'name' => 'Invoice-001.pdf',
            'mime' => 'application/pdf',
        ],
        // From base64 content
        [
            'content' => base64_encode($fileData),
            'name'    => 'Report.xlsx',
            'mime'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ])
    ->send();
```

### In-App Notifications & Broadcasting

Templates with `in_app` in `available_channels` will automatically dispatch broadcast events.

```php
// Simple string channels
NotificationBuilder::make('task_assigned', $user, 'pt_BR')
    ->with(['task' => 'Review PR #42'])
    ->toBroadcastChannels([
        'user.123',
        'admin.dashboard',
    ])
    ->send();

// Model-based obfuscated channels
NotificationBuilder::make('project_update', $user, 'pt_BR')
    ->toBroadcastChannels([
        ['model' => User::class, 'id' => $manager->id],
        ['model' => Team::class, 'id' => $team->id],
    ])
    ->send();

// Channels with custom event names
NotificationBuilder::make('alert', $user, 'en_US')
    ->toBroadcastChannels([
        ['channel' => 'project.alerts', 'event' => 'project.completed'],
    ])
    ->send();
```

**Broadcast payload structure:**

```json
{
    "id": 1,
    "type": "task_assigned",
    "short_message": "You were assigned to Review PR #42",
    "created_at": "2025-01-15T10:30:00Z"
}
```

Default event name: `in-app.notification` (customizable per channel).

### Scheduling

```php
use Carbon\Carbon;

NotificationBuilder::make('weekly_report', $user, 'pt_BR')
    ->forEmail('sendgrid', $configs, ['user@example.com'])
    ->with(['report_url' => 'https://...'])
    ->scheduledTo(Carbon::now()->addHours(2))
    ->send();
```

### Preview (Without Sending)

Render the notification HTML without dispatching:

```php
// With template interpolation
$preview = NotificationBuilder::make('welcome_email', $user, 'pt_BR')
    ->with(['user_name' => 'John', 'code' => '123456'])
    ->preview();

echo $preview['subject']; // Rendered subject
echo $preview['html'];    // Final HTML with layout

// Without interpolation (raw placeholders preserved)
$raw = NotificationBuilder::make('welcome_email', $user, 'pt_BR')
    ->preview(interpolate: false);

echo $raw['body']; // "Hello {{ user_name }}, your code is {{ code }}"
```

**Return format:**

```php
[
    'subject' => 'Rendered subject',
    'title'   => 'Rendered title',
    'body'    => 'Rendered body content',
    'html'    => 'Complete HTML with email layout',
]
```

### Pre-rendered HTML

Send custom HTML without using a template:

```php
NotificationBuilder::make(null, $user, 'pt_BR')
    ->withRenderedHtml('<h1>Custom Email</h1><p>Hello!</p>', 'Custom Subject')
    ->forEmail('smtp', $configs, ['user@example.com'])
    ->send();
```

### Setting Subject Override

Override the template's subject:

```php
NotificationBuilder::make('generic_email', $user, 'pt_BR')
    ->setSubject('Urgent: Action Required')
    ->forEmail('smtp', $configs, ['user@example.com'])
    ->with(['message' => 'Please review immediately.'])
    ->send();
```

---

## Template System

### Template Interpolation Syntax

The `TemplateInterpolator` provides a lightweight template engine for notification content.

#### Variable Replacement

```
Hello {{ user_name }}, your code is {{ otp }}.
```

#### Conditionals

```
@if(is_premium)
  Welcome, premium member!
@else
  Upgrade to premium for more features.
@endif
```

**Supported expressions:**

- Simple: `@if(variable_name)`
- Negation: `@if(!variable_name)`
- `isset()`: `@if(isset(optional_field))`
- `empty()`: `@if(empty(comments))`
- `count()`: `@if(count(items) > 0)`
- Logical `&&` / `||`: `@if(active && verified)`
- Combined: `@if((is_admin || is_moderator) && !banned)`

#### Isset Blocks

```
@isset(discount_code)
  Use code {{ discount_code }} for 10% off!
@endisset
```

#### Foreach Loops

```
Your recent orders:
@foreach(orders as $order)
  - Order #{{ $order.id }}: {{ $order.total }}
@endforeach
```

Both `{{ $item.key }}` and `{{ $item->key }}` syntaxes are supported.

#### Complete Template Example

```
Hello {{ user_name }},

@if(has_orders)
  Here are your recent orders:
  @foreach(orders as $order)
    - #{{ $order.id }}: ${{ $order.total }}
  @endforeach
@else
  You have no orders yet. Start shopping!
@endif

@isset(coupon_code)
  Special offer: use code {{ coupon_code }} for a discount!
@endisset
```

### Email Layouts

Layouts are Blade templates stored in the `emails_layouts` table. They wrap the notification body.

**Available variables in layouts:**

| Variable | Description |
|----------|-------------|
| `$content` | Rendered email body |
| `$subject` | Email subject |
| Metadata keys | All keys from layout `metadata` JSON (e.g. `$logo_url`, `$help_text`) |
| Dynamic data | All keys from `dynamic_data` (template variables) |

**Layout template example:**

```blade
<html>
<body>
  <header>
    <img src="{{ $logo_url }}" width="{{ $logo_width }}" />
  </header>
  <main>
    {!! $content !!}
  </main>
  <footer>
    <p>{{ $help_text }}</p>
    <a href="{{ $privacy_url }}">{{ $privacy_text }}</a>
  </footer>
</body>
</html>
```

**Environment variables for default layout seeder:**

| Variable | Description |
|----------|-------------|
| `APP_EMAIL_SRC` | Logo URL |
| `APP_EMAIL_WIDTH` | Logo width |
| `APP_EMAIL_HEIGHT` | Logo height |
| `APP_EMAIL_SHORT_CTA` | Button text (default: "Click here") |
| `APP_EMAIL_CTA_HELP` | Help text under CTA |
| `APP_EMAIL_HELP_TEXT` | Footer text |
| `APP_EMAIL_SOCIAL_LINK_*` | Social media URLs |
| `APP_EMAIL_PRIVACY_URL` | Privacy policy link |
| `APP_EMAIL_UNSUBSCRIBE_URL` | Unsubscribe link |

---

## Recipient Filter

The recipient filter prevents email delivery to certain addresses. When enabled, it evaluates each recipient before sending.

### Configuration

```php
'email' => [
    'recipient_filter' => [
        'enabled'          => true,
        'blocked_domains'  => ['example.com', 'mailinator.com', '*.test'],
        'block_encrypted'  => true,
        'block_base64'     => true,
        'on_block_status'  => 'simulated', // or 'skipped'
    ],
],
```

### How It Works

Each recipient is checked in order:

1. **Encrypted detection** — blocks if the email has no `@`, starts with `eyJ` (JWT-like), or has a high ratio of non-alphanumeric characters.
2. **Base64 detection** — blocks if the local part (before `@`) is a valid base64 string (8+ chars that decode and re-encode identically).
3. **Domain matching** — blocks if the domain matches any pattern in `blocked_domains`. Supports exact match (`example.com`) and wildcard (`*.test` matches `anything.test`).

### Behavior When Blocked

When **all recipients** are blocked:

| `on_block_status` | Notification Status | `sent_at` | Use Case |
|--------------------|---------------------|-----------|----------|
| `simulated` | `simulated` | Filled (current time) | Testing/staging — simulates successful delivery |
| `skipped` | `skipped` | `null` | Production — clearly marks as not executed |

When **some recipients** are blocked, only the allowed recipients receive the email. Blocked recipients are logged.

### Logging

All blocked recipients generate a `Log::warning` with:

```
Email notification simulated — recipients blocked by filter
```

or

```
Email notification skipped — recipients blocked by filter
```

Including: `notification_id`, blocked email list with reasons (`blocked_domain`, `encrypted`, `base64_encoded`).

---

## Notification Statuses

| Status | Description |
|--------|-------------|
| `pending` | Created, waiting to be dispatched |
| `processing` | Email job started, sending in progress |
| `sent` | Successfully delivered |
| `failed` | Delivery failed (see `failed_log` for details) |
| `simulated` | Blocked by recipient filter, saved as if sent |
| `skipped` | Blocked by recipient filter, marked as not executed |

Status constants are available on the `Notification` model:

```php
use App\Models\Notification;

Notification::NOTIFICATION_STATUS_PENDING;    // 'pending'
Notification::NOTIFICATION_STATUS_PROCESSING; // 'processing'
Notification::NOTIFICATION_STATUS_SENT;       // 'sent'
Notification::NOTIFICATION_STATUS_FAILED;     // 'failed'
Notification::NOTIFICATION_STATUS_SIMULATED;  // 'simulated'
Notification::NOTIFICATION_STATUS_SKIPPED;    // 'skipped'
```

---

## Artisan Commands

### Test Notification

Send a test notification from the command line:

```bash
php artisan meanify:notifications:test \
    --template=welcome_email \
    --user=1 \
    --locale=pt_BR \
    --vars='{"user_name":"John","activation_code":"123456"}'
```

**Options:**

| Option | Required | Description |
|--------|----------|-------------|
| `--template` | Yes | Notification template key |
| `--user` | Yes | User ID of the recipient |
| `--locale` | No | Language code (default: `app.locale`) |
| `--emails` | No | Comma-separated recipient emails (overrides default) |
| `--smtp` | No | SMTP settings ID from `emails_settings` table |
| `--account` | No | Account ID context |
| `--application` | No | Application ID context |
| `--session` | No | Session ID context |
| `--vars` | No | JSON object with template variables |

**Examples:**

```bash
# Basic test
php artisan meanify:notifications:test --template=sign_in_otp --user=1

# With custom recipients
php artisan meanify:notifications:test \
    --template=test_email \
    --user=1 \
    --emails="test1@example.com,test2@example.com"

# With context
php artisan meanify:notifications:test \
    --template=invite_new_user \
    --user=1 \
    --account=10 \
    --application=20 \
    --vars='{"first_name":"John","organization_name":"Acme"}'
```

---

## Models Reference

All models are published to `app/Models/` and can be customized.

| Model | Table | Key Features |
|-------|-------|--------------|
| `Notification` | `notifications` | Encrypted payload, status tracking, user/template relations |
| `NotificationTemplate` | `notifications_templates` | SoftDeletes, channel config, layout relation |
| `NotificationTemplateTranslation` | `notifications_templates_translations` | SoftDeletes, locale-based content |
| `NotificationTemplateVariable` | `notifications_templates_variables` | SoftDeletes, variable documentation |
| `EmailLayout` | `emails_layouts` | SoftDeletes, metadata (array), Blade template |

### Key Relationships

```
NotificationTemplate
├── layout()        → belongsTo(EmailLayout)
├── translations()  → hasMany(NotificationTemplateTranslation)
└── variables()     → hasMany(NotificationTemplateVariable)

Notification
├── user()          → belongsTo(User)
└── template()      → belongsTo(NotificationTemplate)
```

---

## License

MIT License. See [LICENSE](LICENSE) for details.
