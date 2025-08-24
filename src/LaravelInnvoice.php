<?php

declare(strict_types=1);

namespace LovaszCC\LaravelInnvoice;

use Exception;
use Illuminate\Support\Facades\Http;
use LovaszCC\LaravelInnvoice\Helpers\XMLHelpers;

final class LaravelInnvoice
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

    public function getCheckbooks(): array
    {

        $endpoint = "https://api.innvoice.hu/{$this->company_name}/tomb_invoice";
        $response = Http::withHeaders($this->setHeaders())->get($endpoint);

        return XMLHelpers::parseXmlToArray($response->body());
    }

    public function createInvoice(array $data, array $tetelek = []): array
    {
        $endpoint = "https://api.innvoice.hu/{$this->company_name}/invoice";

        if ($tetelek === null) {
            throw new Exception('Tetelek are required');
        }
        $this->addItemsToInvoice($data, $tetelek);
        $xmlData = XMLHelpers::buildXmlFromArray($data);
        $response = Http::withHeaders($this->setHeaders())->withBody($xmlData, 'text/xml')->post($endpoint);
        $responseBody = $response->body();

        try {
            $parsedResponse = XMLHelpers::parseXmlToArray($responseBody);

            $this->checkForApiErrors($parsedResponse);

            return $this->processSuccessfulResponse($parsedResponse);

        } catch (Exception $e) {
            $this->handleApiError($responseBody, $e);

            return [];
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
            throw new Exception("Failed to download invoice PDF: HTTP {$response->status()}");
        }

        $filename = $invoiceNumber.'.pdf';
        $filePath = $storagePath.'/'.$filename;

        file_put_contents($filePath, $response->body());

        return $filePath;
    }

    public function createInvoiceFromProforma(array $data): array
    {
        $endpoint = "https://api.innvoice.hu/{$this->company_name}/proforma_invoice";

        $xmlData = XMLHelpers::buildXmlFromArray($data);
        $response = Http::withHeaders($this->setHeaders())->withBody($xmlData, 'text/xml')->post($endpoint);

        $responseBody = $response->body();

        try {
            $parsedResponse = XMLHelpers::parseXmlToArray($responseBody);

            $this->checkForApiErrors($parsedResponse);

            return $this->processSuccessfulResponse($parsedResponse);

        } catch (Exception $e) {

            $this->handleApiError($responseBody, $e);

            return [];
        }
    }

    private function getInvoceNumberFromProforma(string $techid): string
    {
        $endpoint = "https://api.innvoice.hu/{$this->company_name}/invoice/techid/{$techid}";

        $response = Http::withHeaders($this->setHeaders())->get($endpoint);

        $body = XMLHelpers::parseXmlToArray($response->body());

        return $body['invoice']['SorszamFormatted'];
    }

    private function setHeaders(): array
    {

        return [
            'Content-Type' => 'text/xml',
            'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password),
        ];
    }

    private function checkForApiErrors(array $parsedResponse): void
    {
        if (isset($parsedResponse['response']['error'])) {
            $errorCode = $parsedResponse['response']['error'] ?? 'Unknown';
            $errorMessage = $parsedResponse['response']['message'] ?? 'No error message provided';

            throw new Exception("API Error {$errorCode}: {$errorMessage}");
        }
    }

    private function processSuccessfulResponse(array $parsedResponse): array
    {
        $returnData = [];

        if (isset($parsedResponse['invoice']['techid']) || isset($parsedResponse['proforma_invoice']['techid'])) {

            $isProforma = isset($parsedResponse['proforma_invoice']['techid']);

            if ($isProforma) {

                $returnData['techid'] = $parsedResponse['proforma_invoice']['techid'];
                try {
                    $invoice_number = $this->getInvoceNumberFromProforma($parsedResponse['proforma_invoice']['techid']);
                } catch (Exception $e) {
                    $invoice_number = $parsedResponse['proforma_invoice']['techid'];
                }
                $returnData['invoice_number'] = $invoice_number;
                $returnData['invoice_url'] = $parsedResponse['proforma_invoice']['PrintUrl'] ?? null;
                $returnData['table_id'] = $parsedResponse['proforma_invoice']['TABLE_ID'] ?? null;

                $downloadUrl = $parsedResponse['proforma_invoice']['PrintUrl'] ?? null;
                $downloadFileName = str_replace('/', '_', $invoice_number);
            } else {

                $returnData['techid'] = $parsedResponse['invoice']['techid'];
                $returnData['invoice_number'] = $parsedResponse['invoice']['Sorszam'] ?? $parsedResponse['invoice']['techid'];
                $returnData['invoice_url'] = $parsedResponse['invoice']['PrintUrl'] ?? null;
                $returnData['table_id'] = $parsedResponse['invoice']['TABLE_ID'] ?? null;

                $downloadUrl = $parsedResponse['invoice']['PrintUrl'] ?? null;
                $downloadFileName = $parsedResponse['invoice']['Sorszam'] ?? $parsedResponse['invoice']['techid'];
            }

            if ($downloadUrl) {
                try {
                    $this->downloadInvoice($downloadUrl, $downloadFileName);
                } catch (Exception $e) {
                    $returnData['error'] = 500;
                    $returnData['error_message'] = 'Invoice download failed';
                }
            }
        } else {
            $returnData['error'] = 500;
            $returnData['error_message'] = 'Invoice creation failed';
        }

        return $returnData;
    }

    private function handleApiError(string $responseBody, Exception $originalException): void
    {
        if (str_contains($responseBody, '<error>')) {
            preg_match('/<error>(.*?)<\/error>/s', $responseBody, $errorMatches);
            preg_match('/<message>(.*?)<\/message>/s', $responseBody, $messageMatches);

            $errorCode = $errorMatches[1] ?? 'Unknown';
            $errorMessage = $messageMatches[1] ?? 'No error message provided';

            $errorMessage = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $errorMessage);

            throw new Exception("API Error {$errorCode}: {$errorMessage}");
        }

        throw new Exception('Failed to parse API response: '.$originalException->getMessage());
    }

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
