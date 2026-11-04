<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Enums;

/**
 * Age groups for the `birthdate` filter of the statistics endpoint.
 * The age is calculated in relation to the start date of the query.
 */
enum AgeGroup: int
{
    /** bis 18 Jahre */
    case UpTo18 = 1;

    /** 19 bis 26 Jahre */
    case From19To26 = 2;

    /** 27 bis 40 Jahre */
    case From27To40 = 3;

    /** 41 bis 60 Jahre */
    case From41To60 = 4;

    /** über 60 Jahre */
    case Over60 = 5;
}
