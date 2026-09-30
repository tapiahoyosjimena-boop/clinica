<header class="pp-header">
    <a href="{{ route($homeRoute) }}" class="pp-brand">
        <svg class="pp-logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="20" fill="#2EB67D"/>
            <rect x="17.5" y="10" width="5" height="20" rx="2.5" fill="white"/>
            <rect x="10" y="17.5" width="20" height="5" rx="2.5" fill="white"/>
            <text text-anchor="end" x="39" y="38" font-size="12" font-family="Inter,sans-serif" font-weight="800" fill="#C8F135">N</text>
        </svg>
        <div>
            <div class="pp-brand-name">Clínica Norte</div>
            <div class="pp-brand-sub">{{ $brandSub }}</div>
        </div>
    </a>

    <div class="pp-user">
        @auth
            <x-portal-theme-switcher />
            @php
                $_portalUser = auth()->user();
                $_portalBellUnread = $_portalUser->unreadNotifications()->count();
                $_portalBellRecent = $_portalUser->notifications()->latest()->limit(8)->get();
            @endphp
            <x-portal-notification-bell
                :unread-count="$_portalBellUnread"
                :recent="$_portalBellRecent"
                :view-all-route="route($notificationsRoute)"
                :mark-all-read-route="route($notificationsReadAllRoute)"
                :delete-all-route="route($notificationsDeleteAllRoute)"
                :open-route-name="$notificationsOpenRoute"
            />
            <span class="pp-user-name">{{ $userDisplayName }}</span>
            <form method="POST" action="{{ route($logoutRoute) }}" style="margin:0;">
                @csrf
                <button type="submit" class="pp-logout">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h7a1 1 0 1 1 0 2H3a3 3 0 0 1-3-3V4a3 3 0 0 1 3-3h7a1 1 0 1 1 0 2H3Zm11.293 4.293a1 1 0 0 1 1.414 0l3 3a1 1 0 0 1 0 1.414l-3 3a1 1 0 0 1-1.414-1.414L15.586 11H8a1 1 0 1 1 0-2h7.586l-1.293-1.293a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        @endauth
    </div>
</header>
