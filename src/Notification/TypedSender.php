<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface as HttpTransportExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface as NotifierTransportExceptionInterface;

final class TypedSender implements \Neox\WrapNotificatorBundle\Contract\SenderInterface
{
    public function __construct(
        private readonly ?MailerInterface $mailer = null,
        private readonly ?ChatterInterface $chatter = null,
        private readonly ?TexterInterface $texter = null,
        private readonly ?HubInterface $hub = null,
        private readonly ?WebPush $webPush = null,
    ) {
    }

    public function sendEmail(Email $email): DeliveryStatus
    {
        try {
            if ($this->mailer === null) {
                return DeliveryStatus::failed('email', 'Mailer service not available');
            }
            $this->mailer->send($email);
            return DeliveryStatus::sent('email');
        } catch (TransportExceptionInterface $e) {
            return DeliveryStatus::failed('email', $e->getMessage());
        } catch (\Throwable $e) {
            return DeliveryStatus::failed('email', $e->getMessage());
        }
    }

    public function sendSms(SmsMessage $sms): DeliveryStatus
    {
        try {
            if ($this->texter === null) {
                return DeliveryStatus::failed('sms', 'Texter service not available');
            }
            $this->texter->send($sms);
            return DeliveryStatus::sent('sms');
        } catch (NotifierTransportExceptionInterface $e) {
            return DeliveryStatus::failed('sms', $e->getMessage());
        } catch (\Throwable $e) {
            return DeliveryStatus::failed('sms', $e->getMessage());
        }
    }

    public function sendChat(ChatMessage $chat): DeliveryStatus
    {
        try {
            if ($this->chatter === null) {
                return DeliveryStatus::failed('chat', 'Chatter service not available');
            }
            $this->chatter->send($chat);
            return DeliveryStatus::sent('chat');
        } catch (NotifierTransportExceptionInterface $e) {
            return DeliveryStatus::failed('chat', $e->getMessage());
        } catch (\Throwable $e) {
            return DeliveryStatus::failed('chat', $e->getMessage());
        }
    }

    public function sendBrowser(BrowserPayload $payload): DeliveryStatus
    {
        try {
            if ($this->hub === null) {
                return DeliveryStatus::failed('browser', 'Mercure hub not available');
            }
            $json = json_encode($payload->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $json = '{}';
            }
            $update = new Update(
                topics: $payload->topic,
                data: $json,
            );
            $id = $this->hub->publish($update);
            $idStr = (string) $id;
            return DeliveryStatus::sent('browser', $idStr);
        } catch (HttpTransportExceptionInterface $e) {
            return DeliveryStatus::failed('browser', $e->getMessage());
        } catch (\Throwable $e) {
            return DeliveryStatus::failed('browser', $e->getMessage());
        }
    }

    public function sendPush(WebPushMessage $push): DeliveryStatus
    {
        try {
            if ($this->webPush === null) {
                return DeliveryStatus::failed('push', 'WebPush service not available');
            }
            $subscription = Subscription::create([
                'endpoint' => $push->endpoint,
                'keys' => [
                    'p256dh' => $push->p256dh,
                    'auth' => $push->auth,
                ],
            ]);

            $report = $this->webPush->sendOneNotification($subscription, $push->payloadJson, [
                'TTL' => $push->ttl,
            ]);

            if ($report->isSuccess()) {
                // There is no id, but we can use location or endpoint as id
                return DeliveryStatus::sent('push', $push->endpoint);
            }

            $reason = method_exists($report, 'getReason') ? $report->getReason() : 'Unknown failure';
            return DeliveryStatus::failed('push', $reason);
        } catch (\Throwable $e) {
            return DeliveryStatus::failed('push', $e->getMessage());
        }
    }
}
