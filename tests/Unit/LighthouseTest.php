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

    expect($command)->toEqual(implode(' ', [
        'node',
        'lighthouse',
        '--output=json',
        '--quiet',
        '--config-path=/my/config',
        'http://example.com',
        "--chrome-flags='--headless --disable-gpu --no-sandbox'",
    ]));
});

it('can set a custom node binary', function () {
    $this->lighthouse->setNodePath('/my/node/binary');

    $command = $this->lighthouse->getCommand('http://example.com');

    expect($command)->toContain('/my/node/binary');
});

it('can set chrome flags', function () {
    $this->lighthouse->setChromeFlags('--my-flag');
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain("--chrome-flags='--my-flag'");

    $this->lighthouse->setChromeFlags(['--my-flag', '--second-flag']);
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain("--chrome-flags='--my-flag --second-flag'");
});

it('can set the output file', function () {
    $this->lighthouse->setOutput('/tmp/report.json');

    $command = $this->lighthouse->getCommand('http://example.com');

    expect($command)->toContain('--output-path=/tmp/report.json');
});

it('can disable device emulation', function () {
    $this->lighthouse->disableDeviceEmulation();

    $command = $this->lighthouse->getCommand('http://example.com');

    expect($command)->toContain('--disable-device-emulation');
});

it('can disable cpu throttling', function () {
    $this->lighthouse->disableCpuThrottling();

    $command = $this->lighthouse->getCommand('http://example.com');

    expect($command)->toContain('--disable-cpu-throttling');
});

it('can disable network throttling', function () {
    $this->lighthouse->disableNetworkThrottling();

    $command = $this->lighthouse->getCommand('http://example.com');

    expect($command)->toContain('--disable-network-throttling');
});

it('can guess the output format from the file extension', function () {
    $this->lighthouse->setOutput('/tmp/report.json');
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=json');
    expect($command)->not()->toContain('--output=html');

    $this->lighthouse->setOutput('/tmp/report.html');
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=html');
    expect($command)->not()->toContain('--output=json');

    $this->lighthouse->setOutput('/tmp/report.md');
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=json');
    expect($command)->not()->toContain('--output=html');

    $this->lighthouse->setOutput('/tmp/report');
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=json');
    expect($command)->not()->toContain('--output=html');
});

it('can override the output format', function () {
    $this->lighthouse->setOutput('/tmp/report.json', 'html');
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=html');
    expect($command)->not()->toContain('--output=json');

    $this->lighthouse->setOutput('/tmp/report.md', ['html', 'json']);
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=html');
    expect($command)->toContain('--output=json');

    $this->lighthouse->setOutput('/tmp/report.md', ['html', 'json', 'md']);
    $command = $this->lighthouse->getCommand('http://example.com');
    expect($command)->toContain('--output=html');
    expect($command)->toContain('--output=json');
    expect($command)->not()->toContain('--output=md');
});

it('cannot add the same category multiple times', function ($category, $method = null) {
    $method = $method ?? $category;
    $lighthouse = new MockLighthouse();

    $lighthouse->$method();
    $lighthouse->$method();
    expect(array_count_values($lighthouse->getCategories())[$category])->toEqual(1);
})->with('reportCategories', 'emptyHeaders');

it('can disable a category', function ($category, $method = null) {
    $method = $method ?? $category;
    $lighthouse = new MockLighthouse();

    $lighthouse->$method();
    expect($lighthouse->getCategories())->toContain($category);

    $lighthouse->$method(false);
    expect($lighthouse->getCategories())->not()->toContain($category);

})->with('reportCategories', 'emptyHeaders');

it('can set the headers using an array', function () {
    $lighthouse = new MockLighthouse();

    $lighthouse->setHeaders([
        'Cookie' => 'monster=blue',
        'Authorization' => 'Bearer: ring',
    ]);

    expect($lighthouse->getCommand(''))
        ->toContain('--extra-headers "{\"Cookie\":\"monster=blue\",\"Authorization\":\"Bearer: ring\"}"');
});

it('does not pass headers when empty', function () {
    $lighthouse = new MockLighthouse();

    $lighthouse->setHeaders(['Cookie' => 'monster=blue']);
    expect($lighthouse->getCommand(''))->toContain('--extra-headers');

    $lighthouse->setHeaders([]);
    expect($lighthouse->getCommand(''))->not()->toContain('--extra-headers');
});
