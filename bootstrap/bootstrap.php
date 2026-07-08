<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../components/Log/Log.php';

use App\Components\Log\Log;

$config = require __DIR__ . '/../config/log.php';
Log::init($config);
