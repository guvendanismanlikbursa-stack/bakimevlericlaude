<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

// Push abonelikleri oturuma gore otomatik cozumlenir: hangi session key'i
// doluysa (family_user_id / facility_user_id / admin_id) abonelik o modele
// baglanir. Boylece tek bir uc nokta 3 kullanici tipini de destekler.
class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $subscribable = $this->currentSubscribable();

        if (! $subscribable) {
            return response()->json(['ok' => false], 401);
        }

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'subscribable_type' => get_class($subscribable),
                'subscribable_id' => $subscribable->getKey(),
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aesgcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }

    private function currentSubscribable(): FamilyUser|FacilityUser|Admin|null
    {
        if ($id = session('family_user_id')) {
            return FamilyUser::find($id);
        }
        if ($id = session('facility_user_id')) {
            return FacilityUser::find($id);
        }
        if ($id = session('admin_id')) {
            return Admin::find($id);
        }

        return null;
    }
}
