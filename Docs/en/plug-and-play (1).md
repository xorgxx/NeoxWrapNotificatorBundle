# Plug & Play System

The bundle provides a high-level API to handle notifications using Data Transfer Objects (DTOs) and automatic UI generation.

## Notification DTOs

Instead of passing many arguments to methods, you can use specialized DTOs. Each DTO corresponds to a channel.

### Email Notification
```php
use Neox\WrapNotificatorBundle\Notification\Dto\EmailNotificationDto;

$dto = new EmailNotificationDto();
$dto->to = 'user@example.com';
$dto->subject = 'Hello';
$dto->content = 'Welcome to our platform!';
```

### SMS Notification
```php
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;

$dto = new SmsNotificationDto();
$dto->to = '+33600000000';
$dto->content = 'Your code is 1234';
```

## Sending a DTO

Use the `send()` method of the `NotifierFacade`:

```php
$status = $facade->send($dto);
```

The `send()` method automatically:
1. Validates the DTO using Symfony Validator (if available).
2. Maps the DTO to the correct internal notification method.
3. Returns a `DeliveryStatus`.

## Automatic Form Generation

You can generate a Symfony form automatically for any notification DTO using `GenericNotificationType`.

```php
use Neox\WrapNotificatorBundle\Form\GenericNotificationType;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;

$form = $this->createForm(GenericNotificationType::class, new SmsNotificationDto());
```

The form will automatically include fields for all public properties of the DTO, with appropriate types based on their PHP types and attributes.
