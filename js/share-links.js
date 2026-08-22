(function () {
    'use strict';

    async function copyUrl(input) {
        if (
            navigator.clipboard
            && window.isSecureContext
        ) {
            await navigator.clipboard.writeText(input.value);
            return;
        }

        input.focus();
        input.select();

        if (!document.execCommand('copy')) {
            throw new Error('clipboard');
        }

        window.getSelection().removeAllRanges();
    }

    async function runAction(button) {
        const controls = button.closest('.bitacora-share-controls');

        if (!controls) {
            return;
        }

        const endpoint = controls.dataset.endpoint;
        const postId = controls.dataset.postId;
        const nonce = controls.dataset.nonce;
        const action = button.dataset.action;
        const input = controls.querySelector('.bitacora-share-url');
        const createButton = controls.querySelector('.bitacora-share-create');
        const revokeButton = controls.querySelector('.bitacora-share-revoke');
        const status = controls.querySelector('.bitacora-share-status');

        if (
            !endpoint
            || !postId
            || !nonce
            || !action
            || !input
            || !createButton
            || !revokeButton
            || !status
        ) {
            return;
        }

        createButton.disabled = true;
        revokeButton.disabled = true;
        status.textContent = '';

        const body = new URLSearchParams();
        body.set('action', action);
        body.set('post_id', postId);
        body.set('_wpnonce', nonce);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: body.toString()
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                const message = (
                    result
                    && result.data
                    && result.data.message
                )
                    ? result.data.message
                    : 'No fue posible completar la acción.';

                throw new Error(message);
            }

            if (action === 'bitacora_create_share_link') {
                input.value = result.data.url || '';
                createButton.textContent = 'Copiar enlace';

                try {
                    await copyUrl(input);
                    status.textContent =
                        'Enlace copiado. Podés enviarlo a quien deba ver este contenido.';
                } catch (error) {
                    status.textContent =
                        'Enlace creado. Copialo desde el campo.';
                }
            }

            if (action === 'bitacora_revoke_share_link') {
                input.value = '';
                createButton.textContent = 'Compartir';
                status.textContent = 'Enlace eliminado.';
            }
        } catch (error) {
            status.textContent = error.message || 'No fue posible completar la acción.';
        } finally {
            createButton.disabled = false;
            revokeButton.disabled = input.value === '';
        }
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest(
            '.bitacora-share-create, .bitacora-share-revoke'
        );

        if (!button) {
            return;
        }

        event.preventDefault();
        runAction(button);
    });
}());
