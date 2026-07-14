<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Purchasing')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside class="fixed left-0 top-0 h-screen w-[80px] bg-[#0e3a4c] flex flex-col items-center py-4 z-50">

            {{-- Hamburger menu icon --}}
            <div class="mb-6 text-white/80 hover:text-white transition-colors">
                <x-nav-icon name="hamburger" />
            </div>

            {{-- Navigation icons --}}
            <nav class="flex flex-col items-center w-full flex-1">
                {{-- 1. Truck / Delivery --}}
                <a href="{{ route('main') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('main') ? 'bg-[#1a5a72] border-l-2 border-l-teal-300' : 'text-white/60 hover:text-white hover:bg-[#1a5a72]/50' }}">
                    <x-nav-icon name="truck" />
                    {{-- TODO: replace with real count --}}
                    <span class="absolute top-2 right-4 bg-red-600 text-white text-[10px] font-bold rounded-full w-[18px] h-[18px] flex items-center justify-center">2</span>
                </a>

                {{-- 2. Clipboard / Checklist --}}
                <a href="#"
                   class="relative flex items-center justify-center w-full h-[65px] text-white/60 hover:text-white hover:bg-[#1a5a72]/50 transition-colors">
                    <x-nav-icon name="clipboard" />
                    {{-- TODO: replace with real count --}}
                    <span class="absolute top-2 right-4 bg-red-600 text-white text-[10px] font-bold rounded-full w-[18px] h-[18px] flex items-center justify-center">3</span>
                    {{-- placeholder route --}}
                </a>

                {{-- 3. Document / Invoice --}}
                <a href="#"
                   class="relative flex items-center justify-center w-full h-[65px] text-white/60 hover:text-white hover:bg-[#1a5a72]/50 transition-colors">
                    <x-nav-icon name="invoice" />
                    {{-- TODO: replace with real count --}}
                    <span class="absolute top-2 right-4 bg-red-600 text-white text-[10px] font-bold rounded-full w-[18px] h-[18px] flex items-center justify-center">2</span>
                    {{-- placeholder route --}}
                </a>

                {{-- 4. Grid / Apps --}}
                <a href="#"
                   class="relative flex items-center justify-center w-full h-[65px] text-white/60 hover:text-white hover:bg-[#1a5a72]/50 transition-colors">
                    <x-nav-icon name="grid" />
                    {{-- placeholder route --}}
                </a>

                {{-- 5. Gear / Settings --}}
                <a href="{{ route('settings') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('settings') ? 'bg-[#1a5a72] border-l-2 border-l-teal-300' : 'text-white/60 hover:text-white hover:bg-[#1a5a72]/50' }}">
                    <x-nav-icon name="settings" />
                </a>
            </nav>

            {{-- Logout --}}
            <div class="mt-auto mb-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-white/60 hover:text-white transition-colors cursor-pointer">
                        <x-nav-icon name="logout" />
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content area (right of sidebar) --}}
        <div class="ml-[80px] flex flex-col flex-1 h-screen">

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

</body>
</html>