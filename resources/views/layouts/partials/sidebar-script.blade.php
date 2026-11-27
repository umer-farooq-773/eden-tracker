<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const openBtn = document.getElementById('btn-open-sidebar');
        const closeBtn = document.getElementById('btn-close-sidebar');

        function setSidebar(open) {
            sidebar.classList.toggle('-translate-x-full', !open);
            sidebar.classList.toggle('invisible', !open);
            backdrop.classList.toggle('hidden', !open);
            openBtn.setAttribute('aria-expanded', String(open));
        }

        openBtn.addEventListener('click', () => setSidebar(true));
        closeBtn.addEventListener('click', () => setSidebar(false));
        backdrop.addEventListener('click', () => setSidebar(false));
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') setSidebar(false);
        });
    });
</script>
