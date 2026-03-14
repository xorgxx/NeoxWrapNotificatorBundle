import { Controller } from '@hotwired/stimulus';

/**
 * Controleur Stimulus pour modale de notification generique
 * 
 * Installation :
 * 1. Copiez ce fichier dans : assets/controllers/notify_modal_controller.js
 * 2. Stimulus detectera automatiquement le controleur
 * 3. Incluez la modale dans votre base.html.twig :
 *    {% include '@WrapNotificator/widget/modal_stimulus.html.twig' %}
 * 
 * Compatible avec tous les formulaires Symfony (bundle + projet)
 * Gere le chargement AJAX, la validation, et la fermeture automatique
 */
export default class extends Controller {
    static targets = ['title', 'body'];

    connect() {
        this.modal = new bootstrap.Modal(this.element);
        document.addEventListener('notify-modal:open', this.handleOpen.bind(this));
        this.element.addEventListener('hidden.bs.modal', this.cleanup.bind(this));
    }

    disconnect() {
        document.removeEventListener('notify-modal:open', this.handleOpen.bind(this));
    }

    handleOpen(event) {
        const { url, title } = event.detail;
        
        if (title) {
            this.titleTarget.textContent = title;
        }
        
        if (url) {
            this.loadContent(url);
        }
        
        this.modal.show();
    }

    async loadContent(url) {
        this.showSpinner();

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const html = await response.text();
            this.bodyTarget.innerHTML = html;

            this.interceptFormSubmit();
            this.observeSuccess();

        } catch (error) {
            this.showError('Erreur lors du chargement du formulaire.');
            console.error('[NotifyModal]', error);
        }
    }

    interceptFormSubmit() {
        const form = this.bodyTarget.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitButton = form.querySelector('[type="submit"]');
            
            if (submitButton) {
                submitButton.disabled = true;
                const originalText = submitButton.textContent;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';
                submitButton.setAttribute('data-original-text', originalText);
            }

            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const html = await response.text();
                
                if (html.includes('<form')) {
                    this.bodyTarget.innerHTML = html;
                    this.interceptFormSubmit();
                    this.observeSuccess();
                } else {
                    this.bodyTarget.innerHTML = html;
                    this.observeSuccess();
                }

            } catch (error) {
                this.showError('Erreur lors de l envoi du formulaire.');
                console.error('[NotifyModal]', error);
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'Envoyer';
                }
            }
        });
    }

    observeSuccess() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('alert-success')) {
                        setTimeout(() => {
                            this.modal.hide();
                        }, 1500);
                    }
                });
            });
        });

        observer.observe(this.bodyTarget, {
            childList: true,
            subtree: true
        });
    }

    showSpinner() {
        this.bodyTarget.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
    }

    showError(message) {
        this.bodyTarget.innerHTML = '<div class="alert alert-danger">' + message + '</div>';
    }

    cleanup() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const hasOtherModal = document.querySelector('.modal.show:not(#notifyModal)');
        
        if (!hasOtherModal) {
            backdrops.forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.body.removeAttribute('data-bs-overflow');
            document.body.removeAttribute('data-bs-padding-right');
        }
    }
}