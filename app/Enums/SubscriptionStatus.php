<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Void = 'void';
    case Expired = 'expired';
}
