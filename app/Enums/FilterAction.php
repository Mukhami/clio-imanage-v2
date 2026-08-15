<?php

namespace App\Enums;

enum FilterAction: string
{
    case Skip = 'skip';
    case Proceed = 'proceed';
}
