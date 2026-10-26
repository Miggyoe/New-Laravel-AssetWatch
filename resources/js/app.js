// ---- Mobile sidebar toggle (sidebar is always visible from the lg breakpoint up)
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        document.getElementById('sidebar')?.classList.toggle('max-lg:hidden');
    }

    // Close any open row-action menu when clicking elsewhere
    closeMenus(event.target);

    // ---- Modals: <dialog data-modal id="..."> opened by [data-modal-open="id"], closed by [data-modal-close]
    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
        document.getElementById(opener.dataset.modalOpen)?.showModal();
        return;
    }
    if (event.target.closest('[data-modal-close]')) {
        event.target.closest('dialog')?.close();
        return;
    }
    // The dialog has no padding of its own, so a click on the dialog element itself is a backdrop click
    if (event.target.matches?.('dialog[data-modal]')) {
        event.target.close();
    }
});

document.querySelectorAll('dialog[data-modal][data-autoopen]').forEach((dialog) => dialog.showModal());

function closeMenus(except = null) {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        if (!except || !menu.contains(except)) menu.removeAttribute('open');
    });
}

// Row-action menus ("•••"): pin the panel to the screen so the scrollable table can't clip it.
// "toggle" does not bubble, so listen in the capture phase.
document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!details.matches?.('details[data-menu]') || !details.open) return;

    const panel = details.querySelector('[data-menu-panel]');
    const box = details.getBoundingClientRect();
    panel.style.position = 'fixed';
    panel.style.right = 'auto';
    panel.style.top = `${box.bottom + 4}px`;
    panel.style.left = `${Math.max(8, box.right - panel.offsetWidth)}px`;
}, true);

window.addEventListener('scroll', () => closeMenus(), true);
