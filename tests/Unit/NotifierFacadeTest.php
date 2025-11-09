<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Contract\DedupeRepositoryInterface;
use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

#[CoversClass(NotifierFacade::class)]
final class NotifierFacadeTest extends TestCase
{
    private MessageFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MessageFactory();
    }

    #[Test]
    public function notifySms_direct_send_sent(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $sender->expects(self::once())
            ->method('sendSms')
            ->with(self::isInstanceOf(SmsMessage::class))
            ->willReturn(DeliveryStatus::sent('sms'));

        $facade = new NotifierFacade($this->factory, $sender);

        $status = $facade->notifySms('Ping', '+33600000000');
        $arr = method_exists($status, 'toArray') ? $status->toArray() : ['status' => $status->status ?? null];

        self::assertSame('sent', $arr['status']);
    }

    #[Test]
    public function notifySms_deferred_schedules_with_delay_and_transport_stamp(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function ($envelope) {
                // scheduleDeferredCommon builds an Envelope
                if (!$envelope instanceof Envelope) {
                    return false;
                }
                $message = $envelope->getMessage();
                // The message is DeferredNotification
                if (!\is_object($message) || $message::class !== 'Neox\\WrapNotificatorBundle\\Message\\DeferredNotification') {
                    return false;
                }
                $delayStamp = $envelope->last(DelayStamp::class);
                $transportStamp = $envelope->last(TransportNamesStamp::class);
                return $delayStamp instanceof DelayStamp
                    && $transportStamp instanceof TransportNamesStamp
                    && in_array('asyncRabbitMq', $transportStamp->getTransportNames(), true);
            }))
            ->willReturnArgument(0);

        $facade = new NotifierFacade($this->factory, $sender, dedupe: null, bus: $bus);

        $ctx = DeliveryContext::create(
            deferAt: new \DateTimeImmutable('+10 minutes'),
            viaTransport: 'asyncRabbitMq'
        );

        $status = $facade->notifySms('Ping', '+33600000000', [], $ctx);
        $arr = $status->toArray();

        self::assertSame('queued', $arr['status']);
        self::assertSame('sms', $arr['channel']);
        self::assertArrayHasKey('deferAt', $arr['metadata']);
        self::assertSame('asyncRabbitMq', $arr['metadata']['transport']);
    }

    #[Test]
    public function notifyEmail_forced_transport_sync_wraps_in_SendEmailMessage_and_returns_sent(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(function ($msg) {
                    return $msg instanceof SendEmailMessage; // email wrapped
                }),
                self::callback(function (array $stamps) {
                    $stamp = $stamps[0] ?? null;
                    return $stamp instanceof TransportNamesStamp && in_array('sync', $stamp->getTransportNames(), true);
                })
            )
            ->willReturnCallback(function ($message) {
                return $message instanceof Envelope ? $message : new Envelope($message);
            });

        $facade = new NotifierFacade($this->factory, $sender, bus: $bus);

        $ctx = DeliveryContext::create(viaTransport: 'sync');
        $status = $facade->notifyEmail('Hello', '<p>World</p>', 'user@example.com', true, [], [], $ctx);

        $arr = $status->toArray();
        self::assertSame('sent', $arr['status']);
    }

    #[Test]
    public function notifyChat_forced_transport_async_returns_queued_with_transport_meta(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(ChatMessage::class),
                self::callback(function (array $stamps) {
                    $found = false;
                    foreach ($stamps as $s) {
                        if ($s instanceof TransportNamesStamp && in_array('asyncRabbitMq', $s->getTransportNames(), true)) {
                            $found = true;
                            break;
                        }
                    }
                    return $found;
                })
            )
            ->willReturnCallback(function ($message) {
                return $message instanceof Envelope ? $message : new Envelope($message);
            });

        $facade = new NotifierFacade($this->factory, $sender, bus: $bus);
        $ctx = DeliveryContext::create(viaTransport: 'asyncRabbitMq');
        // Use a generic transport that doesn't require optional bridges
        $status = $facade->notifyChat('test', 'Deploy ok', null, [], [], $ctx);

        $arr = $status->toArray();
        self::assertSame('queued', $arr['status']);
        self::assertSame('asyncRabbitMq', $arr['metadata']['transport'] ?? null);
    }

    #[Test]
    public function dedupe_hit_returns_noop_queued_and_skips_sender_and_bus(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $sender->expects(self::never())->method(self::anything());
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method(self::anything());

        $dedupe = $this->createMock(DedupeRepositoryInterface::class);
        $dedupe->method('exists')->willReturn(true);

        $facade = new NotifierFacade($this->factory, $sender, dedupe: $dedupe, bus: $bus);

        $ctx = DeliveryContext::for('key-123', ttlSeconds: 600);
        $status = $facade->notifyBrowser('users:42', ['title' => 'Hi'], [], $ctx);
        $arr = $status->toArray();

        self::assertSame('queued', $arr['status']);
        self::assertSame('dedup-hit', $arr['metadata']['reason'] ?? null);
    }

    #[Test]
    public function deferred_without_bus_returns_failed(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $facade = new NotifierFacade($this->factory, $sender, bus: null);

        $ctx = DeliveryContext::create(deferAt: new \DateTimeImmutable('+5 minutes'));
        $status = $facade->notifySms('Ping', '+33600000000', [], $ctx);

        $arr = $status->toArray();
        self::assertSame('failed', $arr['status']);
        self::assertSame('sms', $arr['channel']);
    }
}
