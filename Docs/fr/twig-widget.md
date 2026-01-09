# Widget Twig

Le bundle inclut un widget Twig prêt à l'emploi pour afficher des formulaires de notification n'importe où dans votre application.

## Utilisation

Dans n'importe quel template Twig, appelez la fonction `wrap_notify_form` :

```twig
{# Rendre un formulaire de notification SMS #}
{{ wrap_notify_form('sms') }}

{# Rendre un formulaire de notification Email #}
{{ wrap_notify_form('email') }}
```

Types disponibles : `email`, `sms`, `chat`, `browser`, `push`.

### Exemple d'intégration dans une page
```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Centre de Notifications</h1>
    <div class="row">
        <div class="col-md-6">
            <h3>Envoyer un SMS rapide</h3>
            {{ wrap_notify_form('sms') }}
        </div>
        <div class="col-md-6">
            <h3>Envoyer un Email</h3>
            {{ wrap_notify_form('email') }}
        </div>
    </div>
{% endblock %}
```

## Personnalisation

Le widget utilise un template par défaut situé à `@WrapNotificator/widget/form.html.twig`. Vous pouvez surcharger ce template dans votre application :

`templates/bundles/WrapNotificatorBundle/widget/form.html.twig`

### Styles CSS
Le widget est conçu pour être compatible avec Bootstrap, mais il peut être stylisé via CSS personnalisé. Il utilise des classes standards de Symfony Form.

### Gestion des messages Flash
Le contrôleur du widget ajoute automatiquement des messages flash de type `success` ou `error`. Assurez-vous d'afficher ces messages dans votre layout de base pour informer l'utilisateur du résultat de l'envoi :

```twig
{% for label, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ label }}">
            {{ message }}
        </div>
    {% endfor %}
{% endfor %}
```

## Comment ça marche

La fonction Twig effectue une sous-requête interne vers `NotificationWidgetController::renderForm`. Ce contrôleur :
1. Instancie le DTO approprié.
2. Crée et gère le formulaire.
3. Envoie la notification si le formulaire est valide.
4. Ajoute des messages flash pour le succès ou l'erreur.
5. Affiche la vue.
