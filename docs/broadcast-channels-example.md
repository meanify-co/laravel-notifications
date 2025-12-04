# Exemplo de Uso - Canais de Broadcast Customizados

Este documento demonstra como usar canais de broadcast customizados para notificações in-app na biblioteca Meanify Laravel Notifications.

## Como usar

### Exemplo básico com canais simples

```php
use Meanify\LaravelNotifications\Support\NotificationBuilder;

// Enviar para canais específicos
NotificationBuilder::make('user-message', $user, 'pt_BR')
    ->with(['message' => 'Nova mensagem para você!'])
    ->toBroadcastChannels([
        'user.123',           // Canal do usuário específico
        'admin.dashboard',    // Canal do painel administrativo
        'team.developers'     // Canal da equipe de desenvolvedores
    ])
    ->send();
```

### Exemplo com ChannelBuilder automático

```php
use App\Models\User;
use App\Models\Account;

// Usar modelos para gerar canais obfuscados automaticamente
NotificationBuilder::make('account-update', $user, 'pt_BR')
    ->with(['account_name' => 'Minha Empresa'])
    ->toBroadcastChannels([
        ['model' => User::class, 'id' => 123],
        ['model' => Account::class, 'id' => 456],
    ])
    ->send();
```

### Exemplo com eventos customizados

```php
// Definir evento customizado para o broadcast
NotificationBuilder::make('urgent-alert', $user, 'pt_BR')
    ->with(['alert_message' => 'Sistema em manutenção'])
    ->toBroadcastChannels([
        [
            'channel' => 'system.alerts',
            'event' => 'urgent.maintenance'  // Evento customizado
        ]
    ])
    ->send();
```

### Exemplo avançado - Múltiplos canais com configurações diferentes

```php
// Combinar diferentes tipos de canais
NotificationBuilder::make('project-update', $user, 'pt_BR')
    ->with([
        'project_name' => 'Sistema de Vendas',
        'update_type' => 'deployment'
    ])
    ->toBroadcastChannels([
        // Canal simples
        'project.123',
        
        // Canal com modelo obfuscado
        ['model' => User::class, 'id' => $projectManager->id],
        
        // Canal com evento customizado
        [
            'channel' => 'deployments.production',
            'event' => 'deployment.completed'
        ],
        
        // Canal para equipe específica
        'team.backend-developers'
    ])
    ->send();
```

## Estrutura dos canais de broadcast

### 1. Canais simples (string)
```php
'user.123'           // Canal direto
'admin.dashboard'    // Canal administrativo
'team.developers'    // Canal de equipe
```

### 2. Canais com modelo obfuscado
```php
[
    'model' => User::class,    // Classe do modelo
    'id' => 123               // ID do modelo (será obfuscado automaticamente)
]
```

### 3. Canais com configurações avançadas
```php
[
    'channel' => 'system.alerts',      // Nome do canal
    'event' => 'urgent.maintenance'    // Evento customizado (opcional)
]
```

## Como escutar no frontend

### JavaScript com Laravel Echo

```javascript
// Canal simples
Echo.private('user.123')
    .listen('.in-app.notification', (e) => {
        console.log('Nova notificação:', e);
    });

// Canal com evento customizado
Echo.private('system.alerts')
    .listen('.urgent.maintenance', (e) => {
        console.log('Alerta urgente:', e);
        // Mostrar modal de manutenção
    });

// Canal obfuscado (gerado pelo ChannelBuilder)
Echo.private('mfy_channel_' + encodedChannelId)
    .listen('.in-app.notification', (e) => {
        console.log('Notificação obfuscada:', e);
    });
```

### Vue.js com composable

```javascript
// composables/useNotifications.js
import { ref, onMounted, onUnmounted } from 'vue'

export function useNotifications(channels = []) {
    const notifications = ref([])
    
    onMounted(() => {
        channels.forEach(channel => {
            const channelName = typeof channel === 'string' 
                ? channel 
                : channel.name
                
            const eventName = typeof channel === 'string' 
                ? '.in-app.notification' 
                : `.${channel.event || 'in-app.notification'}`
            
            Echo.private(channelName)
                .listen(eventName, (e) => {
                    notifications.value.unshift(e)
                })
        })
    })
    
    return { notifications }
}

// Uso no componente
const { notifications } = useNotifications([
    'user.123',
    { name: 'system.alerts', event: 'urgent.maintenance' }
])
```

## Comportamento padrão

Se nenhum canal customizado for definido com `toBroadcastChannels()`, o sistema usará o comportamento padrão:

- **Canal**: `user.{user_id}` (baseado no usuário da notificação)
- **Evento**: `in-app.notification`

## Segurança e autorização

### Canais privados
Todos os canais são tratados como `PrivateChannel` por padrão, exigindo autorização.

### Validação de canais obfuscados
Para canais gerados com `ChannelBuilder`, a validação é feita automaticamente através do `ObfuscatorBroadcastChannel`.

### Autorização customizada
Para canais simples, você deve definir a autorização no arquivo `routes/channels.php`:

```php
// routes/channels.php
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('team.{teamName}', function ($user, $teamName) {
    return $user->hasTeam($teamName);
});

Broadcast::channel('admin.{section}', function ($user, $section) {
    return $user->isAdmin() && $user->canAccess($section);
});
```

## Casos de uso comuns

### 1. Notificações de usuário específico
```php
->toBroadcastChannels(['user.' . $targetUser->id])
```

### 2. Notificações para equipe
```php
->toBroadcastChannels(['team.marketing', 'team.sales'])
```

### 3. Alertas do sistema
```php
->toBroadcastChannels([
    ['channel' => 'system.maintenance', 'event' => 'maintenance.scheduled']
])
```

### 4. Notificações de projeto
```php
->toBroadcastChannels([
    'project.' . $project->id,
    ['model' => User::class, 'id' => $project->owner_id]
])
```

### 5. Notificações administrativas
```php
->toBroadcastChannels(['admin.dashboard', 'admin.users', 'admin.reports'])
```
