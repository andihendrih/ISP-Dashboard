@extends('layouts.app')
@section('title','Detail Device — ' . ($device->serial_number ?? 'TR-069'))
@section('breadcrumb','Network / TR-069 / Detail')

@section('content')
@php
    $pppoe   = $params['pppoe'] ?? [];
    $wanIp   = $params['wan_ip'] ?? [];
    $wifi24  = $params['wifi_24'] ?? [];
    $wifi5g  = $params['wifi_5g'] ?? [];
    $rxPower = $params['rx_power'] ?? null;
    $uptime  = $params['uptime'] ?? null;
    $tags    = $device->tagsList();
    $isOnline = $device->status === 'online';
    $registeredAt = $params['registered_at'] ?? null;
    $lastInform = $device->last_inform_at;
    $mgmtIp = $wanIp['external_ip'] ?? null;
    // Display title preference: client tag > serial > pppoe username > device_id
    $clientTag = $tags[0] ?? null;
    $title = $clientTag ?: ($device->serial_number ?: ($pppoe['username'] ?? null) ?: $device->device_id);
    // Security values: None=Open, WPAand11i=WPA/WPA2 mixed, 11i=WPA2-only
    $secOptions = ['None'=>'Open (None)','WPA'=>'WPA','11i'=>'WPA2 (11i)','WPAand11i'=>'WPA/WPA2 (WPAand11i)','Basic'=>'WEP (Basic)'];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="flex items-start gap-3">
        <a href="{{ route('genieacs.index') }}" class="w-9 h-9 rounded-xl bg-white shadow-card flex items-center justify-center text-ink/70 hover:text-ink mt-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-base sm:text-lg font-bold flex items-center gap-2 flex-wrap">
                <span class="{{ $clientTag ? '' : 'font-mono' }}">{{ $title }}</span>
                <span class="inline-block w-2 h-2 rounded-full {{ $isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-ink/30' }}"></span>
                @if($isOnline)
                    <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full px-2 py-0.5">Online</span>
                @else
                    <span class="text-xs bg-ink/5 text-ink/60 border border-ink/10 rounded-full px-2 py-0.5">Offline</span>
                @endif
            </h1>
            <p class="text-xs text-ink/55 mt-0.5">
                @if($clientTag && $device->serial_number)<span class="font-mono">{{ $device->serial_number }}</span> ·@endif
                {{ $device->product_class ?: ($device->model_name ?: '—') }} · SW {{ $device->software_version ?? '—' }} · HW {{ $device->hardware_version ?? '—' }}
            </p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($mgmtIp)
            <a href="http://{{ $mgmtIp }}" target="_blank" rel="noopener"
               class="px-3 py-1.5 border border-blue-200 bg-blue-50 text-blue-700 rounded-xl text-xs font-medium hover:bg-blue-100 inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Remote Web
            </a>
        @endif
        <form method="POST" action="{{ route('genieacs.refresh', $device) }}" class="inline">
            @csrf
            <button class="px-3 py-1.5 border border-ink/10 bg-white rounded-xl text-xs font-medium hover:bg-cream-card/40 inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0114-2M20 15a8 8 0 01-14 2"/></svg>
                Refresh
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('info'))
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-3 mb-4 text-sm">{{ session('info') }}</div>
@endif
@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">

        {{-- TAGS / NAMA CLIENT --}}
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold mb-2">TAGS / NAMA CLIENT</div>
            <div class="flex flex-wrap gap-1.5 mt-1">
                @forelse($tags as $tg)
                    <span class="inline-block bg-blue-50 text-blue-700 border border-blue-100 rounded-full px-3 py-1 text-xs">{{ $tg }}</span>
                @empty
                    <span class="text-ink/30 text-xs">Belum ada tag/client.</span>
                @endforelse
            </div>
        </div>

        {{-- INFO DEVICE --}}
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold mb-3">INFO DEVICE</div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Serial Number</dt>
                    <dd class="font-mono text-xs">{{ $device->serial_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Manufacturer</dt>
                    <dd>{{ $device->manufacturer ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Product Class</dt>
                    <dd>{{ $device->product_class ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Model</dt>
                    <dd>{{ $device->model_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">SW Version</dt>
                    <dd class="font-mono text-xs">{{ $device->software_version ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">HW Version</dt>
                    <dd class="font-mono text-xs">{{ $device->hardware_version ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Last Inform</dt>
                    <dd class="text-xs">{{ optional($lastInform)->diffForHumans() ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Registered</dt>
                    <dd class="text-xs">{{ $registeredAt ? \Carbon\Carbon::parse($registeredAt)->diffForHumans() : '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-cream-deep/60 py-1.5">
                    <dt class="text-ink/55">Uptime</dt>
                    <dd class="text-xs font-mono">{{ $uptime ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 sm:col-span-2 py-1.5 mt-1">
                    <dt class="text-ink/55 font-semibold">Redaman (RX)</dt>
                    <dd>
                        @if($rxPower !== null)
                            @php $rxClass = $rxPower >= -25 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($rxPower >= -28 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'); @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-mono font-semibold border {{ $rxClass }}">{{ number_format($rxPower, 2) }} dBm</span>
                        @else
                            <span class="text-ink/30 text-xs">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- WAN PPPOE --}}
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold">WAN PPPoE</div>
                @if(!empty($pppoe['enable']))
                    <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full px-2 py-0.5">WAN Enable</span>
                @else
                    <span class="text-xs bg-ink/5 text-ink/60 border border-ink/10 rounded-full px-2 py-0.5">Disabled</span>
                @endif
            </div>
            @if(!empty($pppoe))
                <form method="POST" action="{{ route('genieacs.pppoe', $device) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3" onsubmit="return ahPartialSubmit(this)">
                    @csrf
                    <input type="hidden" name="username_path" value="{{ $pppoe['username_path'] ?? '' }}">
                    <input type="hidden" name="password_path" value="{{ $pppoe['password_path'] ?? '' }}">
                    <input type="hidden" name="vlan_path" value="{{ $pppoe['vlan_path'] ?? '' }}">
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Service Type</label>
                        <input value="{{ $pppoe['service_type'] ?? 'INTERNET' }}" disabled
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm bg-cream-card/40">
                    </div>
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Vlan ID</label>
                        <input name="vlan" value="{{ $pppoe['vlan'] ?? '' }}" data-original="{{ $pppoe['vlan'] ?? '' }}" placeholder="—" inputmode="numeric" pattern="[0-9]*" {{ empty($pppoe['vlan_path']) ? 'disabled' : '' }}
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100 {{ empty($pppoe['vlan_path']) ? 'bg-cream-card/40' : '' }}">
                    </div>
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Username</label>
                        <input name="username" value="{{ $pppoe['username'] ?? '' }}" data-original="{{ $pppoe['username'] ?? '' }}"
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Password</label>
                        <div class="relative mt-1">
                            <input name="password" type="password" value="{{ $pppoe['password'] ?? '' }}" data-original="{{ $pppoe['password'] ?? '' }}" placeholder="••••••••"
                                   class="w-full border border-ink/10 rounded-xl pl-3 pr-10 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                            <button type="button" onclick="ahTogglePw(this)" tabindex="-1" class="absolute right-2 top-1/2 -translate-y-1/2 text-ink/40 hover:text-ink/80 p-1" aria-label="Show/hide password">
                                <svg class="w-4 h-4 ah-eye-show" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="w-4 h-4 ah-eye-hide hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.94 10.94 0 0112 20c-7 0-11-8-11-8a19.66 19.66 0 015.06-5.94M9.9 4.24A10.93 10.93 0 0112 4c7 0 11 8 11 8a19.78 19.78 0 01-3.16 4.19M14.12 14.12a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button class="px-3 py-1.5 bg-ink text-white rounded-xl text-xs font-medium hover:bg-ink/90 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Simpan PPPoE
                        </button>
                    </div>
                </form>
            @else
                <p class="text-ink/40 text-sm italic">PPPoE belum terdeteksi pada raw device.</p>
            @endif
        </div>

        {{-- WAN IP STATIC --}}
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold mb-3">WAN IP (STATIC)</div>
            @if(!empty($wanIp))
                <form method="POST" action="{{ route('genieacs.wan-ip', $device) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3" onsubmit="return ahPartialSubmit(this)">
                    @csrf
                    <input type="hidden" name="ip_path" value="{{ $wanIp['ip_path'] ?? '' }}">
                    <input type="hidden" name="subnet_path" value="{{ $wanIp['subnet_path'] ?? '' }}">
                    <input type="hidden" name="gateway_path" value="{{ $wanIp['gateway_path'] ?? '' }}">
                    <input type="hidden" name="vlan_path" value="{{ $wanIp['vlan_path'] ?? '' }}">
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Service Type</label>
                        <input value="{{ $wanIp['service_type'] ?? 'INTERNET' }}" disabled
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm bg-cream-card/40">
                    </div>
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Vlan ID</label>
                        <input name="vlan" value="{{ $wanIp['vlan'] ?? '' }}" data-original="{{ $wanIp['vlan'] ?? '' }}" placeholder="—" inputmode="numeric" pattern="[0-9]*" {{ empty($wanIp['vlan_path']) ? 'disabled' : '' }}
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100 {{ empty($wanIp['vlan_path']) ? 'bg-cream-card/40' : '' }}">
                    </div>
                    <div>
                        <label class="text-xs text-ink/55 font-medium">External IP</label>
                        <input name="external_ip" value="{{ $wanIp['external_ip'] ?? '' }}" data-original="{{ $wanIp['external_ip'] ?? '' }}"
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label class="text-xs text-ink/55 font-medium">Subnet Mask</label>
                        <input name="subnet_mask" value="{{ $wanIp['subnet_mask'] ?? '' }}" data-original="{{ $wanIp['subnet_mask'] ?? '' }}"
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs text-ink/55 font-medium">Gateway</label>
                        <input name="gateway" value="{{ $wanIp['gateway'] ?? '' }}" data-original="{{ $wanIp['gateway'] ?? '' }}"
                               class="mt-1 w-full border border-ink/10 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button class="px-3 py-1.5 bg-ink text-white rounded-xl text-xs font-medium hover:bg-ink/90 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Simpan WAN IP
                        </button>
                    </div>
                </form>
            @else
                <p class="text-ink/40 text-sm italic">Tidak ada koneksi WAN IP statis terdeteksi (atau WAN cuma PPPoE).</p>
            @endif
        </div>

        {{-- WIFI / WLAN --}}
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold">WIFI / WLAN</div>
                <span class="text-xs text-ink/45">Pakai parameter <code class="font-mono">PreSharedKey.1.KeyPassphrase</code> untuk model Huawei.</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- 2.4 GHz --}}
                <div class="border border-ink/5 rounded-2xl p-4 bg-cream-card/20">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">2.4 GHZ</span>
                        @if(!empty($wifi24['enable']))
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full px-1.5 py-0.5">Enabled</span>
                        @endif
                    </div>
                    @if(!empty($wifi24))
                        <form method="POST" action="{{ route('genieacs.ssid', $device) }}" class="space-y-2 mb-3">
                            @csrf
                            <input type="hidden" name="ssid_path" value="{{ $wifi24['ssid_path'] ?? '' }}">
                            <div>
                                <label class="text-xs text-ink/55 font-medium">SSID</label>
                                <div class="flex gap-1 mt-1">
                                    <input name="ssid" value="{{ $wifi24['ssid'] ?? '' }}"
                                           class="flex-1 border border-ink/10 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <button class="px-3 py-2 bg-ink text-white rounded-xl text-xs hover:bg-ink/90">Set</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('genieacs.password', $device) }}" class="space-y-2 mb-3">
                            @csrf
                            <input type="hidden" name="password_path" value="{{ $wifi24['password_path'] ?? '' }}">
                            <div>
                                <label class="text-xs text-ink/55 font-medium">Password</label>
                                <div class="flex gap-1 mt-1">
                                    <div class="relative flex-1">
                                        <input name="password" type="password" value="{{ $wifi24['password'] ?? '' }}" minlength="8"
                                               class="w-full border border-ink/10 rounded-xl pl-3 pr-10 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                        <button type="button" onclick="ahTogglePw(this)" tabindex="-1" class="absolute right-2 top-1/2 -translate-y-1/2 text-ink/40 hover:text-ink/80 p-1" aria-label="Show/hide password">
                                            <svg class="w-4 h-4 ah-eye-show" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg class="w-4 h-4 ah-eye-hide hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.94 10.94 0 0112 20c-7 0-11-8-11-8a19.66 19.66 0 015.06-5.94M9.9 4.24A10.93 10.93 0 0112 4c7 0 11 8 11 8a19.78 19.78 0 01-3.16 4.19M14.12 14.12a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                                        </button>
                                    </div>
                                    <button class="px-3 py-2 bg-ink text-white rounded-xl text-xs hover:bg-ink/90">Set</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('genieacs.wifi-security', $device) }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="security_path" value="{{ $wifi24['security_path'] ?? '' }}">
                            <label class="text-xs text-ink/55 font-medium">Security</label>
                            <div class="flex gap-1 mt-1">
                                <select name="security" class="flex-1 border border-ink/10 rounded-xl px-3 py-2 text-sm bg-white">
                                    @foreach($secOptions as $val => $lbl)
                                        <option value="{{ $val }}" @selected(($wifi24['security'] ?? 'WPAand11i') === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <button class="px-3 py-2 bg-ink text-white rounded-xl text-xs hover:bg-ink/90">Set</button>
                            </div>
                        </form>
                    @else
                        <p class="text-ink/40 text-xs italic">2.4 GHz tidak terdeteksi.</p>
                    @endif
                </div>

                {{-- 5 GHz --}}
                <div class="border border-ink/5 rounded-2xl p-4 bg-cream-card/20">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-100 text-violet-700">5 GHZ</span>
                        @if(!empty($wifi5g['enable']))
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full px-1.5 py-0.5">Enabled</span>
                        @endif
                    </div>
                    @if(!empty($wifi5g))
                        <form method="POST" action="{{ route('genieacs.ssid', $device) }}" class="space-y-2 mb-3">
                            @csrf
                            <input type="hidden" name="ssid_path" value="{{ $wifi5g['ssid_path'] ?? '' }}">
                            <div>
                                <label class="text-xs text-ink/55 font-medium">SSID</label>
                                <div class="flex gap-1 mt-1">
                                    <input name="ssid" value="{{ $wifi5g['ssid'] ?? '' }}"
                                           class="flex-1 border border-ink/10 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <button class="px-3 py-2 bg-ink text-white rounded-xl text-xs hover:bg-ink/90">Set</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('genieacs.password', $device) }}" class="space-y-2 mb-3">
                            @csrf
                            <input type="hidden" name="password_path" value="{{ $wifi5g['password_path'] ?? '' }}">
                            <div>
                                <label class="text-xs text-ink/55 font-medium">Password</label>
                                <div class="flex gap-1 mt-1">
                                    <div class="relative flex-1">
                                        <input name="password" type="password" value="{{ $wifi5g['password'] ?? '' }}" minlength="8"
                                               class="w-full border border-ink/10 rounded-xl pl-3 pr-10 py-2 text-sm font-mono focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                        <button type="button" onclick="ahTogglePw(this)" tabindex="-1" class="absolute right-2 top-1/2 -translate-y-1/2 text-ink/40 hover:text-ink/80 p-1" aria-label="Show/hide password">
                                            <svg class="w-4 h-4 ah-eye-show" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg class="w-4 h-4 ah-eye-hide hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.94 10.94 0 0112 20c-7 0-11-8-11-8a19.66 19.66 0 015.06-5.94M9.9 4.24A10.93 10.93 0 0112 4c7 0 11 8 11 8a19.78 19.78 0 01-3.16 4.19M14.12 14.12a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>
                                        </button>
                                    </div>
                                    <button class="px-3 py-2 bg-ink text-white rounded-xl text-xs hover:bg-ink/90">Set</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('genieacs.wifi-security', $device) }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="security_path" value="{{ $wifi5g['security_path'] ?? '' }}">
                            <label class="text-xs text-ink/55 font-medium">Security</label>
                            <div class="flex gap-1 mt-1">
                                <select name="security" class="flex-1 border border-ink/10 rounded-xl px-3 py-2 text-sm bg-white">
                                    @foreach($secOptions as $val => $lbl)
                                        <option value="{{ $val }}" @selected(($wifi5g['security'] ?? 'WPAand11i') === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <button class="px-3 py-2 bg-ink text-white rounded-xl text-xs hover:bg-ink/90">Set</button>
                            </div>
                        </form>
                    @else
                        <p class="text-ink/40 text-xs italic">5 GHz tidak tersedia di model ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- AKSI DEVICE + History --}}
    <div class="space-y-4">
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold mb-3">AKSI DEVICE</div>
            <div class="grid grid-cols-1 gap-2">
                <form method="POST" action="{{ route('genieacs.reboot', $device) }}" onsubmit="return confirm('Reboot device {{ $device->serial_number }}?');">
                    @csrf
                    <button class="w-full px-4 py-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-xl text-sm font-medium inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0114-2M20 15a8 8 0 01-14 2"/></svg>
                        Reboot
                    </button>
                </form>

                @if(!empty($pppoe['enable_path']))
                    <form method="POST" action="{{ route('genieacs.suspend-wan', $device) }}" onsubmit="return confirm('{{ !empty($pppoe['enable']) ? 'Suspend' : 'Aktifkan' }} WAN?');">
                        @csrf
                        <input type="hidden" name="enable_path" value="{{ $pppoe['enable_path'] }}">
                        <input type="hidden" name="enable" value="{{ !empty($pppoe['enable']) ? '0' : '1' }}">
                        <button class="w-full px-4 py-2.5 {{ !empty($pppoe['enable']) ? 'bg-amber-50 hover:bg-amber-100 border-amber-200 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 border-emerald-200 text-emerald-700' }} border rounded-xl text-sm font-medium inline-flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636"/></svg>
                            {{ !empty($pppoe['enable']) ? 'Suspend WAN' : 'Aktifkan WAN' }}
                        </button>
                    </form>
                @endif

                <a href="#sessions" class="block w-full px-4 py-2.5 bg-ink/5 hover:bg-ink/10 border border-ink/10 text-ink/80 rounded-xl text-sm font-medium inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Lihat History Sesi
                </a>

                <form method="POST" action="{{ route('genieacs.factory-reset', $device) }}"
                      onsubmit="return confirm('PERHATIAN: Factory reset akan menghapus semua konfigurasi device. Lanjutkan?');">
                    @csrf
                    <button class="w-full px-4 py-2.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-xl text-sm font-medium inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                        Factory Reset
                    </button>
                </form>
            </div>
        </div>

        {{-- Session History --}}
        <div id="sessions" class="bg-white rounded-3xl shadow-card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[10px] uppercase tracking-widest text-ink/45 font-semibold">HISTORY SESI</div>
                @if($pppoe['username'] ?? null)
                    <span class="text-xs text-ink/55 font-mono">{{ $pppoe['username'] }}</span>
                @endif
            </div>
            @if(!empty($sessions))
                <div class="space-y-2 max-h-[480px] overflow-y-auto">
                    @foreach($sessions as $s)
                        @php
                            $isActive = empty($s->acctstoptime);
                            $start = $s->acctstarttime ? \Carbon\Carbon::parse($s->acctstarttime) : null;
                            $stop  = $s->acctstoptime ? \Carbon\Carbon::parse($s->acctstoptime) : null;
                            $duration = $s->acctsessiontime ? gmdate('H:i:s', $s->acctsessiontime) : '—';
                            $upload = $s->acctinputoctets ? round($s->acctinputoctets / 1024 / 1024, 1) . ' MB' : '—';
                            $download = $s->acctoutputoctets ? round($s->acctoutputoctets / 1024 / 1024, 1) . ' MB' : '—';
                        @endphp
                        <div class="border border-ink/5 rounded-xl p-3 text-xs {{ $isActive ? 'bg-emerald-50/50 border-emerald-200' : '' }}">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-mono text-[11px]">{{ $s->framedipaddress ?? '—' }}</span>
                                @if($isActive)
                                    <span class="text-[10px] bg-emerald-100 text-emerald-700 rounded-full px-1.5 py-0.5">AKTIF</span>
                                @endif
                            </div>
                            <div class="text-ink/60 text-[11px]">
                                {{ $start ? $start->format('d M Y H:i') : '—' }}
                                @if($stop) → {{ $stop->format('H:i') }} @endif
                            </div>
                            <div class="text-ink/55 text-[10px] mt-1 flex gap-3">
                                <span>⏱ {{ $duration }}</span>
                                <span>↑ {{ $upload }}</span>
                                <span>↓ {{ $download }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($pppoe['username'] ?? null)
                <p class="text-ink/40 text-sm italic">Belum ada sesi RADIUS untuk username ini.</p>
            @else
                <p class="text-ink/40 text-sm italic">PPPoE username tidak terdeteksi — history sesi tidak tersedia.</p>
            @endif
        </div>
    </div>
</div>

<script>
// Only submit fields that the user actually changed. Disabled inputs are
// excluded from form data so the controller treats them as "skip".
function ahPartialSubmit(form) {
    const fields = form.querySelectorAll('input[data-original]');
    let dirty = 0;
    fields.forEach(el => {
        const cur  = (el.value ?? '').trim();
        const orig = (el.dataset.original ?? '').trim();
        if (cur === orig) {
            el.disabled = true;
        } else {
            dirty++;
        }
    });
    if (dirty === 0) {
        // Re-enable so the user can keep typing.
        fields.forEach(el => { el.disabled = false; });
        alert('Tidak ada field yang berubah.');
        return false;
    }
    return true;
}

function ahTogglePw(btn) {
    const input = btn.parentElement.querySelector('input');
    if (!input) return;
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    btn.querySelector('.ah-eye-show').classList.toggle('hidden', !showing);
    btn.querySelector('.ah-eye-hide').classList.toggle('hidden', showing);
}
</script>
@endsection
