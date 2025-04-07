<?php


namespace Bercanozcan\Earsiv\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class Client
{
    protected string $baseUrl;

    public function __construct(bool $testMode = false)
    {
        $this->baseUrl = $testMode
            ? 'https://earsivportaltest.efatura.gov.tr'
            : 'https://earsivportal.efatura.gov.tr';
    }

    public function post(string $path, array $data, bool $asForm = true): Response
    {
        $request = $asForm
            ? Http::asForm()
            : Http::withHeaders(['Content-Type' => 'application/json']);

        return $request->post($this->baseUrl . $path, $data);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
