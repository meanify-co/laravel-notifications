<?php

namespace Meanify\LaravelNotifications\Console\Commands;

use Illuminate\Console\Command;
use Meanify\LaravelNotifications\Support\NotificationBuilder;
use App\Models\User;

class TestBroadcastChannelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meanify:test-broadcast-channels
                            {--user-id= : ID do usuário para teste}
                            {--channels=* : Canais de broadcast para teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testar canais de broadcast customizados para notificações in-app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $customChannels = $this->option('channels');

        $this->info("Testando canais de broadcast customizados...");

        // Buscar usuário se fornecido
        $user = null;
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Usuário com ID {$userId} não encontrado.");
                return 1;
            }
            $this->info("Usuário: {$user->name} (ID: {$user->id})");
        }

        // Definir canais de teste
        $testChannels = [];
        
        if (!empty($customChannels)) {
            $testChannels = $customChannels;
            $this->info("Canais customizados: " . implode(', ', $customChannels));
        } else {
            // Canais de exemplo
            $testChannels = [
                'test.channel.1',
                'admin.dashboard',
                'team.developers'
            ];
            
            if ($user) {
                $testChannels[] = ['model' => User::class, 'id' => $user->id];
                $testChannels[] = [
                    'channel' => 'user.notifications.' . $user->id,
                    'event' => 'custom.test.event'
                ];
            }
            
            $this->info("Usando canais de exemplo:");
            foreach ($testChannels as $channel) {
                if (is_string($channel)) {
                    $this->line("  - Canal simples: {$channel}");
                } elseif (is_array($channel)) {
                    if (isset($channel['model'])) {
                        $this->line("  - Canal com modelo: {$channel['model']} (ID: {$channel['id']})");
                    } elseif (isset($channel['channel'])) {
                        $event = $channel['event'] ?? 'in-app.notification';
                        $this->line("  - Canal avançado: {$channel['channel']} (Evento: {$event})");
                    }
                }
            }
        }

        try {
            // Enviar notificação de teste
            $result = NotificationBuilder::make('test-template', $user, 'pt_BR')
                ->with([
                    'test_message' => 'Esta é uma notificação de teste para canais customizados',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'channels_count' => count($testChannels)
                ])
                ->toBroadcastChannels($testChannels)
                ->send();

            if ($result) {
                $this->info("✅ Notificação enviada com sucesso!");
                $this->info("📡 Broadcast realizado para " . count($testChannels) . " canal(is)");
                
                $this->newLine();
                $this->comment("Para escutar as notificações no frontend:");
                
                foreach ($testChannels as $channel) {
                    if (is_string($channel)) {
                        $this->line("Echo.private('{$channel}').listen('.in-app.notification', callback);");
                    } elseif (is_array($channel) && isset($channel['channel'])) {
                        $event = $channel['event'] ?? 'in-app.notification';
                        $this->line("Echo.private('{$channel['channel']}').listen('.{$event}', callback);");
                    }
                }
                
            } else {
                $this->error("❌ Falha ao enviar notificação.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Erro ao enviar notificação: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
