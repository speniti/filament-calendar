#!/usr/bin/env php
<?php

declare(strict_types=1);

putenv('APP_ENV=testing');

$args = array_slice($argv, 1);

$phpstanCommand = sprintf(
    'vendor/bin/phpstan %s',
    implode(' ', array_map('escapeshellarg', $args)),
);

passthru($phpstanCommand, $exitCode);

exit($exitCode);
