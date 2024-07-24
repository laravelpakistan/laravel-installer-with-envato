<?php

namespace AbnDevs\Installer;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class License
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->script = config('installer.license.script');
        $this->platform = config('installer.license.platform');
    }

    public function activate($purchaseCode, $clientName): PromiseInterface|Response
    {
        $response = $this->client->post('/api/license-verification', [
                'script' => $this->script,
                'platform' => $this->platform,
                'username' => $clientName,
                'purchase_code' => $purchaseCode,
        ]);

        if ($response->successful() && $response->json('status')) {
            $this->saveLicense($response->json('lic_response'));

        } else {
            $this->removeLicense();
        }

        return $response;
    }

    public function verify($purchaseCode = null, $clientName = null, bool $timeBased = false): array
    {
        $localLicenseFile = $this->getLicenseFile();

        if ($localLicenseFile) {
            $licenseData = json_encode($localLicenseFile, true);

            return [
                'status' => true,
                'message' => 'License verified successfully.',
                'data' => $licenseData,
            ];
        } else {
            return [
                'status' => false,
                'message' => 'License verification failed.',
                'data' => null,
            ];
        }
    }

    private function saveLicense(array $data): void
    {
        file_put_contents(base_path('vendor/arcoticsolutions/laravel-installer-with-envato/license'), json_encode($data), LOCK_EX);
    }

    private function removeLicense(): void
    {
        if (file_exists(base_path('vendor/arcoticsolutions/laravel-installer-with-envato/license'))) {
            if (!is_writable(base_path('vendor/arcoticsolutions/laravel-installer-with-envato/license'))) {
                @chmod(base_path('vendor/arcoticsolutions/laravel-installer-with-envato/license'), 0777);
            }

            unlink(base_path('vendor/arcoticsolutions/laravel-installer-with-envato/license'));
        }
    }

    private function getLicenseFile(): bool|string|null
    {
        $path = base_path('vendor/arcoticsolutions/laravel-installer-with-envato/license');

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return null;
    }
}
