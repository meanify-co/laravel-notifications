<?php

namespace Meanify\LaravelNotifications\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Meanify\LaravelNotifications\Jobs\DispatchNotificationJob;
use Meanify\LaravelNotifications\Jobs\SendNotificationEmailJob;

class NotificationDispatcher
{
    /**
     * @param string $notificationTemplateKey
     * @param ?object $user
     * @param string $locale
     * @param int|null $accountId
     * @param int|null $applicationId
     * @param int|null $sessionId
     * @param string $mailDriverType
     * @param array $mailDriverConfigs
     * @param array $recipients
     * @param array $dynamicData
     * @param Carbon|null $scheduledTo
     * @param bool $sendEmailImmediately If "true", notification will not dispatch (the email will send at moment)
     * @param array $attachments Array of email attachments
     * @return bool
     */
    public function dispatch(
        string $notificationTemplateKey,
        ?object $user,
        string $locale,
        ?int $accountId = null,
        ?int $applicationId = null,
        ?int $sessionId = null,
        string $mailDriverType = 'smtp',
        array $mailDriverConfigs = [],
        array $recipients = [],
        array $dynamicData = [],
        ?Carbon $scheduledTo = null,
        bool $sendEmailImmediately = false,
        array $attachments = [],
    ): bool {

        $dispatched = true;

        try
        {
            $template = NotificationTemplate::where('key', $notificationTemplateKey)
                ->where('active', true)
                ->with('translations', 'variables', 'layout')
                ->firstOrFail();

            foreach ($template->available_channels as $channel)
            {
                DB::beginTransaction();

                try
                {
                    $translation = $template->translations->where('locale', $locale)->first();

                    $payload = [
                        'dynamic_data'  => $dynamicData,
                        'short_message' => $this->interpolate($translation->short_message ?? '', $dynamicData),
                        'subject'       => $this->interpolate($translation->subject ?? '', $dynamicData),
                        'title'         => $this->interpolate($translation->title ?? '', $dynamicData),
                        'body'          => $this->interpolate($translation->body ?? '', $dynamicData),
                    ];

                    if (!empty($recipients))
                    {
                        $payload['__recipients'] = $recipients;
                    }

                    if (isset($mailDriverType) and !empty($mailDriverConfigs))
                    {
                        $payload['__mail'] = [
                            'driver'  => $mailDriverType,
                            'configs' => $mailDriverConfigs,
                        ];
                    }

                    if (!empty($attachments))
                    {
                        $payload['__attachments'] = $attachments;
                    }
                    
                    $notification = Notification::create([
                        'notification_template_id' => $template->id,
                        'user_id'                  => $user?->id,
                        'application_id'           => $applicationId,
                        'session_id'               => $sessionId,
                        'account_id'               => $accountId,
                        'channel'                  => $channel,
                        'payload'                  => $payload,
                        'status'                   => Notification::NOTIFICATION_STATUS_PENDING,
                    ]);

                    DB::commit();

                    if(!$sendEmailImmediately)
                    {
                        DispatchNotificationJob::dispatch($notification)
                            ->onQueue(config('meanify-laravel-notifications.default_queue_name', 'meanify_queue_notification'))
                            ->delay($scheduledTo ?? now()->addSeconds(1));
                    }
                    else
                    {
                        $dispatched = SendNotificationEmailJob::execute($notification);
                    }

                }
                catch (\Throwable $e2)
                {
                    DB::rollBack();

                    Log::error('Notification dispatch failed', [
                        'template' => $notificationTemplateKey,
                        'user_id' => $user ?? null,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }
        catch(\Exception $e1)
        {
            $dispatched = false;

            Log::error('Notification dispatch failed', [
                'template' => $notificationTemplateKey,
                'user' => $user ?? null,
                'error' => $e1->getMessage(),
            ]);
        }

        return $dispatched;
    }


    /**
     * Interpolates a lightweight "fake blade" template against a data array.
     * Supports nested @isset, @if/@else/@endif (with ||, &&, !, count(), isset(), empty()) and @foreach blocks.
     */
    protected function interpolate(string $text, array $data): string
    {
        // --- helpers -------------------------------------------------------------

        $truthy = function ($val): bool {
            if (is_array($val)) return count($val) > 0;
            if (is_object($val)) return true; // treat any object as truthy
            return !empty($val);
        };

        $has_key = function (array $data, string $key): bool {
            return array_key_exists($key, $data);
        };

        $get_key = function (array $data, string $key) {
            return $data[$key] ?? null;
        };

        // Evaluate atomic boolean: isset(x), !isset(x), empty(x), !empty(x), count(x) op n, key, !key
        $eval_atom = function (string $atom) use ($data, $truthy, $has_key, $get_key): bool {
            $a = trim($atom);

            // Unary NOT
            $neg = false;
            while (strlen($a) && $a[0] === '!') {
                $neg = !$neg;
                $a = ltrim(substr($a, 1));
            }

            // count(key) <op> number
            if (preg_match('/^count\(\s*([a-zA-Z_][\w]*)\s*\)\s*(==|!=|<=|>=|<|>)\s*(\d+)\s*$/', $a, $m)) {
                [$all, $key, $op, $num] = $m;
                $cnt = ($has_key($data, $key) && is_countable($data[$key])) ? count($data[$key]) : 0;
                $res = match ($op) {
                    '==' => $cnt == (int)$num,
                    '!=' => $cnt != (int)$num,
                    '<'  => $cnt <  (int)$num,
                    '<=' => $cnt <= (int)$num,
                    '>'  => $cnt >  (int)$num,
                    '>=' => $cnt >= (int)$num,
                };
                return $neg ? !$res : $res;
            }

            // isset(key)
            if (preg_match('/^isset\(\s*([a-zA-Z_][\w]*)\s*\)$/', $a, $m)) {
                $res = $has_key($data, $m[1]);
                return $neg ? !$res : $res;
            }

            // empty(key)
            if (preg_match('/^empty\(\s*([a-zA-Z_][\w]*)\s*\)$/', $a, $m)) {
                $val = $get_key($data, $m[1]);
                $res = empty($val);
                return $neg ? !$res : $res;
            }

            // bare variable: key
            if (preg_match('/^([a-zA-Z_][\w]*)$/', $a, $m)) {
                $val = $get_key($data, $m[1]);
                $res = $truthy($val);
                return $neg ? !$res : $res;
            }

            // Unsupported atom -> false (fail-safe)
            return false;
        };

        // Evaluate boolean expression with parentheses, &&, ||
        $eval_expr = function (string $expr) use (&$eval_expr, $eval_atom): bool {
            $e = trim($expr);

            // Resolve parenthesis from inside out
            while (preg_match('/\(([^\(\)]*)\)/', $e, $pm)) {
                $inner = $pm[1];
                $val   = $eval_expr($inner) ? '1' : '0';
                $e = preg_replace('/\(([^\(\)]*)\)/', $val, $e, 1);
            }

            // Normalize spaces
            $e = preg_replace('/\s+/', ' ', trim($e));

            // Split by || (lowest precedence)
            $or_parts = preg_split('/\s*\|\|\s*/', $e);
            $or_acc = false;
            foreach ($or_parts as $or_part) {
                // Split by && (higher precedence)
                $and_parts = preg_split('/\s*&&\s*/', $or_part);
                $and_acc = true;
                foreach ($and_parts as $and_part) {
                    $and_acc = $and_acc && $eval_atom($and_part);
                    if (!$and_acc) break;
                }
                $or_acc = $or_acc || $and_acc;
                if ($or_acc) break;
            }
            return $or_acc;
        };

        // Extract a balanced block starting at $open_pos for a directive with $open_tag/$close_tag.
        // Optionally returns position of a top-level $else_tag inside.
        $extract_block = function (string $text, int $open_pos, string $open_tag, string $close_tag, ?string $else_tag = null) {
            $len = strlen($text);
            $pos = $open_pos + strlen($open_tag);
            $depth = 1;
            $else_pos = null;

            while ($pos < $len) {
                // Find next interesting token
                $next_if   = strpos($text, $open_tag, $pos);
                $next_end  = strpos($text, $close_tag, $pos);
                $next_else = $else_tag ? strpos($text, $else_tag, $pos) : false;

                // pick nearest token among existing ones
                $candidates = [];
                if ($next_if   !== false) $candidates[$next_if]   = 'open';
                if ($next_end  !== false) $candidates[$next_end]  = 'close';
                if ($else_tag && $next_else !== false) $candidates[$next_else] = 'else';

                if (!$candidates) break;

                ksort($candidates);
                $nearest_pos = array_key_first($candidates);
                $type = $candidates[$nearest_pos];

                if ($type === 'open') {
                    $depth++;
                    $pos = $nearest_pos + strlen($open_tag);
                } elseif ($type === 'else') {
                    if ($depth === 1 && $else_pos === null) {
                        $else_pos = $nearest_pos;
                    }
                    $pos = $nearest_pos + strlen($else_tag);
                } else { // close
                    $depth--;
                    $pos = $nearest_pos + strlen($close_tag);
                    if ($depth === 0) {
                        $block_start = $open_pos + strlen($open_tag);
                        $block_end   = $nearest_pos;
                        return [
                            'block_start' => $block_start,
                            'block_end'   => $block_end,
                            'after_end'   => $pos,
                            'else_pos'    => $else_pos
                        ];
                    }
                }
            }

            // Unbalanced -> treat as empty
            return null;
        };

        // Process all @if blocks (supports @else)
        $process_ifs = function (string $text) use ($eval_expr, $extract_block): string {
            $open_tag  = '@if(';
            $close_tag = '@endif';
            $else_tag  = '@else';

            $offset = 0;
            while (($start = strpos($text, $open_tag, $offset)) !== false) {
                // Extract expression inside @if(...)
                $expr_start = $start + strlen($open_tag);
                $p = $expr_start; $depth = 1; $len = strlen($text);
                while ($p < $len && $depth > 0) {
                    if ($text[$p] === '(') $depth++;
                    elseif ($text[$p] === ')') $depth--;
                    $p++;
                }
                if ($depth !== 0) break; // malformed

                $expr_end = $p - 1; // index of ')'
                $expr = substr($text, $expr_start, $expr_end - $expr_start);

                // Find block boundaries
                $block_info = $extract_block($text, $start, '@if', $close_tag, $else_tag);
                if (!$block_info) { $offset = $expr_end; continue; }

                $block_start = $block_info['block_start'];
                $block_end   = $block_info['block_end'];
                $after_end   = $block_info['after_end'];
                $else_pos    = $block_info['else_pos'];

                $then_content = $else_pos
                    ? substr($text, $block_start, $else_pos - $block_start)
                    : substr($text, $block_start, $block_end - $block_start);

                $else_content = $else_pos
                    ? substr($text, $else_pos + strlen($else_tag), $block_end - ($else_pos + strlen($else_tag)))
                    : '';

                $replacement = $eval_expr($expr) ? $then_content : $else_content;

                // Replace whole "@if(...) ... @endif"
                $text = substr($text, 0, $start) . $replacement . substr($text, $after_end);
                // Restart scan from beginning to handle nested results
                $offset = 0;
            }
            return $text;
        };

        // Process all @isset blocks
        $process_isset = function (string $text) use ($has_key, $extract_block): string {
            $open_tag  = '@isset(';
            $close_tag = '@endisset';

            $offset = 0;
            while (($start = strpos($text, $open_tag, $offset)) !== false) {
                // Extract key inside @isset(key)
                $p = $start + strlen($open_tag);
                $len = strlen($text);
                $key_buf = '';
                while ($p < $len && $text[$p] !== ')') {
                    $key_buf .= $text[$p++];
                }
                if ($p >= $len || $text[$p] !== ')') break;
                $key = trim($key_buf);

                $block_info = $extract_block($text, $start, '@isset', $close_tag, null);
                if (!$block_info) { $offset = $p + 1; continue; }

                $content = substr($text, $block_info['block_start'], $block_info['block_end'] - $block_info['block_start']);
                $replacement = $has_key($GLOBALS['__interp_data__'] ?? [], $key) ? $content : ''; // will reset below
                // We cannot access $data here, so quick hack: use global shadow replaced below
                $replacement = array_key_exists($key, $GLOBALS['__interp_data__']) ? $content : '';

                $text = substr($text, 0, $start) . $replacement . substr($text, $block_info['after_end']);
                $offset = 0;
            }
            return $text;
        };

        // Process all @foreach blocks
        $process_foreach = function (string $text, array $data): string {
            $open_tag  = '@foreach(';
            $close_tag = '@endforeach';

            $offset = 0;
            while (($start = strpos($text, $open_tag, $offset)) !== false) {
                // Extract "key as item"
                $p = $start + strlen($open_tag);
                $len = strlen($text);
                $expr_buf = '';
                while ($p < $len && $text[$p] !== ')') {
                    $expr_buf .= $text[$p++];
                }
                if ($p >= $len || $text[$p] !== ')') break;
                $expr = trim($expr_buf);

                if (!preg_match('/^([a-zA-Z_][\w]*)\s+as\s+\$?([a-zA-Z_][\w]*)$/', $expr, $m)) {
                    // malformed -> drop
                    $text = substr($text, 0, $start) . '' . substr($text, $p + 1);
                    $offset = 0;
                    continue;
                }
                $key = $m[1];
                $item_var = $m[2];

                // Find block content
                $depth = 1; $pos = $p + 1;
                while ($pos < $len) {
                    $next_open = strpos($text, '@foreach(', $pos);
                    $next_end  = strpos($text, $close_tag, $pos);
                    if ($next_end === false) break;
                    if ($next_open !== false && $next_open < $next_end) {
                        $depth++; $pos = $next_open + strlen($open_tag);
                    } else {
                        $depth--; $pos = $next_end + strlen($close_tag);
                        if ($depth === 0) {
                            $block_start = $p + 1;
                            $block_end   = $next_end;
                            $content = substr($text, $block_start, $block_end - $block_start);

                            $out = '';
                            $iter = $data[$key] ?? [];
                            if (is_iterable($iter)) {
                                foreach ($iter as $item) {
                                    $row = $content;
                                    $arr = is_object($item) ? (array)$item : (array)$item;
                                    foreach ($arr as $k => $v) {
                                        $row = str_replace(['{{ '.$item_var.'.'.$k.' }}', '{!! '.$item_var.'.'.$k.' !!}'], (string)$v, $row);
                                        $row = str_replace(['{{ $'.$item_var.'->'.$k.' }}', '{!! $'.$item_var.'->'.$k.' !!}'], (string)$v, $row);
                                    }
                                    $out .= $row;
                                }
                            }

                            $text = substr($text, 0, $start) . $out . substr($text, $pos);
                            $offset = 0;
                            continue 2; // restart outer while
                        }
                    }
                }

                // Unbalanced -> remove
                $text = substr($text, 0, $start) . '' . substr($text, $p + 1);
                $offset = 0;
            }

            return $text;
        };

        // Shadow data for isset processor (closure scope workaround)
        $GLOBALS['__interp_data__'] = $data;

        // --- iterative passes until stabilization (support nesting) --------------

        $prev = null;
        while ($prev !== $text) {
            $prev = $text;
            $text = $process_ifs($text);
            $text = $process_foreach($text, $data);

            // Rebuild isset using current data
            $text = preg_replace_callback('/@isset\(\s*([a-zA-Z_][\w]*)\s*\)(.*?)@endisset/s', function ($m) use ($data) {
                return array_key_exists($m[1], $data) ? $m[2] : '';
            }, $text);
        }

        unset($GLOBALS['__interp_data__']);

        // --- scalar replacements --------------------------------------------------

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace(['{{ '.$key.' }}', '{!! '.$key.' !!}'], (string)$value, $text);
            }
        }

        // --- cleanup --------------------------------------------------------------

        // Remove unresolved variables
        $text = preg_replace('/{{\s*[^}]+\s*}}/', '', $text);
        $text = preg_replace('/{!!\s*[^}]+\s*!!}/', '', $text);

        // Safety net: strip any leftover directives
        $text = preg_replace('/@(?:endif|else|if|endisset|isset|endforeach|foreach)\b(?:\s*\(.*?\))?/s', '', $text);

        return $text;
    }
}

