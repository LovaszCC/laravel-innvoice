<?php

namespace LovaszCC\LaravelInnvoice;

use Illuminate\Support\Facades\Http;
use LovaszCC\LaravelInnvoice\Helpers\XMLHelpers;

class LaravelInnvoice
{
    public string $username;

    public string $password;

    public string $company_name;

    public string $checkbook_id;

    public string $storage_path;

    public function __construct()
    {
        $this->username = config('innvoice.username');
        $this->password = config('innvoice.password');
        $this->company_name = config('innvoice.company_name');
        $this->checkbook_id = config('innvoice.checkbook_id');
        $this->storage_path = config('innvoice.storage_path');
    }

    private function setHeaders(): array
    {
        // Basic Authentication
        return [
            'Content-Type' => 'text/xml',
            'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password),
        ];
    }

    public function getCheckbooks(): array
    {

        $endpoint = "https://api.innvoice.hu/{$this->company_name}/tomb_invoice";
        $response = Http::withHeaders($this->setHeaders())->get($endpoint);

        return XMLHelpers::parseXmlToArray($response->body());
    }

    public function createInvoice(array $data, array $tetelek = []): array
    {
        $endpoint = "https://api.innvoice.hu/{$this->company_name}/invoice";
        if ($tetelek == null) {
            throw new \Exception('Tetelek are required');
        }

        // Add items to the invoice data
        $this->addItemsToInvoice($data, $tetelek);

        $xmlData = XMLHelpers::buildXmlFromArray($data);
        $response = Http::withHeaders($this->setHeaders())->withBody($xmlData, 'text/xml')->post($endpoint);

        $responseBody = $response->body();

        try {
            $parsedResponse = XMLHelpers::parseXmlToArray($responseBody);

            if (isset($parsedResponse['response']['error'])) {
                $errorCode = $parsedResponse['response']['error'] ?? 'Unknown';
                $errorMessage = $parsedResponse['response']['message'] ?? 'No error message provided';

                throw new \Exception("API Error {$errorCode}: {$errorMessage}");
            }

            $returnData = [];
            if (isset($parsedResponse['invoice']['techid'])) {
                $returnData['techid'] = $parsedResponse['invoice']['techid'];
                $returnData['invoice_number'] = $parsedResponse['invoice']['Sorszam'];
                $returnData['invoice_url'] = $parsedResponse['invoice']['PrintUrl'];

                try {
                    $this->downloadInvoice($parsedResponse['invoice']['PrintUrl'], $parsedResponse['invoice']['Sorszam']);
                } catch (\Exception $e) {
                    $returnData['error'] = 500;
                    $returnData['error_message'] = 'Invoice download failed';
                }
            } else {
                $returnData['error'] = 500;
                $returnData['error_message'] = 'Invoice creation failed';
            }

            return $returnData;

        } catch (\Exception $e) {
            if (str_contains($responseBody, '<error>')) {
                preg_match('/<error>(.*?)<\/error>/s', $responseBody, $errorMatches);
                preg_match('/<message>(.*?)<\/message>/s', $responseBody, $messageMatches);

                $errorCode = $errorMatches[1] ?? 'Unknown';
                $errorMessage = $messageMatches[1] ?? 'No error message provided';

                $errorMessage = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $errorMessage);

                throw new \Exception("API Error {$errorCode}: {$errorMessage}");
            }

            throw new \Exception('Failed to parse API response: '.$e->getMessage());
        }
    }

    public function downloadInvoice(string $url, string $invoiceNumber): string
    {
        $invoiceNumber = str_replace('/', '_', $invoiceNumber);
        $storagePath = storage_path($this->storage_path);

        if (! file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $response = Http::get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to download invoice PDF: HTTP {$response->status()}");
        }

        $filename = $invoiceNumber.'.pdf';
        $filePath = $storagePath.'/'.$filename;

        file_put_contents($filePath, $response->body());

        return $filePath;
    }

    /**
     * Add items to the invoice data structure
     */
    private function addItemsToInvoice(array &$data, array $tetelek): void
    {
        $itemCount = count($tetelek);

        foreach ($tetelek as $index => $item) {
            $suffix = $itemCount > 1 ? ($index + 1) : '';

            $data['invoices']['invoice']["TetelNev{$suffix}"] = $item['TetelNev'];
            $data['invoices']['invoice']["AfaSzoveg{$suffix}"] = $item['AfaSzoveg'];
            $data['invoices']['invoice']["Brutto{$suffix}"] = $item['Brutto'];
            $data['invoices']['invoice']["EgysegAr{$suffix}"] = $item['EgysegAr'];
            $data['invoices']['invoice']["Mennyiseg{$suffix}"] = $item['Mennyiseg'];
            $data['invoices']['invoice']["MennyisegEgyseg{$suffix}"] = $item['MennyisegEgyseg'];
        }
    }
}
