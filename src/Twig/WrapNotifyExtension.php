<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WrapNotifyExtension extends AbstractExtension
{
    public function __construct(
        private readonly array $wrap_notificator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wrap_notify_bootstrap', $this->renderBootstrap(...), [ 'is_safe' => [ 'html']]),
            new TwigFunction('wrap_notify_browser', $this->renderBrowser(...), [ 'is_safe' => [ 'html']]),
            new TwigFunction('wrap_notify_system', $this->renderSystem(...), [ 'is_safe' => [ 'html']]),
        ];
    }


    public function renderBootstrap(): string
    {
        if (!$this->wrap_notificator["enabled"]) {
            return '';
        }
        // Expose global helpers and subscribe function; also export from the ES module
        return <<<'HTML'
<script type="module">
    // Subscribe to Mercure topics. Uses EventSource with credentials to support JWT subscriber cookie
    function subscribeMercure(baseUrl, topics = [], onMessage = null, options = {}) {
        try {
            const url = new URL(baseUrl);
            const params = new URLSearchParams(url.search);
            (Array.isArray(topics) ? topics : [topics]).forEach(t => {
                if (t) { params.append('topic', t); }
            });
            url.search = params.toString();

            // Decide credentials: default false; enable by passing options.withCredentials === true
            const withCreds = options && Object.prototype.hasOwnProperty.call(options, 'withCredentials')
                ? !!options.withCredentials
                : false;

            const es = new EventSource(url.toString(), { withCredentials: withCreds });

            // Graceful shutdown on page unload to avoid spurious console errors during refresh/navigation
            let isUnloading = false;
            const closeES = () => { try { es.close(); } catch(_) {} };
            window.addEventListener('pagehide', () => { isUnloading = true; closeES(); });
            window.addEventListener('beforeunload', () => { isUnloading = true; closeES(); });

            es.addEventListener('message', (event) => {
                let payload = event.data;
                try {
                    payload = JSON.parse(event.data);
                } catch (e) {
                    // keep as raw string if not JSON
                }
                // Optionally re-dispatch for consumers
                const detail = { payload, raw: event, topics };
                if (typeof onMessage === 'function') {
                    try { onMessage(payload, event, detail); } catch (e) { console.error(e); }
                }
                window.dispatchEvent(new CustomEvent('wrap:mercure', { detail }));
            });

            es.addEventListener('error', (err) => {
                console.error('[WrapNotificator][Mercure] error:', err);
            });

            return es;
        } catch (e) {
            console.error('[WrapNotificator][Mercure] subscribe error:', e);
            return null;
        }
    }

    function ensureToastContainer(position = 'top-right') {
        let container = document.getElementById('wrap-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'wrap-toast-container';
            document.body.appendChild(container);
        }
        // Apply positioning
        const pos = String(position || 'top-right').toLowerCase();
        // reset
        container.style.top = '';
        container.style.right = '';
        container.style.bottom = '';
        container.style.left = '';
        if (pos === 'top-left') {
            container.style.top = '1rem';
            container.style.left = '1rem';
        } else if (pos === 'bottom-right') {
            container.style.bottom = '1rem';
            container.style.right = '1rem';
        } else if (pos === 'bottom-left') {
            container.style.bottom = '1rem';
            container.style.left = '1rem';
        } else { // top-right default
            container.style.top = '1rem';
            container.style.right = '1rem';
        }
        return container;
    }

    function showBootstrapToast({ title = 'Notification', body = '', delay = 5000, autohide = true, variant = 'info', icon = undefined, iconUrl = undefined, iconHtml = undefined, iconClass = undefined, iconAlt = undefined, density = 'cozy', position = 'top-right', rounded = true, shadow = 'md', glass = false, opacity = 1 } = {}) {
        const clamp = (n, min, max) => Math.max(min, Math.min(max, n));
        delay = clamp(Number(delay) || 5000, 1500, 15000);
        const container = ensureToastContainer(position);

        const variants = {
            info: 'info',
            success: 'success',
            warning: 'warning',
            warn: 'warning',
            danger: 'danger',
            error: 'danger',
            primary: 'primary',
            secondary: 'secondary',
            light: 'light',
            dark: 'dark'
        };
        const v = variants[String(variant||'').toLowerCase()] || 'info';

        // Small inline SVG fallbacks per variant (for when Bootstrap Icons are not available)
        const defaultSvgByVariant = {
            info: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path fill="#fff" d="M8.93 6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM8 4.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/></svg>',
            success: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path fill="#fff" d="M11.03 5.97a.75.75 0 0 0-1.06-1.06L6.75 8.13 6 7.38a.75.75 0 1 0-1.06 1.06l1.25 1.25c.3.3.79.3 1.09 0l3.75-3.75z"/></svg>',
            warning: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7.938 2.016a1.13 1.13 0 0 1 2.02 0l6.857 12.5A1.13 1.13 0 0 1 15.857 16H2.143a1.13 1.13 0 0 1-.958-1.484L7.938 2.016z"/><path fill="#000" d="M8 6v4h1V6H8zm0 5v1h1v-1H8z"/></svg>',
            danger: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path fill="#fff" d="M5.354 5.354a.5.5 0 1 1 .707-.707L8 6.586l1.939-1.939a.5.5 0 1 1 .707.707L8.707 7.293l1.939 1.939a.5.5 0 1 1-.707.707L8 8l-1.939 1.939a.5.5 0 1 1-.707-.707L7.293 7.293 5.354 5.354z"/></svg>'
        };

        const toastEl = document.createElement('div');
        toastEl.className = `toast wrap-toast is-${v} align-items-center border-0 overflow-hidden`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        if (opacity !== 1) toastEl.style.opacity = String(opacity);
        if (glass) toastEl.classList.add('glass');
        if (density === 'compact') toastEl.classList.add('wrap-toast-compact');
        // shadow levels via classes
        if (shadow === 'sm') toastEl.classList.add('wrap-shadow-sm');
        else if (shadow === 'lg') toastEl.classList.add('wrap-shadow-lg');
        // rounded via class
        if (rounded === false) toastEl.classList.add('wrap-rounded-sm');

        // Accent bar (left)
        const accent = document.createElement('div');
        accent.className = 'wrap-toast-accent';

        // Progress bar (top)
        const progress = document.createElement('div');
        progress.className = 'wrap-toast-progress';

        toastEl.innerHTML = `
            <div class="toast-header bg-transparent border-0">
                <strong class="me-auto">${title}</strong>
                <span class="wrap-toast-icon" aria-hidden="true"></span>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">${body}</div>
        `;
        toastEl.prepend(progress);
        toastEl.prepend(accent);
        container.appendChild(toastEl);

        // Fill icon area
        const iconSpan = toastEl.querySelector('.wrap-toast-icon');
        if (iconSpan) {
            const chosenUrl = iconUrl || icon; // allow alias
            if (iconHtml && typeof iconHtml === 'string') {
                iconSpan.innerHTML = iconHtml;
            } else if (iconClass && typeof iconClass === 'string') {
                const i = document.createElement('i');
                i.className = iconClass;
                i.setAttribute('aria-hidden', 'true');
                iconSpan.appendChild(i);
            } else if (chosenUrl && typeof chosenUrl === 'string') {
                const img = document.createElement('img');
                img.src = chosenUrl;
                if (iconAlt) img.alt = String(iconAlt);
                iconSpan.appendChild(img);
            } else {
                // default SVG by variant
                iconSpan.innerHTML = defaultSvgByVariant[v] || defaultSvgByVariant.info;
            }
        }

        // Unified timer with pause/resume on hover
        let rafId = null;
        let timeoutId = null;
        let elapsed = 0;          // accumulated elapsed while running
        let baseStart = 0;        // timestamp when (re)started
        let paused = false;

        const updateProgress = (totalElapsed) => {
            const ratio = clamp(1 - (totalElapsed / delay), 0, 1);
            progress.style.width = (ratio * 100) + '%';
        };

        const animate = (now) => {
            const total = elapsed + (now - baseStart);
            updateProgress(total);
            if (total < delay && !paused) {
                rafId = requestAnimationFrame(animate);
            }
        };

        const doHide = () => {
            if (rafId) cancelAnimationFrame(rafId);
            if (timeoutId) clearTimeout(timeoutId);
            if (window.bootstrap && bootstrap.Toast) {
                try {
                    const instance = bootstrap.Toast.getInstance(toastEl) || new bootstrap.Toast(toastEl, { autohide: false });
                    instance.hide();
                } catch {
                    toastEl.remove();
                }
            } else {
                toastEl.remove();
            }
        };

        const startTimer = () => {
            paused = false;
            baseStart = performance.now();
            rafId = requestAnimationFrame(animate);
            const remaining = Math.max(0, delay - elapsed);
            timeoutId = setTimeout(doHide, remaining);
        };

        const pauseTimer = () => {
            if (paused) return;
            paused = true;
            if (rafId) cancelAnimationFrame(rafId);
            if (timeoutId) clearTimeout(timeoutId);
            elapsed += performance.now() - baseStart;
            updateProgress(elapsed);
        };

        const resumeTimer = () => {
            if (!paused) return;
            startTimer();
        };

        toastEl.addEventListener('mouseenter', pauseTimer);
        toastEl.addEventListener('mouseleave', resumeTimer);

        if (window.bootstrap && bootstrap.Toast) {
            const toast = new bootstrap.Toast(toastEl, { autohide: false });
            toastEl.addEventListener('shown.bs.toast', () => {
                startTimer();
            });
            toastEl.addEventListener('hide.bs.toast', () => {
                if (rafId) cancelAnimationFrame(rafId);
                if (timeoutId) clearTimeout(timeoutId);
            });
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            toast.show();
        } else {
            // Fallback if Bootstrap JS not present: our injected CSS ensures visuals
            startTimer();
        }
    }

    function coerceText(value) {
        if (value == null) return '';
        if (typeof value === 'string') return value;
        try { return JSON.stringify(value); } catch { return String(value); }
    }

    function notifyBrowser(detail) {
        const clamp = (n, min, max) => Math.max(min, Math.min(max, n));
        const payload = detail && detail.payload !== undefined ? detail.payload : null;
        let title = 'Notification';
        let body = '';
        let variant = 'info'; // default to info if no level provided
        let delay = 5000;

        if (payload && typeof payload === 'object') {
            title = payload.title || payload.subject || title;
            body = payload.body || payload.message || payload.text || coerceText(payload.data || payload);
            // Prefer the semantic "level" (or "leve") then fall back to other common keys
            const rawLevel = payload.level ?? payload.leve ?? payload.type ?? payload.variant ?? payload.status ?? payload.severity ?? payload.kind;
            if (typeof rawLevel === 'string') {
                const v = rawLevel.toLowerCase();
                if (v === 'info' || v === 'information') variant = 'info';
                else if (v === 'danger' || v === 'error' || v === 'fail' || v === 'failed') variant = 'danger';
                else if (v === 'warning' || v === 'warn' || v === 'avertissement') variant = 'warning';
                else if (v === 'success' || v === 'ok' || v === 'done' || v === 'valid') variant = 'success';
                else variant = 'info'; // any other value falls back to info
            }
            const rawDelay = payload.delay ?? payload.duration ?? payload.ttl;
            if (rawDelay != null && !Number.isNaN(Number(rawDelay))) {
                delay = clamp(Number(rawDelay), 1500, 15000);
            }
        } else {
            body = coerceText(payload);
        }

        const iconUrl = payload && (payload.iconUrl || payload.icon) ? (payload.iconUrl || payload.icon) : undefined;
        const iconHtml = payload && payload.iconHtml ? payload.iconHtml : undefined;
        const iconClass = payload && payload.iconClass ? payload.iconClass : undefined;
        const iconAlt = payload && (payload.iconAlt || payload.title || payload.subject) ? (payload.iconAlt || payload.title || payload.subject) : undefined;
        // UI options (optional)
        const density = payload && (payload.density || (payload.ui && payload.ui.density)) ? (payload.density || payload.ui.density) : 'cozy';
        const position = payload && (payload.position || (payload.ui && payload.ui.position)) ? (payload.position || payload.ui.position) : 'top-right';
        const rounded = payload && (payload.rounded ?? (payload.ui && payload.ui.rounded)) !== undefined ? (payload.rounded ?? payload.ui.rounded) : true;
        const shadow = payload && (payload.shadow || (payload.ui && payload.ui.shadow)) ? (payload.shadow || payload.ui.shadow) : 'md';
        const glass = payload && (payload.glass ?? (payload.ui && payload.ui.glass)) !== undefined ? (payload.glass ?? payload.ui.glass) : false;
        const opacity = payload && (payload.opacity ?? (payload.ui && payload.ui.opacity)) !== undefined ? Number(payload.opacity ?? payload.ui.opacity) : 1;
        showBootstrapToast({ title: coerceText(title), body: coerceText(body), variant, delay, icon: iconUrl, iconUrl, iconHtml, iconClass, iconAlt, density, position, rounded, shadow, glass, opacity });
    }

    async function requestNotificationPermission() {
        if (!('Notification' in window)) return 'denied';
        if (Notification.permission === 'granted' || Notification.permission === 'denied') {
            return Notification.permission;
        }
        try { return await Notification.requestPermission(); } catch { return 'denied'; }
    }

    async function notifySystem(detail) {
        const payload = detail && detail.payload !== undefined ? detail.payload : null;
        let title = 'Système';
        let body = '';
        let icon = undefined;
        if (payload && typeof payload === 'object') {
            title = payload.title || payload.subject || title;
            body = payload.body || payload.message || payload.text || coerceText(payload.data || payload);
            icon = payload.icon || payload.image || undefined;
        } else {
            body = coerceText(payload);
        }
        const perm = await requestNotificationPermission();
        if (perm === 'granted') {
            try {
                const n = new Notification(coerceText(title), { body: coerceText(body), icon });
                setTimeout(() => n.close(), 8000);
            } catch (e) {
                console.warn('Web Notification failed, fallback to toast', e);
                showBootstrapToast({ title: coerceText(title), body: coerceText(body) });
            }
        } else {
            // Fallback if not granted
            showBootstrapToast({ title: coerceText(title), body: coerceText(body) });
        }
    }

    // expose globally so other inline modules/scripts can reuse it
    window.subscribeMercure = subscribeMercure;
    window.wrapNotify = { showBootstrapToast, notifyBrowser, notifySystem };

    // also export from the module context
    export { subscribeMercure, showBootstrapToast, notifyBrowser, notifySystem };
</script>
HTML;
    }

    /**
     * Standard front listener (user notifications, toasts, messages)
     * @param array<int,string> $topics
     * @throws \JsonException
     */
    public function renderBrowser(array $topics = [], array $options = []): string
    {
        if (!$this->wrap_notificator["enabled"]) {
            return '';
        }

        $baseUrlJs = json_encode($this->wrap_notificator["public_url"], JSON_THROW_ON_ERROR);
        $topicsJs = json_encode(array_values($topics), JSON_THROW_ON_ERROR);
        $turboJs = $this->wrap_notificator["turbo_enabled"] ? 'true' : 'false';

        // Merge provided options with defaults from configuration when not set
        if (!array_key_exists('turbo', $options)) {
            $options['turbo'] = $this->wrap_notificator["turbo_enabled"];
        }
        if (!array_key_exists('withCredentials', $options)) {
            $options['withCredentials'] = (bool)($this->wrap_notificator['with_credentials_default'] ?? false);
        }
        $optionsJs = json_encode($options, JSON_THROW_ON_ERROR);

        return <<<HTML
<script type="module">
    // Ensure bootstrap is present before subscribing
    if (typeof window.subscribeMercure === 'function') {
        window.subscribeMercure({$baseUrlJs}, {$topicsJs}, (payload, event, detail) => {
            // re-emit a more specific event for browser listeners
            detail = detail || { payload, raw: event, topics: {$topicsJs} };
            // Show a Bootstrap toast in the browser
            if (window.wrapNotify?.notifyBrowser) {
                try { window.wrapNotify.notifyBrowser(detail); } catch (e) { console.error(e); }
            }
            // Optional Turbo Stream support
            if ({$turboJs} && window.Turbo && payload && payload.turbo && payload.turbo.stream) {
                try { window.Turbo.renderStreamMessage(payload.turbo.stream); } catch (e) { console.warn('Turbo stream render failed', e); }
            }
            window.dispatchEvent(new CustomEvent('wrap:mercure', { detail }));
        }, {$optionsJs});
    } else {
        console.warn('[WrapNotificator] wrap_notify_bootstrap() must be included before wrap_notify_browser().');
    }
</script>
HTML;
    }

    /**
     * System listener (logs, maintenance, etc.)
     * @param array<int,string> $topics
     */
    public function renderSystem(array $topics = [], array $options = []): string
    {
        if (!$this->wrap_notificator["enabled"]) {
            return '';
        }

        $baseUrlJs = json_encode($this->wrap_notificator["public_url"], JSON_THROW_ON_ERROR);
        $topicsJs = json_encode(array_values($topics), JSON_THROW_ON_ERROR);
        $turboJs = $this->wrap_notificator["turbo_enabled"] ? 'true' : 'false';

        if (!array_key_exists('turbo', $options)) {
            $options['turbo'] = $this->wrap_notificator["turbo_enabled"];
        }
        $optionsJs = json_encode($options, JSON_THROW_ON_ERROR);

        return <<<HTML
<script type="module">
    if (typeof window.subscribeMercure === 'function') {
        window.subscribeMercure({$baseUrlJs}, {$topicsJs}, (payload, event, detail) => {
            detail = detail || { payload, raw: event, topics: {$topicsJs} };
            // OS-level notification via Web Notifications API
            if (window.wrapNotify?.notifySystem) {
                try { window.wrapNotify.notifySystem(detail); } catch (e) { console.error(e); }
            }
            window.dispatchEvent(new CustomEvent('wrap:mercure:system', { detail }));
        }, {$optionsJs});
    } else {
        console.warn('[WrapNotificator] wrap_notify_bootstrap() must be included before wrap_notify_system().');
    }
</script>
HTML;
    }
}
