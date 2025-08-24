<?php

declare(strict_types=1);

use LovaszCC\LaravelInnvoice\Helpers\XMLHelpers;

describe('XMLHelpers', function () {
    describe('parseXmlToArray', function () {
        it('should parse simple XML response from API', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <invoice>
        <techid>12345</techid>
        <Sorszam>INV-2024-001</Sorszam>
        <PrintUrl>https://api.innvoice.hu/download/invoice.pdf</PrintUrl>
    </invoice>
</response>';

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                'invoice' => [
                    'techid' => '12345',
                    'Sorszam' => 'INV-2024-001',
                    'PrintUrl' => 'https://api.innvoice.hu/download/invoice.pdf',
                ],
            ]);
        });

        it('should parse XML with CDATA sections', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <error>500</error>
    <message><![CDATA[Invoice creation failed due to invalid data]]></message>
</response>';

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                'error' => '500',
                'message' => 'Invoice creation failed due to invalid data',
            ]);
        });

        it('should handle XML with attributes', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<response status="success">
    <invoice id="12345">
        <techid>12345</techid>
    </invoice>
</response>';

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                '@attributes' => [
                    'status' => 'success',
                ],
                'invoice' => [
                    '@attributes' => [
                        'id' => '12345',
                    ],
                    'techid' => '12345',
                ],
            ]);
        });

        it('should handle XML with multiple children of same name', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <invoices>
        <invoice>
            <techid>12345</techid>
        </invoice>
        <invoice>
            <techid>67890</techid>
        </invoice>
    </invoices>
</response>';

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                'invoices' => [
                    'invoice' => [
                        [
                            'techid' => '12345',
                        ],
                        [
                            'techid' => '67890',
                        ],
                    ],
                ],
            ]);
        });

        it('should clean XML string before parsing', function () {
            $xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\t<response>\n\t\t<invoice>\n\t\t\t<techid>12345</techid>\n\t\t</invoice>\n\t</response>";

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                'invoice' => [
                    'techid' => '12345',
                ],
            ]);
        });

        it('should throw exception for invalid XML', function () {
            $invalidXml = '<response><invoice><techid>12345</techid></response>';

            expect(fn () => XMLHelpers::parseXmlToArray($invalidXml))
                ->toThrow(Exception::class);
        });
    });

    describe('buildXmlFromArray', function () {
        it('should build XML from invoice data structure', function () {
            $data = [
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

            $result = XMLHelpers::buildXmlFromArray($data);

            expect($result)->toContain('<invoices>');
            expect($result)->toContain('<invoice>');
            expect($result)->toContain('<VevoNev><![CDATA[Test Company Ltd.]]></VevoNev>');
            expect($result)->toContain('<TetelNev><![CDATA[Web Development Services]]></TetelNev>');
            expect($result)->toContain('<Brutto><![CDATA[127000]]></Brutto>');
        });

        it('should handle nested arrays correctly', function () {
            $data = [
                'invoices' => [
                    'invoice' => [
                        'VevoNev' => 'Test Company',
                        'items' => [
                            'item' => [
                                'name' => 'Service 1',
                                'price' => '100',
                            ],
                        ],
                    ],
                ],
            ];

            $result = XMLHelpers::buildXmlFromArray($data);

            expect($result)->toContain('<invoices>');
            expect($result)->toContain('<invoice>');
            expect($result)->toContain('<items>');
            expect($result)->toContain('<item>');
            expect($result)->toContain('<name><![CDATA[Service 1]]></name>');
            expect($result)->toContain('<price><![CDATA[100]]></price>');
        });

        it('should handle multiple items in array', function () {
            $data = [
                'invoices' => [
                    'invoice' => [
                        'VevoNev' => 'Test Company',
                        'items' => [
                            [
                                'name' => 'Service 1',
                                'price' => '100',
                            ],
                            [
                                'name' => 'Service 2',
                                'price' => '200',
                            ],
                        ],
                    ],
                ],
            ];

            $result = XMLHelpers::buildXmlFromArray($data);

            expect($result)->toContain('<name><![CDATA[Service 1]]></name>');
            expect($result)->toContain('<price><![CDATA[100]]></price>');
            expect($result)->toContain('<name><![CDATA[Service 2]]></name>');
            expect($result)->toContain('<price><![CDATA[200]]></price>');
        });

        it('should wrap all values in CDATA', function () {
            $data = [
                'test' => [
                    'simple' => 'simple value',
                    'special' => 'value with <tags> & symbols',
                    'number' => '12345',
                ],
            ];

            $result = XMLHelpers::buildXmlFromArray($data);

            expect($result)->toContain('<simple><![CDATA[simple value]]></simple>');
            expect($result)->toContain('<special><![CDATA[value with <tags> & symbols]]></special>');
            expect($result)->toContain('<number><![CDATA[12345]]></number>');
        });
    });

    describe('cleanXmlString', function () {
        it('should remove tabs and normalize whitespace', function () {
            $xmlString = "\t<response>\n\t\t<invoice>\n\t\t\t<techid>12345</techid>\n\t\t</invoice>\n\t</response>";

            $result = XMLHelpers::cleanXmlString($xmlString);

            expect($result)->toBe('<response><invoice><techid>12345</techid></invoice></response>');
        });

        it('should remove empty lines', function () {
            $xmlString = "<response>\n\n<invoice>\n\n<techid>12345</techid>\n\n</invoice>\n\n</response>";

            $result = XMLHelpers::cleanXmlString($xmlString);

            expect($result)->toBe('<response><invoice><techid>12345</techid></invoice></response>');
        });

        it('should trim whitespace around tags', function () {
            $xmlString = '<response> <invoice> <techid>12345</techid> </invoice> </response>';

            $result = XMLHelpers::cleanXmlString($xmlString);

            expect($result)->toBe('<response><invoice><techid>12345</techid></invoice></response>');
        });

        it('should handle mixed whitespace and tabs', function () {
            $xmlString = "\t<response>\n\t\t<invoice>\n\t\t\t<techid>12345</techid>\n\t\t\t<status>active</status>\n\t\t</invoice>\n\t</response>";

            $result = XMLHelpers::cleanXmlString($xmlString);

            expect($result)->toBe('<response><invoice><techid>12345</techid><status>active</status></invoice></response>');
        });
    });

    describe('xmlToArray', function () {
        it('should convert SimpleXML element to array', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?><response><invoice><techid>12345</techid></invoice></response>';
            $xml = simplexml_load_string($xmlString);

            $result = XMLHelpers::xmlToArray($xml);

            expect($result)->toBe([
                'invoice' => [
                    'techid' => '12345',
                ],
            ]);
        });

        it('should handle leaf nodes with string values', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?><response>Success</response>';
            $xml = simplexml_load_string($xmlString);

            $result = XMLHelpers::xmlToArray($xml);

            expect($result)->toBe('Success');
        });

        it('should handle CDATA in leaf nodes', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?><response><![CDATA[Success with CDATA]]></response>';
            $xml = simplexml_load_string($xmlString);

            $result = XMLHelpers::xmlToArray($xml);

            expect($result)->toBe('Success with CDATA');
        });

        it('should handle attributes in XML elements', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?><response status="success"><invoice id="12345"><techid>12345</techid></invoice></response>';
            $xml = simplexml_load_string($xmlString);

            $result = XMLHelpers::xmlToArray($xml);

            expect($result)->toBe([
                '@attributes' => [
                    'status' => 'success',
                ],
                'invoice' => [
                    '@attributes' => [
                        'id' => '12345',
                    ],
                    'techid' => '12345',
                ],
            ]);
        });
    });

    describe('Integration tests based on LaravelInnvoice usage', function () {
        it('should handle API response format from getCheckbooks', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<tomb_invoice>
    <invoice>
        <techid>12345</techid>
        <Sorszam>INV-2024-001</Sorszam>
        <VevoNev>Test Company</VevoNev>
    </invoice>
    <invoice>
        <techid>67890</techid>
        <Sorszam>INV-2024-002</Sorszam>
        <VevoNev>Another Company</VevoNev>
    </invoice>
</tomb_invoice>';

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                'invoice' => [
                    [
                        'techid' => '12345',
                        'Sorszam' => 'INV-2024-001',
                        'VevoNev' => 'Test Company',
                    ],
                    [
                        'techid' => '67890',
                        'Sorszam' => 'INV-2024-002',
                        'VevoNev' => 'Another Company',
                    ],
                ],
            ]);
        });

        it('should handle error response format from createInvoice', function () {
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<response>
    <error>500</error>
    <message><![CDATA[Invoice creation failed due to invalid customer data]]></message>
</response>';

            $result = XMLHelpers::parseXmlToArray($xmlString);

            expect($result)->toBe([
                'error' => '500',
                'message' => 'Invoice creation failed due to invalid customer data',
            ]);
        });

        it('should build XML for invoice creation request', function () {
            $data = [
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

            $result = XMLHelpers::buildXmlFromArray($data);

            // Verify the structure matches what LaravelInnvoice expects
            expect($result)->toContain('<invoices>');
            expect($result)->toContain('<invoice>');
            expect($result)->toContain('<VevoNev><![CDATA[Test Company Ltd.]]></VevoNev>');
            expect($result)->toContain('<TetelNev><![CDATA[Web Development Services]]></TetelNev>');
            expect($result)->toContain('<Brutto><![CDATA[127000]]></Brutto>');
        });
    });
});
