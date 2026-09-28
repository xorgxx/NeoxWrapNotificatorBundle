<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Message\Handler;

use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Message\DeferredNotification;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

#[AsMessageHandler]
final class DeferredNotificationHandler
{
    public function __construct(
        private readonly MessageFactory $factory,
        private readonly SenderInterface $sender,
    ) {
    }

    public function __invoke(DeferredNotification $msg): void
    {
        $channel = $msg->channel;
        $p = $msg->payload;
        switch ($channel) {
            case 'email':
                $opts = (array)($p['opts'] ?? []);
                $opts['html'] = (bool)($p['isHtml'] ?? ($opts['html'] ?? true));
                $email = $this->factory->email((string)$p['subject'], (string)$p['content'], (string)$p['to'], $opts);
                $this->ensureDelivered($this->sender->sendEmail($email));
                break;
            case 'sms':
                $sms = $this->factory->sms((string)$p['content'], (string)$p['to']);
                $this->ensureDelivered($this->sender->sendSms($sms));
                break;
            case 'chat':
                $rawContent = $p['content'] ?? '';
                if (!is_string($rawContent)) {
                    $json = json_encode($rawContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $rawContent = $json !== false ? $json : '';
                }
                $chat = $this->factory->chat((string)$p['transport'], $rawContent, $p['subject'] !== null ? (string)$p['subject'] : null, (array)($p['opts'] ?? []));
                $this->ensureDelivered($this->sender->sendChat($chat));
                break;
            case 'browser':
                $payload = $this->factory->browser((string)$p['topic'], (array)($p['data'] ?? []));
                $this->ensureDelivered($this->sender->sendBrowser($payload));
                break;
            case 'push':
                /** @var array{endpoint: string, keys: array{p256dh: string, auth: string}} $subscription */
                $subscription = (array)($p['subscription'] ?? []);
                $data = (array)($p['data'] ?? []);
                $ttl = isset($p['ttl']) ? (int)$p['ttl'] : null;
                $push = $this->factory->push($subscription, $data, $ttl);
                $this->ensureDelivered($this->sender->sendPush($push));
                break;
            case 'desktop':
                /** @var array{endpoint: string, keys: array{p256dh: string, auth: string}} $subscription */
                $subscription = (array)($p['subscription'] ?? []);
                $data = (array)($p['data'] ?? []);
                $ttl = isset($p['ttl']) ? (int)$p['ttl'] : null;
                $push = $this->factory->push($subscription, $data, $ttl);
                $this->ensureDelivered($this->sender->sendPush($push));
                break;
            default:
                // unknown channel -> ignore
                break;
        }
    }
    private function ensureDelivered(DeliveryStatus $status): void
    {
        if ($status->status !== DeliveryStatus::STATUS_FAILED) {
            return;
        }

        throw new RecoverableMessageHandlingException(
            $status->message ?? sprintf('Deferred %s notification failed', $status->channel)
        );
    }
}
