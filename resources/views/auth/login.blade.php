<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login · {{ config('ahnet.brand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream:      '#f4ecd8',
                        'cream-deep':'#ede2c8',
                        'cream-card':'#faf3e0',
                        ink:        '#1a1a1a',
                        accent:     '#f5c542',
                        'accent-soft':'#fde79a',
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        card:  '0 10px 30px -12px rgba(40,30,0,.18)',
                        card2: '0 24px 64px -24px rgba(40,30,0,.28)',
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes ah-float {
            0%,100% { transform: translateY(0) }
            50%     { transform: translateY(-10px) }
        }
        .ah-float { animation: ah-float 6s ease-in-out infinite; }
        .ah-float-slow { animation: ah-float 9s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-cream text-ink antialiased">

<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- ========== LEFT PANEL (illustration / brand) ========== --}}
    <aside class="relative overflow-hidden bg-cream-deep lg:w-1/2 px-6 sm:px-10 py-8 lg:py-12 flex flex-col">

        {{-- decorative floating dots --}}
        <div class="absolute -top-16 -left-16 w-72 h-72 rounded-full bg-accent/25 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-10 -right-20 w-80 h-80 rounded-full bg-accent-soft/40 blur-3xl pointer-events-none"></div>
        <span class="hidden lg:block absolute top-24 right-16 w-3 h-3 rounded-full bg-accent ah-float"></span>
        <span class="hidden lg:block absolute top-1/2 left-12 w-2.5 h-2.5 rounded-full bg-ink/30 ah-float-slow"></span>
        <span class="hidden lg:block absolute bottom-32 right-24 w-2 h-2 rounded-full bg-ink/40 ah-float"></span>
        <span class="hidden lg:block absolute top-40 left-1/3 w-1.5 h-1.5 rounded-full bg-accent ah-float-slow"></span>

        {{-- brand top --}}
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-ink text-accent flex items-center justify-center font-extrabold text-lg shadow-card">A</div>
            <div>
                <div class="text-lg font-extrabold leading-tight">AHNet</div>
                <div class="text-xs text-ink/55 leading-tight">ISP Management Dashboard</div>
            </div>
        </div>

        {{-- middle: illustration + headline --}}
        <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center mt-8 lg:mt-0">

            {{-- inline SVG illustration: tech operator + chart --}}
            <svg class="w-56 sm:w-64 lg:w-80 h-auto mb-6 ah-float-slow" viewBox="0 0 320 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                {{-- chart bars (background) --}}
                <rect x="200" y="160" width="18" height="50" rx="3" fill="#1a1a1a" opacity="0.85"/>
                <rect x="225" y="135" width="18" height="75" rx="3" fill="#f5c542"/>
                <rect x="250" y="105" width="18" height="105" rx="3" fill="#1a1a1a" opacity="0.85"/>
                <rect x="275" y="80" width="18" height="130" rx="3" fill="#f5c542"/>

                {{-- arrow up --}}
                <path d="M205 95 L300 30" stroke="#1a1a1a" stroke-width="3" stroke-linecap="round"/>
                <path d="M285 28 L300 30 L298 45" stroke="#1a1a1a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>

                {{-- person --}}
                {{-- head --}}
                <circle cx="105" cy="90" r="22" fill="#fde79a"/>
                <path d="M83 92 a22 22 0 0 1 44 0 v-12 a22 22 0 0 0 -44 0 z" fill="#1a1a1a"/>
                {{-- body --}}
                <path d="M70 210 q0 -55 35 -85 q35 30 35 85 z" fill="#1a1a1a"/>
                {{-- shirt collar accent --}}
                <path d="M95 130 l10 10 l10 -10" stroke="#f5c542" stroke-width="3" fill="none" stroke-linecap="round"/>
                {{-- arm holding tablet --}}
                <rect x="125" y="135" width="50" height="35" rx="5" transform="rotate(-15 125 135)" fill="#faf3e0" stroke="#1a1a1a" stroke-width="2"/>
                <line x1="135" y1="148" x2="160" y2="142" stroke="#1a1a1a" stroke-width="1.5" opacity="0.4"/>
                <line x1="138" y1="158" x2="163" y2="152" stroke="#1a1a1a" stroke-width="1.5" opacity="0.4"/>

                {{-- floating sparkles --}}
                <path d="M30 50 l4 -8 l4 8 l8 4 l-8 4 l-4 8 l-4 -8 l-8 -4 z" fill="#f5c542"/>
                <circle cx="50" cy="180" r="5" fill="#f5c542"/>
                <circle cx="20" cy="120" r="3" fill="#1a1a1a" opacity="0.4"/>
            </svg>

            <h2 class="text-2xl lg:text-3xl font-extrabold text-ink leading-tight max-w-md">
                Kelola jaringan ISP <span class="text-ink/50">dengan</span> tenang.
            </h2>
            <p class="mt-3 text-sm lg:text-base text-ink/60 max-w-md">
                Pelanggan, billing, perangkat, GenieACS, RADIUS — semua di satu dashboard.
            </p>
        </div>

        {{-- footer --}}
        <div class="relative z-10 hidden lg:flex items-center justify-between text-xs text-ink/50 mt-6">
            <span>&copy; {{ date('Y') }} {{ config('ahnet.company') }}</span>
            <span>v1.0</span>
        </div>
    </aside>

    {{-- ========== RIGHT PANEL (login form) ========== --}}
    <main class="relative lg:w-1/2 flex items-center justify-center px-4 sm:px-8 py-10 lg:py-12 bg-cream">

        {{-- subtle decorative --}}
        <div class="absolute top-10 right-10 w-40 h-40 rounded-full bg-accent/15 blur-2xl pointer-events-none"></div>
        <div class="absolute bottom-10 left-10 w-32 h-32 rounded-full bg-accent-soft/30 blur-2xl pointer-events-none"></div>

        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-card2 p-7 sm:p-9">

            <div class="mb-7">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Sign In</h1>
                <p class="text-sm text-ink/55 mt-1">Masuk pakai akun staff atau pelanggan kamu.</p>
            </div>

            @if($errors->any())
                <div class="mb-4 p-3 rounded-2xl bg-rose-50 text-rose-800 border border-rose-200 text-sm flex gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold text-ink/70 mb-1.5">Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-ink/40">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4 0-7 2-7 6h14c0-4-3-6-7-6z"/>
                            </svg>
                        </span>
                        <input type="email" name="email" required autofocus value="{{ old('email') }}"
                            placeholder="admin@ahnet.local"
                            class="w-full rounded-2xl border border-ink/10 bg-cream-card/60 focus:bg-white focus:border-accent focus:ring-2 focus:ring-accent/30 pl-10 pr-4 py-3 outline-none transition text-sm">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-ink/70">Password</label>
                        <a href="#" class="text-xs text-ink/45 hover:text-ink">Lupa password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-ink/40">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2H6a2 2 0 00-2 2v7a2 2 0 002 2zM8 11V7a4 4 0 118 0v4"/>
                            </svg>
                        </span>
                        <input id="ah-pw" type="password" name="password" required placeholder="••••••••"
                            class="w-full rounded-2xl border border-ink/10 bg-cream-card/60 focus:bg-white focus:border-accent focus:ring-2 focus:ring-accent/30 pl-10 pr-11 py-3 outline-none transition text-sm">
                        <button type="button" onclick="ahTogglePw()" tabindex="-1"
                            class="absolute inset-y-0 right-3 flex items-center text-ink/40 hover:text-ink"
                            aria-label="Tampilkan password">
                            <svg id="ah-eye-show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <svg id="ah-eye-hide" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 6.1A9.7 9.7 0 0112 6c7 0 10 7 10 7a13.7 13.7 0 01-3.4 4.3M6.6 6.6A13.7 13.7 0 002 13s3 7 10 7a9.7 9.7 0 005.4-1.6M9.9 9.9a3 3 0 104.2 4.2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember me --}}
                <label class="flex items-center gap-2 text-sm text-ink/70 select-none">
                    <input type="checkbox" name="remember" class="rounded border-ink/20 text-accent focus:ring-accent">
                    Ingat saya
                </label>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full bg-ink hover:bg-black text-white py-3 rounded-2xl font-semibold flex items-center justify-center gap-2 transition shadow-card">
                    Login
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <p class="mt-7 text-xs text-ink/45 text-center lg:hidden">
                &copy; {{ date('Y') }} {{ config('ahnet.company') }}
            </p>
        </div>
    </main>
</div>

<script>
    function ahTogglePw() {
        const i = document.getElementById('ah-pw');
        const showing = i.type === 'text';
        i.type = showing ? 'password' : 'text';
        document.getElementById('ah-eye-show').classList.toggle('hidden', !showing);
        document.getElementById('ah-eye-hide').classList.toggle('hidden', showing);
    }
</script>

</body>
</html>
