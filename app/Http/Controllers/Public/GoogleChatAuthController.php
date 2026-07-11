<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

// Canli destek sohbeti widget'inda "Google ile devam et" - kullaniciyi
// manuel isim yazmaktan kurtarmak icin. Google OAuth turu tam sayfa
// yonlendirmesi gerektirdigi icin (widget'in JS state'i kaybolur), niyet/
// cinsiyet/varsa mevcut guest_token redirect() adiminda session'a yazilip
// callback()'te geri okunur, sonuc query string ile ana sayfaya donup
// widget'in kendisi (JS) devraliyor. Yas bilgisi Google'dan guvenilir
// gelmedigi icin (bkz. kullanici karari) burada istenmez - widget geri
// donusten sonra sadece yas icin kisa bir adim gosterir.
class GoogleChatAuthController extends Controller
{
    public function redirect(Request $request)
    {
        session(['chat_google_state' => [
            'guest_token' => $request->query('guest_token'),
            'intent' => $request->query('intent'),
            'gender' => $request->query('gender'),
        ]]);

        return Socialite::driver('google')
            ->redirectUrl(brand_route('support-chat.google-callback'))
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $state = session('chat_google_state', []);
        session()->forget('chat_google_state');

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(brand_route('support-chat.google-callback'))
                ->user();
        } catch (\Throwable $e) {
            return redirect(brand_route('home'))->with('error', 'Google ile giriş başarısız oldu, lütfen tekrar deneyin.');
        }

        return redirect(brand_route('home', array_filter([
            'chat_google_name' => $googleUser->getName() ?: $googleUser->getNickname(),
            'chat_google_avatar' => $googleUser->getAvatar(),
            'chat_google_intent' => $state['intent'] ?? null,
            'chat_google_gender' => $state['gender'] ?? null,
            'chat_google_token' => $state['guest_token'] ?? null,
        ])));
    }
}
