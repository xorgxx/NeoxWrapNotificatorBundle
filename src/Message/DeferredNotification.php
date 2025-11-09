<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Message;

/**
 * Generic deferred notification message dispatched on Messenger when scheduling is requested.
 *
 * Carries the target channel and a normalized payload array that the handler can interpret
 * to rebuild the final transport-specific message and send it through the TypedSender.
 */
final class DeferredNotification
{
    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $metadata
     * @param array<string,mixed> $context
     */
    public function __construct(
        public readonly string $channel,
        public readonly array $payload,
        public readonly array $metadata = [],
        public readonly array $context = [],
    ) {
    }
}
