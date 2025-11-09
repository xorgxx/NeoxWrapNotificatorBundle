<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Integration;

use Neox\WrapNotificatorBundle\Message\DeferredNotification;
use Neox\WrapNotificatorBundle\Message\Handler\DeferredNotificationHandler;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

final class DeferredNotificationHandlerTest extends TestCase
{
    #[Test]
    public function handle_email_rebuilds_and_calls_sender(): void
    {
        $factory = new MessageFactory();
        $sender = $this->createMock(\Neox\WrapNotificatorBundle\Contract\SenderInterface::class);
        $sender->expects(self::once())
            ->method('sendEmail')
            ->with(self::isInstanceOf(Email::class))
            ->willReturn(\Neox\WrapNotificatorBundle\Notification\DeliveryStatus::sent('email'));

        $handler = new DeferredNotificationHandler($factory, $sender);

        $payload = [
            'subject' => 'Hello',
            'content' => '<p>World</p>',
            'to' => 'user@example.com',
            'opts' => ['from' => ['from@example.com', 'Sender']],
            'isHtml' => true,
        ];
        $msg = new DeferredNotification('email', $payload, [], []);

        ($handler)($msg);
        self::assertTrue(true); // if no exception and expected call happened, test passes
    }

    #[Test]
    public function handle_sms_rebuilds_and_calls_sender(): void
    {
        $factory = new MessageFactory();
        $sender = $this->createMock(\Neox\WrapNotificatorBundle\Contract\SenderInterface::class);
        $sender->expects(self::once())
            ->method('sendSms')
            ->with(self::isInstanceOf(SmsMessage::class))
            ->willReturn(\Neox\WrapNotificatorBundle\Notification\DeliveryStatus::sent('sms'));

        $handler = new DeferredNotificationHandler($factory, $sender);

        $payload = [
            'content' => 'Ping',
            'to' => '+33600000000',
        ];
        $msg = new DeferredNotification('sms', $payload, [], []);

        ($handler)($msg);
        self::assertTrue(true);
    }

    #[Test]
    public function handle_chat_rebuilds_and_calls_sender(): void
    {
        $factory = new MessageFactory();
        $sender = $this->createMock(\Neox\WrapNotificatorBundle\Contract\SenderInterface::class);
        $sender->expects(self::once())
            ->method('sendChat')
            ->with(self::isInstanceOf(ChatMessage::class))
            ->willReturn(\Neox\WrapNotificatorBundle\Notification\DeliveryStatus::sent('chat'));

        $handler = new DeferredNotificationHandler($factory, $sender);

        $payload = [
            'transport' => 'test',
            'content' => 'Deploy ok',
            'subject' => 'Release',
            'opts' => ['channel' => 'ops'],
        ];
        $msg = new DeferredNotification('chat', $payload, [], []);

        ($handler)($msg);
        self::assertTrue(true);
    }
}
