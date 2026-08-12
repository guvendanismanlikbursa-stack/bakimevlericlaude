<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $whatsappDefaults = config('platform.default_whatsapp');
        $brands = config('brands.brands');

        $settings = [
            'quote_price' => Setting::get('quote_price', config('platform.default_quote_price')),
            'whatsapp_number' => Setting::get('whatsapp_number', $whatsappDefaults['number']),
            'whatsapp_message' => Setting::get('whatsapp_message', $whatsappDefaults['message']),
            'facility_invitation_message' => Setting::get('facility_invitation_message', facility_invitation_message_default()),
        ];

        // Her marka/site kendi banka hesap bilgisini gosterir (bkz. kurum panelindeki
        // Bakiyem sayfasi, hangi siteden erisiliyorsa o sitenin hesabi gosterilir).
        $bankAccounts = [];
        foreach ($brands as $slug => $brand) {
            $bankAccounts[$slug] = [
                'label' => $brand['name'],
                'bank_name' => Setting::get("bank_name_{$slug}", config("platform.default_bank_info.{$slug}.bank_name")),
                'bank_account_holder' => Setting::get("bank_account_holder_{$slug}", config("platform.default_bank_info.{$slug}.account_holder")),
                'bank_iban' => Setting::get("bank_iban_{$slug}", config("platform.default_bank_info.{$slug}.iban")),
            ];
        }

        return view('admin.settings.edit', compact('settings', 'bankAccounts'));
    }

    public function update(Request $request)
    {
        $brandSlugs = array_keys(config('brands.brands'));

        $rules = [
            'quote_price' => 'required|numeric|min:0',
            'whatsapp_number' => 'required|string|max:20|regex:/^[0-9]+$/',
            'whatsapp_message' => 'required|string|max:300',
            'facility_invitation_message' => 'required|string|max:600',
        ];

        foreach ($brandSlugs as $slug) {
            $rules["bank_name_{$slug}"] = 'required|string|max:120';
            $rules["bank_account_holder_{$slug}"] = 'required|string|max:150';
            $rules["bank_iban_{$slug}"] = 'required|string|max:60';
        }

        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Ayarlar güncellendi.');
    }
}
