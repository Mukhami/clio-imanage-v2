<?php

namespace App\Enums;

enum CustomFieldSourceType: string
{
    case MatterStatus = 'matter_status';
    case ResponsibleAttorney = 'responsible_attorney';
    case OriginatingAttorney = 'originating_attorney';
    case PracticeArea = 'practice_area';
    case Template = 'template';
    case ClioCustomField = 'clio_custom_field';
    case OpenDate = 'open_date';
    case Static = 'static';
}
