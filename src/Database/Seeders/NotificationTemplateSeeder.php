<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $email_layout    = DB::table('emails_layouts')->first();
        $email_layout_id = isset($email_layout) ? $email_layout->id : null;

        $templates = [
            [
                'key'          => 'sign_in_otp',
                'channels'     => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Código de verificação de acesso',
                        'title'         => 'Código de verificação de acesso',
                        'body'          => 'Seu código de verificação é: <br><strong>{{ otp }}</strong>',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject'       => 'Sign-in verification code',
                        'title'         => 'Sign-in verification code',
                        'body'          => 'Your verification code is: <br><strong>{{ otp }}</strong>',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'otp', 'description' => 'Código de verificação', 'example' => '123456'],
                ],
            ],
            [
                'key'          => 'forgot_password_otp',
                'channels'     => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Código para redefinir sua senha',
                        'title'         => 'Código para redefinir sua senha',
                        'body'          => 'Utilize o código <strong>{{ otp }}</strong> para redefinir sua senha.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject'       => 'Password reset code',
                        'title'         => 'Password reset code',
                        'body'          => 'Use the code <strong>{{ otp }}</strong> to reset your password.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'otp', 'description' => 'Código de redefinição', 'example' => '789123'],
                ],
            ],
            [
                'key'          => 'new_login_detected',
                'channels'     => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Novo login detectado',
                        'title'         => 'Novo login detectado',
                        'body'          => 'Um novo login foi detectado em sua conta. IP: {{ ip }} - Navegador: {{ browser }}',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject'       => 'New login detected',
                        'title'         => 'New login detected',
                        'body'          => 'A new login was detected on your account. IP: {{ ip }} - Browser: {{ browser }}',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'ip', 'description' => 'Endereço IP', 'example' => '192.168.0.1'],
                    ['key' => 'browser', 'description' => 'Navegador ou dispositivo', 'example' => 'Chrome no Windows'],
                ],
            ],
            [
                'key'          => 'email_change_verification',
                'channels'     => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Verificação de alteração de e-mail',
                        'title'         => 'Verificação de alteração de e-mail',
                        'body'          => 'Use o código <strong>{{ otp }}</strong> para confirmar a alteração do seu e-mail.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject'       => 'Email change verification',
                        'title'         => 'Email change verification',
                        'body'          => 'Use the code <strong>{{ otp }}</strong> to confirm your email change.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'otp', 'description' => 'Código de verificação', 'example' => '445566'],
                ],
            ],
            [
                'key'          => 'password_changed_notice',
                'channels'     => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Sua senha foi alterada',
                        'title'         => 'Sua senha foi alterada',
                        'body'          => 'Este é um aviso de que sua senha foi alterada com sucesso. Se não foi você, entre em contato conosco imediatamente.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject'       => 'Your password has been changed',
                        'title'         => 'Your password has been changed',
                        'body'          => 'This is a notice that your password has been successfully changed. If it wasn’t you, contact us immediately.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [],
            ],
            [
                'key'          => 'email_changed_notice',
                'channels'     => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Seu e-mail foi alterado',
                        'title'         => 'Alteração de e-mail',
                        'body'          => 'Seu endereço de e-mail <strong>{{ old_email }}</strong> foi alterado com sucesso no sistema <strong>{{ system_name }}</strong>.<br><br>Se você não reconhece essa alteração, entre em contato com o suporte imediatamente.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'Your email address has been changed',
                        'title' => 'Email address changed',
                        'body' => 'Your email <strong>{{ old_email }}</strong> has been successfully changed in the <strong>{{ system_name }}</strong> system.<br><br>If you did not perform this change, please contact support immediately',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'old_email', 'description' => 'Email antigo do usuário', 'example' => 'joao@email.com'],
                    ['key' => 'system_name', 'description' => 'Nome do sistema ou plataforma', 'example' => 'Meanify'],
                ],
            ],
            [
                'key' => 'invite_new_user',
                'channels' => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Você foi convidado para o {{ system_name }}',
                        'title' => 'Convite para acessar o {{ system_name }}',
                        'body' => 'Olá {{ first_name }} {{ last_name }},<br><br>Você foi convidado para acessar o sistema <strong>{{ system_name }}</strong>.<br>Ao clicar no botão abaixo, seu convite será aceito automaticamente.<br><br>Sua senha foi gerada automaticamente e poderá ser alterada após o primeiro acesso.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'You were invited to join {{ system_name }}',
                        'title' => 'You’ve been invited to {{ system_name }}',
                        'body' => 'Hello {{ first_name }} {{ last_name }},<br><br>You’ve been invited to join <strong>{{ system_name }}</strong>.<br>Click the button below to accept your invitation.<br><br>A temporary password has been generated and can be changed after your first login.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'first_name', 'description' => 'Primeiro nome do usuário', 'example' => 'Ana'],
                    ['key' => 'last_name', 'description' => 'Sobrenome do usuário', 'example' => 'Silva'],
                    ['key' => 'system_name', 'description' => 'Nome da plataforma', 'example' => 'Meanify'],
                ],
            ],
            [
                'key'          => 'in_app_only_1',
                'channels'     => ['in_app'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => null,
                        'title'         => null,
                        'body'          => null,
                        'short_message' => 'Você recebeu uma nova tarefa!',
                    ],
                    'en_US' => [
                        'subject'       => null,
                        'title'         => null,
                        'body'          => null,
                        'short_message' => 'You received a new task!',
                    ],
                ],
                'variables' => [],
            ],
            [
                'key'          => 'combined_notification',
                'channels'     => ['email', 'in_app'],
                'translations' => [
                    'pt_BR' => [
                        'subject'       => 'Você foi mencionado em um comentário',
                        'title'         => 'Você foi mencionado em um comentário',
                        'body'          => 'Olá {{ user_name }}, você foi mencionado em um comentário no projeto "{{ project }}".',
                        'short_message' => 'Você foi mencionado em um comentário.',
                    ],
                    'en_US' => [
                        'subject'       => 'You were mentioned in a comment',
                        'title'         => 'You were mentioned in a comment',
                        'body'          => 'Hi {{ user_name }}, you were mentioned in a comment in the project "{{ project }}".',
                        'short_message' => 'You were mentioned in a comment.',
                    ],
                ],
                'variables' => [
                    ['key' => 'user_name', 'description' => 'Nome do usuário', 'example' => 'Maria'],
                    ['key' => 'project', 'description' => 'Nome do projeto', 'example' => 'Projeto Alpha'],
                ],
            ],
        ];

        foreach ($templates as $data)
        {
            $templateId = DB::table('notifications_templates')->insertGetId(
                [
                    'key'                => $data['key'],
                    'email_layout_id'    => $email_layout_id,
                    'available_channels' => json_encode($data['channels']),
                    'active'             => true,
                    'updated_at'         => now(),
                    'created_at'         => now(),
                ]
            );

            foreach ($data['translations'] as $locale => $content)
            {
                DB::table('notifications_templates_translations')->insert(
                    [
                        'notification_template_id' => $templateId,
                        'locale'                   => $locale,
                        'subject'                  => $content['subject'],
                        'title'                    => $content['title'],
                        'body'                     => $content['body'],
                        'short_message'            => $content['short_message'],
                        'updated_at'               => now(),
                        'created_at'               => now(),
                    ]
                );
            }

            foreach ($data['variables'] as $var)
            {
                DB::table('notifications_templates_variables')->insert(
                    [
                        'notification_template_id' => $templateId,
                        'key'                      => $var['key'],
                        'description'              => $var['description'],
                        'example'                  => $var['example'],
                        'updated_at'               => now(),
                        'created_at'               => now(),
                    ]
                );
            }
        }
    }
}
