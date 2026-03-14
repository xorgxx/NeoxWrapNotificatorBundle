# Widget Twig

Le bundle inclut un widget Twig prêt à l'emploi pour afficher des formulaires de notification n'importe où dans votre application.

## Utilisation simple (formulaire inline)

Dans n'importe quel template Twig, appelez la fonction `wrap_notify_form` :

```twig
{# Rendre un formulaire de notification SMS #}
{{ wrap_notify_form('sms') }}

{# Rendre un formulaire de notification Email #}
{{ wrap_notify_form('email') }}
```

Types disponibles : `email`, `sms`, `chat`, `browser`, `push`.

## Utilisation avec modale Stimulus (recommandé)

Pour une expérience utilisateur moderne, utilisez le système de modale Stimulus avec chargement AJAX.

### Prérequis

- Bootstrap 5 (CSS + JS)
- Stimulus (`npm install @hotwired/stimulus`)

### Étape 1 : Inclure la modale dans votre layout

**IMPORTANT** : Ajoutez cette ligne **UNE SEULE FOIS** dans votre `templates/base.html.twig`, juste avant `</body>` :

```twig
{# Modale Stimulus pour notifications - OBLIGATOIRE #}
{% include '@WrapNotificator/widget/modal_stimulus.html.twig' %}
```

**Exemple complet de base.html.twig** :

```twig
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {% block body %}{% endblock %}
    
    {# Modale générique - UNE SEULE FOIS #}
    {% include '@WrapNotificator/widget/modal_stimulus.html.twig' %}
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{ encore_entry_script_tags('app') }}
</body>
</html>
```

### Étape 2 : Créer le contrôleur Stimulus

**Copiez** le fichier `Docs/examples/notify_modal_controller.js` du bundle vers votre projet :

```bash
cp vendor/xorgxx/wrap-notificator-bundle/Docs/examples/notify_modal_controller.js assets/controllers/
```

Stimulus détectera automatiquement le contrôleur.

### Optionnel : Configurer la validation des pièces jointes (sécurité)

Les pièces jointes email sont validées côté serveur. Vous pouvez configurer les limites via :

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

### Étape 3 : Utiliser la modale

#### Option A : Avec la macro Twig (recommandé)

```twig
{% import '@WrapNotificator/widget/modal_button.html.twig' as modal %}

{# Formulaire simple (recommandé pour contact) - masque les champs techniques #}
{{ modal.button('email', 'Envoyer Email', 'btn btn-primary') }}
{# Affiche : sender (email du client), subject, content #}
{# Masque : recipient (auto-rempli), template, templateVars, isHtml, attachments #}

{# Formulaire avec pièces jointes - IMPORTANT : utiliser les paramètres positionnels #}
{{ modal.button('email', 'Email avec fichiers', 'btn btn-primary', 'Envoyer avec pièces jointes', null, true) }}
{# Paramètres : type, text, class, title, exclude, attached #}
{# Le 6ème paramètre (attached) = true affiche le champ attachments #}
{# Affiche : subject, content, attachments #}
{# Masque : sender (auto), recipient (auto) #}

{# Formulaire complet - affiche TOUS les champs #}
{{ modal.button('email', 'Email avance', 'btn btn-primary', 'Email avec template', '') }}
{# Le 5ème paramètre '' = chaîne vide = aucune exclusion #}
{# Affiche : sender, recipient, subject, content, template, templateVars, isHtml #}

{# Exclusion personnalisée #}
{{ modal.button('email', 'Email custom', 'btn btn-primary', 'Email', 'template,isHtml') }}
{# Masque uniquement template et isHtml #}
{# Affiche : sender, recipient, subject, content, templateVars #}

### Bouton style transparent (texte)

```twig
{{ modal.button({type: 'email', text: 'Contact', class: 'btn btn-link'}) }}
```

### Bouton style transparent (icône uniquement)

```twig
{{ modal.button({type: 'email', text: 'Contact', class: 'btn btn-link', icon_class: 'bi bi-envelope'}) }}
```

**Paramètres de la macro `button(type, text, class, title, exclude, attached)` :**

**Syntaxe** : Vous pouvez utiliser des paramètres positionnels OU nommés avec la notation hash `{...}` :

1. **`type`** (obligatoire) : Type de notification (`email`, `sms`, `chat`)
2. **`text`** (optionnel) : Texte du bouton (défaut: `'Notification'`)
3. **`class`** (optionnel) : Classes CSS du bouton (défaut: `'btn btn-primary'`)
4. **`title`** (optionnel) : Titre de la modale (défaut: texte du bouton)
5. **`exclude`** (optionnel) : Champs à masquer, séparés par des virgules
   - **Défaut** : `'recipient,template,templateVars,isHtml,attachments'` (formulaire simple)
   - Le champ `sender` est visible par défaut pour que le client saisisse son email
   - **Chaîne vide `''`** : Aucune exclusion (tous les champs)
   - **Personnalisé** : `'field1,field2'` (masque uniquement ces champs)
   - **`null`** : Utilise la valeur par défaut
6. **`attached`** (optionnel, email uniquement) : Afficher le champ pièces jointes
   - **`false`** ou non défini : Masque le champ attachments (défaut)
   - **`true`** : Affiche le champ attachments pour joindre des fichiers
7. **`icon_class`** (optionnel) : Classe CSS de l'icône (défaut: `''`)
8. **`icon_only`** (optionnel) : Bouton icône uniquement (défaut: `false`)

### Optionnel : reCAPTCHA v3 (KarserRecaptcha3Bundle)

Si vous ajoutez `Recaptcha3Type` au formulaire (par exemple via `GenericNotificationType`), assurez-vous que :

- Le form theme Twig est activé :

```yaml
# config/packages/twig.yaml
twig:
  form_themes:
    - '@KarserRecaptcha3/Form/karser_recaptcha3_widget.html.twig'
