# Twig Widget

The bundle includes a ready-to-use Twig widget to display notification forms anywhere in your application.

## Simple Usage (inline form)

In any Twig template, call the `wrap_notify_form` function:

```twig
{# Render an SMS notification form #}
{{ wrap_notify_form('sms') }}

{# Render an Email notification form #}
{{ wrap_notify_form('email') }}
```

Available types: `email`, `sms`, `chat`, `browser`, `push`.

## Stimulus Modal Usage (recommended)

For a modern user experience, use the Stimulus modal system with AJAX loading.

### Prerequisites

- Bootstrap 5 (CSS + JS)
- Stimulus (`npm install @hotwired/stimulus`)

### Step 1: Include modal in your layout

**IMPORTANT**: Add this line **ONCE ONLY** in your `templates/base.html.twig`, just before `</body>`:

```twig
{# Stimulus modal for notifications - REQUIRED #}
{% include '@WrapNotificator/widget/modal_stimulus.html.twig' %}
```

**Complete base.html.twig example**:

```twig
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {% block body %}{% endblock %}
    
    {# Generic modal - ONCE ONLY #}
    {% include '@WrapNotificator/widget/modal_stimulus.html.twig' %}
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{ encore_entry_script_tags('app') }}
</body>
</html>
```

### Step 2: Create Stimulus controller

**Copy** the file `Docs/examples/notify_modal_controller.js` from the bundle to your project:

```bash
cp vendor/xorgxx/wrap-notificator-bundle/Docs/examples/notify_modal_controller.js assets/controllers/
```

Stimulus will automatically detect the controller.

### Optional: Configure attachments validation (security)

Email attachments are validated server-side. You can configure limits using:

```yaml
# config/packages/wrap_notificator.yaml
wrap_notificator:
  attachments_validation:
    max_files: 5
    max_size: '1M'
    mime_types:
      - 'application/pdf'
      - 'image/png'
      - 'image/jpeg'
      - 'image/gif'
      - 'text/plain'
      - 'application/msword'
      - 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      - 'application/vnd.ms-excel'
      - 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
```

### Step 3: Use the modal

#### Option A: With Twig macro (recommended)

```twig
{% import '@WrapNotificator/widget/modal_button.html.twig' as modal %}

{# Simple form (recommended for contact) - hides technical fields #}
{{ modal.button('email', 'Send Email', 'btn btn-primary') }}
{# Shows: sender (client email), subject, content #}
{# Hides: recipient (auto-filled), template, templateVars, isHtml, attachments #}

{# Form with attachments - IMPORTANT: use positional parameters #}
{{ modal.button('email', 'Email with files', 'btn btn-primary', 'Send with attachments', null, true) }}
{# Parameters: type, text, class, title, exclude, attached #}
{# 6th parameter (attached) = true shows attachments field #}
{# Shows: subject, content, attachments #}
{# Hides: sender (auto), recipient (auto) #}

{# Complete form - shows ALL fields #}
{{ modal.button('email', 'Advanced Email', 'btn btn-primary', 'Email with template', '') }}
{# 5th parameter '' = empty string = no exclusion #}
{# Shows: sender, recipient, subject, content, template, templateVars, isHtml #}

{# Custom exclusion #}
{{ modal.button('email', 'Custom Email', 'btn btn-primary', 'Email', 'template,isHtml') }}
{# Hides only template and isHtml #}
{# Shows: sender, recipient, subject, content, templateVars #}

{# Text button #}
{{ modal.button({type: 'email', text: 'Contact', class: 'btn btn-link'}) }}

{# Icon button #}
{{ modal.button({type: 'email', text: 'Contact', class: 'btn btn-link', icon_class: 'bi bi-envelope'}) }}

**Macro `button(type, text, class, title, exclude, attached)` parameters:**

**Syntax**: You can use positional parameters OR named parameters with hash notation `{...}`:

1. **`type`** (required): Notification type (`email`, `sms`, `chat`)
2. **`text`** (optional): Button text (default: `'Notification'`)
3. **`class`** (optional): Button CSS classes (default: `'btn btn-primary'`)
4. **`title`** (optional): Modal title (default: button text)
5. **`exclude`** (optional): Fields to hide, comma-separated
   - **Default**: `'recipient,template,templateVars,isHtml,attachments'` (simple form)
   - The `sender` field is visible by default so clients can enter their email
   - **Empty string `''`**: No exclusion (all fields)
   - **Custom**: `'field1,field2'` (hide only these fields)
   - **`null`**: Use default value
6. **`attached`** (optional, email only): Show attachments field
   - **`false`** or undefined: Hide attachments field (default)
   - **`true`**: Show attachments field for file uploads
7. **`icon_class`** (optional): Icon CSS class (e.g. `'bi bi-envelope'`)
8. **`icon_only`** (optional): Display only the icon (default: `false`)

### Optional: reCAPTCHA v3 (KarserRecaptcha3Bundle)

If you add `Recaptcha3Type` to the form (for example via `GenericNotificationType`), you must ensure:

- Twig form theme is enabled:

```yaml
# config/packages/twig.yaml
twig:
  form_themes:
    - '@KarserRecaptcha3/Form/karser_recaptcha3_widget.html.twig'
