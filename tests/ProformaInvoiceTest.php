<?php

declare(strict_types=1);

use LovaszCC\LaravelInnvoice\LaravelInnvoice;

describe('Proforma Invoice Handling', function () {
    it('should handle proforma invoice responses correctly', function () {
        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;
        $reflection = new ReflectionClass($innvoice);
        $processMethod = $reflection->getMethod('processSuccessfulResponse');
        $processMethod->setAccessible(true);

        // Test proforma invoice response (no Sorszam field)
        $proformaResponse = [
            'proforma_invoice' => [
                'techid' => 'PROF-12345',
                'PrintUrl' => 'https://api.innvoice.hu/download/proforma.pdf',
                'TABLE_ID' => 'PROF-001',
            ],
        ];

        $result = $processMethod->invoke($innvoice, $proformaResponse);

        // Verify proforma invoice handling
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result)->toHaveKey('table_id');

        expect($result['techid'])->toBe('PROF-12345');
        expect($result['invoice_number'])->toBe('PROF-12345'); // Should use techid as invoice number
        expect($result['invoice_url'])->toBe('https://api.innvoice.hu/download/proforma.pdf');
        expect($result['table_id'])->toBe('PROF-001');
    });

    it('should handle regular invoice responses correctly', function () {
        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;
        $reflection = new ReflectionClass($innvoice);
        $processMethod = $reflection->getMethod('processSuccessfulResponse');
        $processMethod->setAccessible(true);

        // Test regular invoice response (with Sorszam field)
        $regularResponse = [
            'invoice' => [
                'techid' => 'INV-67890',
                'Sorszam' => 'INV-2024-001',
                'PrintUrl' => 'https://api.innvoice.hu/download/invoice.pdf',
                'TABLE_ID' => 'INV-001',
            ],
        ];

        $result = $processMethod->invoke($innvoice, $regularResponse);

        // Verify regular invoice handling
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result)->toHaveKey('table_id');

        expect($result['techid'])->toBe('INV-67890');
        expect($result['invoice_number'])->toBe('INV-2024-001'); // Should use Sorszam
        expect($result['invoice_url'])->toBe('https://api.innvoice.hu/download/invoice.pdf');
        expect($result['table_id'])->toBe('INV-001');
    });

    it('should handle proforma invoice without PrintUrl', function () {
        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;
        $reflection = new ReflectionClass($innvoice);
        $processMethod = $reflection->getMethod('processSuccessfulResponse');
        $processMethod->setAccessible(true);

        // Test proforma invoice response without PrintUrl
        $proformaResponse = [
            'proforma_invoice' => [
                'techid' => 'PROF-12345',
                'TABLE_ID' => 'PROF-001',
                // No PrintUrl field
            ],
        ];

        $result = $processMethod->invoke($innvoice, $proformaResponse);

        // Verify proforma invoice handling without PrintUrl
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result)->toHaveKey('table_id');

        expect($result['techid'])->toBe('PROF-12345');
        expect($result['invoice_number'])->toBe('PROF-12345'); // Should use techid as invoice number
        expect($result['invoice_url'])->toBeNull(); // Should be null when no PrintUrl
        expect($result['table_id'])->toBe('PROF-001');
    });

    it('should handle regular invoice without Sorszam', function () {
        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;
        $reflection = new ReflectionClass($innvoice);
        $processMethod = $reflection->getMethod('processSuccessfulResponse');
        $processMethod->setAccessible(true);

        // Test regular invoice response without Sorszam (fallback to techid)
        $regularResponse = [
            'invoice' => [
                'techid' => 'INV-67890',
                'PrintUrl' => 'https://api.innvoice.hu/download/invoice.pdf',
                'TABLE_ID' => 'INV-001',
                // No Sorszam field
            ],
        ];

        $result = $processMethod->invoke($innvoice, $regularResponse);

        // Verify regular invoice handling without Sorszam
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result)->toHaveKey('table_id');

        expect($result['techid'])->toBe('INV-67890');
        expect($result['invoice_number'])->toBe('INV-67890'); // Should fallback to techid
        expect($result['invoice_url'])->toBe('https://api.innvoice.hu/download/invoice.pdf');
        expect($result['table_id'])->toBe('INV-001');
    });
});
