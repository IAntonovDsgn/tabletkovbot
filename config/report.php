<?php

return [
    'max_days' => max(1, (int) ($_ENV['REPORT_MAX_DAYS'] ?? 365)),
    'max_send_attempts' => max(1, (int) ($_ENV['REPORT_MAX_SEND_ATTEMPTS'] ?? 3)),
    'temp_file_ttl' => max(60, (int) ($_ENV['REPORT_TEMP_FILE_TTL'] ?? 3600)),
];
