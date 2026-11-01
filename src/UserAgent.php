<?php

declare(strict_types=1);

namespace IPClues\Sdk;

use Composer\InstalledVersions;

final class UserAgent
{
    public static function get(): string
    {
        $version = InstalledVersions::getPrettyVersion('ipclues/sdk') ?? '0.0.0';

        return \sprintf(
            'IPCluesPHP/%s (php/%s; os/%s; arch/%s)',
            $version,
            PHP_VERSION,
            strtolower(PHP_OS_FAMILY),
            php_uname('m')
        );
    }
}
