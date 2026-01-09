# Twig Widget

The bundle includes a ready-to-use Twig widget to display notification forms anywhere in your application.

## Usage

In any Twig template, call the `wrap_notify_form` function:

```twig
{# Render an SMS notification form #}
{{ wrap_notify_form('sms') }}

{# Render an Email notification form #}
{{ wrap_notify_form('email') }}
```

Available types: `email`, `sms`, `chat`, `browser`, `push`.

### Integration Example
```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Notification Center</h1>
    <div class="row">
        <div class="col-md-6">
            <h3>Send a quick SMS</h3>
            {{ wrap_notify_form('sms') }}
        </div>
        <div class="col-md-6">
            <h3>Send an Email</h3>
            {{ wrap_notify_form('email') }}
        </div>
    </div>
{% endblock %}
```

## Customization

The widget uses a default template located at `@WrapNotificator/widget/form.html.twig`. You can override this template in your application:

`templates/bundles/WrapNotificatorBundle/widget/form.html.twig`

### CSS Styles
The widget is designed to be Bootstrap-compatible, but can be styled via custom CSS. It uses standard Symfony Form classes.

### Flash Messages Management
The widget controller automatically adds `success` or `error` flash messages. Make sure to display these messages in your base layout to inform the user of the delivery result:

```twig
{% for label, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ label }}">
            {{ message }}
        </div>
    {% endfor %}
{% endfor %}
```

## How it works

The Twig function performs an internal sub-request to `NotificationWidgetController::renderForm`. This controller:
1. Instantiates the appropriate DTO.
2. Creates and handles the form.
3. Sends the notification if the form is valid.
4. Adds flash messages for success or error.
5. Renders the view.
