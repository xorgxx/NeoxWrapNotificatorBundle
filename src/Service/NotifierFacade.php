<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Service;

use Neox\WrapNotificatorBundle\Contract\DedupeRepositoryInterface;
use Neox\WrapNotificatorBundle\Contract\NotificationLoggerInterface;
use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\Dto\BrowserNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\ChatNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\EmailNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\NotificationDtoInterface;
use Neox\WrapNotificatorBundle\Notification\Dto\PushNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class NotifierFacade
{
    /**
     * @param array<string, mixed> $mercureConfig
     * @param array<string, mixed> $loggingConfig
     */
    public function __construct(
        private readonly MessageFactory $factory,
        private readonly SenderInterface $sender,
        private readonly ?DedupeRepositoryInterface $dedupe = null,
        private readonly ?MessageBusInterface $bus = null,
        private readonly ?Environment $twig = null,
        private readonly ?ValidatorInterface $validator = null,
        public readonly ?FormFactoryInterface $formFactory = null,
        private readonly ?NotificationLoggerInterface $logger = null,
        private readonly ?HubInterface $hub = null,
        private readonly array $mercureConfig = [],
        private readonly array $loggingConfig = [],
    ) {
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public function send(NotificationDtoInterface $dto, array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        if ($this->validator !== null) {
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return DeliveryStatus::failed($dto->getChannel(), (string) $errors, null, $metadata);
            }
        }

        return match (true) {
            $dto instanceof EmailNotificationDto => $this->notifyEmail(
                $dto->subject,
                $dto->content ?? '',
                $dto->recipient,
                $dto->isHtml,
                [
                    'template' => $dto->template,
                    'vars' => $dto->templateVars,
                    'from' => [$dto->from ?? $dto->recipient, ''],
                    'replyTo' => [$dto->sender, ''],
                    'attachments' => $this->convertUploadedFilesToAttachments($dto->attachments)
                ],
                $metadata,
                $ctx
            ),
            $dto instanceof SmsNotificationDto => $this->notifySms(
                $dto->content,
                $dto->recipient,
                $metadata,
                $ctx
            ),
            $dto instanceof ChatNotificationDto => $this->notifyChat(
                $dto->transport,
                $dto->content,
                $dto->subject,
                $dto->options,
                $metadata,
                $ctx
            ),
            $dto instanceof BrowserNotificationDto => $this->notifyBrowser(
                $dto->topic,
                $dto->data,
                $metadata,
                $ctx
            ),
            $dto instanceof PushNotificationDto => $this->notifyPush(
                /** @phpstan-ignore-next-line */
                $dto->subscription,
                $dto->data,
                $dto->ttl,
                $metadata,
                $ctx
            ),
            default => DeliveryStatus::failed($dto->getChannel(), 'Unsupported DTO type', null, $metadata),
        };
    }

    /**
     * @param array<string,mixed> $opts
     * @param array<string,mixed> $metadata
     */
    public function notifyEmail(string $subject, string $htmlOrText, string $to, bool $isHtml = true, array $opts = [], array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        try {
            if ($hit = $this->checkDedupe('email', $ctx, $metadata)) {
                return $hit;
            }
            $opts['html'] = $isHtml;

            // Template rendering if provided
            $template = $opts['template'] ?? null;
            if (is_string($template) && $template !== '') {
                $vars = is_array($opts['vars'] ?? null) ? $opts['vars'] : [];
                if ($this->twig !== null) {
                    $htmlOrText = $this->twig->render($template, $vars);
                    $isHtml = true;
                    $opts['html'] = true;
                }
            }

            // Deferred scheduling
            if ($ctx?->deferAt instanceof \DateTimeImmutable) {
                if ($fail = $this->requireBusOrFail('email', $metadata, $ctx, 'Deferred send requires Messenger (async mode). MessageBus not available.')) {
                    return $fail;
                }
                $payload = [
                    'subject' => $subject,
                    'content' => $htmlOrText,
                    'to' => $to,
                    'opts' => $opts,
                    'isHtml' => $isHtml,
                ];
                return $this->scheduleDeferredCommon('email', $payload, $metadata, $ctx);
            }

            // Forced transport (async/sync)
            if ($ctx?->viaTransport !== null) {
                if ($fail = $this->requireBusOrFail('email', $metadata, $ctx, 'Forcing transport requires Messenger. MessageBus not available.')) {
                    return $fail;
                }
                return $this->forcedTransportCommon('email', $metadata, $ctx, fn () => $this->factory->email($subject, $htmlOrText, $to, $opts), true);
            }

            // Direct send
            $email = $this->factory->email($subject, $htmlOrText, $to, $opts);
            $status = $this->sender->sendEmail($email);
            return $this->finalize($status, $metadata, $ctx);
        } catch (\Throwable $e) {
            return $this->finalize(DeliveryStatus::failed('email', $e->getMessage(), null, $metadata), [], $ctx);
        }
    }

    /**
     * @param array<string,mixed> $metadata
     */
    public function notifySms(string $content, string $to, array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        try {
            if ($hit = $this->checkDedupe('sms', $ctx, $metadata)) {
                return $hit;
            }

            // Deferred scheduling
            if ($ctx?->deferAt instanceof \DateTimeImmutable) {
                if ($fail = $this->requireBusOrFail('sms', $metadata, $ctx, 'Deferred send requires Messenger (async mode). MessageBus not available.')) {
                    return $fail;
                }
                $payload = [
                    'content' => $content,
                    'to' => $to,
                ];
                return $this->scheduleDeferredCommon('sms', $payload, $metadata, $ctx);
            }

            // Forced transport (async/sync)
            if ($ctx?->viaTransport !== null) {
                if ($fail = $this->requireBusOrFail('sms', $metadata, $ctx, 'Forcing transport requires Messenger. MessageBus not available.')) {
                    return $fail;
                }
                return $this->forcedTransportCommon('sms', $metadata, $ctx, fn () => $this->factory->sms($content, $to));
            }

            // Direct send
            $sms = $this->factory->sms($content, $to);
            $status = $this->sender->sendSms($sms);
            return $this->finalize($status, $metadata, $ctx);
        } catch (\Throwable $e) {
            return $this->finalize(DeliveryStatus::failed('sms', $e->getMessage(), null, $metadata), [], $ctx);
        }
    }

    /**
     * @param array<string,mixed> $opts
     * @param array<string,mixed> $metadata
     */
    public function notifyChat(string $transport, mixed $content, ?string $subject = null, array $opts = [], array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        try {
            if ($hit = $this->checkDedupe('chat', $ctx, $metadata)) {
                return $hit;
            }

            if ($content instanceof ChatMessage) {
                // Deferred scheduling is not supported for complex ChatMessage payloads
                if ($ctx?->deferAt instanceof \DateTimeImmutable) {
                    return $this->finalize(
                        DeliveryStatus::failed('chat', 'Deferred chat is not supported when content is a ChatMessage', null, $metadata),
                        [],
                        $ctx
                    );
                }

                // Forced transport (async/sync)
                if ($ctx?->viaTransport !== null) {
                    if ($fail = $this->requireBusOrFail('chat', $metadata, $ctx, 'Forcing transport requires Messenger. MessageBus not available.')) {
                        return $fail;
                    }

                    return $this->forcedTransportCommon('chat', $metadata, $ctx, function () use ($content, $transport, $subject) {
                        $msg = clone $content;
                        $msg->transport($transport);
                        if ($subject !== null) {
                            $msg->subject($subject);
                        }
                        return $msg;
                    });
                }

                // Direct send
                $content->transport($transport);
                if ($subject !== null) {
                    $content->subject($subject);
                }
                $status = $this->sender->sendChat($content);
                return $this->finalize($status, $metadata, $ctx);
            }

            $content = $this->normalizeChatContent($content);

            // Deferred scheduling
            if ($ctx?->deferAt instanceof \DateTimeImmutable) {
                if ($fail = $this->requireBusOrFail('chat', $metadata, $ctx, 'Deferred send requires Messenger (async mode). MessageBus not available.')) {
                    return $fail;
                }
                $payload = [
                    'transport' => $transport,
                    'content' => $content,
                    'subject' => $subject,
                    'opts' => $opts,
                ];
                return $this->scheduleDeferredCommon('chat', $payload, $metadata, $ctx);
            }

            // Forced transport (async/sync)
            if ($ctx?->viaTransport !== null) {
                if ($fail = $this->requireBusOrFail('chat', $metadata, $ctx, 'Forcing transport requires Messenger. MessageBus not available.')) {
                    return $fail;
                }
                return $this->forcedTransportCommon('chat', $metadata, $ctx, fn () => $this->factory->chat($transport, $content, $subject, $opts));
            }

            // Direct send
            $chat = $this->factory->chat($transport, $content, $subject, $opts);
            $status = $this->sender->sendChat($chat);
            return $this->finalize($status, $metadata, $ctx);
        } catch (\Throwable $e) {
            return $this->finalize(DeliveryStatus::failed('chat', $e->getMessage(), null, $metadata), [], $ctx);
        }
    }

    private function normalizeChatContent(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }
        if ($content instanceof \Stringable) {
            return (string) $content;
        }
        if (is_int($content) || is_float($content) || is_bool($content) || $content === null) {
            return (string) $content;
        }
        if (is_array($content) || is_object($content)) {
            $json = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                return $json;
            }
        }
        return '';
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $metadata
     */
    public function notifyBrowser(string $topic, array $data, array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        try {
            if ($hit = $this->checkDedupe('browser', $ctx, $metadata)) {
                return $hit;
            }

            // Deferred scheduling
            if ($ctx?->deferAt instanceof \DateTimeImmutable) {
                if ($fail = $this->requireBusOrFail('browser', $metadata, $ctx, 'Deferred send requires Messenger (async mode). MessageBus not available.')) {
                    return $fail;
                }
                $payload = [
                    'topic' => $topic,
                    'data' => $data,
                ];
                return $this->scheduleDeferredCommon('browser', $payload, $metadata, $ctx);
            }

            // Forced transport (async/sync)
            if ($ctx?->viaTransport !== null) {
                if ($fail = $this->requireBusOrFail('browser', $metadata, $ctx, 'Forcing transport requires Messenger. MessageBus not available.')) {
                    return $fail;
                }
                return $this->forcedTransportCommon('browser', $metadata, $ctx, function () use ($topic, $data) {
                    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if ($json === false) {
                        $json = '{}';
                    }
                    return new Update($topic, $json);
                });
            }

            // Direct send
            $payload = $this->factory->browser($topic, $data);
            $status = $this->sender->sendBrowser($payload);
            return $this->finalize($status, $metadata, $ctx);
        } catch (\Throwable $e) {
            return $this->finalize(DeliveryStatus::failed('browser', $e->getMessage(), null, $metadata), [], $ctx);
        }
    }

    /**
     * @param array{endpoint:string,keys:array{p256dh:string,auth:string}} $subscription
     * @param array<string,mixed> $data
     * @param array<string,mixed> $metadata
     */
    public function notifyPush(array $subscription, array $data, ?int $ttl = null, array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        try {
            if ($hit = $this->checkDedupe('push', $ctx, $metadata)) {
                return $hit;
            }

            // Deferred scheduling (no specific transport stamp for push)
            if ($ctx?->deferAt instanceof \DateTimeImmutable) {
                if ($fail = $this->requireBusOrFail('push', $metadata, $ctx, 'Deferred send requires Messenger (async mode). MessageBus not available.')) {
                    return $fail;
                }
                $payload = [
                    'subscription' => $subscription,
                    'data' => $data,
                    'ttl' => $ttl,
                ];
                return $this->scheduleDeferredCommon('push', $payload, $metadata, $ctx, includeTransportStamp: false);
            }

            // Direct send only (no forced transport path for push)
            $msg = $this->factory->push($subscription, $data, $ttl);
            $status = $this->sender->sendPush($msg);
            return $this->finalize($status, $metadata, $ctx);
        } catch (\Throwable $e) {
            return $this->finalize(DeliveryStatus::failed('push', $e->getMessage(), null, $metadata), [], $ctx);
        }
    }

    /**
     * Alias vers notifyPush (extensible plus tard)
     * @param array{endpoint:string,keys:array{p256dh:string,auth:string}} $subscription
     * @param array<string,mixed> $data
     * @param array<string,mixed> $metadata
     */
    public function notifyDesktop(array $subscription, array $data, ?int $ttl = null, array $metadata = [], ?DeliveryContext $ctx = null): DeliveryStatus
    {
        return $this->notifyPush($subscription, $data, $ttl, $metadata, $ctx);
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function withMetadata(DeliveryStatus $status, array $metadata): DeliveryStatus
    {
        if ($metadata === []) {
            return $status;
        }
        return $status->withMetadata($metadata);
    }

    /**
     * Convert UploadedFile objects to attachment format for MessageFactory
     * @param array<int, \Symfony\Component\HttpFoundation\File\UploadedFile> $uploadedFiles
     * @return array<int, array{path: string, name: string, mime: string}>
     */
    private function convertUploadedFilesToAttachments(array $uploadedFiles): array
    {
        $attachments = [];
        foreach ($uploadedFiles as $file) {
            if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $attachments[] = [
                    'path' => $file->getPathname(),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?? 'application/octet-stream'
                ];
            }
        }
        return $attachments;
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function finalize(DeliveryStatus $status, array $metadata, ?DeliveryContext $ctx): DeliveryStatus
    {
        $status = $this->withMetadata($status, $metadata);
        if ($ctx !== null) {
            $status = $status->withContext($ctx);
        }

        if (($this->loggingConfig['enabled'] ?? false) && $this->logger !== null) {
            try {
                $this->logger->log($status);
            } catch (\Throwable) {
                // Silently fail logging to avoid breaking the main flow
            }
        }

        if (($this->mercureConfig['notify_status'] ?? false) && $this->hub !== null) {
            try {
                $topic = $status->metadata['correlationId'] ?? 'wrap_notificator/status';
                $update = new Update((string) $topic, (string) json_encode($status->toArray()));
                $this->hub->publish($update);
            } catch (\Throwable) {
                // Silently fail status notification
            }
        }

        return $status;
    }

    /**
     * Ensure Messenger bus is available; otherwise return a finalized failed status with the provided message.
     * @param array<string,mixed> $metadata
     */
    private function requireBusOrFail(string $channel, array $metadata, ?DeliveryContext $ctx, string $failureMessage): ?DeliveryStatus
    {
        if ($this->bus === null) {
            return $this->finalize(DeliveryStatus::failed($channel, $failureMessage), $metadata, $ctx);
        }
        return null;
    }

    private function computeDelayMs(\DateTimeImmutable $at): int
    {
        return max(0, $at->getTimestamp() - (new \DateTimeImmutable('now'))->getTimestamp()) * 1000;
    }

    /**
     * Schedule a DeferredNotification on Messenger. Optionally carry the viaTransport stamp/metadata.
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $metadata
     * @throws ExceptionInterface
     */
    private function scheduleDeferredCommon(string $channel, array $payload, array $metadata, DeliveryContext $ctx, bool $includeTransportStamp = true): DeliveryStatus
    {
        $delayMs = $this->computeDelayMs($ctx->deferAt);
        $message = new \Neox\WrapNotificatorBundle\Message\DeferredNotification($channel, $payload, $metadata, $ctx->toArray());
        $stamps = [new DelayStamp($delayMs)];
        if ($includeTransportStamp && $ctx->viaTransport) {
            $stamps[] = new TransportNamesStamp([$ctx->viaTransport]);
        }
        $envelope = new Envelope($message, $stamps);
        $this->bus->dispatch($envelope);
        $meta = ['deferAt' => $ctx->deferAt->format(DATE_ATOM)];
        if ($includeTransportStamp) {
            $meta['transport'] = $ctx->viaTransport;
        }
        return $this->finalize(DeliveryStatus::queued($channel, null, 'scheduled', $meta), $metadata, $ctx);
    }

    /**
     * Dispatch a message on a specific transport (async or sync) and return a normalized status.
     * @param array<string,mixed> $metadata
     * @param (callable(): mixed) $buildMessage function(): mixed — returns the message to dispatch (Email|SmsMessage|ChatMessage|Update|...)
     * @throws ExceptionInterface
     */
    private function forcedTransportCommon(string $channel, array $metadata, DeliveryContext $ctx, callable $buildMessage, bool $wrapEmail = false): DeliveryStatus
    {
        $msg = $buildMessage();
        if ($wrapEmail) {
            $msg = new SendEmailMessage($msg);
        }
        $stamps = [new TransportNamesStamp([$ctx->viaTransport])];
        $this->bus->dispatch($msg, $stamps);
        $status = ($ctx->viaTransport === 'sync')
            ? DeliveryStatus::sent($channel)
            : DeliveryStatus::queued($channel, null, 'forced-transport', ['transport' => $ctx->viaTransport]);
        return $this->finalize($status, $metadata, $ctx);
    }

    /**
     * Returns DeliveryStatus queued when dedupe hit, otherwise null. Also calls remember() on miss when applicable.
     * @param array<string,mixed> $metadata
     */
    private function checkDedupe(string $channel, ?DeliveryContext $ctx, array $metadata): ?DeliveryStatus
    {
        if ($ctx === null || $ctx->dedupeKey === null || $this->dedupe === null) {
            return null;
        }
        if ($this->dedupe->exists($ctx->dedupeKey)) {
            $status = DeliveryStatus::queued($channel, null, 'noop', ['reason' => 'dedup-hit']);
            return $this->finalize($status, $metadata, $ctx);
        }
        $this->dedupe->remember($ctx->dedupeKey, $ctx->ttlSeconds ?? 600);
        return null;
    }
}
