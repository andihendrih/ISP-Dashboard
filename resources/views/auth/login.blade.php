<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login · {{ config('ahnet.brand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#0f4d8a] via-[#0a3a6c] to-[#062c54] flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-[#0f4d8a] text-white flex items-center justify-center font-bold text-xl">A</div>
            <div>
                <div class="text-xl font-bold">AHNet Dashboard</div>
                <div class="text-sm text-slate-500">ISP Management System</div>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-rose-50 text-rose-700 border border-rose-200 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                    class="mt-1 w-full rounded-lg border-slate-300 focus:border-[#0f4d8a] focus:ring-[#0f4d8a] border px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" required
                    class="mt-1 w-full rounded-lg border-slate-300 focus:border-[#0f4d8a] focus:ring-[#0f4d8a] border px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Ingat saya
            </label>
            <button class="w-full bg-[#0f4d8a] hover:bg-[#0a3a6c] text-white py-2.5 rounded-lg font-medium">
                Masuk
            </button>
        </form>
        <div class="mt-6 text-xs text-slate-500 text-center">
            &copy; {{ date('Y') }} {{ config('ahnet.company') }}
        </div>
    </div>
</body>
</html>
