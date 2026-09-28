<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DeliveryContextTest extends TestCase
{
    #[Test]
    public function create_generates_uuid_and_serializes_with_viaTransport(): void
    {
        $ctx = DeliveryContext::create(viaTransport: 'asyncRabbitMq');
        $arr = $ctx->toArray();

        self::assertArrayHasKey('correlationId', $arr);
        self::assertNotEmpty($arr['correlationId']);
        self::assertNull($arr['dedupeKey']);
        self::assertNull($arr['ttlSeconds']);
        self::assertNull($arr['deferAt']);
        self::assertSame('asyncRabbitMq', $arr['viaTransport']);
    }

    #[Test]
    public function for_sets_dedupeKey_ttl_and_optionally_deferAt_and_transport(): void
    {
        $at = new \DateTimeImmutable('+5 minutes');
        $ctx = DeliveryContext::for('key-42', ttlSeconds: 900, deferAt: $at, viaTransport: 'sync');
        $arr = $ctx->toArray();

        self::assertSame('key-42', $arr['dedupeKey']);
        self::assertSame(900, $arr['ttlSeconds']);
        self::assertNotNull($arr['deferAt']);
        self::assertSame('sync', $arr['viaTransport']);
    }
}
