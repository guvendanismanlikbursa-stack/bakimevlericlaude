<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use Throwable;

// Hosting kaynakli kisa MySQL kopmalarinda (Connection refused / server has gone away) baglantiyi
// birkac saniye boyunca yeniden dener; kopma bu sure icinde biterse ziyaretci/arka plan isi hic hata gormez.
class RetryingMySqlConnector extends MySqlConnector
{
    public function createConnection($dsn, array $config, array $options)
    {
        $attempts = max(1, (int) ($config['connect_retries'] ?? 8));
        $sleepMs = max(0, (int) ($config['connect_retry_sleep_ms'] ?? 1000));

        for ($i = 1; ; $i++) {
            try {
                return parent::createConnection($dsn, $config, $options);
            } catch (Throwable $e) {
                $transient = preg_match('/\[2002\]|\[2006\]|Connection refused|server has gone away|Connection timed out/i', $e->getMessage());
                if (! $transient || $i >= $attempts) {
                    throw $e;
                }
                usleep($sleepMs * 1000);
            }
        }
    }
}
