<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\OfferRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $brand = current_brand();
        $user = FacilityUser::with('facility.category', 'facility.city')->findOrFail(session('facility_user_id'));
        $facility = $user->facility;
        $facilityInBrandScope = $facility->isInBrandScope($brand['category_scope']);

        $alreadyQuotedIds = $facility->quotes()
            ->whereHas('offerRequest', fn ($q) => $q->where('brand', $brand['slug']))
            ->pluck('offer_request_id');

        $directRequests = OfferRequest::where('brand', $brand['slug'])
            ->where('facility_id', $facility->id)
            ->with(['familyUser', 'city', 'category', 'quotes' => fn ($q) => $q->where('facility_id', $facility->id), 'messages'])
            ->latest()
            ->get();

        $broadcastLeads = collect();

        if ($facilityInBrandScope) {
            $broadcastLeads = OfferRequest::where('brand', $brand['slug'])
                ->whereNull('facility_id')
                ->where('city_id', $facility->city_id)
                ->where('facility_category_id', $facility->facility_category_id)
                ->whereNotIn('id', $alreadyQuotedIds)
                ->with(['familyUser', 'city', 'category', 'quotes', 'messages'])
                ->latest()
                ->get();
        }

        $sentQuotes = $facility->quotes()
            ->whereHas('offerRequest', fn ($q) => $q->where('brand', $brand['slug']))
            ->with('offerRequest.familyUser', 'offerRequest.city', 'offerRequest.category')
            ->latest()
            ->get();

        $stats = [
            'direct_requests' => $directRequests->count(),
            'broadcast_leads' => $broadcastLeads->count(),
            'sent_quotes' => $sentQuotes->count(),
            'accepted_quotes' => $sentQuotes->where('status', 'accepted')->count(),
            'pending_quotes' => $sentQuotes->where('status', 'pending')->count(),
            'message_threads' => $sentQuotes->pluck('offer_request_id')->unique()->count(),
        ];

        return view("themes.{$brand['theme']}.facility.dashboard", compact(
            'user',
            'facility',
            'directRequests',
            'broadcastLeads',
            'sentQuotes',
            'facilityInBrandScope',
            'stats'
        ));
    }
}