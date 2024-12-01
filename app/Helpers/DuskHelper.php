<?php

namespace App\Helpers;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Laravel\Dusk\Concerns\ProvidesBrowser;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class DuskHelper.
 */
class DuskHelper
{
    use ProvidesBrowser;

    /**
     * The Chrome process instance.
     *
     * @var Process
     */
    protected Process $process;

    /**
     * Name to be used for Chrome process.
     *
     * @var string
     */
    protected string $name;

    /**
     * Port to be used for Chrome process.
     *
     * @var int
     */
    protected int $port;

    /**
     * If true, then browser won't be opened in a real window.
     *
     * @var bool
     */
    protected bool $headless;

    /**
     * DuskHelper's constructor.
     *
     * @param bool $headless
     * @param int|null $port
     */
    public function __construct(
        bool $headless = true,
        int $port = null
    ) {
        $this->setHeadless($headless);
        $this->setPort($port ?? rand(9100, 9500));
    }

    /**
     * Set paths for the browser to use.
     *
     * @param string $screenshots
     * @param string $console
     * @param string $source
     *
     * @return static
     */
    public function setPaths(string $screenshots, string $console, string $source): static
    {
        Browser::$storeScreenshotsAt = $screenshots;

        Browser::$storeConsoleLogAt = $console;

        Browser::$storeSourceAt = $source;

        return $this;
    }

    /**
     * DuskHelper's destructor.
     */
    public function __destruct()
    {
        $this->stop();
    }

    /**
     * Get name to be used for Chrome process.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->getName();
    }

    /**
     * Get name to be used for Chrome process.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'dusk_' . $this->getPort();
    }

    /**
     * Get data name to be used for Chrome process.
     *
     * @return string
     */
    public function dataName(): string
    {
        return $this->getName();
    }

    /**
     * Get port to be used for Chrome process.
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Set port to be used for Chrome process.
     *
     * @param int $port
     *
     * @return static
     */
    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get `headless` property value.
     *
     * @return bool
     */
    public function isHeadless(): bool
    {
        return $this->headless;
    }

    /**
     * Get `headless` property value.
     *
     * @param bool $headless
     *
     * @return static
     */
    public function setHeadless(bool $headless): static
    {
        $this->headless = $headless;

        return $this;
    }

    /**
     * Returns url of the Chrome process.
     *
     * @return string
     */
    protected function driverUrl(): string
    {
        return 'http://localhost:' . $this->port;
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return RemoteWebDriver
     */
    protected function driver(): RemoteWebDriver
    {
        $args = [
            '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            'disable-infobars',
            '--no-sandbox',          // Required for some environments
            '--enable-automation',   // Ensure automation features
            '--disable-extensions',
            '--disable-dev-shm-usage', // Avoid shared memory issues
            '--disable-blink-features=AutomationControlled', // Mask automated behavior
            '--allow-running-insecure-content', // Allow insecure content (if needed)
            '--user-agent=' . 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ];

        if ($this->headless) {
            $args[] = '--headless';
            $args[] = '--disable-gpu';
        }

        $options = (new ChromeOptions())
            ->addArguments($args);

        $capabilities = (DesiredCapabilities::chrome())
            ->setCapability(ChromeOptions::CAPABILITY, $options);

        return RemoteWebDriver::create($this->driverUrl(), $capabilities);
    }

    /**
     * Build the process to run the Chrome.
     *
     * @param array $arguments
     *
     * @return Process
     * @throws RuntimeException
     */
    protected function build(array $arguments = []): Process
    {
        return (new ChromeProcess())
            ->toProcess($arguments);
    }

    /**
     * Start the Chrome process.
     *
     * @return static
     */
    public function start(): static
    {
        $this->process = $this->build(['--port=' . $this->getPort()]);

        $this->process->start();

        return $this;
    }

    /**
     * Stop the Chrome process.
     *
     * @return static
     */
    public function stop(): static
    {
        if (isset($this->process)) {
            $this->process->stop();
        }

        return $this;
    }
}
