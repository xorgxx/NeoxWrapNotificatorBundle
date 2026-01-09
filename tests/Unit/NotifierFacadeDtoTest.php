<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\Dto\EmailNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(NotifierFacade::class)]
final class NotifierFacadeDtoTest extends TestCase
{
    private MessageFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MessageFactory();
    }

    #[Test]
    public function send_with_email_dto_calls_notifyEmail(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $sender->expects(self::once())
            ->method('sendEmail')
            ->willReturn(DeliveryStatus::sent('email'));

        $facade = new NotifierFacade($this->factory, $sender);

        $dto = new EmailNotificationDto();
        $dto->to = 'test@example.com';
        $dto->subject = 'Hello';
        $dto->content = 'World';

        $status = $facade->send($dto);
        self::assertSame('sent', $status->status);
        self::assertSame('email', $status->channel);
    }

    #[Test]
    public function send_with_sms_dto_calls_notifySms(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $sender->expects(self::once())
            ->method('sendSms')
            ->willReturn(DeliveryStatus::sent('sms'));

        $facade = new NotifierFacade($this->factory, $sender);

        $dto = new SmsNotificationDto();
        $dto->to = '+33600000000';
        $dto->content = 'Hello';

        $status = $facade->send($dto);
        self::assertSame('sent', $status->status);
        self::assertSame('sms', $status->channel);
    }

    #[Test]
    public function send_with_validation_error_returns_failed(): void
    {
        $sender = $this->createMock(SenderInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);

        $violations = new ConstraintViolationList([
            $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class)
        ]);

        $validator->expects(self::once())
            ->method('validate')
            ->willReturn($violations);

        $facade = new NotifierFacade($this->factory, $sender, validator: $validator);

        $dto = new SmsNotificationDto();
        $status = $facade->send($dto);

        self::assertSame('failed', $status->status);
        self::assertSame('sms', $status->channel);
    }
}
