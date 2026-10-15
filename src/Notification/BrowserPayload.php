<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification;

final class BrowserPayload
{
    /** @param array<string,mixed> $data */
    public function __construct(
        public readonly string $topic,
        public readonly array $data,
        public readonly bool $private = false,
    ) {
    }

    /**
     * @return array{topic:string,data:array<string,mixed>,private:bool}
     */
    public function toArray(): array
    {
        return [
            'topic' => $this->topic,
            'data' => $this->data,
            'private' => $this->private,
        ];
    }
}
