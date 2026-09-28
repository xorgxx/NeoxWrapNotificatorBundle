<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification;

final class WebPushMessage
{
    public function __construct(
        public readonly string $endpoint,
        public readonly string $p256dh,
        public readonly string $auth,
        public readonly string $payloadJson,
        public readonly ?int $ttl = null,
    ) {
    }

    /**
     * @return array{
     *   endpoint:string,
     *   keys:array{p256dh:string,auth:string},
     *   payload:string,
     *   ttl:int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->p256dh,
                'auth' => $this->auth,
            ],
            'payload' => $this->payloadJson,
            'ttl' => $this->ttl,
        ];
    }
}
