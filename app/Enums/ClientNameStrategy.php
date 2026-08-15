<?php

namespace App\Enums;

enum ClientNameStrategy: string
{
    case None = 'none';
    case LastNameFirst = 'last_name_first';
    case ReverseWords = 'reverse_words';
    case CustomTemplate = 'custom_template';
}
