<?php

namespace App\Enums;

enum ParsingStrategy: string
{
    case SplitDelimiter = 'split_delimiter';
    case SplitDelimiterNested = 'split_delimiter_nested';
    case Regex = 'regex';
    case BracketExtraction = 'bracket_extraction';
    case ClioIds = 'clio_ids';
    case CustomFieldExtraction = 'custom_field_extraction';
    case DisplayNumberAsMatter = 'display_number_as_matter';
    case DisplayNumberAsClient = 'display_number_as_client';
    case SequenceAuto = 'sequence_auto';
    case LegacyAliasLookup = 'legacy_alias_lookup';
    case Custom = 'custom';
}
