<?php

namespace TobiasMaxham\PhpLims\Models;

class License extends Model
{
    protected array $casts = [
        'lid'                => 'int',
        'training_course_id' => 'int',
        'organisation_id'    => 'int',
        'birthdate'          => 'int',
        'valid_until'        => 'int',
        'issue_date'         => 'int',
        'first_issue_date'   => 'int',
        'honor_code_date'    => 'int',
        'first_aid_date'     => 'int',
        /**
         *  Seems like the service uses a wired timezone for the dates.
         *  So we won't use the DateTime object in this version.
         */
        //        'birthdate'          => 'datetime',
        //        'valid_until'        => 'datetime',
        //        'issue_date'         => 'datetime',
        //        'first_issue_date'   => 'datetime',
        //        'honor_code_date'    => 'datetime',
        //        'first_aid_date'     => 'datetime',
        'honor_code'         => 'bool',
        'first_aid'          => 'bool',
    ];

}