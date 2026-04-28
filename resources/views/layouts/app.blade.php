<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('ahnet.brand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,.25); border-radius: 999px; }
    </style>
    @stack('head')
</head>
<body class="h-full bg-slate-50 text-slate-800">
<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="w-64 shrink-0 bg-[#0f4d8a] text-white flex flex-col">
        <div class="px-5 py-5 flex items-center gap-3 border-b border-white/10">
            <div class="w-9 h-9 rounded-lg bg-white text-[#0f4d8a] flex items-center justify-center font-bold">A</div>
            <div>
                <div class="font-bold leading-tight">AHNet</div>
                <div class="text-xs text-white/70">ISP Dashboard</div>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 text-sm scrollbar-thin">
            @php($u = auth()->user())
            @php($role = $u?->role?->name)

            <div class="px-5 pb-2 text-xs uppercase tracking-wider text-white/50">Menu</div>
            <a href="{{ route('dashboard') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/15' : '' }}">
                Dashboard
            </a>

            <div class="px-5 pt-4 pb-2 text-xs uppercase tracking-wider text-white/50">Pelanggan</div>
            <a href="{{ route('customers.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('customers.*') ? 'bg-white/15' : '' }}">Pelanggan</a>
            <a href="{{ route('users.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('users.*') ? 'bg-white/15' : '' }}">User RADIUS</a>

            @if(in_array($role, ['admin','noc']))
                <div class="px-5 pt-4 pb-2 text-xs uppercase tracking-wider text-white/50">Layanan</div>
                <a href="{{ route('pppoe.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('pppoe.*') ? 'bg-white/15' : '' }}">PPPoE</a>
                <a href="{{ route('hotspot.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('hotspot.*') ? 'bg-white/15' : '' }}">Hotspot</a>

                <div class="px-5 pt-4 pb-2 text-xs uppercase tracking-wider text-white/50">Network</div>
                <a href="{{ route('mikrotik.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('mikrotik.*') ? 'bg-white/15' : '' }}">Mikrotik</a>
                <a href="{{ route('snmp.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('snmp.*') ? 'bg-white/15' : '' }}">SNMP Monitor</a>
                <a href="{{ route('genieacs.index') }}" class="block px-5 py-2 hover:bg-white/10 {{ request()->routeIs('genieacs.*') ? 'bg-white/15' : '' }}">GenieACS</a>
            @endif
        </nav>
        <div class="p-4 text-xs text-white/60 border-t border-white/10">
            v1.0 · {{ now()->format('Y') }}
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Top bar --}}
        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
            <div class="font-semibold text-slate-700">
                {{ config('ahnet.company') }}
            </div>
            <div class="flex items-center gap-6 text-sm text-slate-500">
                <div>Timezone: {{ config('app.timezone') }}</div>
                <div id="now-clock" class="font-medium text-slate-700">{{ now()->format('l, d M Y · H:i:s') }}</div>
                <div class="flex items-center gap-2">
                    <span class="text-slate-700 font-medium">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-xs text-slate-500 hover:text-rose-600">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            @if(session('success'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-rose-50 text-rose-800 border border-rose-200">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 px-4 py-3 rounded-lg bg-rose-50 text-rose-800 border border-rose-200">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        const el = document.getElementById('now-clock');
        if (!el) return;
        const fmt = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long', day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
        });
        setInterval(() => { el.textContent = fmt.format(new Date()).replace(',', ' ·'); }, 1000);
    })();
</script>
@stack('scripts')
</body>
</html>
