#!/usr/bin/env php
<?php

use igorw\edn;

/* required because PHP does not like comparing deeply nested object structures by value */
function normalize($data) {
    $iterator = null;
    if ($data instanceof \IteratorAggregate) {
        $iterator = $data->getIterator();
    } elseif ($data instanceof \Iterator) {
        $iterator = $data;
    } elseif (is_array($data)) {
        $iterator = new \ArrayIterator($data);
    }

    if ($iterator) {
        return normalize_iterator($iterator);
    }

    if (is_object($data)) {
        return serialize($data);
    }

    return $data;
}

function normalize_iterator($iterator) {
    $normalized = [];

    for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
        $normalized[] = [
            '___NORMALIZED_ITERATOR___',
            normalize($iterator->key()),
            normalize($iterator->current()),
        ];
    }

    return $normalized;
}

function normalized_compare($a, $b) {
    return normalize($a) === normalize($b);
}

require 'vendor/autoload.php';

$options = getopt('hd', ['help', 'debug']);
$help = (isset($options['h']) || isset($options['help']));
$debug = (isset($options['d']) || isset($options['debug']));

if ($help) {
    echo "Usage: runner.php [options]\n";
    echo "\n";
    echo "  --help  This help screen\n";
    echo "  --debug Print debug information\n";
    exit;
}

$validEdnDir = __DIR__."/../../../valid-edn";
$results = new Ardent\HashMap('serialize');

$files = new FilesystemIterator($validEdnDir);
$ednFiles = array_map(function ($file) { return $file; }, iterator_to_array($files));
foreach ($ednFiles as $ednFile) {
    $ednFileName = basename($ednFile, '.edn');
    $validEdn = file_get_contents("$validEdnDir/$ednFileName.edn");
    $expectedFile = __DIR__."/../$ednFileName.php";

    if ($debug) {
        echo "$ednFileName\n";
    }

    try {
        if (!file_exists($expectedFile)) {
            throw new \Exception("Missing expected file for $ednFileName");
        }

        $expectedData = file_get_contents($expectedFile);
        $expectedCode = 'use igorw\edn; return '.$expectedData.';';
        $expected = $expectedData ? [eval($expectedCode)] : [];

        $parsed = edn\parse($validEdn);
        $result = normalized_compare($expected, $parsed);

        if (!$result && $debug) {
            echo "Values did not match\n";
            printf("\tEXPECTED: %s\n", edn\encode([$expected]));
            printf("\tPARSED: %s\n", edn\encode([$parsed]));
        }
        $results[realpath($ednFile)] = $result;
    } catch (\Exception $e) {
        $results[realpath($ednFile)] = false;
        if ($debug)
            echo "\tFailed to parse <<$validEdn>> with: $e\n";
    }
}

echo edn\encode([$results])."\n";
