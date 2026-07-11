<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    /**
     * $notifiable'a bagli TUM push aboneliklerine bildirim gonderir. Gecersiz
     * hale gelmis (410/404 donen) abonelikler otomatik silinir - standart
     * web-push hijyeni. Herhangi bir hata bu akisi asla kesmemeli, bu yuzden
     * cagiran taraf (notify_user()) zaten try/catch ile sarmaliyor; burada
     * ek olarak abonelik bazinda da hata izole edilir (bir cihazdaki hata
     * digerlerinin gonderimini engellemesin).
     */
    public function sendToNotifiable($notifiable, string $title, ?string $body, ?string $actionUrl): void
    {
        if (! $notifiable) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject = config('services.vapid.subject');

        if (! $publicKey || ! $privateKey) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $actionUrl,
        ]);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $statusCode = $report->getResponse()?->getStatusCode();
            if (in_array($statusCode, [404, 410], true)) {
                PushSubscription::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
            } else {
                Log::warning('Web push gonderilemedi: ' . $report->getReason(), ['endpoint' => $report->getEndpoint()]);
            }
        }
    }
}
