<?php

namespace Octoper\Lighthouse;

use Exception;
use JsonException;
use Octoper\Lighthouse\Exceptions\AuditFailedException;
use Symfony\Component\Process\Process;

class Lighthouse
{
    /** @var int */
    protected int $timeout = 60;

    /** @var string */
    protected string $nodePath = 'node';

    /** @var string|null */
    protected ?string $chromePath = null;

    /** @var string */
    protected string $lighthousePath = 'lighthouse';

    /** @var string|null */
    protected ?string $configPath = null;

    /** @var array|string|null */
    protected $config = null;

    /** @var array */
    protected array $categories = [];

    /** @var array */
    protected array $options = [];

    /** @var string */
    protected string $outputFormat = '--output=json';

    /** @var array */
    protected array $availableFormats = ['json', 'html'];

    /** @var string */
    protected string $defaultFormat = 'json';

    /** @var array|string|null */
    protected $headers;

    public function __construct()
    {
        $this->setChromeFlags(['--headless', '--disable-gpu', '--no-sandbox']);
    }

    /**
     * Set the flags to pass to the spawned Chrome instance.
     *
     * @param array|string $flags
     *
     * @return $this
     */
    public function setChromeFlags($flags): self
    {
        if (is_array($flags)) {
            $flags = implode(' ', $flags);
        }

        $this->setOption('--chrome-flags', "'$flags'");

        return $this;
    }

    /**
     * @param string $option
     * @param mixed  $value
     *
     * @return $this
     */
    public function setOption(string $option, $value = null): self
    {
        if (($foundIndex = array_search($option, $this->options)) !== false) {
            $this->options[$foundIndex] = $option;

            return $this;
        }

        if ($value === null) {
            $this->options[] = $option;
        }

        if ($value !== null) {
            $this->options[$option] = $value;
        }

        return $this;
    }

    /**
     * @param string $url
     *
     * @throws AuditFailedException
     *
     * @return string
     */
    public function audit(string $url): string
    {
        $process = Process::fromShellCommandline($this->getCommand($url));

        $process->setTimeout($this->timeout)->run();

        if (!$process->isSuccessful()) {
            throw new AuditFailedException($url, $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getCommand(string $url): string
    {
        if ($this->configPath === null || $this->config !== null) {
            $this->buildConfig();
        }

        $command = array_merge([
            $this->chromePath,
            $this->nodePath,
            $this->lighthousePath,
            $this->outputFormat,
            $this->headers,
            '--quiet',
            "--config-path={$this->configPath}",
            $url,
        ], $this->processOptions());

        return implode(' ', array_filter($command));
    }

    /**
     * Creates the config file used during the audit.
     *
     * @throws Exception
     *
     * @return $this
     */
    protected function buildConfig(): self
    {
        $config = tmpfile();

        if (!$config) {
            throw new Exception('Cannot build config file.');
        }

        $this->withConfig(stream_get_meta_data($config)['uri']);
        /** @phpstan-ignore-next-line  */
        $this->config = $config;

        $options = 'module.exports = '.json_encode([
            'extends'  => 'lighthouse:default',
            'settings' => [
                'onlyCategories' => $this->categories,
            ],
        ]);

        fwrite($config, $options);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function withConfig(string $path): self
    {
        $this->configPath = $path;
        $this->config = null;

        return $this;
    }

    /**
     * Convert the options array to an array that can be used
     * to construct the command arguments.
     *
     * @return array
     */
    protected function processOptions(): array
    {
        return array_map(function ($value, $option) {
            return is_numeric($option) ? $value : "$option=$value";
        }, $this->options, array_keys($this->options));
    }

    /**
     * Enable the accessibility audit.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function accessibility(bool $enable = true): self
    {
        $this->setCategory('accessibility', $enable);

        return $this;
    }

    /**
     * Enable or disable a category.
     *
     * @param string $category
     * @param bool   $enable
     *
     * @return $this
     */
    protected function setCategory(string $category, bool $enable): self
    {
        $index = array_search($category, $this->categories);

        if ($index !== false) {
            if ($enable == false) {
                unset($this->categories[$index]);
            }
        } elseif ($enable) {
            $this->categories[] = $category;
        }

        return $this;
    }

    /**
     * Enable the best practices audit.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function bestPractices(bool $enable = true): self
    {
        $this->setCategory('best-practices', $enable);

        return $this;
    }

    /**
     * Enable the best performance audit.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function performance(bool $enable = true): self
    {
        $this->setCategory('performance', $enable);

        return $this;
    }

    /**
     * Enable the progressive web app audit.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function pwa(bool $enable = true): self
    {
        $this->setCategory('pwa', $enable);

        return $this;
    }

    /**
     * Enable the search engine optimization audit.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function seo(bool $enable = true): self
    {
        $this->setCategory('seo', $enable);

        return $this;
    }

    /**
     * Disables Device Emulator.
     *
     * @return $this
     */
    public function disableDeviceEmulation(): self
    {
        $this->setOption('--disable-device-emulation');

        return $this;
    }

    public function disableCpuThrottling(): self
    {
        $this->setOption('--disable-cpu-throttling');

        return $this;
    }

    public function disableNetworkThrottling(): self
    {
        $this->setOption('--disable-network-throttling');

        return $this;
    }

    /**
     * @param string            $path
     * @param null|string|array $format
     *
     * @return $this
     */
    public function setOutput(string $path, $format = null): self
    {
        $this->setOption('--output-path', $path);

        if ($format === null) {
            $format = $this->guessOutputFormatFromFile($path);
        }

        if (!is_array($format)) {
            $format = [$format];
        }

        $format = array_intersect($this->availableFormats, $format);

        $this->outputFormat = implode(' ', array_map(function ($format) {
            return "--output=$format";
        }, $format));

        return $this;
    }

    /**
     * Guesses the file format.
     *
     * @param string $path
     *
     * @return string
     */
    private function guessOutputFormatFromFile($path): string
    {
        $format = pathinfo($path, PATHINFO_EXTENSION);

        if (!in_array($format, $this->availableFormats)) {
            $format = $this->defaultFormat;
        }

        return $format;
    }

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setDefaultFormat(string $format): self
    {
        $this->defaultFormat = $format;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setNodePath(string $path): self
    {
        $this->nodePath = $path;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setLighthousePath(string $path): self
    {
        $this->lighthousePath = $path;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setChromePath(string $path): self
    {
        $this->chromePath = "CHROME_PATH=$path";

        return $this;
    }

    /**
     * Set the flags to pass to the spawned Chrome instance.
     *
     * @param array|string|null $headers
     *
     * @throws JsonException
     *
     * @return $this
     */
    public function setHeaders($headers = null): self
    {
        if (empty($headers)) {
            $this->headers = '';

            return $this;
        }

        $headers = json_encode($headers, JSON_THROW_ON_ERROR);
        $headers = str_replace('"', '\"', $headers);

        $this->headers = "--extra-headers \"$headers\"";

        return $this;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }
}
