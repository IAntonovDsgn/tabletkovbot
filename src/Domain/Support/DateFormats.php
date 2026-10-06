<?php

declare(strict_types=1);

namespace App\Domain\Support;

final class DateFormats
{
    public const string DATE = 'Y-m-d';
    public const string DATE_TIME = 'Y-m-d H:i:s';
    public const string TIME = 'H:i:s';
    public const string TIME_UI = 'H:i';
    public const string YEAR_MONTH = 'Y-m';
    public const string TIME_ZONE = 'Asia/Yekaterinburg';

    private function __construct() {}
}
