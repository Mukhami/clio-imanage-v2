<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookStatus: string
{
    case Active  = 'active';
    case Expired = 'expired';
    case Failed  = 'failed';
}
