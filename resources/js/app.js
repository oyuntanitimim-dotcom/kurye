import './bootstrap';

function openDialog(dialogId) {
    const el = document.getElementById(dialogId);
    if (!el) return;
    if (typeof el.showModal === 'function') {
        el.showModal();
    } else {
        // Fallback: if <dialog> unsupported, degrade gracefully
        el.setAttribute('open', 'open');
    }
}

function closeDialog(dialogId) {
    const el = document.getElementById(dialogId);
    if (!el) return;
    if (typeof el.close === 'function') {
        el.close();
    } else {
        el.removeAttribute('open');
    }
}

document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-dialog-open]');
    if (openBtn) {
        e.preventDefault();
        openDialog(openBtn.getAttribute('data-dialog-open'));
        return;
    }

    const closeBtn = e.target.closest('[data-dialog-close]');
    if (closeBtn) {
        e.preventDefault();
        closeDialog(closeBtn.getAttribute('data-dialog-close'));
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const openDialogs = document.querySelectorAll('dialog[open]');
    const last = openDialogs[openDialogs.length - 1];
    if (last?.id) closeDialog(last.id);
});

if (window.__openFirmCreateDialog) {
    openDialog('firm-create');
}
