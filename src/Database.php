<?php

declare(strict_types=1);

namespace IPClues\Sdk;

enum Database: string
{
    case GeoI3Lite = 'geoi3-lite';
    case GeoI3Country = 'geoi3-country';
    case NetI3Identity = 'neti3-identity';
}
