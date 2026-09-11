<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\NumberOffer;
use App\Models\Service;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function countries()
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'iso2', 'iso3', 'name', 'dialing_code']);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    public function services()
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'description']);

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function offers(Request $request)
    {
        $validated = $request->validate([
            'country' => ['nullable', 'string', 'size:2'],
            'service' => ['nullable', 'string', 'max:80'],
            'term' => ['nullable', 'in:instant,daily,weekly,monthly,quarterly,annual,custom'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $query = NumberOffer::query()
            ->with([
                'country:id,iso2,name,dialing_code',
                'service:id,slug,name',
                'provider:id,slug,name,status',
            ])
            ->where('is_active', true);

        if (!empty($validated['country'])) {
            $country = strtoupper($validated['country']);
            $query->whereHas('country', function ($countryQuery) use ($country) {
                $countryQuery->where('iso2', $country)->where('is_active', true);
            });
        }

        if (!empty($validated['service'])) {
            $query->whereHas('service', function ($serviceQuery) use ($validated) {
                $serviceQuery->where('slug', $validated['service'])->where('is_active', true);
            });
        }

        if (!empty($validated['term'])) {
            $query->where('term_type', $validated['term']);
        }

        if (!empty($validated['currency'])) {
            $query->where('currency', strtoupper($validated['currency']));
        }

        $offers = $query
            ->orderBy('price_minor')
            ->paginate(30)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $offers->items(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }
}
