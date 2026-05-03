<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(Request $request): View
    {
        $totalAll      = CustomerProfile::count();
        $totalGeo      = CustomerProfile::whereNotNull('latitude')->whereNotNull('longitude')->count();
        $totalNoGeo    = $totalAll - $totalGeo;

        $packages = CustomerProfile::query()
            ->select('package')->distinct()
            ->whereNotNull('package')->orderBy('package')->pluck('package');

        return view('map.index', [
            'totalAll'   => $totalAll,
            'totalGeo'   => $totalGeo,
            'totalNoGeo' => $totalNoGeo,
            'packages'   => $packages,
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $q = CustomerProfile::query()
            ->select(['id', 'customer_code', 'full_name', 'phone', 'address',
                      'latitude', 'longitude', 'status', 'service_type', 'package'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($s = $request->query('status'))      $q->where('status', $s);
        if ($p = $request->query('package'))     $q->where('package', $p);
        if ($t = $request->query('service_type'))$q->where('service_type', $t);
        if ($search = $request->query('q'))      $q->where(function ($w) use ($search) {
            $w->where('full_name', 'like', "%{$search}%")
              ->orWhere('customer_code', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });

        $rows = $q->limit(5000)->get()->map(fn ($r) => [
            'id'            => $r->id,
            'code'          => $r->customer_code,
            'name'          => $r->full_name,
            'phone'         => $r->phone,
            'address'       => $r->address,
            'lat'           => (float) $r->latitude,
            'lng'           => (float) $r->longitude,
            'status'        => $r->status,
            'service_type'  => $r->service_type,
            'package'       => $r->package,
            'detail_url'    => route('customers.edit', $r->id),
        ]);

        return response()->json(['count' => $rows->count(), 'data' => $rows]);
    }
}
