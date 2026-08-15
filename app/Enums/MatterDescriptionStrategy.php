<?php

namespace App\Enums;

enum MatterDescriptionStrategy: string
{
    case None = 'none';
    case UseDisplayNumber = 'use_display_number';
    case UseClientDescription = 'use_client_description';
    case CompositeTemplate = 'composite_template';
    case StripPrefix = 'strip_prefix';
}
