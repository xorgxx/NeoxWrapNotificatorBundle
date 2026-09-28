<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification;

use Symfony\Component\Uid\Uuid;

final class DeliveryContext
{
    public function __construct(
        public readonly string $correlationId,
        public readonly ?string $dedupeKey = null,
        public readonly ?int $ttlSeconds = null,
        public readonly ?\DateTimeImmutable $deferAt = null,
        public readonly ?string $viaTransport = null, // e.g. 'asyncRabbitMq' or 'sync'
    ) {
    }

    public static function create(?string $correlationId = null, ?string $dedupeKey = null, ?int $ttlSeconds = null, ?\DateTimeImmutable $deferAt = null, ?string $viaTransport = null): self
    {
        return new self($correlationId ?? Uuid::v4()->toRfc4122(), $dedupeKey, $ttlSeconds, $deferAt, $viaTransport);
    }

    public static function for(string $dedupeKey, ?int $ttlSeconds = 600, ?\DateTimeImmutable $deferAt = null, ?string $viaTransport = null): self
    {
        return new self(Uuid::v4()->toRfc4122(), $dedupeKey, $ttlSeconds, $deferAt, $viaTransport);
    }

    /**
     * @return array{correlationId:string,dedupeKey:string|null,ttlSeconds:int|null,deferAt:string|null,viaTransport:string|null}
     */
    public function toArray(): array
    {
        return [
            'correlationId' => $this->correlationId,
            'dedupeKey' => $this->dedupeKey,
            'ttlSeconds' => $this->ttlSeconds,
            'deferAt' => $this->deferAt?->format(DATE_ATOM),
            'viaTransport' => $this->viaTransport,
        ];
    }
}
