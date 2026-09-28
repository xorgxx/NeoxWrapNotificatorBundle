<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Minishlink\WebPush\WebPush;
use Neox\WrapNotificatorBundle\Notification\BrowserPayload;
use Neox\WrapNotificatorBundle\Notification\TypedSender;
use Neox\WrapNotificatorBundle\Notification\WebPushMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

final class TypedSenderTest extends TestCase
{
    public function testSendEmailSent(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(Email::class));

        $sender = new TypedSender($mailer);
        $status = $sender->sendEmail(new Email());
        self::assertSame('email', $status->channel);
        self::assertSame('sent', $status->status);
        self::assertNotEmpty($status->uuid);
    }

    public function testSendSmsFailedWithoutTexter(): void
    {
        $sender = new TypedSender();
        $status = $sender->sendSms(new SmsMessage('+336', 'hi'));
        self::assertSame('failed', $status->status);
    }

    public function testSendChatSent(): void
    {
        $chatter = $this->createMock(ChatterInterface::class);
        $chatter->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(ChatMessage::class));

        $sender = new TypedSender(mailer: null, chatter: $chatter);
        $status = $sender->sendChat(new ChatMessage('content'));
        self::assertSame('sent', $status->status);
    }

    public function testSendBrowserSentWithId(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(Update::class))
            ->willReturn('id-123');

        $sender = new TypedSender(null, null, null, $hub);
        $status = $sender->sendBrowser(new BrowserPayload('topic', ['a' => 1]));
        self::assertSame('sent', $status->status);
        self::assertSame('id-123', $status->id);
    }

    public function testSendPushFailedReason(): void
    {
        if (!class_exists(WebPush::class)) {
            self::markTestSkipped('minishlink/web-push not installed in this project');
        }
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $report = new \Minishlink\WebPush\MessageSentReport($request, null, false, 'invalid token');
        $webPush = $this->createMock(WebPush::class);
        $webPush->method('sendOneNotification')->willReturn($report);

        $sender = new TypedSender(null, null, null, null, $webPush);
        $msg = new WebPushMessage('https://ep', 'p', 'a', '{"hi":1}', 60);
        $status = $sender->sendPush($msg);
        self::assertSame('failed', $status->status);
        self::assertSame('invalid token', $status->message);
    }
}
