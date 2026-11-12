<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    if (localStorage.getItem('sidebarExpanded') === '1') {
        document.documentElement.classList.add('sidebar-expanded');
    }
</script>
    <title>@yield('title', 'Purchasing')</title>
    <script src="https://cdn.tailwindcss.com"></script>
<style>
        html, body { overflow: hidden; height: 100%; }

        .sidebar-icon { padding-left: 28px; }
        .label {
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-width 300ms ease, opacity 200ms ease;
        }
        html.sidebar-expanded .label { max-width: 160px; opacity: 1; }

        #sidebar { width: 80px; }
        html.sidebar-expanded #sidebar { width: 16rem; }

        #mainContent { margin-left: 80px; }
        html.sidebar-expanded #mainContent { margin-left: 16rem; }

        /* Profile dropdown: compact presentation while the sidebar is collapsed. */
        html:not(.sidebar-expanded) #userMenu .signout-label { display: none; }
        html:not(.sidebar-expanded) #userMenu form button { justify-content: center; }
        html:not(.sidebar-expanded) .user-caret { display: none; }
        html.sidebar-expanded .sidebar-user { padding-right: 12px; }
        html.sidebar-expanded .sidebar-user .label { max-width: 130px; }

        /*
         * Success flash overlay.
         * Transparent, non-blocking layer pinned to the exact rectangle the
         * old inline success banner used to occupy: content top (header
         * 5rem + main padding 2rem) and the content column's side padding.
         * Because it is fixed, showing/hiding it never shifts the page.
         */
        .flash-success-overlay {
            position: fixed;
            top: 7rem;
            left: calc(5rem + 2rem);
            right: 2rem;
            z-index: 60;
            pointer-events: none;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms ease, transform 300ms ease;
        }
        html.sidebar-expanded .flash-success-overlay { left: calc(16rem + 2rem); }
        .flash-success-overlay.is-hiding { opacity: 0; transform: translateY(-4px); }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed left-0 top-0 h-screen bg-[#0e3a4c] flex flex-col items-center pt-0 z-50 transition-all duration-300 overflow-hidden">

  {{-- Authenticated user + role (profile dropdown) --}}
<div class="relative h-[80px] w-full flex items-center text-white">
    <button type="button" id="userMenuBtn" aria-haspopup="true" aria-expanded="false"
            class="sidebar-icon sidebar-user flex h-full w-full items-center gap-3 text-left">
        <svg class="user-avatar w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <span class="label whitespace-nowrap min-w-0">
            <span class="block text-xs font-semibold truncate">{{ auth()->user()->name }}</span>
            <span class="block text-[11px] text-white/50 truncate">{{ auth()->user()->role?->name ?? 'User' }}</span>
        </span>
        <svg class="user-caret ml-auto h-4 w-4 flex-shrink-0 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
        </svg>
    </button>

    {{-- Profile dropdown --}}
    <div id="userMenu" class="hidden absolute left-2 right-2 top-[calc(100%-8px)] z-[60] overflow-hidden rounded-lg bg-[#0e5266] shadow-xl ring-1 ring-white/10">
        <button type="button" id="signoutOpen" title="Sign out" aria-haspopup="dialog"
                class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-white/80 transition-colors hover:bg-white/10 hover:text-white">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span class="signout-label">Sign out</span>
        </button>
    </div>
</div>
            {{-- Navigation icons --}}
            <nav class="flex flex-col items-stretch w-full flex-1">
                {{-- Projects --}}
                @if (auth()->user()->canModule('projects'))
                <a href="{{ route('projects') }}"
                   class="relative flex items-center w-full h-[65px] transition-colors
                          {{ request()->is('projects') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('projects'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span class="label whitespace-nowrap">Projects</span>
                    </div>
                </a>
                @endif

                            {{-- PrepList --}}
                @if (auth()->user()->canModule('preplist'))
                <a href="{{ route('preplist') }}"
                class="relative flex items-center w-full h-[65px] transition-colors
                        {{ request()->is('preplist') || request()->is('preplist/*') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">

                    @if(request()->is('preplist') || request()->is('preplist/*'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif

                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-width="2" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        </svg>
                        <span class="label whitespace-nowrap">Preplist</span>
                    </div>
                </a>
                @endif

                            {{-- Request for Quotation --}}
                @if (auth()->user()->canModule('quotation'))
                <a href="{{ route('quotation') }}"
                class="relative flex items-center w-full h-[65px] transition-colors
                        {{ request()->is('quotation') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('quotation'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif

                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                            <line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                        <span class="label whitespace-nowrap">Quotation</span>
                    </div>
                </a>
                @endif

                {{-- Price Index --}}
                @if (auth()->user()->canModule('priceindex'))
                <a href="{{ route('priceindex') }}"
                class="relative flex items-center w-full h-[65px] transition-colors
                        {{ request()->is('priceindex') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('priceindex'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif

                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span class="label whitespace-nowrap">Price Index</span>
                    </div>
                </a>
                @endif

                {{-- Purchase Order --}}
                @if (auth()->user()->canModule('purchase-order'))
                <a href="{{ route('purchase-order') }}"
                class="relative flex items-center w-full h-[65px] transition-colors
                        {{ request()->is('purchase-order') || request()->is('purchase-order/*') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">

                    @if(request()->is('purchase-order') || request()->is('purchase-order/*'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif

                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75"/>
                        </svg>
                        <span class="label whitespace-nowrap">Purchase Order</span>
                    </div>
                </a>
                @endif

                {{-- Entities --}}
                @if (auth()->user()->canModule('entities'))
                <a href="{{ route('entities') }}"
                class="relative flex items-center w-full h-[65px] transition-colors
                        {{ request()->is('entities') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('entities'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif

                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="label whitespace-nowrap">Entities</span>
                    </div>
                </a>
                @endif

                {{-- Settings (administrator only) --}}
                @can('access-settings')
                <a href="{{ route('settings') }}"
                class="relative flex items-center w-full h-[65px] transition-colors
                        {{ request()->is('settings') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('settings'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif

                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <span class="label whitespace-nowrap">Settings</span>
                    </div>
                </a>
                @endcan
                </nav>

            {{-- Collapse toggle --}}
            <div class="mb-4 w-full flex justify-center">
                <button type="button" id="sidebarToggle" class="text-white/60 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </aside>

        {{-- Main content area (right of sidebar) --}}
       <div id="mainContent" class="flex flex-col flex-1 h-screen transition-all duration-300">

            {{-- Header --}}
            <header class="h-[80px] bg-[#0e5266] flex items-center justify-between px-8 shrink-0">
                <h1 class="text-white text-2xl font-bold tracking-wide uppercase">
                    @yield('page-title', 'PAGE TITLE')
                </h1>
                <div class="flex items-center gap-4">
                    @yield('header-actions')

                    
                   {{-- Notification bell --}}
            <div class="relative">
                <button id="notifBell" type="button" aria-label="Delivery deadlines" class="text-white/80 hover:text-white transition-colors cursor-pointer">
                    <x-nav-icon name="bell" />
                </button>
                @php
                    $deliveryNotifications = $deliveryNotifications ?? collect();
                    $deliveryNotificationCount = $deliveryNotificationCount ?? $deliveryNotifications->count();
                @endphp
                @if ($deliveryNotificationCount > 0)
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold rounded-full w-[18px] h-[18px] flex items-center justify-center">{{ $deliveryNotificationCount }}</span>
                @endif

                <div id="notifPanel" class="hidden absolute right-0 top-[calc(100%+10px)] w-72 bg-white rounded-lg shadow-xl overflow-hidden z-50">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Delivery deadlines approaching</p>
                    </div>
                    <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                        @forelse ($deliveryNotifications as $notification)
                            <a href="{{ $notification['href'] }}" class="block px-4 py-3 hover:bg-gray-50">
                                <p class="text-sm font-medium text-gray-800">{{ $notification['title'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $notification['entity'] }}
                                    @if ($notification['total_items'] > 0)
                                        · {{ $notification['fulfilled_items'] }} / {{ $notification['total_items'] }} items fulfilled
                                    @endif
                                </p>
                                <p class="mt-1 text-xs font-medium {{ $notification['severity'] === 'red' ? 'text-red-600' : 'text-amber-600' }}">
                                    {{ $notification['label'] }}
                                </p>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-center text-xs text-gray-500">
                                No delivery deadlines approaching. You are all caught up.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 overflow-y-auto bg-gray-100 p-8">
                @include('partials.flash-success')
                @yield('content')
            </main>
        </div>

    </div>

    {{-- Sign-out confirmation modal --}}
    <div id="signoutModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="signoutModalTitle">
        <div id="signoutModalOverlay" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
            <div class="flex items-start gap-3 px-5 py-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="signoutModalTitle" class="text-base font-bold text-gray-900">Sign out?</h3>
                    <p class="mt-1 text-sm text-gray-500">You will be returned to the sign-in page and will need to enter your credentials again.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                @csrf
                <button type="button" id="signoutCancel"
                        class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-md bg-[#0e5266] text-white text-sm font-medium hover:bg-[#0c4757] transition-colors cursor-pointer">
                    Sign out
                </button>
            </form>
        </div>
    </div>
<script>
    // Success flash overlay: fade out, then remove after a few seconds.
    // Overlays that start hidden (e.g. the PO create banner) manage their own timing.
    document.querySelectorAll('.flash-success-overlay:not(.hidden)').forEach(function (el) {
        setTimeout(function () {
            el.classList.add('is-hiding');
            setTimeout(function () { el.remove(); }, 350);
        }, 4000);
    });

    const toggleBtn = document.getElementById('sidebarToggle');
    const collapseIcon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>';
    const expandIcon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>';

    function syncIcon() {
        const isExpanded = document.documentElement.classList.contains('sidebar-expanded');
        toggleBtn.innerHTML = isExpanded ? expandIcon : collapseIcon;
    }
    syncIcon();

    toggleBtn.addEventListener('click', function () {
        const isExpanded = document.documentElement.classList.toggle('sidebar-expanded');
        localStorage.setItem('sidebarExpanded', isExpanded ? '1' : '0');
        syncIcon();
    });

    const notifBell = document.getElementById('notifBell');
    const notifPanel = document.getElementById('notifPanel');

    notifBell.addEventListener('click', function (e) {
        e.stopPropagation();
        notifPanel.classList.toggle('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!notifPanel.contains(e.target) && !notifBell.contains(e.target)) {
            notifPanel.classList.add('hidden');
        }
    });

    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    function closeUserMenu() {
        userMenu.classList.add('hidden');
        userMenuBtn.setAttribute('aria-expanded', 'false');
    }

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isHidden = userMenu.classList.toggle('hidden');
        userMenuBtn.setAttribute('aria-expanded', String(! isHidden));
    });

    document.addEventListener('click', function (e) {
        if (!userMenu.contains(e.target) && !userMenuBtn.contains(e.target)) {
            closeUserMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeUserMenu();
            closeSignoutModal();
        }
    });

    // Sign-out confirmation modal
    const signoutModal = document.getElementById('signoutModal');
    const signoutModalOverlay = document.getElementById('signoutModalOverlay');
    const signoutOpen = document.getElementById('signoutOpen');
    const signoutCancel = document.getElementById('signoutCancel');

    function openSignoutModal() {
        closeUserMenu();
        signoutModal.classList.remove('hidden');
        signoutModal.classList.add('flex');
        signoutCancel.focus();
    }

    function closeSignoutModal() {
        if (signoutModal.classList.contains('hidden')) {
            return;
        }
        signoutModal.classList.add('hidden');
        signoutModal.classList.remove('flex');
        userMenuBtn.focus();
    }

    signoutOpen.addEventListener('click', openSignoutModal);
    signoutCancel.addEventListener('click', closeSignoutModal);
    signoutModalOverlay.addEventListener('click', closeSignoutModal);
</script>
@stack('scripts')
</body>
</html>