```

- Si le formulaire est chargé en AJAX et injecté dans le DOM (modale), les `<script>` inclus dans le HTML injecté doivent être exécutés après l'injection (sinon `grecaptcha.execute()` ne s'exécutera pas et le token restera vide).

**Exemples avec paramètres positionnels** :
```twig
{# Formulaire simple #}
{{ modal.button('email', 'Contact', 'btn btn-primary') }}

{# Avec pièces jointes - utiliser null pour garder exclude par défaut #}
{{ modal.button('email', 'Email avec fichiers', 'btn btn-primary', 'Contact', null, true) }}

{# Tous les champs sauf attachments #}
{{ modal.button('email', 'Email complet', 'btn btn-primary', 'Email', '', false) }}
```

**Exemples avec paramètres nommés (hash)** :
```twig
{# Formulaire simple #}
{{ modal.button({type: 'email', text: 'Contact', class: 'btn btn-primary'}) }}

{# Avec pièces jointes #}
{{ modal.button({type: 'email', text: 'Email avec fichiers', class: 'btn btn-primary', attached: true}) }}

{# Personnalisé #}
{{ modal.button({type: 'email', text: 'Email', title: 'Formulaire contact', exclude: 'template', attached: true}) }}
```

**Note** : Le champ `recipient` est automatiquement rempli depuis `default_recipients` dans la configuration.

#### Option B : Avec onclick manuel

```twig
{# Bouton pour ouvrir la modale avec formulaire Email #}
<button 
    type="button" 
    class="btn btn-primary"
    onclick="document.dispatchEvent(new CustomEvent('notify-modal:open', {
        detail: {
            url: '{{ path('wrap_notificator_form', {type: 'email'}) }}',
            title: 'Envoyer un Email'
        }
    }))"
>
    📧 Envoyer Email
</button>
```

### Fonctionnalités

✅ **Chargement AJAX** : Le formulaire se charge dynamiquement
✅ **Validation automatique** : Les erreurs Symfony s'affichent dans la modale
✅ **Soumission AJAX** : Pas de rechargement de page
✅ **Fermeture automatique** : La modale se ferme après succès (1.5s)
✅ **Réutilisable** : Fonctionne avec tous vos formulaires Symfony
✅ **Compatible Canvas** : Gestion intelligente du backdrop
✅ **Thème automatique** : Utilise les paramètres `force_theme` et `toast_theme` de votre configuration

### Thèmes et styles

La modale utilise automatiquement les paramètres de thème configurés dans `config/packages/wrap_notificator.yaml` :

```yaml
wrap_notificator:
    mercure:
        ui:
            force_theme: 'dark'      # 'auto', 'dark' ou 'light'
            toast_theme: 'google'    # 'default', 'google', 'amazon', 'dark'
```

- **`force_theme: 'dark'`** : Applique un thème sombre à la modale (fond noir, texte blanc)
- **`toast_theme: 'google'`** : Applique le style Google Material Design (coins arrondis, ombres douces)

### Configuration des destinataires par défaut

Pour les formulaires de contact, vous pouvez configurer des destinataires par défaut qui seront automatiquement remplis :

```yaml
wrap_notificator:
    default_recipients:
        email: 'contact@monsite.fr'
        sms: '+33612345678'
        chat: 'admin-channel'
```

Le champ `recipient` sera automatiquement exclu du formulaire et rempli avec ces valeurs. L'utilisateur ne verra que le champ `sender` (expéditeur).

### Champs de type array (JSON)

Certains champs comme `templateVars`, `options` ou `data` sont de type `array` dans les DTOs. Le formulaire les affiche automatiquement comme des champs `<textarea>` avec conversion JSON :

**Exemple pour `templateVars`** :
```json
{"name": "John", "order_id": 123, "total": 99.99}
```

**Fonctionnement automatique** :
- **Affichage** : L'array est converti en JSON pour être éditable
- **Validation** : Le JSON saisi est validé
- **Sauvegarde** : Le JSON est reconverti en array PHP

Les champs concernés :
- `templateVars` (Email) : Variables pour le template Twig
- `options` (Chat) : Options du transport
- `data` (Browser/Push) : Données de la notification

## Dépannage

### Le formulaire se soumet mais n'envoie pas l'email

**Vérifications à effectuer** :

1. **Configuration Mailer Symfony** :
   ```yaml
   # config/packages/mailer.yaml
   framework:
       mailer:
           dsn: '%env(MAILER_DSN)%'
   ```
   
   ```env
   # .env
   MAILER_DSN=smtp://user:pass@localhost:1025
   # ou pour Gmail:
   # MAILER_DSN=gmail://username:password@default
   ```

2. **Configuration default_recipients** :
   ```yaml
   # config/packages/wrap_notificator.yaml
   wrap_notificator:
       default_recipients:
           email: 'contact@monsite.fr'  # Adresse qui recevra les emails du formulaire de contact
       email_template:
           enabled: true  # Active le template HTML moderne par défaut
           template: '@WrapNotificator/email/contact_form.html.twig'  # Template à utiliser
   ```
   
   **Note** : Le champ `sender` n'est PAS auto-rempli - c'est le client qui saisit son email dans le formulaire de contact.
   
   **Template email** : Par défaut, les emails de contact utilisent un template HTML moderne qui affiche :
   - Email de l'expéditeur (client)
   - Sujet du message
   - Contenu du message
   - Liste des pièces jointes (si présentes)
   - Date et heure de réception
   - Adresse IP du client
   
   Pour désactiver le template et envoyer uniquement le contenu brut :
   ```yaml
   wrap_notificator:
       email_template:
           enabled: false
   ```

3. **Vérifier les logs Symfony** :
   ```bash
   tail -f var/log/dev.log
   ```

4. **Tester l'envoi d'email manuellement** :
   ```php
   // Dans un contrôleur de test
   $this->mailer->send(
       (new Email())
           ->from('test@example.com')
           ->to('contact@monsite.fr')
           ->subject('Test')
           ->text('Test email')
   );
   ```

5. **Vérifier que le Stimulus controller est chargé** :
   - Ouvrez la console navigateur (F12)
   - Vérifiez qu'il n'y a pas d'erreurs JavaScript
   - Le fichier doit être dans `assets/controllers/notify_modal_controller.js`

### La modale affiche la page d'accueil après soumission

Cela signifie que le formulaire redirige au lieu de soumettre en AJAX. Vérifiez :

1. Le Stimulus controller intercepte bien le submit
2. Le formulaire a l'attribut `enctype="multipart/form-data"`
3. La console navigateur ne montre pas d'erreurs JavaScript

### Erreur "Sender mismatch" persiste après configuration

Si l'erreur persiste après avoir configuré `default_senders`, c'est que RabbitMQ a encore des anciens messages en queue. Solutions :

**Option 1 : Purger la queue RabbitMQ**
```bash
# Via rabbitmqctl
rabbitmqctl purge_queue asyncRabbitMq

# Ou via l'interface web RabbitMQ
# http://localhost:15672 → Queues → asyncRabbitMq → Purge Messages
```

**Option 2 : Attendre la consommation**
Laissez le worker consommer tous les anciens messages (ils échoueront) puis testez avec un nouveau message.

**Option 3 : Redémarrer le worker**
```bash
php bin/console messenger:stop-workers
# Puis relancer
php bin/console messenger:consume asyncRabbitMq
```

### Sécurité des pièces jointes

Les pièces jointes sont **validées côté serveur** (au submit) pour limiter les contenus potentiellement malveillants.

- **Nombre max** : 5 fichiers
- **Taille max** : 5 MB par fichier
- **Types autorisés (MIME)** :
  - `application/pdf`
  - `image/png`, `image/jpeg`, `image/gif`
  - `text/plain`
  - `application/msword`
  - `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
  - `application/vnd.ms-excel`
  - `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

Si un fichier ne respecte pas ces règles, le formulaire sera en erreur et l'envoi sera bloqué.

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
