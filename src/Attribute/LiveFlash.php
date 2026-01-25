<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class LiveFlash
{
    public function __construct(
        public readonly bool $enabled = true,
        public readonly ?string $topic = null,
        public readonly ?bool $consume = null,
    ) {
    }
}
