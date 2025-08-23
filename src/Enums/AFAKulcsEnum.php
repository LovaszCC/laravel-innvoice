<?php

namespace LovaszCC\LaravelInnvoice\Enums;

enum AFAKulcsEnum: string
{
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

}
