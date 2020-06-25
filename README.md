# Lighthouse

[![Packagist Version](https://img.shields.io/packagist/v/octoper/lighthouse-php)](https://packagist.org/packages/octoper/lighthouse-php)
[![Packagist Downloads](https://img.shields.io/packagist/dm/octoper/lighthouse-php)](https://packagist.org/packages/octoper/lighthouse-php)
[![License](https://img.shields.io/github/license/octoper/lighthouse-php)](LICENSE.md)
[![Tests](https://github.com/octoper/lighthouse-php/workflows/Tests/badge.svg)](https://github.com/octoper/lighthouse-php/actions?query=workflow%3ATests)

**This package is a fork of** [dzava/lighthouse-php](https://github.com/dzava/lighthouse-php)

This package provides a PHP interface for [Google Lighthouse](https://github.com/GoogleChrome/lighthouse).

## Installation

You can install the package via composer:

```bash
composer require octoper/lighthouse-php
```

## Usage

Here's an example that will perform the default Lighthouse audits and store the result in `report.json` (You can use the [Lighthouse Viewer](https://googlechrome.github.io/lighthouse/viewer/) to open the report):

```php
use Octoper\Lighthouse\Lighthouse;

(new Lighthouse())
    ->setOutput('report.json')
    ->accessibility()
    ->bestPractices()
    ->performance()
    ->pwa()
    ->seo()
    ->audit('http://example.com');
```

### Output

The `setOutput` method accepts a second argument that can be used to specify the format (json,html).
If the format argument is missing then the file extension will be used to determine the output format.
If the file extension does not specify an accepted format, then json will be used.

You can output both the json and html reports by passing an array as the second argument. For the example
the following code will create two reports `example.report.html` and `example.report.json`.

```php
use Octoper\Lighthouse\Lighthouse;

(new Lighthouse())
    ->setOutput('example', ['html', 'json'])
    ->performance()
    ->audit('http://example.com');
```

### Using a custom config

You can provide your own configuration file using the `withConfig` method.
```php
use Octoper\Lighthouse\Lighthouse;

(new Lighthouse())
    ->withConfig('./my-config.js')
    ->audit('http://example.com');
```

### Customizing node and Lighthouse paths

If you need to manually set these paths, you can do this by calling the `setNodeBinary` and `setLighthousePath` methods.

```php
use Octoper\Lighthouse\Lighthouse;

(new Lighthouse())
    ->setNodeBinary('/usr/bin/node')
    ->setLighthousePath('./lighthouse.js')
    ->audit('http://example.com');
```

### Passing flags to Chrome
Use the `setChromeFlags` method to pass any flags to the Chrome instance.
```php
use Octoper\Lighthouse\Lighthouse;

(new Lighthouse())
    // these are the default flags used
    ->setChromeFlags(['--headless', '--disable-gpu', '--no-sandbox'])
    ->audit('http://example.com');
```

## Testing

``` bash
composer test
```

## Security

If you discover any security related issues, please email me@octoper.me instead of using the issue tracker.

## Credits

- [dzava](https://github.com/dzava)
- [octoper](https://github.com/octoper)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.