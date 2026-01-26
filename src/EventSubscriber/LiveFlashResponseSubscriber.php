<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\EventSubscriber;

use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LiveFlashResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotifierFacade $facade,
        private readonly array $mercureConfig = [],
        private readonly array $liveFlashConfig = [],
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!($this->mercureConfig['enabled'] ?? false)) {
            return;
        }

        $request = $event->getRequest();

        $enabled = $request->attributes->get(LiveFlashControllerSubscriber::ATTR_ENABLED, (bool) ($this->liveFlashConfig['enabled'] ?? false));
        if (!$enabled) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            return;
        }

        $consume = (bool) $request->attributes->get(LiveFlashControllerSubscriber::ATTR_CONSUME, (bool) ($this->liveFlashConfig['consume'] ?? true));

        $flashBag = $session->getFlashBag();
        if ($consume) {
            $flashes = $flashBag->all();
        } else {
            if (method_exists($flashBag, 'peekAll')) {
                $flashes = $flashBag->peekAll();
            } else {
                $flashes = $flashBag->all();
            }
        }

        if (!is_array($flashes) || $flashes === []) {
            return;
        }

        $topic = $request->attributes->get(LiveFlashControllerSubscriber::ATTR_TOPIC);
        if (!is_string($topic) || $topic === '') {
            $prefix = (string) ($this->liveFlashConfig['default_topic_prefix'] ?? 'wrap_notificator/flash');
            $topic = rtrim($prefix, '/').'/'.$session->getId();
        }

        $groupMessages = (bool) ($this->liveFlashConfig['group_messages'] ?? false);
        if ($groupMessages) {
            $items = [];
            foreach ($flashes as $type => $messages) {
                if (!is_iterable($messages)) {
                    continue;
                }
                foreach ($messages as $message) {
                    $items[] = [
                        'level' => (string) $type,
                        'message' => (string) $message,
                    ];
                }
            }

            if ($items === []) {
                return;
            }

            $this->facade->notifyBrowser(
                topic: $topic,
                data: [
                    'type' => 'flash_group',
                    'title' => 'Notifications',
                    'messages' => $items,
                ],
                metadata: ['source' => 'live_flash'],
            );

            return;
        }

        foreach ($flashes as $type => $messages) {
            if (!is_iterable($messages)) {
                continue;
            }
            foreach ($messages as $message) {
                $this->facade->notifyBrowser(
                    topic: $topic,
                    data: [
                        'type' => 'flash',
                        'level' => (string) $type,
                        'title' => 'Notification',
                        'message' => (string) $message,
                    ],
                    metadata: ['source' => 'live_flash'],
                );
            }
        }
    }
}
