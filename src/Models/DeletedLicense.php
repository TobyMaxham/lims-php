<?php

namespace LimsPhp\Lims\Models;

class DeletedLicense extends Model
{
    protected array $casts = [
        'lid' => 'int',
    ];

}