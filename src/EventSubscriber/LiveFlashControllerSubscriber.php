<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\EventSubscriber;

use Neox\WrapNotificatorBundle\Attribute\LiveFlash;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LiveFlashControllerSubscriber implements EventSubscriberInterface
{
    public const ATTR_ENABLED = '_wrap_notificator_live_flash_enabled';
    public const ATTR_TOPIC = '_wrap_notificator_live_flash_topic';
    public const ATTR_CONSUME = '_wrap_notificator_live_flash_consume';

    public function __construct(
        private readonly array $liveFlashConfig = [],
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $defaultEnabled = (bool) ($this->liveFlashConfig['enabled'] ?? false);
        $defaultConsume = (bool) ($this->liveFlashConfig['consume'] ?? true);

        $enabled = $defaultEnabled;
        $topic = null;
        $consume = $defaultConsume;

        $controller = $event->getController();
        $reflectionMethod = null;
        $reflectionClass = null;

        if (is_array($controller) && isset($controller[0], $controller[1]) && is_object($controller[0]) && is_string($controller[1])) {
            $reflectionClass = new \ReflectionClass($controller[0]);
            if ($reflectionClass->hasMethod($controller[1])) {
                $reflectionMethod = $reflectionClass->getMethod($controller[1]);
            }
        } elseif (is_object($controller) && method_exists($controller, '__invoke')) {
            $reflectionClass = new \ReflectionClass($controller);
            $reflectionMethod = $reflectionClass->getMethod('__invoke');
        }

        $methodAttr = $reflectionMethod?->getAttributes(LiveFlash::class)[0] ?? null;
        if ($methodAttr !== null) {
            /** @var LiveFlash $instance */
            $instance = $methodAttr->newInstance();
            $enabled = $instance->enabled;
            $topic = $instance->topic;
            if ($instance->consume !== null) {
                $consume = $instance->consume;
            }
        } else {
            $classAttr = $reflectionClass?->getAttributes(LiveFlash::class)[0] ?? null;
            if ($classAttr !== null) {
                /** @var LiveFlash $instance */
                $instance = $classAttr->newInstance();
                $enabled = $instance->enabled;
                $topic = $instance->topic;
                if ($instance->consume !== null) {
                    $consume = $instance->consume;
                }
            }
        }

        $request->attributes->set(self::ATTR_ENABLED, $enabled);
        $request->attributes->set(self::ATTR_TOPIC, $topic);
        $request->attributes->set(self::ATTR_CONSUME, $consume);
    }
}
