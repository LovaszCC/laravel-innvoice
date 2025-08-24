<?php

declare(strict_types=1);

use LovaszCC\LaravelInnvoice\LaravelInnvoice;

describe('LaravelInnvoice Refactored Methods', function () {
    it('should demonstrate the refactored createInvoice method structure', function () {
        // This test demonstrates that the refactored methods work correctly
        // We'll use reflection to test the private methods

        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;

        // Test checkForApiErrors method
        $reflection = new ReflectionClass($innvoice);
        $checkForApiErrorsMethod = $reflection->getMethod('checkForApiErrors');
        $checkForApiErrorsMethod->setAccessible(true);

        // Test with no errors (should not throw)
        $noErrorResponse = ['invoice' => ['techid' => '12345']];
        $checkForApiErrorsMethod->invoke($innvoice, $noErrorResponse);

        // Test with API error (should throw)
        $errorResponse = [
            'response' => [
                'error' => '500',
                'message' => 'Test error message',
            ],
        ];

        expect(fn () => $checkForApiErrorsMethod->invoke($innvoice, $errorResponse))
            ->toThrow(Exception::class, 'API Error 500: Test error message');
    });

    it('should demonstrate processSuccessfulResponse method', function () {
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

        // Test successful response with regular invoice data
        $successResponse = [
            'invoice' => [
                'techid' => '12345',
                'Sorszam' => 'INV-2024-001',
                'PrintUrl' => 'https://api.innvoice.hu/download/invoice.pdf',
            ],
        ];

        $result = $processMethod->invoke($innvoice, $successResponse);

        // The result might include download error since we're in test environment
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result['techid'])->toBe('12345');
        expect($result['invoice_number'])->toBe('INV-2024-001');
        expect($result['invoice_url'])->toBe('https://api.innvoice.hu/download/invoice.pdf');

        // Test successful response with proforma invoice data
        $proformaResponse = [
            'proforma_invoice' => [
                'techid' => '67890',
                'PrintUrl' => 'https://api.innvoice.hu/download/proforma.pdf',
                'TABLE_ID' => 'PROF-001',
            ],
        ];

        $result = $processMethod->invoke($innvoice, $proformaResponse);

        // For proforma invoices, invoice_number should be the techid
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result)->toHaveKey('table_id');
        expect($result['techid'])->toBe('67890');
        expect($result['invoice_number'])->toBe('67890'); // Should be techid for proforma
        expect($result['invoice_url'])->toBe('https://api.innvoice.hu/download/proforma.pdf');
        expect($result['table_id'])->toBe('PROF-001');

        // Test response without invoice data (should return error)
        $noInvoiceResponse = ['some' => 'data'];
        $result = $processMethod->invoke($innvoice, $noInvoiceResponse);

        expect($result)->toBe([
            'error' => 500,
            'error_message' => 'Invoice creation failed',
        ]);
    });

    it('should demonstrate handleApiError method', function () {
        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;
        $reflection = new ReflectionClass($innvoice);
        $handleErrorMethod = $reflection->getMethod('handleApiError');
        $handleErrorMethod->setAccessible(true);

        // Test with XML error response
        $errorResponseBody = '<?xml version="1.0"?><response><error>500</error><message><![CDATA[Test error]]></message></response>';
        $originalException = new Exception('Original error');

        expect(fn () => $handleErrorMethod->invoke($innvoice, $errorResponseBody, $originalException))
            ->toThrow(Exception::class, 'API Error 500: Test error');

        // Test with non-XML error (should throw original exception message)
        $nonXmlResponse = 'Some non-XML response';

        expect(fn () => $handleErrorMethod->invoke($innvoice, $nonXmlResponse, $originalException))
            ->toThrow(Exception::class, 'Failed to parse API response: Original error');
    });

    it('should show how the refactored methods can be reused', function () {
        // This demonstrates how the extracted methods can be used in other functions
        // For example, if you wanted to create a createInvoiceFromProforma method

        // Mock the config values to avoid constructor issues
        config(['innvoice.username' => 'test_user']);
        config(['innvoice.password' => 'test_pass']);
        config(['innvoice.company_name' => 'test_company']);
        config(['innvoice.checkbook_id' => 'test_checkbook']);
        config(['innvoice.storage_path' => 'test_storage']);

        $innvoice = new LaravelInnvoice;
        $reflection = new ReflectionClass($innvoice);

        // You could use the same error handling methods in other functions
        $checkForApiErrorsMethod = $reflection->getMethod('checkForApiErrors');
        $processSuccessfulResponseMethod = $reflection->getMethod('processSuccessfulResponse');
        $handleApiErrorMethod = $reflection->getMethod('handleApiError');

        $checkForApiErrorsMethod->setAccessible(true);
        $processSuccessfulResponseMethod->setAccessible(true);
        $handleApiErrorMethod->setAccessible(true);

        // Example: How you might use these in createInvoiceFromProforma
        $mockProformaResponse = [
            'proforma_invoice' => [
                'techid' => '67890',
                'PrintUrl' => 'https://api.innvoice.hu/download/proforma.pdf',
                'TABLE_ID' => 'PROF-001',
            ],
        ];

        // Check for errors (reusable)
        $checkForApiErrorsMethod->invoke($innvoice, $mockProformaResponse);

        // Process response (reusable)
        $result = $processSuccessfulResponseMethod->invoke($innvoice, $mockProformaResponse);

        // The result might include download error since we're in test environment
        expect($result)->toHaveKey('techid');
        expect($result)->toHaveKey('invoice_number');
        expect($result)->toHaveKey('invoice_url');
        expect($result)->toHaveKey('table_id');
        expect($result['techid'])->toBe('67890');
        expect($result['invoice_number'])->toBe('67890'); // Should be techid for proforma
        expect($result['invoice_url'])->toBe('https://api.innvoice.hu/download/proforma.pdf');
        expect($result['table_id'])->toBe('PROF-001');
    });
});
