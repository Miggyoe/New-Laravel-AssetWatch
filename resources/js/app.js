// Mobile sidebar toggle (sidebar is always visible from the lg breakpoint up)
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        document.getElementById('sidebar')?.classList.toggle('max-lg:hidden');
    }

    // Close any open row-action menu when clicking elsewhere
    closeMenus(event.target);
});

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
