<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

final class MessageFactoryTest extends TestCase
{
    private MessageFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MessageFactory();
    }

    #[Test]
    public function email_builds_html_with_from(): void
    {
        $email = $this->factory->email('Hello', '<p>World</p>', 'to@example.com', [
            'html' => true,
            'from' => ['from@example.com', 'Sender'],
        ]);

        self::assertInstanceOf(Email::class, $email);
        self::assertSame('Hello', $email->getSubject());
        $to = array_map(static fn($a) => $a->getAddress(), $email->getTo());
        self::assertContains('to@example.com', $to);
        $from = array_map(static fn($a) => [$a->getAddress(), $a->getName()], $email->getFrom());
        self::assertContains(['from@example.com', 'Sender'], $from);
        self::assertSame('<p>World</p>', $email->getHtmlBody());
    }

    #[Test]
    public function email_builds_text_when_flag_is_false(): void
    {
        $email = $this->factory->email('Hi', 'Plain', 'to@example.com', ['html' => false]);
        self::assertSame('Plain', $email->getTextBody());
    }

    #[Test]
    public function sms_builds_message(): void
    {
        $sms = $this->factory->sms('Ping', '+336');
        self::assertInstanceOf(SmsMessage::class, $sms);
    }

    #[Test]
    public function chat_slack_builds_with_options(): void
    {
        if (!class_exists(SlackOptions::class)) {
            self::markTestSkipped('slack-notifier not installed in this project');
        }
        $chat = $this->factory->chat('slack', 'Deploy ok', 'Release', [
            'channel' => 'ops',
            'iconEmoji' => ':rocket:',
            'blocks' => [ ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => 'Deploy']] ],
        ]);
        self::assertInstanceOf(ChatMessage::class, $chat);
        /** @var SlackOptions $opts */
        $opts = $chat->getOptions();
        self::assertInstanceOf(SlackOptions::class, $opts);
    }

    #[Test]
    public function chat_telegram_builds_with_options(): void
    {
        if (!class_exists(TelegramOptions::class)) {
            self::markTestSkipped('telegram-notifier not installed in this project');
        }
        $chat = $this->factory->chat('telegram', '<b>Alert</b>', null, [
            'chatId' => 123456,
            'parseMode' => 'HTML',
            'disableWebPagePreview' => true,
        ]);
        self::assertInstanceOf(ChatMessage::class, $chat);
        /** @var TelegramOptions $opts */
        $opts = $chat->getOptions();
        self::assertInstanceOf(TelegramOptions::class, $opts);
    }
}
