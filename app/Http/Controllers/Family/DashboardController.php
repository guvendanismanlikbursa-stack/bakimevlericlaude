<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $brand = current_brand();

        /** @var FamilyUser $family */
        $family = FamilyUser::findOrFail(session('family_user_id'));

        $requests = $family->offerRequests()
            ->where('brand', $brand['slug'])
            ->with([
                'facility',
                'city',
                'category',
                'quotes.facility',
                'acceptedQuote.facility',
                'messages',
            ])
            ->latest()
            ->get();

        $stats = [
            'total_requests' => $requests->count(),
            'open_requests' => $requests->whereNull('accepted_quote_id')->count(),
            'total_quotes' => $requests->sum(fn ($req) => $req->quotes->count()),
            'accepted_quotes' => $requests->whereNotNull('accepted_quote_id')->count(),
            'message_count' => $requests->sum(fn ($req) => $req->messages->count()),
        ];

        return view("themes.{$brand['theme']}.family.dashboard", compact('family', 'requests', 'stats'));
    }

    public function acceptQuote(Request $request)
    {
        $brand = current_brand();
        $quote = $this->quoteFromRoute($request);
        $family = FamilyUser::findOrFail(session('family_user_id'));
        $quote->load('offerRequest');
        $offerRequest = $quote->offerRequest;

        abort_unless($offerRequest && $offerRequest->family_user_id === $family->id, 403);
        abort_unless($offerRequest->brand === $brand['slug'], 403);

        if ($offerRequest->accepted_quote_id && $offerRequest->accepted_quote_id !== $quote->id) {
            return back()->withErrors(['quote' => 'Bu talep için daha önce başka bir teklif kabul edilmiş.']);
        }

        DB::transaction(function () use ($offerRequest, $quote) {
            $offerRequest->update(['accepted_quote_id' => $quote->id, 'status' => 'contacted']);
            $quote->update(['status' => 'accepted']);
            $offerRequest->quotes()->where('id', '!=', $quote->id)->update(['status' => 'declined']);
        });

        return back()->with('success', 'Teklifi kabul ettiniz. Kurumla mesajlaşma ekranından iletişime geçebilirsiniz.');
    }

    private function quoteFromRoute(Request $request): Quote
    {
        $value = $request->route('quote');

        return $value instanceof Quote ? $value : Quote::findOrFail($value);
    }
}