<?php

declare(strict_types=1);

namespace App\Enums;

enum ProcessingStage: string
{
    case Received      = 'received';
    case Validated     = 'validated';
    case Parsed        = 'parsed';
    case Filtered      = 'filtered';
    case Enqueued      = 'enqueued';
    case Processing    = 'processing';
    case PostProcessing = 'post_processing';
    case Completed     = 'completed';
    case Failed        = 'failed';
    case Skipped       = 'skipped';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Skipped]);
    }
}
