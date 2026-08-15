<?php

namespace App\Enums;

enum FilterOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case MatchesRegex = 'matches_regex';
    case Contains = 'contains';
    case ClioPicklistEquals = 'clio_picklist_equals';
}
