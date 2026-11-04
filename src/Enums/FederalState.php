<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Enums;

/**
 * Federal states as expected by the `federal_state` filter of the LiMS API.
 */
enum FederalState: string
{
    case BadenWuerttemberg = 'Baden-Württemberg';
    case Bayern = 'Bayern';
    case Berlin = 'Berlin';
    case Brandenburg = 'Brandenburg';
    case Bremen = 'Bremen';
    case Hamburg = 'Hamburg';
    case Hessen = 'Hessen';
    case MecklenburgVorpommern = 'Mecklenburg-Vorpommern';
    case Niedersachsen = 'Niedersachsen';
    case NordrheinWestfalen = 'Nordrhein-Westfalen';
    case RheinlandPfalz = 'Rheinland-Pfalz';
    case Saarland = 'Saarland';
    case Sachsen = 'Sachsen';
    case SachsenAnhalt = 'Sachsen-Anhalt';
    case SchleswigHolstein = 'Schleswig-Holstein';
    case Thueringen = 'Thüringen';
    case Bundesverband = 'Bundesverband';
}
