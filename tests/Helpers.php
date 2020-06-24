<?php

function assertReportIncludesCategory($report, $expectedCategory)
{
    $report = json_decode($report, true);
    $categories = array_map(function ($category) {
        return $category['title'];
    }, $report['categories']);

    if (is_array($expectedCategory)) {
        sort($expectedCategory);
        sort($categories);
        test()->assertArraySubset($expectedCategory, $categories);
    } else {
        test()->assertContains($expectedCategory, $categories);
    }
}

function assertReportDoesNotIncludeCategory($report, $expectedCategory)
{
    $report = json_decode($report, true);
    $categories = array_map(function ($category) {
        return $category['title'];
    }, $report['categories']);

    return test()->assertNotContains($expectedCategory, $categories);
}

function assertReportContainsHeader($report, $name, $value)
{
    $report = json_decode($report, true);

    $headers = $report['configSettings']['extraHeaders'];
    test()->assertNotNull($headers, 'No extra headers found in report');
    test()->assertArrayHasKey($name, $headers, "Header '$name' is missing from report. [".implode($headers, ', ').']');

    return test()->assertEquals($value, $headers[$name]);
}

function removeTempFile($path)
{
    if (file_exists($path)) {
        unlink($path);
    }

    return test();
}

function assertFileStartsWith($prefix, $outputPath)
{
    test()->assertStringStartsWith(
        $prefix,
        file_get_contents($outputPath),
        "Failed asserting that the file '$outputPath' starts with '$prefix'"
    );

    return test();
}

function createLighthouseConfig($categories)
{
    if (!is_array($categories)) {
        $categories = [$categories];
    }

    $config = tmpfile();

    $r = 'module.exports = '.json_encode([
        'extends'  => 'lighthouse:default',
        'settings' => [
            'onlyCategories' => $categories,
        ],
    ]);

    fwrite($config, $r);

    return $config;
}
