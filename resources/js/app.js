// Mobile sidebar toggle (sidebar is always visible from the lg breakpoint up)
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        document.getElementById('sidebar')?.classList.toggle('max-lg:hidden');
    }
});
