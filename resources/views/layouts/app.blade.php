<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('ahnet.brand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream: '#f4ecd8',
                        'cream-deep': '#ede2c8',
                        'cream-card': '#faf3e0',
                        ink: '#1a1a1a',
                        'ink-soft': '#2b2b2b',
                        accent: '#f5c542',
                        'accent-soft': '#fde79a',
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    boxShadow: {
                        soft: '0 8px 32px -16px rgba(40, 30, 0, 0.18)',
                        card: '0 4px 20px -8px rgba(40, 30, 0, 0.10)',
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4ecd8; font-size: 14px; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 999px; }
        .nav-item { display:flex; align-items:center; gap:.6rem; padding:.5rem .8rem; border-radius:.85rem; font-weight:500; color:#3b3b3b; transition:all .15s; font-size: .85rem; }
        .nav-item:hover { background:#ffffff; }
        .nav-item.active { background:#1a1a1a; color:#fff; box-shadow: 0 6px 20px -8px rgba(0,0,0,.4); }
        .nav-item svg { width: .95rem; height: .95rem; flex-shrink: 0; }
        .nav-section { font-size:.65rem; letter-spacing:.08em; text-transform:uppercase; color:#8a7e5b; padding:.75rem .8rem .25rem; }

        /* Mobile sidebar drawer */
        @media (max-width: 767px) {
            .ah-sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 82% !important;
                max-width: 18rem !important;
                height: 100vh !important;
                height: 100dvh !important;
                max-height: none !important;
                z-index: 50;
                transform: translateX(-100%);
                transition: transform .25s ease-out;
                border-radius: 0 1.5rem 1.5rem 0 !important;
                box-shadow: 0 10px 40px -10px rgba(0,0,0,.3);
                margin: 0 !important;
                padding: 1rem !important;
                overflow: hidden;
            }
            .ah-sidebar > nav { min-height: 0; }
            body.ah-sidebar-open .ah-sidebar { transform: translateX(0); }
            body.ah-sidebar-open .ah-backdrop { opacity: 1; pointer-events: auto; }
            body.ah-sidebar-open { overflow: hidden; }
        }
        .ah-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,.4);
            opacity: 0; pointer-events: none;
            transition: opacity .25s; z-index: 40;
        }
        @media (min-width: 768px) {
            .ah-backdrop { display: none !important; }
        }

        /* Desktop default — kompak */
        @media (min-width: 768px) {
            h1 { line-height: 1.2; }
        }
    </style>
    @stack('head')
</head>
<body class="h-full bg-cream text-ink">
<div class="ah-backdrop" id="ah-backdrop" onclick="document.body.classList.remove('ah-sidebar-open')"></div>
<div class="flex min-h-screen p-3 sm:p-4 gap-3 sm:gap-4">

    {{-- Sidebar --}}
    <aside class="ah-sidebar w-52 shrink-0 bg-cream-deep/60 backdrop-blur rounded-3xl flex flex-col p-3 sticky top-4 self-start max-h-[calc(100vh-2rem)]">
        <div class="px-2 py-1.5 flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-ink text-accent flex items-center justify-center font-extrabold text-base">A</div>
            <div>
                <div class="font-extrabold leading-tight text-ink text-sm">AHNet</div>
                <div class="text-[10px] text-ink/50 font-medium">ISP Dashboard</div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto mt-2 text-sm scrollbar-thin">
            @php($u = auth()->user())
            @php($role = $u?->role?->name)

            <div class="nav-section">Menu</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h4v-6h6v6h4V10"/></svg>
                Dashboard
            </a>

            <div class="nav-section">Pelanggan</div>
            <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 10-8 0 4 4 0 008 0zM2 22a10 10 0 0120 0"/></svg>
                Pelanggan
            </a>
            <a href="{{ route('map.index') }}" class="nav-item {{ request()->routeIs('map.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.553 2.776A1 1 0 0022 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Map
            </a>

            <div class="nav-section">Billing</div>
            <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                Tagihan
            </a>
            <a href="{{ route('payments.index') }}" class="nav-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                Pembayaran
            </a>
            <a href="{{ route('plans.index') }}" class="nav-item {{ request()->routeIs('plans.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 12h14M5 16h10"/></svg>
                Paket Layanan
            </a>

            <div class="nav-section">Laporan</div>
            <a href="{{ route('reports.financial') }}" class="nav-item {{ request()->routeIs('reports.financial*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 5-5"/></svg>
                Keuangan
            </a>
            <a href="{{ route('reports.churn') }}" class="nav-item {{ request()->routeIs('reports.churn') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6m0 0l-3 3m3-3l3 3M15 5v13m0 0l3-3m-3 3l-3-3"/></svg>
                Churn &amp; Growth
            </a>

            <div class="nav-section">Support</div>
            <a href="{{ route('tickets.index') }}" class="nav-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                Tiket Support
                @php($openTickets = \App\Models\SupportTicket::whereIn('status', ['open','in_progress'])->count())
                @if($openTickets > 0)
                    <span class="ml-auto text-[10px] bg-accent text-ink rounded-full px-2 py-0.5 font-bold">{{ $openTickets }}</span>
                @endif
            </a>
            <a href="{{ route('notifications.settings') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 10-12 0v3a2 2 0 01-.6 1.6L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                Notifikasi
            </a>

            @if(in_array($role, ['admin','noc','superadmin']))
                <div class="nav-section">Layanan</div>
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    Radius
                </a>
                <a href="{{ route('pppoe.index') }}" class="nav-item {{ request()->routeIs('pppoe.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12l4-4M5 12l4 4M19 12l-4-4M19 12l-4 4"/></svg>
                    PPPoE
                </a>
                <a href="{{ route('hotspot.index') }}" class="nav-item {{ request()->routeIs('hotspot.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.55a11 11 0 0114 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/></svg>
                    Hotspot
                </a>
                <a href="{{ route('vouchers.index') }}" class="nav-item {{ request()->routeIs('vouchers.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Voucher
                </a>

                <div class="nav-section">Inventory</div>
                <a href="{{ route('devices.index') }}" class="nav-item {{ request()->routeIs('devices.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Perangkat
                </a>

                <div class="nav-section">Network</div>
                <a href="{{ route('mikrotik.index') }}" class="nav-item {{ request()->routeIs('mikrotik.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 9.5h20M2 14.5h20M5 4.5h14a3 3 0 013 3v9a3 3 0 01-3 3H5a3 3 0 01-3-3v-9a3 3 0 013-3z"/></svg>
                    Mikrotik
                </a>
                <a href="{{ route('snmp.index') }}" class="nav-item {{ request()->routeIs('snmp.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8"/></svg>
                    SNMP Monitor
                </a>
                <a href="{{ route('genieacs.index') }}" class="nav-item {{ request()->routeIs('genieacs.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 100 20 10 10 0 000-20zM2 12h20M12 2a15 15 0 010 20M12 2a15 15 0 000 20"/></svg>
                    GenieACS
                </a>
            @endif

            @if(in_array($role, ['superadmin','admin']))
                <div class="nav-section">Pengaturan</div>
                <a href="{{ route('settings.users.index') }}" class="nav-item {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Pengguna
                </a>
                @if($role === 'superadmin')
                    <a href="{{ route('settings.tenants.index') }}" class="nav-item {{ request()->routeIs('settings.tenants.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Tenants
                    </a>
                @endif
            @endif
        </nav>

        {{-- User profile pill --}}
        <div class="mt-2 bg-white rounded-2xl p-2.5 flex items-center gap-2 shadow-card shrink-0">
            <div class="w-8 h-8 rounded-full bg-accent text-ink flex items-center justify-center font-bold text-sm shrink-0">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold truncate">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-ink/50 capitalize">{{ $role ?? 'user' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button title="Logout" class="w-7 h-7 rounded-lg bg-cream-deep hover:bg-ink hover:text-white flex items-center justify-center transition shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m0-8H5a2 2 0 00-2 2v12a2 2 0 002 2h4"/></svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Top bar --}}
        <header class="flex items-center justify-between mb-3 sm:mb-4 px-1 sm:px-2 gap-2">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                {{-- Hamburger (mobile only) --}}
                <button type="button" class="md:hidden w-10 h-10 rounded-xl bg-white shadow-card flex items-center justify-center shrink-0"
                        onclick="document.body.classList.toggle('ah-sidebar-open')" aria-label="Menu">
                    <svg class="w-5 h-5 text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="text-xs sm:text-sm text-ink/55 truncate">
                    <span class="font-semibold text-ink/80">{{ config('ahnet.company') }}</span>
                    <span class="mx-1 sm:mx-2 text-ink/30">/</span>
                    <span>@yield('breadcrumb', 'Dashboard')</span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs sm:text-sm shrink-0">
                <div class="bg-white rounded-xl px-3 py-1.5 shadow-card text-ink/70 font-medium hidden lg:block">
                    <span id="now-clock">{{ now()->format('l, d M Y · H:i:s') }}</span>
                </div>
                <div class="bg-white rounded-xl px-3 py-1.5 shadow-card text-ink/70 font-medium">
                    {{ config('app.timezone') }}
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto pb-4">
            @php($activeTenantId = session('active_tenant_id'))
            @php($activeTenant = ($role === 'superadmin' && $activeTenantId) ? \App\Models\Tenant::find($activeTenantId) : null)
            @if($activeTenant)
                <div class="mb-4 px-5 py-2.5 rounded-2xl bg-amber-100 text-amber-900 border border-amber-300 shadow-card flex items-center justify-between gap-3 text-sm">
                    <div>
                        <span class="font-semibold">View as Tenant:</span>
                        {{ $activeTenant->name }}
                        <span class="font-mono text-xs ml-1 opacity-70">({{ $activeTenant->code }})</span>
                        — semua menu di-filter ke tenant ini.
                    </div>
                    <form method="POST" action="{{ route('settings.tenants.clear-switch') }}">
                        @csrf
                        <button class="px-3 py-1 rounded-lg bg-amber-200 hover:bg-amber-300 text-amber-900 text-xs font-semibold whitespace-nowrap">Lihat Semua Tenant</button>
                    </form>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 px-5 py-3 rounded-2xl bg-emerald-100 text-emerald-900 border border-emerald-200 shadow-card">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-4 px-5 py-3 rounded-2xl bg-amber-100 text-amber-900 border border-amber-200 shadow-card">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-5 py-3 rounded-2xl bg-rose-100 text-rose-900 border border-rose-200 shadow-card">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 px-5 py-3 rounded-2xl bg-rose-100 text-rose-900 border border-rose-200 shadow-card">
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
        if (el) {
            const fmt = new Intl.DateTimeFormat('id-ID', {
                weekday: 'long', day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
            });
            setInterval(() => { el.textContent = fmt.format(new Date()).replace(',', ' ·'); }, 1000);
        }

        // Auto-close mobile sidebar on nav link click & on route change
        document.querySelectorAll('aside.ah-sidebar a.nav-item').forEach(a => {
            a.addEventListener('click', () => {
                if (window.innerWidth < 768) document.body.classList.remove('ah-sidebar-open');
            });
        });
        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') document.body.classList.remove('ah-sidebar-open');
        });
    })();
</script>
@stack('scripts')
</body>
</html>
