<?php

namespace Meanify\LaravelNotifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'sign_in_code',
                'channels' => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Código de verificação de acesso',
                        'body' => 'Seu código de verificação é: <strong>{{ code }}</strong>',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'Sign-in verification code',
                        'body' => 'Your verification code is: <strong>{{ code }}</strong>',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'code', 'description' => 'Código de verificação', 'example' => '123456'],
                ],
            ],
            [
                'key' => 'forgot_password_code',
                'channels' => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Código para redefinir sua senha',
                        'body' => 'Utilize o código <strong>{{ code }}</strong> para redefinir sua senha.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'Password reset code',
                        'body' => 'Use the code <strong>{{ code }}</strong> to reset your password.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'code', 'description' => 'Código de redefinição', 'example' => '789123'],
                ],
            ],
            [
                'key' => 'new_login_detected',
                'channels' => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Novo login detectado',
                        'body' => 'Um novo login foi detectado em sua conta. IP: {{ ip }} - Navegador: {{ browser }}',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'New login detected',
                        'body' => 'A new login was detected on your account. IP: {{ ip }} - Browser: {{ browser }}',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'ip', 'description' => 'Endereço IP', 'example' => '192.168.0.1'],
                    ['key' => 'browser', 'description' => 'Navegador ou dispositivo', 'example' => 'Chrome no Windows'],
                ],
            ],
            [
                'key' => 'email_change_verification',
                'channels' => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Verificação de alteração de e-mail',
                        'body' => 'Use o código <strong>{{ code }}</strong> para confirmar a alteração do seu e-mail.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'Email change verification',
                        'body' => 'Use the code <strong>{{ code }}</strong> to confirm your email change.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [
                    ['key' => 'code', 'description' => 'Código de verificação', 'example' => '445566'],
                ],
            ],
            [
                'key' => 'password_changed_notice',
                'channels' => ['email'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Sua senha foi alterada',
                        'body' => 'Este é um aviso de que sua senha foi alterada com sucesso. Se não foi você, entre em contato conosco imediatamente.',
                        'short_message' => null,
                    ],
                    'en_US' => [
                        'subject' => 'Your password has been changed',
                        'body' => 'This is a notice that your password has been successfully changed. If it wasn’t you, contact us immediately.',
                        'short_message' => null,
                    ],
                ],
                'variables' => [],
            ],
            [
                'key' => 'in_app_only_1',
                'channels' => ['in_app'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => null,
                        'body' => null,
                        'short_message' => 'Você recebeu uma nova tarefa!',
                    ],
                    'en_US' => [
                        'subject' => null,
                        'body' => null,
                        'short_message' => 'You received a new task!',
                    ],
                ],
                'variables' => [],
            ],
            [
                'key' => 'combined_notification',
                'channels' => ['email', 'in_app'],
                'translations' => [
                    'pt_BR' => [
                        'subject' => 'Você foi mencionado em um comentário',
                        'body' => 'Olá {{ user_name }}, você foi mencionado em um comentário no projeto "{{ project }}".',
                        'short_message' => 'Você foi mencionado em um comentário.',
                    ],
                    'en_US' => [
                        'subject' => 'You were mentioned in a comment',
                        'body' => 'Hi {{ user_name }}, you were mentioned in a comment in the project "{{ project }}".',
                        'short_message' => 'You were mentioned in a comment.',
                    ],
                ],
                'variables' => [
                    ['key' => 'user_name', 'description' => 'Nome do usuário', 'example' => 'Maria'],
                    ['key' => 'project', 'description' => 'Nome do projeto', 'example' => 'Projeto Alpha'],
                ],
            ],
        ];

        foreach ($templates as $data) {
            $templateId = DB::table('notifications_templates')->insertGetId([
                'key' => $data['key'],
                'available_channels' => json_encode($data['channels']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['translations'] as $locale => $content) {
                DB::table('notifications_templates_translations')->insert([
                    'notification_template_id' => $templateId,
                    'locale' => $locale,
                    'subject' => $content['subject'],
                    'body' => $content['body'],
                    'short_message' => $content['short_message'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($data['variables'] as $var) {
                DB::table('notifications_templates_variables')->insert([
                    'notification_template_id' => $templateId,
                    'key' => $var['key'],
                    'description' => $var['description'],
                    'example' => $var['example'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
