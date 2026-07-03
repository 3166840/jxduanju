<?php
$footerIcpNumber = trim((string) ($site_config['icp_number'] ?? ''));
?>
<?php if (empty($isAdminView) && $footerIcpNumber !== ''): ?>
    <footer class="client-footer">
        <span><?= htmlspecialchars($footerIcpNumber) ?></span>
    </footer>
<?php endif; ?>
</div>
<script>
(() => {
    document.addEventListener('error', (event) => {
        const image = event.target instanceof HTMLImageElement ? event.target : null;
        if (!image || image.dataset.fallbackApplied === '1') {
            return;
        }

        image.dataset.fallbackApplied = '1';
        const card = image.closest('a, .card, .row-card, .drama-card');
        const title = image.dataset.fallbackTitle || image.alt || card?.querySelector('h1, h2, h3, strong')?.textContent || '精秀短剧';
        const palette = ['#1f2937', '#334155', '#374151', '#0f766e', '#7c2d12', '#581c87', '#9f1239'];
        let hash = 0;
        for (const char of title) {
            hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
        }
        const bg = palette[hash % palette.length];
        const safeTitle = title.trim().slice(0, 18) || '精秀短剧';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="800" viewBox="0 0 600 800"><rect width="600" height="800" fill="${bg}"/><rect x="34" y="34" width="532" height="732" rx="44" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.20)" stroke-width="2"/><text x="50%" y="46%" fill="#fff" font-size="44" font-weight="800" text-anchor="middle" font-family="PingFang SC, Microsoft YaHei, Arial">${safeTitle.replace(/[&<>"']/g, '')}</text><text x="50%" y="55%" fill="rgba(255,255,255,.72)" font-size="24" text-anchor="middle" font-family="Arial">JINGXIU DRAMA</text></svg>`;
        image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }, true);

    const loader = document.querySelector('[data-page-loader]');
    const loaderText = document.querySelector('[data-page-loader-text]');
    if (!loader) {
        return;
    }

    let hideTimer = null;
    let submitLockTimer = null;
    const defaultText = loaderText?.textContent || '页面加载中...';

    const setText = (text) => {
        if (loaderText) {
            loaderText.textContent = text || defaultText;
        }
    };
    const show = (text = defaultText) => {
        window.clearTimeout(hideTimer);
        setText(text);
        loader.classList.add('is-visible');
        loader.setAttribute('aria-busy', 'true');
        document.body.classList.add('is-page-loading');
    };
    const hide = () => {
        window.clearTimeout(hideTimer);
        loader.classList.remove('is-visible');
        loader.setAttribute('aria-busy', 'false');
        document.body.classList.remove('is-page-loading');
        hideTimer = window.setTimeout(() => setText(defaultText), 260);
    };

    window.JXPageLoader = { show, hide };
    globalThis.JXPageLoader = window.JXPageLoader;

    const hideWhenReady = () => {
        window.setTimeout(hide, 180);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideWhenReady, { once: true });
    } else {
        hideWhenReady();
    }
    window.addEventListener('pageshow', hide);
    const showOnLeaving = () => {
        if (!loader.classList.contains('is-visible')) {
            show(defaultText);
        }
    };
    window.addEventListener('pagehide', showOnLeaving);
    window.addEventListener('beforeunload', showOnLeaving);

    const isModifiedClick = (event) => event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
    const shouldSkipLink = (link, event) => {
        if (!link || event.defaultPrevented || isModifiedClick(event) || link.closest('[data-no-page-loader]') || link.hasAttribute('download')) {
            return true;
        }
        const target = (link.getAttribute('target') || '').trim();
        const rawHref = (link.getAttribute('href') || '').trim();
        if (target && target !== '_self') {
            return true;
        }
        if (rawHref === '' || rawHref === '#' || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) {
            return true;
        }

        let url;
        try {
            url = new URL(rawHref, window.location.href);
        } catch (error) {
            return true;
        }

        if (url.origin !== window.location.origin) {
            return true;
        }
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash !== window.location.hash) {
            return true;
        }

        return false;
    };

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (shouldSkipLink(link, event)) {
            return;
        }

        show('正在打开...');
        window.setTimeout(() => {
            if (event.defaultPrevented) {
                hide();
            }
        }, 0);
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || form.closest('[data-no-page-loader]') || form.dataset.noPageLoader !== undefined) {
            return;
        }
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        const isAdminPost = document.body.classList.contains('is-admin') && method === 'post';
        if (isAdminPost) {
            const token = document.body.dataset.csrfToken || '';
            if (token) {
                let input = form.querySelector('input[name="csrf_token"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    form.appendChild(input);
                }
                input.value = token;
            }
        }
        if (!form.checkValidity()) {
            return;
        }

        show('正在提交...');
        const submitter = event.submitter instanceof HTMLButtonElement || event.submitter instanceof HTMLInputElement
            ? event.submitter
            : form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitter && !submitter.disabled) {
            submitter.dataset.pageLoaderDisabled = '1';
            submitter.disabled = true;
        }
        window.setTimeout(() => {
            if (event.defaultPrevented || !form.checkValidity()) {
                hide();
                form.querySelectorAll('[data-page-loader-disabled="1"]').forEach((button) => {
                    button.disabled = false;
                    delete button.dataset.pageLoaderDisabled;
                });
                return;
            }

            window.clearTimeout(submitLockTimer);
            submitLockTimer = window.setTimeout(() => {
                form.querySelectorAll('[data-page-loader-disabled="1"]').forEach((button) => {
                    button.disabled = false;
                    delete button.dataset.pageLoaderDisabled;
                });
            }, 12000);
        }, 0);
    }, true);
})();
</script>
</body>
</html>
