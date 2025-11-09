<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Contract;

use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Neox\WrapNotificatorBundle\Notification\BrowserPayload;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\WebPushMessage;

interface SenderInterface
{
    public function sendEmail(Email $email): DeliveryStatus;

    public function sendSms(SmsMessage $sms): DeliveryStatus;

    public function sendChat(ChatMessage $chat): DeliveryStatus;

    public function sendBrowser(BrowserPayload $payload): DeliveryStatus;

    public function sendPush(WebPushMessage $push): DeliveryStatus;
}
