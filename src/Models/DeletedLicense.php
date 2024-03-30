<?php

namespace TobiasMaxham\PhpLims\Models;

class DeletedLicense extends Model
{
    protected array $casts = [
        'lid' => 'int',
    ];

}