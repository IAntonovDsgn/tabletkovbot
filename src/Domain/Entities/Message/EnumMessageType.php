<?php

declare(strict_types=1);

namespace App\Domain\Entities\Message;

enum EnumMessageType: string
{
    case MESSAGE = 'message';
    case REPORT = 'report';
}
