<?php

namespace App\config;

return [
    'token' => $_ENV['TG_BOT_TOKEN'],
    'base_url' => $_ENV['TG_BOT_BASE_URL'],
    'default_user_id' => $_ENV['TG_BOT_DEFAULT_USER_ID_FOR_ANSWER'],
];
