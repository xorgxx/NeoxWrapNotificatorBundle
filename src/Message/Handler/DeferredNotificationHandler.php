<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Message\Handler;

use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Message\DeferredNotification;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

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
                $this->sender->sendEmail($email);
                break;
            case 'sms':
                $sms = $this->factory->sms((string)$p['content'], (string)$p['to']);
                $this->sender->sendSms($sms);
                break;
            case 'chat':
                $chat = $this->factory->chat((string)$p['transport'], (string)$p['content'], $p['subject'] !== null ? (string)$p['subject'] : null, (array)($p['opts'] ?? []));
                $this->sender->sendChat($chat);
                break;
            case 'browser':
                $payload = $this->factory->browser((string)$p['topic'], (array)($p['data'] ?? []));
                $this->sender->sendBrowser($payload);
                break;
            case 'push':
                $subscription = (array)($p['subscription'] ?? []);
                $data = (array)($p['data'] ?? []);
                $ttl = isset($p['ttl']) ? (int)$p['ttl'] : null;
                $push = $this->factory->push($subscription, $data, $ttl);
                $this->sender->sendPush($push);
                break;
            case 'desktop':
                $subscription = (array)($p['subscription'] ?? []);
                $data = (array)($p['data'] ?? []);
                $ttl = isset($p['ttl']) ? (int)$p['ttl'] : null;
                $push = $this->factory->push($subscription, $data, $ttl);
                $this->sender->sendPush($push);
                break;
            default:
                // unknown channel -> ignore
                break;
        }
    }
}
