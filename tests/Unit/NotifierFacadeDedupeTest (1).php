<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Contract\DedupeRepositoryInterface;
use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class NotifierFacadeDedupeTest extends TestCase
{
    #[Test]
    public function dedupe_hit_returns_noop_queued_and_skips_sender_and_bus(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $sender->expects(self::never())->method(self::anything());

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method(self::anything());

        $dedupe = $this->createMock(DedupeRepositoryInterface::class);
        $dedupe->method('exists')->willReturn(true);

        $facade = new NotifierFacade(new MessageFactory(), $sender, dedupe: $dedupe, bus: $bus);
        $ctx = DeliveryContext::for('dedup:key:1', ttlSeconds: 600);

        $status = $facade->notifyBrowser('users:42', ['title' => 'Hello'], [], $ctx);
        $arr = $status->toArray();

        self::assertSame('queued', $arr['status']);
        self::assertSame('browser', $arr['channel']);
        self::assertSame('dedup-hit', $arr['metadata']['reason'] ?? null);
        self::assertSame($ctx->correlationId, $arr['metadata']['correlationId'] ?? null);
        self::assertSame($ctx->dedupeKey, $arr['metadata']['dedupeKey'] ?? null);
    }
}
