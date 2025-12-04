<p align="center">
  <a href="https://www.meanify.co?from=github&lib=laravel-payment-hub">
    <img src="https://meanify.co/assets/core/img/logo/png/meanify_color_dark_horizontal_02.png" width="200" alt="Meanify Logo" />
  </a>
</p>

# Laravel Notifications

Uma biblioteca completa para envio de notificações centralizadas no Laravel, com suporte a múltiplos drivers de email e broadcasting.

---

## ✨ Funcionalidades

- 📧 **Múltiplos drivers de email**: SMTP, Mailgun, SendGrid, SendPulse
- 📎 **Anexos em emails**: Suporte completo a arquivos anexos
- 🎯 **Broadcasting**: Notificações em tempo real
- 📡 **Canais customizados**: Broadcast para canais específicos
- 🌍 **Multi-idioma**: Suporte a templates traduzidos
- 📊 **Templates dinâmicos**: Sistema flexível de templates
- ⚡ **Jobs em fila**: Processamento assíncrono
- 🔒 **Segurança**: Criptografia de dados sensíveis

## 📎 Nova Funcionalidade: Anexos em Emails

Agora você pode enviar emails com anexos usando qualquer driver suportado:

```php
use Meanify\LaravelNotifications\Support\NotificationBuilder;

NotificationBuilder::make('invoice-email', $user, 'pt_BR')
    ->forEmail('smtp', $smtpConfigs, ['client@example.com'])
    ->with(['invoice_number' => '12345'])
    ->withAttachments([
        [
            'path' => '/path/to/invoice.pdf',
            'name' => 'Fatura-12345.pdf',
            'mime' => 'application/pdf'
        ],
        [
            'content' => base64_encode($generatedContent),
            'name' => 'Report.xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]
    ])
    ->send();
```

## 📡 Nova Funcionalidade: Canais de Broadcast Customizados

Agora você pode definir canais específicos para suas notificações in-app:

```php
use Meanify\LaravelNotifications\Support\NotificationBuilder;

// Canais simples
NotificationBuilder::make('user-alert', $user, 'pt_BR')
    ->with(['message' => 'Alerta importante!'])
    ->toBroadcastChannels([
        'user.123',
        'admin.dashboard',
        'team.developers'
    ])
    ->send();

// Canais com modelos obfuscados
NotificationBuilder::make('project-update', $user, 'pt_BR')
    ->toBroadcastChannels([
        ['model' => User::class, 'id' => $manager->id],
        ['channel' => 'project.alerts', 'event' => 'project.completed']
    ])
    ->send();
```

## Documentation:

### - [Português (pt-BR)](docs/pt-BR.md) 🇧🇷

### - [English](docs/en-US.md) 🇺🇸

### - [📎 Exemplos de Anexos](docs/attachments-example.md)

### - [📡 Canais de Broadcast Customizados](docs/broadcast-channels-example.md)
