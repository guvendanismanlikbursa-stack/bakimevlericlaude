<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class GoogleMapsDataExtractorService
{
    public function scrape(string $query, int $limit): array
    {
        $limit = min(1000, max(1, $limit));
        $toolPath = base_path('tools/veri-cekici');
        $scraper = $toolPath.DIRECTORY_SEPARATOR.'google_maps_scraper.py';

        if (! is_file($scraper)) {
            throw new RuntimeException('Veri cekici Python dosyasi bulunamadi.');
        }

        $runner = storage_path('framework/cache/data-extractor-runner.py');
        if (! is_dir(dirname($runner))) {
            mkdir(dirname($runner), 0777, true);
        }

        file_put_contents($runner, <<<'PY'
import json
import sys
import threading
from google_maps_scraper import scrape_google_maps

query = sys.argv[1]
limit = int(sys.argv[2])
data = scrape_google_maps(query, limit, lambda message: None, threading.Event(), False)
print(json.dumps(data, ensure_ascii=False))
PY);

        $python = $this->resolvePythonBinary();

        if (! $python) {
            throw new RuntimeException(
                'Sunucuda calistirilabilir bir Python bulunamadi (python3/python). '.
                'Bu ozellik icin sunucuya Python 3, sonra "pip install playwright openpyxl requests beautifulsoup4" '.
                've "playwright install chromium --with-deps" kurulmalidir. Bu adim docs/PRODUCTION.md icinde '.
                '"Veri Cekici (opsiyonel canli API)" bolumunde belgelenmistir.'
            );
        }

        $process = new Process([$python, '-X', 'utf8', $runner, $query, (string) $limit], $toolPath, null, null, 1800);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Veri cekici calistirilamadi.');
        }

        $output = trim($process->getOutput());
        $data = json_decode($output, true);

        if (! is_array($data)) {
            throw new RuntimeException('Veri cekici JSON sonucu okunamadi.');
        }

        return array_slice($data, 0, $limit);
    }

    /**
     * Cogu Linux sunucusunda sadece "python3" bulunur, "python" yoktur.
     * Windows'ta ise genelde "python" vardir. Ikisini de dener.
     */
    private function resolvePythonBinary(): ?string
    {
        foreach (['python3', 'python'] as $binary) {
            $probe = new Process([$binary, '--version']);
            $probe->run();

            if ($probe->isSuccessful()) {
                return $binary;
            }
        }

        return null;
    }
}
