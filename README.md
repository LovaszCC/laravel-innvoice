# This is a Laravel wrapper for innvoice.hu API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lovaszcc/laravel-innvoice.svg?style=flat-square)](https://packagist.org/packages/lovaszcc/laravel-innvoice)

[![Total Downloads](https://img.shields.io/packagist/dt/lovaszcc/laravel-innvoice.svg?style=flat-square)](https://packagist.org/packages/lovaszcc/laravel-innvoice)

## Telepítés

Telepítsd a csomagot composerrel

```bash
composer require lovaszcc/laravel-innvoice
```

Publikáld a konfigurációs fájlt

```bash
php artisan vendor:publish --tag="innvoice-config"
```

This is the contents of the published config file:

```php
return [
    'username' => env('INNVOICE_USERNAME'),
    'password' => env('INNVOICE_PASSWORD'),
    'company_name' => env('INNVOICE_COMPANY_NAME'),
    'checkbook_id' => env('INNVOICE_CHECKBOOK_ID'),
    'storage_path' => env('INNVOICE_STORAGE_PATH', 'app/public/innvoice'),
];
```

Vedd fel a .env fájlba az innvoice.hu tól kapott adatokat, illetve adj meg egy elérési utat a számlák tárolására, amelyet később e-mailben kiküldesz.

## Checkbook ID

Ahhoz, hogy megtudd mi a számlatömb azonosítója futtasd a következő kódot:

```php
    dd(LaravelInnvoice::getCheckbooks());
```

A visszakapott tömbben láthatod számlatömbjeidet, válaszd ki, hogy melyiket szeretnéd használni és annak a TABLE_ID értékét tedd a checkbook_id env változóba.

## API Dokumentáció

https://innvoicesupport.zendesk.com/hc/hu/sections/360001065819-API-hozz%C3%A1f%C3%A9r%C3%A9s

## Áfa kulcsok

```php
    case EIGHTEEN = '18%';
    case FIVE = '5%';
    case TWENTYSEVEN = '27%';

    // adómentes kulcsok
    case AAM = '0% - AAM'; // alanyi adómentes
    case TAM = '0% - TAM'; // tárgyi adómentes
    case KBA = '0% - KBA'; // adómentes közösségen belüli termékértékesítés
    case KBAUK = '0% - KBAUK'; // adómentes közösségen belüli új közlekedési eszköz értékesítés
    case EAM = '0% - EAM'; // adómentes termékértékesítés a Közésség területén kívülre
    case NAM = '0% - NAM'; // Adómentesség egyéb nemzetközi ügyletekhez

    // ÁFA tv hátályán kívüli kulcsok
    case ATK = '0% - ÁTK';
    case EUFAD37 = '0% - EUFAD37';
    case EUFADE = '0% - EUFADE';
    case EUE = '0% - EUE';
    case HO = '0% - HO';

    case FOA = '0% - FOA';
    case KAFA = '0% - K.AFA';
    case AFAMENTES = '0% - AFAMENTES';
    case NONREFUNDABLE_VAT = '0% - NONREFUNDABLE_VAT';
    case REFUNDABLE_VAT = '0% - REFUNDABLE_VAT';
```

## Számla készítés

```php
use LovaszCC\LaravelInnvoice\Enums\AFAKulcsEnum;
use LovaszCC\LaravelInnvoice\Facades\LaravelInnvoice;

    $tetelek = [
        [
            'TetelNev' => 'Próba tétel 1',
            'AfaSzoveg' => AFAKulcsEnum::AAM->value,
            'Brutto' => '1',
            'EgysegAr' => '1700',
            'Mennyiseg' => '1',
            'MennyisegEgyseg' => 'db',
        ],
        [
            'TetelNev' => 'Próba tétel 2',
            'AfaSzoveg' => AFAKulcsEnum::AAM->value,
            'Brutto' => '1',
            'EgysegAr' => '2600',
            'Mennyiseg' => '1',
            'MennyisegEgyseg' => 'db',
        ],
    ];

    $data = [
        'invoices' => [
            'invoice' => [
                'VevoNev' => 'Gipsz Jakab',
                'VevoIrsz' => '1119',
                'VevoOrszag' => 'HU',
                'VevoTelep' => 'Budapest',
                'VevoUtcaHsz' => 'Próba u. 2.',
                'SzamlatombID' => '1',
                'SzamlaKelte' => '2025.08.23.',
                'TeljesitesKelte' => '2025.08.23.',
                'Hatarido' => '2025.08.23.',
                'Devizanem' => 'Ft',
                'FizetesiMod' => 'bankkártya', // szöveges mező bármi értéke lehet
                'Fizetve' => '1', // 1 fizetett státusz 0 nem fizetett státusz
                'Eszamla' => '1', // 0 papír alapú számla 1 elektronikus számla
                'VevoAdoszam' => '12345678-x-yy', // Ne kerüljön a tömbbe ha magánszemély
                'Felretett' => '0', // fizetett státusz esetén 0 mint lezárt számla 1 esetén "piszkozat"
                'Proforma' => '0',
            ],
        ],
    ];
    LaravelInnvoice::createInvoice($data, $tetelek)
```

## Visszaadott adatok

```php
array:3 [▼ /
  "techid" => "techid számsor"
  "invoice_number" => "sorszám"
  "invoice_url" => "printurl amiről le tudod tölteni a számlát"
]
```

## Proforma számla készítés

```php
    $tetelek = [
        [
            'TetelNev' => 'Próba tétel 1',
            'AfaSzoveg' => AFAKulcsEnum::AAM->value,
            'Brutto' => '1',
            'EgysegAr' => '1700',
            'Mennyiseg' => '1',
            'MennyisegEgyseg' => 'db',
        ],
        [
            'TetelNev' => 'Próba tétel 2',
            'AfaSzoveg' => AFAKulcsEnum::AAM->value,
            'Brutto' => '1',
            'EgysegAr' => '2600',
            'Mennyiseg' => '1',
            'MennyisegEgyseg' => 'db',
        ],
    ];

    $data = [
        'invoices' => [
            'invoice' => [
                'VevoNev' => 'Gipsz Jakab',
                'VevoIrsz' => '1119',
                'VevoOrszag' => 'HU',
                'VevoTelep' => 'Budapest',
                'VevoUtcaHsz' => 'Próba u. 2.',
                'SzamlatombID' => '1',
                'SzamlaKelte' => date('Y.m.d.'),
                'TeljesitesKelte' => date('Y.m.d.'),
                'Hatarido' => date('Y.m.d.'),
                'Devizanem' => 'HUF',
                'FizetesiMod' => 'átutalás',
                'Fizetve' => '0',
                'Eszamla' => '1',
                'VevoAdoszam' => '12345678-1-27', // Magánszemély esetén ne kerüljön a tömbbe
                'Felretett' => '0',
                'Proforma' => '1',
            ],
        ],
    ];
    LaravelInnvoice::createInvoice($data, $tetelek)
```

## Számla készítés proforma számlából

```php
    $proforma = [
        'proforma_invoices' => [
            'proforma_invoice' => [
                'TABLE_ID' => 'PROFORMA_SZÁMLA_TABLE_ID_ÉRTÉKE',
                'SzamlaKelte' => date('Y.m.d.'),
                'TeljesitesKelte' => date('Y.m.d.'),
                'Hatarido' => date('Y.m.d.'),

            ],
        ],
    ];
    LaravelInnvoice::createInvoiceFromProforma($proforma);
```

## Credits

-   [Lovász Krisztián](https://github.com/LovaszCC)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
