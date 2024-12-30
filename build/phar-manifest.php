#!/usr/bin/env php
<?php

declare(strict_types=1);

print 'systemsdk/phpcpd: ' . getenv('VERSION');
print "\n";

$composerLock = file_get_contents(__DIR__ . '/../composer.lock');

if ($composerLock === false) {
    throw new \RuntimeException('Can not read composer.lock');
}

$data = json_decode(json: $composerLock, flags: JSON_THROW_ON_ERROR);

if (!is_object($data) || !property_exists($data, 'packages') || !is_array($data->packages)) {
    throw new \RuntimeException('Can not decode composer.lock');
}

/** @var object{name: string, version: string, source: object{reference: string}} $package */
foreach ($data->packages as $package) {
    print $package->name . ': ' . $package->version;

    if (
        !preg_match(
            '/^[v= ]*(([0-9]+)(\\.([0-9]+)(\\.([0-9]+)(-([0-9]+))?(-?([a-zA-Z-+][a-zA-Z0-9\\.\\-:]*)?)?)?)?)$/',
            $package->version
        )
    ) {
        print '@' . $package->source->reference;
    }

    print "\n";
}
