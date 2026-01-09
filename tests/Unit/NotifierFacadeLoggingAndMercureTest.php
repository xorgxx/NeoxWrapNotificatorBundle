<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Contract\NotificationLoggerInterface;
use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[CoversClass(NotifierFacade::class)]
final class NotifierFacadeLoggingAndMercureTest extends TestCase
{
    #[Test]
    public function finalize_calls_logger_when_enabled(): void
    {
        $factory = new MessageFactory();
        $sender = $this->createMock(SenderInterface::class);
        $logger = $this->createMock(NotificationLoggerInterface::class);

        $sender->method('sendSms')->willReturn(DeliveryStatus::sent('sms'));

        $logger->expects(self::once())
            ->method('log')
            ->with(self::isInstanceOf(DeliveryStatus::class));

        $facade = new NotifierFacade(
            $factory,
            $sender,
            logger: $logger,
            loggingConfig: ['enabled' => true]
        );

        $facade->notifySms('Hello', '+33600000000');
    }

    #[Test]
    public function finalize_calls_mercure_when_enabled(): void
    {
        $factory = new MessageFactory();
        $sender = $this->createMock(SenderInterface::class);
        $hub = $this->createMock(HubInterface::class);

        $sender->method('sendSms')->willReturn(DeliveryStatus::sent('sms'));

        $hub->expects(self::once())
            ->method('publish')
            ->with(self::callback(function (Update $update) {
                return $update->getTopics()[0] === 'wrap_notificator/status';
            }));

        $facade = new NotifierFacade(
            $factory,
            $sender,
            hub: $hub,
            mercureConfig: ['notify_status' => true]
        );

        $facade->notifySms('Hello', '+33600000000');
    }
}
