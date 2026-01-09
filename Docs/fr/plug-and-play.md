# Système Plug & Play

Le bundle fournit une API de haut niveau pour gérer les notifications à l'aide d'objets de transfert de données (DTO) et une génération automatique d'interface utilisateur.

## DTO de Notification

Au lieu de passer de nombreux arguments aux méthodes, vous pouvez utiliser des DTO spécialisés. Chaque DTO correspond à un canal.

### Notification Email
```php
use Neox\WrapNotificatorBundle\Notification\Dto\EmailNotificationDto;

$dto = new EmailNotificationDto();
$dto->to = 'user@example.com';
$dto->subject = 'Bonjour';
$dto->content = 'Bienvenue sur notre plateforme !';
```

### Notification SMS
```php
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;

$dto = new SmsNotificationDto();
$dto->to = '+33600000000';
$dto->content = 'Votre code est 1234';
```

## Envoyer un DTO

Utilisez la méthode `send()` de la `NotifierFacade` :

```php
$status = $facade->send($dto);
```

La méthode `send()` effectue automatiquement les actions suivantes :
1. Valide le DTO à l'aide du Validator de Symfony (si disponible).
2. Mappe le DTO vers la bonne méthode de notification interne.
3. Retourne un `DeliveryStatus`.

## Génération Automatique de Formulaire

Vous pouvez générer un formulaire Symfony automatiquement pour n'importe quel DTO de notification en utilisant `GenericNotificationType`.

```php
use Neox\WrapNotificatorBundle\Form\GenericNotificationType;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;

$form = $this->createForm(GenericNotificationType::class, new SmsNotificationDto());
```

Le formulaire inclura automatiquement des champs pour toutes les propriétés publiques du DTO, avec des types appropriés basés sur leurs types PHP et leurs attributs.
