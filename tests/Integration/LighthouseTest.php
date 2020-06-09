<?php

use Octoper\Lighthouse\Exceptions\AuditFailedException;
use Octoper\Lighthouse\Lighthouse;

beforeEach(function () {
    $this->lighthouse = (new Lighthouse())->setLighthousePath('/usr/bin/lighthouse');
});

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
    test()->assertArrayHasKey($name, $headers, "Header '$name' is missing from report. [" . implode($headers, ', ') . ']');

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

function fileOutputDataProvider()
{
    return [
        ['/tmp/report.json', '{'],
        ['/tmp/report.html', '<!--'],
    ];
}

function createLighthouseConfig($categories)
{
    if (! is_array($categories)) {
        $categories = [$categories];
    }

    $config = tmpfile();

    $r = 'module.exports = ' . json_encode([
            'extends' => 'lighthouse:default',
            'settings' => [
                'onlyCategories' => $categories,
            ],
        ]);

    fwrite($config, $r);

    return $config;
}

it('can run only one audit', function () {
    $report = $this->lighthouse
        ->performance()
        ->audit('http://example.com');

    assertReportIncludesCategory($report, 'Performance');
    assertReportDoesNotIncludeCategory($report, 'Progressive Web App');
});

it('can run all audits', function () {
    $report = $this->lighthouse
        ->accessibility()
        ->bestPractices()
        ->performance()
        ->pwa()
        ->seo()
        ->audit('http://example.com');

    assertReportIncludesCategory($report, [
        'Accessibility', 'Best Practices', 'Performance', 'Progressive Web App', 'SEO',
    ]);
});

test('updates the config when a category is added or removed', function () {
    $report = $this->lighthouse
        ->performance()
        ->audit('http://example.com');

    assertReportIncludesCategory($report, 'Performance');
    assertReportDoesNotIncludeCategory($report, 'Accessibility');

    $report = $this->lighthouse
        ->accessibility()
        ->audit('http://example.com');

    assertReportIncludesCategory($report, 'Performance');
    assertReportIncludesCategory($report, 'Accessibility');

    $report = $this->lighthouse
        ->accessibility(false)
        ->audit('http://example.com');

    assertReportIncludesCategory($report, 'Performance');
    assertReportDoesNotIncludeCategory($report, 'Accessibility');
});

test('does not override the user provided config', function () {
    $config = createLighthouseConfig('performance');
    $configPath = stream_get_meta_data($config)['uri'];

    $report = $this->lighthouse
        ->withConfig($configPath)
        ->accessibility()
        ->performance(false)
        ->audit('http://example.com');

    file_put_contents('/tmp/report', $report);

    assertReportIncludesCategory($report, 'Performance');
    assertReportDoesNotIncludeCategory($report, 'Accessibility');
});

test('throws_an_exception_when_the_audit_fails', function () {
    $url = 'not-a-valid-url';

    $this->lighthouse
        ->seo()
        ->audit($url);
})->throws(AuditFailedException::class);

// test('outputs_to_a_file', function () {
//     $this->removeTempFile($outputPath);

//     $this->lighthouse
//             ->setOutput($outputPath)
//             ->seo()
//             ->audit('http://example.com');

//     $this->assertFileExists($outputPath);
//     assertFileStartsWith($content, $outputPath);
// });

test('outputs_both_json_and_html_reports_at_the_same_time', function () {
    removeTempFile('/tmp/example.report.json');
    removeTempFile('/tmp/example.report.html');

    $this->lighthouse
        ->setOutput('/tmp/example', ['json', 'html'])
            ->seo()
            ->audit('http://example.com');

    assertFileExists('/tmp/example.report.html');
    assertFileExists('/tmp/example.report.json');
});

test('passes_the_http_headers_to_the_requests', function () {
    $report = $this->lighthouse
            ->setHeaders(['Cookie' => 'monster:blue', 'Authorization' => 'Bearer: ring'])
            ->performance()
            ->audit('http://example.com');

    assertReportContainsHeader($report, 'Cookie', 'monster:blue');
    assertReportContainsHeader($report, 'Authorization', 'Bearer: ring');
});
