<p align="center">
  <a href="https://www.meanify.co?from=github&lib=laravel-payment-hub">
    <img src="https://meanify.co/assets/core/img/logo/png/meanify_color_dark_horizontal_02.png" width="200" alt="Meanify Logo" />
  </a>
</p>


# Laravel Notifications

Um sistema robusto e extensível de notificações para Laravel que unifica **e-mail**, **in-app** e **notificações em tempo real**.

> ✅ Suporte completo a templates dinâmicos, layouts HTML, traduções, substituição de SMTP, canais seguros e muito mais.

---

## 📦 Instalação

```bash
composer require meanify-co/laravel-notifications
```

---

## 🚀 Publicação dos recursos

```bash
php artisan vendor:publish --provider="Meanify\LaravelNotifications\Providers\MeanifyLaravelNotificationServiceProvider"
```

Isso irá publicar:

- `config/meanify-laravel-notifications.php`
- Migrations
- Seeders (opcional)
- Views (layouts Blade)

---

## ⚙️ Configuração

Edite `config/meanify-laravel-notifications.php` para personalizar:

- Nomes de filas
- Layout padrão
- Retries e backoff para e-mail
- Prefixo dos canais de broadcast

---

## 🧱 Estrutura do banco

Este pacote utiliza as seguintes tabelas:

- `emails_layouts`
- `notifications_templates`
- `notifications_templates_translations`
- `notifications_templates_variables`
- `notifications`

Execute as migrations:

```bash
php artisan migrate
```

---

## 🌱 Seeders

Opcionalmente, rode os seeders:

```bash
php artisan db:seed --class="\\Meanify\\LaravelNotifications\\Database\\Seeders\\EmailLayoutSeeder"
php artisan db:seed --class="\\Meanify\\LaravelNotifications\\Database\\Seeders\\NotificationTemplateSeeder"
```

---

## 💡 Exemplo com Helper

```php
meanify_notifications()
    ->to($user)
    ->locale('pt_BR')
    ->onAccount($accountId)
    ->onApplication($appId)
    ->onSession($sessionId)
    ->email($smtpConfigs, $recipients, 'sign_in_code')
    ->with(['code' => '123456'])
    ->send();
```

Ou utilizando diretamente:

```php
NotificationBuilder::make($user, 'pt_BR')
    ->onAccount($accountId)
    ->onApplication($appId)
    ->onSession($sessionId)
    ->email($smtpConfigs, $recipients, 'sign_in_code')
    ->with(['code' => '123456'])
    ->send();
```

---

## 🧪 Comando Artisan de Teste

```bash
php artisan meanify:notifications:test \
    --template=sign_in_code \
    --user=1 \
    --emails=usuario@exemplo.com \
    --vars='{"code": "123456"}'
```

Parâmetros opcionais:

- `--template=` Chave do template de notificação
- `--locale=` Idioma (pt-BR ou en-US)
- `--user=` ID do usuário
- `--emails=` Lista de e-mails separados por vírgula
- `--smtp=` ID da configuração de e-mail (tabela `emails_settings`)
- `--account=`, `--application=`, `--session=` Contexto opcional
- `--vars=` JSON com variáveis de substituição

---

## ✨ Templates Dinâmicos

- Armazenados no banco com traduções por idioma
- Suporte a `{{ variable }}` no assunto, corpo e mensagens in-app

---

## 🎨 Layout HTML com Blade

Todos os e-mails usam um layout Blade salvo no banco, com suporte às variáveis:

```blade
{{ \$logo_url }}, {{ \$title }}, {{ \$text }}, {{ \$otp }}, {{ \$cta_link }}, {{ \$cta_button }}, {{ \$short_cta }}, {{ \$cta_help }}, {{ \$help_text }}, {{ \$social_links }}, {{ \$privacy_url }}, {{ \$unsubscribe_url }}
```

> O layout será renderizado usando `Blade::render()` via `NotificationRenderer`.

---

## 📡 Notificações em Tempo Real (Laravel Reverb)

Utiliza canais com prefixo `mfy_channel_` e ID ofuscado + base64:

```php
ChannelBuilder::makeChannel(User::class, $user);
```

Exemplo de evento emitido:

```php
broadcast(new InAppNotificationCreated($notification));
```

> Usa `meanify-co/laravel-obfuscator` para mascarar IDs.

---

## 🛠️ Configuração do Supervisor

### Exemplo: notifications.conf

```ini
[program:meanify-notifications-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --queue=meanify_queue_notification --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/supervisor-notifications.log
stopwaitsecs=3600
```

Crie também workers para:

- `meanify_queue_notification_emails`
- `meanify_queue_notification_in_app`

---

## ✅ Canais Suportados

- [x] E-mail (SMTP customizável)
- [x] In-App
- [x] Laravel Reverb (Broadcast)

---

## 🧰 Personalização

- SMTP dinâmico por envio (criptografado)
- Variáveis e layouts HTML por template
- Builder fluente com contexto completo
- Integração com sistemas multi-conta, multi-app, multi-sessão

---

## 📄 Licença

MIT © [Meanify](https://meanify.co)

