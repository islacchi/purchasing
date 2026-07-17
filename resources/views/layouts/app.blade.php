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
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed left-0 top-0 h-screen bg-[#0e3a4c] flex flex-col items-center pt-0 z-50 transition-all duration-300 overflow-hidden">

  {{-- User icon + name/role (hardcoded for now — replace with auth()->user()->name and role once DB is wired up) --}}
<div class="h-[80px] w-full flex items-center text-white">
    <div class="sidebar-icon flex items-center gap-3">
        <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <span class="label whitespace-nowrap">
            <span class="block text-xs font-semibold">Juan Dela Cruz</span>
            <span class="block text-[11px] text-white/50">Procurement Officer</span>
        </span>
    </div>
</div>
            {{-- Navigation icons --}}
            <nav class="flex flex-col items-stretch w-full flex-1">
                {{-- Projects --}}
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

                {{-- PrepList --}}
                <a href="#"
                   class="relative flex items-center w-full h-[65px] transition-colors text-white/60 hover:text-white hover:bg-[#2a7a94]/50">
                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span class="label whitespace-nowrap">Preplist</span>
                    </div>
                </a>

                {{-- Request for Quotation --}}
                <a href="#"
                   class="relative flex items-center w-full h-[65px] transition-colors text-white/60 hover:text-white hover:bg-[#2a7a94]/50">
                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                        <span class="label whitespace-nowrap">Request for Quotation</span>
                    </div>
                </a>

                {{-- Users --}}
                <a href="{{ route('users') }}"
                   class="relative flex items-center w-full h-[65px] transition-colors
                          {{ request()->is('users') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('users'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="label whitespace-nowrap">Users</span>
                    </div>
                </a>

                {{-- Pricing/Price Index --}}
                <a href="{{ route('pricing') }}"
                   class="relative flex items-center w-full h-[65px] transition-colors
                          {{ request()->is('pricing') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('pricing'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.95 1.012c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span class="label whitespace-nowrap">Price Index</span>
                    </div>
                </a>

                {{-- Settings --}}
                <a href="{{ route('settings') }}"
                   class="relative flex items-center w-full h-[65px] transition-colors
                          {{ request()->is('settings') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('settings'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <div class="sidebar-icon flex items-center gap-3">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <span class="label whitespace-nowrap">Settings</span>
                    </div>
                </a>
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
                        <button class="text-white/80 hover:text-white transition-colors cursor-pointer">
                            <x-nav-icon name="bell" />
                        </button>
                        {{-- TODO: replace with real count --}}
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold rounded-full w-[18px] h-[18px] flex items-center justify-center">2</span>
                    </div>
                </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 overflow-y-auto bg-gray-100 p-8">
                @yield('content')
            </main>
        </div>

    </div>
<script>
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
</script>
</body>
</html>