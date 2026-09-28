<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification;

use Symfony\Component\Uid\Uuid;

final class DeliveryStatus
{
    public const STATUS_SENT = 'sent';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_FAILED = 'failed';

    public readonly string $uuid;

    /**
     * @param array<string,mixed> $metadata
     */
    private function __construct(
        public readonly string $channel,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?string $id = null,
        public readonly array $metadata = [],
    ) {
        $this->uuid = Uuid::v4()->toRfc4122();
    }

    /**
     * Returns a new DeliveryStatus with merged metadata.
     * Note: a new UUID will be generated as each instance is immutable.
     * @param array<string,mixed> $extra
     */
    public function withMetadata(array $extra): self
    {
        return new self($this->channel, $this->status, $this->message, $this->id, array_merge($this->metadata, $extra));
    }

    public function withContext(DeliveryContext $ctx): self
    {
        $meta = $this->metadata;
        $meta['correlationId'] = $ctx->correlationId;
        $meta['dedupeKey'] = $ctx->dedupeKey;
        return new self($this->channel, $this->status, $this->message, $this->id, $meta);
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public static function sent(string $channel, ?string $id = null, ?string $message = null, array $metadata = []): self
    {
        return new self($channel, self::STATUS_SENT, $message, $id, $metadata);
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public static function queued(string $channel, ?string $id = null, ?string $message = null, array $metadata = []): self
    {
        return new self($channel, self::STATUS_QUEUED, $message, $id, $metadata);
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public static function failed(string $channel, ?string $message = null, ?string $id = null, array $metadata = []): self
    {
        return new self($channel, self::STATUS_FAILED, $message, $id, $metadata);
    }

    /**
     * @return array{
     *   uuid:string,
     *   channel:string,
     *   status:string,
     *   message: string|null,
     *   id: string|null,
     *   metadata: array<string,mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'channel' => $this->channel,
            'status' => $this->status,
            'message' => $this->message,
            'id' => $this->id,
            'metadata' => $this->metadata,
        ];
    }
}