```

- If the form is loaded via AJAX and injected into the DOM (modal), embedded `<script>` tags must be executed after injection (otherwise `grecaptcha.execute()` will never run and the token will remain empty).

**Examples with positional parameters**:
```twig
{# Simple form #}
{{ modal.button('email', 'Contact', 'btn btn-primary') }}

{# With attachments - use null to keep default exclude #}
{{ modal.button('email', 'Email with files', 'btn btn-primary', 'Contact', null, true) }}

{# All fields except attachments #}
{{ modal.button('email', 'Complete Email', 'btn btn-primary', 'Email', '', false) }}
```

**Examples with named parameters (hash)**:
```twig
{# Simple form #}
{{ modal.button({type: 'email', text: 'Contact', class: 'btn btn-primary'}) }}

{# With attachments #}
{{ modal.button({type: 'email', text: 'Email with files', class: 'btn btn-primary', attached: true}) }}

{# Custom #}
{{ modal.button({type: 'email', text: 'Email', title: 'Contact form', exclude: 'template', attached: true}) }}
```

**Note**: The `recipient` field is automatically filled from `default_recipients` configuration.

#### Option B: With manual onclick

```twig
{# Button to open modal with Email form #}
<button 
    type="button" 
    class="btn btn-primary"
    onclick="document.dispatchEvent(new CustomEvent('notify-modal:open', {
        detail: {
            url: '{{ path('wrap_notificator_form', {type: 'email'}) }}',
            title: 'Send an Email'
        }
    }))"
>
    📧 Send Email
</button>

{### Use with your own forms

The modal works with **any Symfony form**:

```twig
{% import '@WrapNotificator/widget/modal_button.html.twig' as modal %}

{# With macro #}
{{ modal.custom_button(path('my_form_edit', {id: entity.id}), 'Edit', 'btn btn-warning', 'Edit my entity') }}

{# Or with manual onclick #}
<button 
    type="button" 
    class="btn btn-warning"
    onclick="document.dispatchEvent(new CustomEvent('notify-modal:open', {
        detail: {
            url: '{{ path('my_form_edit', {id: entity.id}) }}',
            title: 'Edit {{ entity.name }}'
        }
    }))"
>
    ✏️ Edit
</button>
```

### Features

✅ **AJAX loading**: Form loads dynamically
✅ **Automatic validation**: Symfony errors display in modal
✅ **AJAX submission**: No page reload
✅ **Auto-close**: Modal closes after success (1.5s)
✅ **Reusable**: Works with all your Symfony forms
✅ **Canvas compatible**: Smart backdrop management
✅ **Automatic theming**: Uses `force_theme` and `toast_theme` settings from your configuration

### Themes and styling

The modal automatically uses the theme settings configured in `config/packages/wrap_notificator.yaml`:

```yaml
wrap_notificator:
    mercure:
        ui:
            force_theme: 'dark'      # 'auto', 'dark' or 'light'
            toast_theme: 'google'    # 'default', 'google', 'amazon', 'dark'
```

- **`force_theme: 'dark'`**: Applies dark theme to modal (black background, white text)
- **`toast_theme: 'google'`**: Applies Google Material Design style (rounded corners, soft shadows)

### Default recipients configuration

For contact forms, you can configure default recipients that will be automatically filled:

```yaml
wrap_notificator:
    default_recipients:
        email: 'contact@mysite.com'
        sms: '+1234567890'
        chat: 'admin-channel'
```

The `recipient` field will be automatically excluded from the form and filled with these values. Users will only see the `sender` field.

### Array fields (JSON)

Some fields like `templateVars`, `options` or `data` are of type `array` in the DTOs. The form automatically displays them as `<textarea>` fields with JSON conversion:

**Example for `templateVars`**:
```json
{"name": "John", "order_id": 123, "total": 99.99}
```

**Automatic handling**:
- **Display**: Array is converted to JSON for editing
- **Validation**: Entered JSON is validated
- **Save**: JSON is converted back to PHP array

Affected fields:
- `templateVars` (Email): Variables for Twig template
- `options` (Chat): Transport options
- `data` (Browser/Push): Notification data

## Troubleshooting

### Form submits but doesn't send email

**Checks to perform**:

1. **Symfony Mailer configuration**:
   ```yaml
   # config/packages/mailer.yaml
   framework:
       mailer:
           dsn: '%env(MAILER_DSN)%'
   ```
   
   ```env
   # .env
   MAILER_DSN=smtp://user:pass@localhost:1025
   # or for Gmail:
   # MAILER_DSN=gmail://username:password@default
   ```

2. **Configure default_recipients**:
   ```yaml
   # config/packages/wrap_notificator.yaml
   wrap_notificator:
       default_recipients:
           email: 'contact@mysite.com'  # Address that will receive contact form emails
       email_template:
           enabled: true  # Enable modern HTML template by default
           template: '@WrapNotificator/email/contact_form.html.twig'  # Template to use
   ```
   
   **Note**: The `sender` field is NOT auto-filled - clients enter their email in the contact form.
   
   **Email template**: By default, contact form emails use a modern HTML template that displays:
   - Sender's email (client)
   - Message subject
   - Message content
   - List of attachments (if any)
   - Date and time received
   - Client IP address
   
   To disable the template and send only raw content:
   ```yaml
   wrap_notificator:
       email_template:
           enabled: false
   ```

3. **Check Symfony logs**:
   ```bash
   tail -f var/log/dev.log
   ```

4. **Test email sending manually**:
   ```php
   // In a test controller
   $this->mailer->send(
       (new Email())
           ->from('test@example.com')
           ->to('contact@mysite.com')
           ->subject('Test')
           ->text('Test email')
   );
   ```

5. **Verify Stimulus controller is loaded**:
   - Open browser console (F12)
   - Check for JavaScript errors
   - File must be in `assets/controllers/notify_modal_controller.js`

### Modal shows homepage after submission

This means the form redirects instead of submitting via AJAX. Check:

1. Stimulus controller intercepts the submit
2. Form has `enctype="multipart/form-data"` attribute
3. Browser console shows no JavaScript errors

### "Sender mismatch" error persists after configuration

If the error persists after configuring `default_senders`, RabbitMQ still has old messages in queue. Solutions:

**Option 1: Purge RabbitMQ queue**
```bash
# Via rabbitmqctl
rabbitmqctl purge_queue asyncRabbitMq

# Or via RabbitMQ web interface
# http://localhost:15672 → Queues → asyncRabbitMq → Purge Messages
```

**Option 2: Wait for consumption**
Let the worker consume all old messages (they will fail) then test with a new message.

**Option 3: Restart worker**
```bash
php bin/console messenger:stop-workers
# Then restart
php bin/console messenger:consume asyncRabbitMq
```

### Attachments security

Attachments are **validated server-side** (on submit) to reduce the risk of malicious uploads.

- **Max count**: 5 files
- **Max size**: 5 MB per file
- **Allowed MIME types**:
  - `application/pdf`
  - `image/png`, `image/jpeg`, `image/gif`
  - `text/plain`
  - `application/msword`
  - `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
  - `application/vnd.ms-excel`
  - `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

If a file does not match these rules, the form will fail validation and the email won't be sent.

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
