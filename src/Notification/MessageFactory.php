<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

final class MessageFactory
{
    /**
     * @param array{
     *   html?:bool,
     *   from?:array{0:string,1:string|null},
     *   attachments?:array<mixed>,
     *   inline?:array<mixed>
     * } $opts
     */
    public function email(string $subject, string $content, string $to, array $opts = []): Email
    {
        $email = new Email();
        $email = $email->subject($subject)
            ->to($to);

        if (isset($opts['from'])) {
            $from = $opts['from'];
            $email = $email->from(new Address($from[0], (string)($from[1] ?? '')));
        }

        $isHtml = $opts['html'] ?? true;
        if ($isHtml) {
            $email = $email->html($content);
        } else {
            $email = $email->text($content);
        }

        // Attachments (auto-detection)
        /** @var list<mixed> $attachments */
        $attachments = is_array($opts['attachments'] ?? null) ? $opts['attachments'] : [];
        if ($attachments !== []) {
            foreach ($attachments as $item) {
                $norm = $this->normalizeAttachment($item, false);
                if ($norm === null) {
                    continue;
                }
                if ($norm['mode'] === 'path') {
                    $email->attachFromPath($norm['path'], $norm['name'], $norm['mime']);
                } else {
                    $email->attach($norm['content'], $norm['name'], $norm['mime']);
                }
            }
        }

        // Inline images/files (CID auto)
        /** @var list<mixed> $inline */
        $inline = is_array($opts['inline'] ?? null) ? $opts['inline'] : [];
        if ($inline !== []) {
            foreach ($inline as $item) {
                $norm = $this->normalizeAttachment($item, true);
                if ($norm === null) {
                    continue;
                }
                $cid = $norm['cid'] ?? null;
                if ($norm['mode'] === 'path') {
                    $email->embedFromPath($norm['path'], $cid, $norm['mime']);
                } else {
                    $email->embed($norm['content'], $cid ?? $norm['name'], $norm['mime']);
                }
            }
        }

        return $email;
    }

    /**
     * Normalize an attachment or inline item.
     * @return array|null {mode: 'path'|'bin', path?:string, content?:string, name:string, mime:string, cid?:string, icon:string}
     */
    private function normalizeAttachment(mixed $item, bool $inline): ?array
    {
        $name = null;
        $mime = null;
        $cid  = null;
        $icon = 'file';
        $mode = null;
        $path = null;
        $content = null;

        // string path
        if (is_string($item)) {
            $path = $item;
            $mode = 'path';
            $name = basename($path) ?: 'attachment.bin';
            $mime = $this->guessMimeFromPathOrName($path, $name);
            $icon = $this->guessIcon($mime, $name);
            if ($inline) {
                $cid = pathinfo($name, PATHINFO_FILENAME) ?: uniqid('cid_', true);
            }
            return ['mode' => $mode,'path' => $path,'name' => $name,'mime' => $mime,'cid' => $cid,'icon' => $icon];
        }

        if (is_array($item)) {
            if (!empty($item['path']) && is_string($item['path'])) {
                $path = $item['path'];
                $mode = 'path';
                $name = $item['name'] ?? (basename($path) ?: 'attachment.bin');
                $mime = $item['mime'] ?? $this->guessMimeFromPathOrName($path, $name);
            } elseif (array_key_exists('bin', $item) || array_key_exists('content_base64', $item)) {
                $raw = $item['bin'] ?? $item['content_base64'] ?? '';
                if (is_resource($raw)) {
                    $content = stream_get_contents($raw) ?: '';
                } elseif (is_string($raw)) {
                    $decoded = base64_decode($raw, true);
                    $content = $decoded !== false ? $decoded : $raw;
                } else {
                    return null;
                }
                if ($content === '') {
                    return null;
                }
                $mode = 'bin';
                $name = $item['name'] ?? 'attachment.bin';
                $mime = $item['mime'] ?? $this->guessMimeFromName($name);
            } else {
                return null;
            }

            $cid = $item['cid'] ?? ($inline ? (pathinfo($name, PATHINFO_FILENAME) ?: uniqid('cid_', true)) : null);
            $icon = $this->guessIcon($mime, $name);

            if ($mode === 'path') {
                return ['mode' => 'path','path' => $path,'name' => $name,'mime' => $mime,'cid' => $cid,'icon' => $icon];
            }
            return ['mode' => 'bin','content' => $content,'name' => $name,'mime' => $mime,'cid' => $cid,'icon' => $icon];
        }

        return null;
    }

