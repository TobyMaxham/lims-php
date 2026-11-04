<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Enums;

/**
 * Organisation categories (Verbändegruppen) for the `organisation_category` filter.
 */
enum OrganisationCategory: string
{
    /** Spitzenverbände */
    case Spitzenverband = 'SV';

    /** Landessportbünde/-verbände */
    case Landessportbund = 'LSB';

    /** Verbände mit besonderen Aufgaben */
    case VerbandMitBesonderenAufgaben = 'VmbA';

    /** Untergliederung */
    case Untergliederung = 'UG';
}
