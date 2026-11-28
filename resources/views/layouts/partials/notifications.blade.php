<div class="relative" id="tracker-notifications" data-index-url="{{ route('tracker.notifications.index') }}" data-read-url="{{ route('tracker.notifications.read') }}" data-trips-url="{{ route('tracker.locations.index') }}">
    <button id="notification-toggle" type="button" class="relative inline-flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" aria-label="Notifications" aria-expanded="false" aria-controls="notification-panel">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
        </svg>
        <span id="notification-count" class="absolute -right-1 -top-1 hidden min-w-5 rounded-full bg-rose-600 px-1 text-center text-[10px] font-bold leading-5 text-white">0</span>
    </button>

    <section id="notification-panel" class="absolute right-0 z-50 mt-3 hidden w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl" aria-label="Vehicle notifications">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Notifications</h2>
                <p class="text-xs text-slate-500">Parking and trip updates</p>
            </div>
            <button id="notification-mark-read" type="button" class="text-xs font-semibold text-blue-700 hover:text-blue-900">Mark all read</button>
        </div>
        <div id="notification-list" class="max-h-96 divide-y divide-slate-100 overflow-y-auto" aria-live="polite">
            <p class="px-4 py-6 text-center text-sm text-slate-500">Loading notifications…</p>
        </div>
    </section>
</div>

<div id="notification-toast-region" class="pointer-events-none fixed right-4 top-20 z-[60] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2" aria-live="polite" aria-atomic="false"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('tracker-notifications');
        const toggle = document.getElementById('notification-toggle');
        const panel = document.getElementById('notification-panel');
        const count = document.getElementById('notification-count');
        const list = document.getElementById('notification-list');
        const markRead = document.getElementById('notification-mark-read');
        const toastRegion = document.getElementById('notification-toast-region');
        if (!root || !toggle || !panel || !count || !list) return;
        let latestNotificationId = null;

        function notificationUrl(notification) {
            return notification.tracker_device_id
                ? `${root.dataset.tripsUrl}#vehicle-trip-${notification.tracker_device_id}`
                : `${root.dataset.tripsUrl}#vehicle-trips`;
        }

        function showNotificationToast(notification) {
            if (!toastRegion) return;

            const toast = document.createElement('a');
            toast.href = notificationUrl(notification);
            toast.className = 'pointer-events-auto block rounded-xl border border-slate-200 bg-white p-4 text-left shadow-xl shadow-slate-900/15 transition hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-200';

            const title = document.createElement('p');
            title.className = 'text-sm font-semibold text-slate-900';
            title.textContent = notification.title;

            const message = document.createElement('p');
            message.className = 'mt-1 text-xs leading-5 text-slate-600';
            message.textContent = notification.message;

            const vehicle = document.createElement('p');
            vehicle.className = 'mt-2 text-[11px] font-medium text-indigo-700';
            vehicle.textContent = `${notification.device_name} · Click to open trip controls`;

            toast.append(title, message, vehicle);
            toastRegion.prepend(toast);
            window.setTimeout(() => toast.remove(), 12000);
        }

        async function refreshNotifications() {
            try {
                const response = await fetch(root.dataset.indexUrl, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const payload = await response.json();
                const unread = Number(payload.unread_count || 0);
                count.textContent = unread > 99 ? '99+' : String(unread);
                count.classList.toggle('hidden', unread === 0);

                const notifications = payload.notifications || [];
                const notificationIds = notifications.map(notification => Number(notification.id)).filter(Number.isFinite);
                const newestNotificationId = notificationIds.length ? Math.max(...notificationIds) : 0;
                if (latestNotificationId === null) {
                    latestNotificationId = newestNotificationId;
                } else if (newestNotificationId > latestNotificationId) {
                    notifications
                        .filter(notification => Number(notification.id) > latestNotificationId)
                        .sort((first, second) => Number(first.id) - Number(second.id))
                        .slice(-3)
                        .forEach(showNotificationToast);
                    latestNotificationId = newestNotificationId;
                }

                list.replaceChildren();
                if (!notifications.length) {
                    const empty = document.createElement('p');
                    empty.className = 'px-4 py-6 text-center text-sm text-slate-500';
                    empty.textContent = 'No notifications yet.';
                    list.appendChild(empty);
                    return;
                }

                notifications.forEach(notification => {
                    const item = document.createElement('a');
                    item.href = notificationUrl(notification);
                    item.className = `block px-4 py-3 transition hover:bg-slate-50 focus:bg-slate-50 focus:outline-none ${notification.read ? 'bg-white' : 'bg-blue-50/70'}`;
                    const title = document.createElement('p');
                    title.className = 'text-sm font-semibold text-slate-900';
                    title.textContent = notification.title;
                    const message = document.createElement('p');
                    message.className = 'mt-1 text-xs leading-5 text-slate-600';
                    message.textContent = notification.message;
                    const timestamp = document.createElement('p');
                    timestamp.className = 'mt-1 text-[11px] text-slate-400';
                    timestamp.textContent = `${notification.device_name} · ${notification.created_at}`;
                    item.append(title, message, timestamp);
                    list.appendChild(item);
                });
            } catch (error) {
                list.textContent = 'Notifications could not be loaded.';
            }
        }

        toggle.addEventListener('click', async () => {
            const isOpening = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !isOpening);
            toggle.setAttribute('aria-expanded', String(isOpening));
            if (isOpening) {
                await fetch(root.dataset.readUrl, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' }
                });
                await refreshNotifications();
            }
        });

        markRead?.addEventListener('click', async () => {
            await fetch(root.dataset.readUrl, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' }
            });
            await refreshNotifications();
        });

        document.addEventListener('click', event => {
            if (!root.contains(event.target)) {
                panel.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        refreshNotifications();
        window.setInterval(refreshNotifications, 5000);
    });
</script>
