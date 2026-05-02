<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login · {{ config('ahnet.brand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background:#f4ecd8; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    {{-- decorative blobs --}}
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-[#f5c542]/40 blur-3xl"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-[#fde79a]/60 blur-3xl"></div>
    <div class="absolute top-1/3 right-1/4 w-72 h-72 rounded-full bg-white/40 blur-3xl"></div>

    <div class="w-full max-w-md bg-white/85 backdrop-blur rounded-3xl p-9 relative z-10" style="box-shadow:0 24px 64px -24px rgba(40,30,0,.25)">
        <div class="flex items-center gap-3 mb-7">
            <div class="w-12 h-12 rounded-2xl bg-[#1a1a1a] text-[#f5c542] flex items-center justify-center font-extrabold text-xl">A</div>
            <div>
                <div class="text-xl font-extrabold text-[#1a1a1a]">AHNet Dashboard</div>
                <div class="text-sm text-[#1a1a1a]/55">ISP Management System</div>
            </div>
        </div>

        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-[#1a1a1a]">Welcome back 👋</h1>
            <p class="text-sm text-[#1a1a1a]/55 mt-1">Masuk untuk mengelola jaringan kamu.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 rounded-2xl bg-rose-100 text-rose-800 border border-rose-200 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-semibold text-[#1a1a1a]">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}" placeholder="admin@ahnet.local"
                    class="mt-1 w-full rounded-2xl border border-[#1a1a1a]/10 bg-[#faf3e0]/60 focus:bg-white focus:border-[#f5c542] focus:ring-2 focus:ring-[#f5c542]/40 px-4 py-3 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-semibold text-[#1a1a1a]">Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                    class="mt-1 w-full rounded-2xl border border-[#1a1a1a]/10 bg-[#faf3e0]/60 focus:bg-white focus:border-[#f5c542] focus:ring-2 focus:ring-[#f5c542]/40 px-4 py-3 outline-none transition">
            </div>
            <label class="flex items-center gap-2 text-sm text-[#1a1a1a]/70">
                <input type="checkbox" name="remember" class="rounded border-[#1a1a1a]/20 text-[#f5c542] focus:ring-[#f5c542]">
                Ingat saya
            </label>
            <button class="w-full bg-[#1a1a1a] hover:bg-black text-white py-3 rounded-2xl font-semibold flex items-center justify-center gap-2 transition shadow-lg shadow-[#1a1a1a]/15">
                Masuk
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>
        <div class="mt-7 text-xs text-[#1a1a1a]/50 text-center">
            &copy; {{ date('Y') }} {{ config('ahnet.company') }}
        </div>
    </div>
</body>
</html>
