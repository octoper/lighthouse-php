<?php

use Octoper\Lighthouse\Exceptions\AuditFailedException;
use Octoper\Lighthouse\Lighthouse;

beforeEach(function () {
    $this->lighthouse = (new Lighthouse())
        ->setLighthousePath('./node_modules/.bin/lighthouse');
});

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

test('throws an exception when the audit fails', function () {
    $url = 'not-a-valid-url';

    $this->lighthouse
        ->seo()
        ->audit($url);
})->throws(AuditFailedException::class);

test('outputs to a file', function ($outputPath, $content) {
    removeTempFile($outputPath);

    $this->lighthouse
             ->setOutput($outputPath)
             ->seo()
             ->audit('http://example.com');

    expect($outputPath)->toBeFile();

    assertFileStartsWith($content, $outputPath);

})->with('fileOutputData');

test('outputs both json and html reports at the same time', function () {
    removeTempFile('/tmp/example.report.json');
    removeTempFile('/tmp/example.report.html');

    $this->lighthouse
        ->setOutput('/tmp/example', ['json', 'html'])
            ->seo()
            ->audit('http://example.com');

    expect('/tmp/example.report.html')->toBeFile();
    expect('/tmp/example.report.json')->toBeFile();
});

test('passes the http headers to the requests', function () {
    $report = $this->lighthouse
            ->setHeaders(['Cookie' => 'monster:blue', 'Authorization' => 'Bearer: ring'])
            ->performance()
            ->audit('http://example.com');

    assertReportContainsHeader($report, 'Cookie', 'monster:blue');
    assertReportContainsHeader($report, 'Authorization', 'Bearer: ring');
});
