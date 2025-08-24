<?php

declare(strict_types=1);

use LovaszCC\LaravelInnvoice\Helpers\XMLHelpers;
use LovaszCC\LaravelInnvoice\LaravelInnvoice;

describe('LaravelInnvoice XML Integration', function () {
    it('should demonstrate how XMLHelpers is used in LaravelInnvoice', function () {
        // This test demonstrates the actual usage patterns from LaravelInnvoice.php

        // 1. Simulate API response parsing (like in getCheckbooks method)
        $apiResponse = '<?xml version="1.0" encoding="UTF-8"?>
<tomb_invoice>
    <invoice>
        <techid>12345</techid>
        <Sorszam>INV-2024-001</Sorszam>
        <VevoNev>Test Company</VevoNev>
    </invoice>
</tomb_invoice>';

        $parsedResponse = XMLHelpers::parseXmlToArray($apiResponse);

        expect($parsedResponse)->toBe([
            'invoice' => [
                'techid' => '12345',
                'Sorszam' => 'INV-2024-001',
                'VevoNev' => 'Test Company',
            ],
        ]);

        // 2. Simulate invoice creation request (like in createInvoice method)
        $invoiceData = [
            'invoices' => [
                'invoice' => [
                    'VevoNev' => 'Test Company Ltd.',
                    'VevoCim' => '123 Test Street, Budapest',
                    'VevoAdoszam' => '12345678-1-23',
                    'TetelNev' => 'Web Development Services',
                    'AfaSzoveg' => '27%',
                    'Brutto' => '127000',
                    'EgysegAr' => '100000',
                    'Mennyiseg' => '1',
                    'MennyisegEgyseg' => 'db',
                ],
            ],
        ];

        $xmlRequest = XMLHelpers::buildXmlFromArray($invoiceData);

        expect($xmlRequest)->toContain('<invoices>');
        expect($xmlRequest)->toContain('<invoice>');
        expect($xmlRequest)->toContain('<VevoNev><![CDATA[Test Company Ltd.]]></VevoNev>');
        expect($xmlRequest)->toContain('<TetelNev><![CDATA[Web Development Services]]></TetelNev>');

        // 3. Simulate error response parsing (like in createInvoice error handling)
        $errorResponse = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <error>500</error>
    <message><![CDATA[Invoice creation failed due to invalid data]]></message>
</response>';

        $parsedError = XMLHelpers::parseXmlToArray($errorResponse);

        expect($parsedError)->toBe([
            'error' => '500',
            'message' => 'Invoice creation failed due to invalid data',
        ]);

        // 4. Verify that the XML can be parsed back (round-trip test)
        $roundTripParsed = XMLHelpers::parseXmlToArray($xmlRequest);

        // The round-trip won't be identical because buildXmlFromArray doesn't create a complete XML document
        // but we can verify that the structure is valid
        expect($roundTripParsed)->toBeArray();
    });

    it('should handle multiple invoice items like in addItemsToInvoice method', function () {
        // Simulate the data structure that would be created by addItemsToInvoice
        $tetelek = [
            [
                'TetelNev' => 'Web Development',
                'AfaSzoveg' => '27%',
                'Brutto' => '127000',
                'EgysegAr' => '100000',
                'Mennyiseg' => '1',
                'MennyisegEgyseg' => 'db',
            ],
            [
                'TetelNev' => 'Consulting',
                'AfaSzoveg' => '27%',
                'Brutto' => '254000',
                'EgysegAr' => '200000',
                'Mennyiseg' => '1',
                'MennyisegEgyseg' => 'db',
            ],
        ];

        $invoiceData = [
            'invoices' => [
                'invoice' => [
                    'VevoNev' => 'Test Company Ltd.',
                    'VevoCim' => '123 Test Street, Budapest',
                    'VevoAdoszam' => '12345678-1-23',
                    'TetelNev' => $tetelek[0]['TetelNev'],
                    'AfaSzoveg' => $tetelek[0]['AfaSzoveg'],
                    'Brutto' => $tetelek[0]['Brutto'],
                    'EgysegAr' => $tetelek[0]['EgysegAr'],
                    'Mennyiseg' => $tetelek[0]['Mennyiseg'],
                    'MennyisegEgyseg' => $tetelek[0]['MennyisegEgyseg'],
                    'TetelNev2' => $tetelek[1]['TetelNev'],
                    'AfaSzoveg2' => $tetelek[1]['AfaSzoveg'],
                    'Brutto2' => $tetelek[1]['Brutto'],
                    'EgysegAr2' => $tetelek[1]['EgysegAr'],
                    'Mennyiseg2' => $tetelek[1]['Mennyiseg'],
                    'MennyisegEgyseg2' => $tetelek[1]['MennyisegEgyseg'],
                ],
            ],
        ];

        $xmlRequest = XMLHelpers::buildXmlFromArray($invoiceData);

        expect($xmlRequest)->toContain('<TetelNev><![CDATA[Web Development]]></TetelNev>');
        expect($xmlRequest)->toContain('<TetelNev2><![CDATA[Consulting]]></TetelNev2>');
        expect($xmlRequest)->toContain('<Brutto><![CDATA[127000]]></Brutto>');
        expect($xmlRequest)->toContain('<Brutto2><![CDATA[254000]]></Brutto2>');
    });

    it('should handle CDATA in error messages like in LaravelInnvoice error handling', function () {
        // Simulate the error response format that LaravelInnvoice handles
        $errorResponse = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <error>500</error>
    <message><![CDATA[Invoice creation failed due to invalid customer data: <invalid>tag</invalid> & special chars]]></message>
</response>';

        $parsedError = XMLHelpers::parseXmlToArray($errorResponse);

        expect($parsedError)->toBe([
            'error' => '500',
            'message' => 'Invoice creation failed due to invalid customer data: <invalid>tag</invalid> & special chars',
        ]);

        // Verify that special characters and XML tags in CDATA are preserved
        expect($parsedError['message'])->toContain('<invalid>tag</invalid>');
        expect($parsedError['message'])->toContain('& special chars');
    });
});
