<?php

use Octoper\Lighthouse\Lighthouse;
use Octoper\Lighthouse\Test\MockLighthouse;

beforeEach(function () {
    $this->lighthouse = new Lighthouse();
});

it('constructs the correct command', function () {
    $command = $this->lighthouse
        ->withConfig('/my/config')
        ->getCommand('http://example.com');

    $this->assertEquals(implode(' ', [
        'node',
        'lighthouse',
        '--output=json',
        '--quiet',
        "--config-path=/my/config",
        "http://example.com",
        "--chrome-flags='--headless --disable-gpu --no-sandbox'",
    ]), $command);
});

it('can_set_a_custom_node_binary', function () {
    $this->lighthouse->setNodePath('/my/node/binary');

    $command = $this->lighthouse->getCommand('http://example.com');

    $this->assertStringContainsString('/my/node/binary', $command);
});


it('can_set_chrome_flags', function () {
    $this->lighthouse->setChromeFlags('--my-flag');
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--chrome-flags='--my-flag'", $command);

    $this->lighthouse->setChromeFlags(['--my-flag', '--second-flag']);
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--chrome-flags='--my-flag --second-flag'", $command);
});

it('can_set_the_output_file', function () {
    $this->lighthouse->setOutput('/tmp/report.json');

    $command = $this->lighthouse->getCommand('http://example.com');

    $this->assertStringContainsString("--output-path=/tmp/report.json", $command);
});

it('can_disable_device_emulation', function () {
    $this->lighthouse->disableDeviceEmulation();

    $command = $this->lighthouse->getCommand('http://example.com');

    $this->assertStringContainsString('--disable-device-emulation', $command);
});

it('can_disable_cpu_throttling', function () {
    $this->lighthouse->disableCpuThrottling();

    $command = $this->lighthouse->getCommand('http://example.com');

    $this->assertStringContainsString('--disable-cpu-throttling', $command);
});

it('can_disable_network_throttling', function () {
    $this->lighthouse->disableNetworkThrottling();

    $command = $this->lighthouse->getCommand('http://example.com');

    $this->assertStringContainsString('--disable-network-throttling', $command);
});

it('can guess the output format from the file extension', function () {
    $this->lighthouse->setOutput('/tmp/report.json');
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=json", $command);
    $this->assertStringNotContainsString("--output=html", $command);

    $this->lighthouse->setOutput('/tmp/report.html');
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=html", $command);
    $this->assertStringNotContainsString("--output=json", $command);

    $this->lighthouse->setOutput('/tmp/report.md');
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=json", $command);
    $this->assertStringNotContainsString("--output=html", $command);

    $this->lighthouse->setOutput('/tmp/report');
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=json", $command);
    $this->assertStringNotContainsString("--output=html", $command);
});

it('can_override_the_output_format', function () {
    $this->lighthouse->setOutput('/tmp/report.json', 'html');
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=html", $command);
    $this->assertStringNotContainsString("--output=json", $command);

    $this->lighthouse->setOutput('/tmp/report.md', ['html', 'json']);
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=html", $command);
    $this->assertStringContainsString("--output=json", $command);

    $this->lighthouse->setOutput('/tmp/report.md', ['html', 'json', 'md']);
    $command = $this->lighthouse->getCommand('http://example.com');
    $this->assertStringContainsString("--output=html", $command);
    $this->assertStringContainsString("--output=json", $command);
    $this->assertStringNotContainsString("--output=md", $command);
});

it('cannot_add_the_same_category_multiple_times', function ($category, $method = null) {
    $method = $method ?? $category;
    $lighthouse = new MockLighthouse();

    $lighthouse->$method();
    $lighthouse->$method();
    $this->assertEquals(1, array_count_values($lighthouse->getCategories())[$category]);
})->with('reportCategories', 'emptyHeaders');

it('can_disable_a_category', function ($category, $method = null) {
    $method = $method ?? $category;
    $lighthouse = new MockLighthouse();

    $lighthouse->$method();
    $this->assertContains($category, $lighthouse->getCategories());

    $lighthouse->$method(false);
    $this->assertNotContains($category, $lighthouse->getCategories());
})->with('reportCategories', 'emptyHeaders');

it('can_set_the_headers_using_an_array', function () {
    $lighthouse = new MockLighthouse();

    $lighthouse->setHeaders([
        'Cookie' => 'monster=blue',
        'Authorization' => 'Bearer: ring',
    ]);

    $this->assertStringContainsString('--extra-headers "{\"Cookie\":\"monster=blue\",\"Authorization\":\"Bearer: ring\"}"', $lighthouse->getCommand(''));
});

it('does_not_pass_headers_when_empty', function () {
    $lighthouse = new MockLighthouse();

    $lighthouse->setHeaders(['Cookie' => 'monster=blue']);
    $this->assertStringContainsString('--extra-headers', $lighthouse->getCommand(''));

    $lighthouse->setHeaders([]);
    $this->assertStringNotContainsString('--extra-headers', $lighthouse->getCommand(''));
});
