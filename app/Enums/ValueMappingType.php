<?php

namespace App\Enums;

enum ValueMappingType: string
{
    case Direct = 'direct';
    case Lookup = 'lookup';
    case Static = 'static';
    case DateFormat = 'date_format';
}
