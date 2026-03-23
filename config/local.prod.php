<?php

declare(strict_types=1);
// Production environment

return function (array $settings): array {
    $settings['db']['database'] = 'smart-store-db';
    $settings['db']['hostname'] = 'root';

    return $settings;
};
