<?php

namespace Meanify\LaravelNotifications\Support;

class TemplateInterpolator
{
    /**
     * Interpolates a lightweight "fake blade" template against a data array.
     * Supports nested @isset, @if/@else/@endif (with ||, &&, !, count(), isset(), empty()) and @foreach blocks.
     */
    public static function render(string $text, array $data): string
    {
        // --- helpers -------------------------------------------------------------

        $truthy = function ($val): bool {
            if (is_array($val)) return count($val) > 0;
            if (is_object($val)) return true;
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
                $next_if   = strpos($text, $open_tag, $pos);
                $next_end  = strpos($text, $close_tag, $pos);
                $next_else = $else_tag ? strpos($text, $else_tag, $pos) : false;

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
                        return [
                            'block_start' => $open_pos + strlen($open_tag),
                            'block_end'   => $nearest_pos,
                            'after_end'   => $pos,
                            'else_pos'    => $else_pos,
                        ];
                    }
                }
            }

            return null; // Unbalanced -> treat as empty
        };

        // Process all @if blocks (supports @else)
        $process_ifs = function (string $text) use ($eval_expr, $extract_block): string {
            $open_tag  = '@if(';
            $close_tag = '@endif';
            $else_tag  = '@else';

            $offset = 0;
            while (($start = strpos($text, $open_tag, $offset)) !== false) {
                $expr_start = $start + strlen($open_tag);
                $p = $expr_start; $depth = 1; $len = strlen($text);
                while ($p < $len && $depth > 0) {
                    if ($text[$p] === '(') $depth++;
                    elseif ($text[$p] === ')') $depth--;
                    $p++;
                }
                if ($depth !== 0) break;

                $expr_end = $p - 1;
                $expr = substr($text, $expr_start, $expr_end - $expr_start);

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

                $text = substr($text, 0, $start) . $replacement . substr($text, $after_end);
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
                $p = $start + strlen($open_tag);
                $len = strlen($text);
                $expr_buf = '';
                while ($p < $len && $text[$p] !== ')') {
                    $expr_buf .= $text[$p++];
                }
                if ($p >= $len || $text[$p] !== ')') break;
                $expr = trim($expr_buf);

                if (!preg_match('/^([a-zA-Z_][\w]*)\s+as\s+\$?([a-zA-Z_][\w]*)$/', $expr, $m)) {
                    $text = substr($text, 0, $start) . '' . substr($text, $p + 1);
                    $offset = 0;
                    continue;
                }
                $key      = $m[1];
                $item_var = $m[2];

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
                            $content = substr($text, $p + 1, $next_end - ($p + 1));

                            $out = '';
                            $iter = $data[$key] ?? [];
                            if (is_iterable($iter)) {
                                foreach ($iter as $item) {
                                    $row = $content;
                                    $arr = is_array($item) ? $item : (array)$item;
                                    foreach ($arr as $k => $v) {
                                        $row = str_replace(['{{ '.$item_var.'.'.$k.' }}', '{!! '.$item_var.'.'.$k.' !!}'], (string)$v, $row);
                                        $row = str_replace(['{{ $'.$item_var.'->'.$k.' }}', '{!! $'.$item_var.'->'.$k.' !!}'], (string)$v, $row);
                                    }
                                    $out .= $row;
                                }
                            }

                            $text = substr($text, 0, $start) . $out . substr($text, $pos);
                            $offset = 0;
                            continue 2;
                        }
                    }
                }

                $text = substr($text, 0, $start) . '' . substr($text, $p + 1);
                $offset = 0;
            }

            return $text;
        };

        // --- iterative passes until stabilization (support nesting) --------------

        $prev = null;
        while ($prev !== $text) {
            $prev = $text;
            $text = $process_ifs($text);
            $text = $process_foreach($text, $data);

            $text = preg_replace_callback('/@isset\(\s*([a-zA-Z_][\w]*)\s*\)(.*?)@endisset/s', function ($m) use ($data) {
                return array_key_exists($m[1], $data) ? $m[2] : '';
            }, $text);
        }

        // --- scalar replacements --------------------------------------------------

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace(['{{ '.$key.' }}', '{!! '.$key.' !!}'], (string)$value, $text);
            }
        }

        // --- cleanup --------------------------------------------------------------

        $text = preg_replace('/{{\s*[^}]+\s*}}/', '', $text);
        $text = preg_replace('/{!!\s*[^}]+\s*!!}/', '', $text);
        $text = preg_replace('/@(?:endif|else|if|endisset|isset|endforeach|foreach)\b(?:\s*\(.*?\))?/s', '', $text);

        return $text;
    }
}
