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
        <aside class="fixed left-0 top-0 h-screen w-[80px] bg-[#0e3a4c] flex flex-col items-center pt-0 z-50">

            {{-- Hamburger menu icon --}}
            <div class="h-[80px] flex items-center justify-center text-white/80 hover:text-white transition-colors">
                <x-nav-icon name="hamburger" />
            </div>

            {{-- Navigation icons --}}
            <nav class="flex flex-col items-center w-full flex-1">
                {{-- 1. Projects --}}
                <a href="{{ route('projects') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('projects') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('projects'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <x-nav-icon name="projects" />
                </a>

                {{-- 2. Pricing --}}
                <a href="{{ route('pricing') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('pricing') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('pricing'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <x-nav-icon name="pricing" />
                </a>

                {{-- 3. Quotation --}}
                <a href="{{ route('quotation') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('quotation') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('quotation'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <x-nav-icon name="quotation" />
                </a>

                {{-- 4. Entities --}}
                <a href="{{ route('entities') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('entities') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('entities'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <x-nav-icon name="entities" />
                </a>

                {{-- 5. Users --}}
                <a href="{{ route('users') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('users') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('users'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
                    <x-nav-icon name="users" />
                </a>

                {{-- 6. Settings --}}
                <a href="{{ route('settings') }}"
                   class="relative flex items-center justify-center w-full h-[65px] transition-colors
                          {{ request()->is('settings') ? 'bg-[#2a7a94] text-white' : 'text-white/60 hover:text-white hover:bg-[#2a7a94]/50' }}">
                    @if(request()->is('settings'))
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-teal-300"></span>
                    @endif
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