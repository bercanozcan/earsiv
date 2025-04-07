<?php

namespace Bercanozcan\Earsiv;

use Bercanozcan\Earsiv\Exceptions\LoginException;
use Bercanozcan\Earsiv\Exceptions\RequestException;
use Bercanozcan\Earsiv\Http\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Gib
{
    protected bool $testMode;
    protected ?string $username;
    protected ?string $password;
    protected ?string $token = null;

    public function __construct(bool $testMode = false, ?string $username = null, ?string $password = null)
    {
        $this->testMode = $testMode;
        $this->username = $username;
        $this->password = $password;
    }

    public function login(): static
    {
        $client = new Client($this->testMode);

        $response = $client->post('/earsiv-services/assos-login', [
            'assoscmd' => $this->testMode ? 'login' : 'anologin',
            'userid'   => $this->username,
            'sifre'    => $this->password,
            'sifre2'   => $this->password,
            'parola'   => $this->password,
        ]);

        if ($response->failed() || !$response->json('token')) {
            throw new \Exception('Giriş başarısız: ' . $response->body());
        }

        $this->token = $response->json('token');
        return $this;
    }

    public function setTestCredentials(): self
    {
        $client = new Client(true); // testMode true

        $response = $client->post('/earsiv-services/esign', [
            'assoscmd' => 'kullaniciOner',
            'rtype'    => 'json'
        ]);

        $userid = $response->json('userid');

        if (!$userid) {
            throw new RequestException('Test hesabı alınamadı: sistemdeki tüm hesaplar kullanılıyor olabilir.');
        }

        $this->username = $userid;
        $this->password = '1';
        $this->testMode = true;

        return $this;
    }

    public function getTestCredentials(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getUserData(): array
    {
        if (!$this->token) {
            throw new RequestException('Kullanıcı verisi alınamıyor: önce giriş yapmalısınız.');
        }

        $client = new Client($this->testMode);

        $response = $client->post('/earsiv-services/dispatch', [
            'callid'   => (string) Str::uuid(),
            'token'    => $this->token,
            'cmd'      => 'EARSIV_PORTAL_KULLANICI_BILGILERI_GETIR',
            'pageName' => 'RG_KULLANICI',
            'jp'       => '{}'
        ]);

        if ($response->failed() || !$response->json('data')) {
            throw new RequestException('GİB kullanıcı bilgileri alınamadı: ' . $response->body());
        }

        return $response->json('data');
    }

    public function getInvoices(string $startDate, string $endDate): array
    {
        if (!$this->token) {
            throw new RequestException('Faturaları çekmek için önce giriş yapmalısınız.');
        }

        // GİB dd/MM/yyyy formatını bekler
        $payload = [
            'baslangic' => $startDate,
            'bitis'     => $endDate,
            'hangiTip'  => '5000/30000'
        ];

        $client = new Client($this->testMode);

        $response = $client->post('/earsiv-services/dispatch', [
            'callid'   => (string) Str::uuid(),
            'token'    => $this->token,
            'cmd'      => 'EARSIV_PORTAL_TASLAKLARI_GETIR',
            'pageName' => 'RG_TASLAKLAR',
            'jp'       => json_encode($payload),
        ]);

        if ($response->failed() || !$response->json('data')) {
            throw new RequestException('Fatura verileri alınamadı: ' . $response->body());
        }

        return $response->json('data');
    }

    public function getInvoice(string $ettn): array
    {
        if (!$this->token) {
            throw new RequestException('Fatura bilgisi alınamıyor: önce giriş yapmalısınız.');
        }

        $client = new Client($this->testMode);

        $response = $client->post('/earsiv-services/dispatch', [
            'callid'   => (string) Str::uuid(),
            'token'    => $this->token,
            'cmd'      => 'EARSIV_PORTAL_FATURA_GETIR',
            'pageName' => 'RG_TASLAKLAR',
            'jp'       => json_encode(['ettn' => $ettn]),
        ]);

        if ($response->failed() || !$response->json('data')) {
            throw new RequestException('Fatura detayları alınamadı: ' . $response->body());
        }

        return $response->json('data');
    }

    public function getDownloadUrl(string $ettn, bool $signed = true): string
    {
        if (!$this->token) {
            throw new RequestException('Download linki oluşturulamıyor: önce giriş yapmalısınız.');
        }

        $baseUrl = $this->testMode
            ? 'https://earsivportaltest.efatura.gov.tr'
            : 'https://earsivportal.efatura.gov.tr';

        $params = http_build_query([
            'token'      => $this->token,
            'ettn'       => $ettn,
            'onayDurumu' => $signed ? 'Onaylandı' : 'Onaylanmadı',
            'belgeTip'   => 'FATURA',
            'cmd'        => 'EARSIV_PORTAL_BELGE_INDIR',
        ]);

        return "{$baseUrl}/earsiv-services/download?{$params}";
    }

    public function saveToDisk(string $ettn, ?string $dirName = null, ?string $fileName = null): string
    {
        if (!$this->token) {
            throw new RequestException('Dosya indirilemedi: önce giriş yapmalısınız.');
        }

        $diskName    = 'public';
        $directory   = $dirName ?? 'faturalar';
        $file        = ($fileName ?? $ettn) . '.zip';
        $fullPath    = trim($directory, '/') . '/' . $file;

        $downloadUrl = $this->getDownloadUrl($ettn);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
        ])->timeout(60)->get($downloadUrl);

        if ($response->failed() || !$response->body()) {
            throw new RequestException("Fatura indirilemedi: $ettn");
        }

        Storage::disk($diskName)->put($fullPath, $response->body());

        return asset(Storage::url($fullPath));
    }

    public function getHtml(string $ettn, bool $signed = true): string
    {
        if (!$this->token) {
            throw new RequestException('Fatura önizlemesi alınamıyor: önce giriş yapmalısınız.');
        }

        $client = new Client($this->testMode);

        $payload = [
            'ettn'       => $ettn,
            'onayDurumu' => $signed ? 'Onaylandı' : 'Onaylanmadı',
        ];

        $response = $client->post('/earsiv-services/dispatch', [
            'callid'   => (string) Str::uuid(),
            'token'    => $this->token,
            'cmd'      => 'EARSIV_PORTAL_FATURA_GOSTER',
            'pageName' => 'RG_TASLAKLAR',
            'jp'       => json_encode($payload),
        ]);

        if ($response->failed() || !$response->json('data')) {
            throw new RequestException("Fatura önizleme alınamadı: $ettn");
        }

        return $response->json('data');
    }

    public function createDraft(array $invoice): string
    {
        if (!$this->token) {
            throw new RequestException('Fatura oluşturulamadı: önce giriş yapmalısınız.');
        }

        $client = new Client($this->testMode);

        $response = $client->post('/earsiv-services/dispatch', [
            'callid'   => (string) Str::uuid(),
            'token'    => $this->token,
            'cmd'      => 'EARSIV_PORTAL_FATURA_OLUSTUR',
            'pageName' => 'RG_BASITFATURA',
            'jp'       => json_encode($invoice, JSON_UNESCAPED_UNICODE),
        ]);

        $result = $response->json('data');

        if ($response->failed() || !$result || !str_contains($result, 'başarıyla')) {
            throw new RequestException('Fatura oluşturulamadı: ' . ($result ?? 'Bilinmeyen hata'));
        }

        return $invoice['faturaUuid'];
    }
}