    private function guessMimeFromPathOrName(string $path, string $name): string
    {
        try {
            $mimeTypes = \Symfony\Component\Mime\MimeTypes::getDefault();
            $mime = $mimeTypes->guessMimeType($path);
            if ($mime) {
                return $mime;
            }
            return $this->guessMimeFromName($name);
        } catch (\Throwable) {
            return $this->guessMimeFromName($name);
        }
    }

    private function guessMimeFromName(string $name): string
    {
        try {
            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== '') {
                $mimeTypes = \Symfony\Component\Mime\MimeTypes::getDefault();
                $mimes = $mimeTypes->getMimeTypes($ext);
                if (!empty($mimes)) {
                    return $mimes[0];
                }
            }
        } catch (\Throwable) {
            // ignore
        }
        return 'application/octet-stream';
    }

    private function guessIcon(string $mime, string $name): string
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            $ext === 'pdf' || $mime === 'application/pdf' => 'pdf',
            in_array($ext, ['doc','docx','odt'], true) => 'doc',
            in_array($ext, ['xls','xlsx','ods','csv'], true) => 'xls',
            in_array($ext, ['ppt','pptx','odp'], true) => 'ppt',
            in_array($ext, ['zip','rar','7z','gz','bz2','tar'], true) => 'archive',
            in_array($ext, ['txt','md','rtf'], true) => 'text',
            default => 'file',
        };
    }

    public function sms(string $content, string $to): SmsMessage
    {
        return new SmsMessage($to, $content);
    }

    /**
     * @param array<string,mixed> $opts
     */
    public function chat(string $transport, string $content, ?string $subject = null, array $opts = []): ChatMessage
    {
        $message = new ChatMessage($content);
        $message->transport($transport);
        if ($subject !== null) {
            $message->subject($subject);
        }

        if ($transport === 'slack') {
            $options = new SlackOptions();
            if (isset($opts['channel'])) {
                // Map legacy 'channel' option to Slack recipient id (webhook id)
                $options = $options->recipient((string) $opts['channel']);
            }
            if (isset($opts['iconEmoji'])) {
                $options = $options->iconEmoji((string) $opts['iconEmoji']);
            }
            // 'blocks' option is ignored in this lightweight mapping to avoid depending on Slack block classes
            $message->options($options);
        } elseif ($transport === 'telegram') {
            $options = new TelegramOptions();
            if (isset($opts['chatId'])) {
                $options = $options->chatId((string) $opts['chatId']);
            }
            if (isset($opts['parseMode'])) {
                $options = $options->parseMode((string) $opts['parseMode']);
            }
            if (isset($opts['disableWebPagePreview'])) {
                $options = $options->disableWebPagePreview((bool) $opts['disableWebPagePreview']);
            }
            $message->options($options);
        }

        return $message;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function browser(string $topic, array $data): BrowserPayload
    {
        return new BrowserPayload($topic, $data);
    }

    /**
     * @param array<string,mixed> $data
     * @param array{endpoint:string,keys:array{p256dh:string,auth:string}} $subscription
     */
    public function push(array $subscription, array $data, ?int $ttl = null): WebPushMessage
    {
        $payloadJson = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            $payloadJson = '{}';
        }

        return new WebPushMessage(
            endpoint: $subscription['endpoint'],
            p256dh: $subscription['keys']['p256dh'],
            auth: $subscription['keys']['auth'],
            payloadJson: $payloadJson,
            ttl: $ttl,
        );
    }
}
