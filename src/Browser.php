<?php

declare(strict_types=1);

namespace McMatters\ChromeTester;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

use function array_key_exists, dirname, strncasecmp, usleep;

use const null, PHP_OS;

/**
 * Class Browser
 *
 * @package McMatters\ChromeTester
 */
class Browser
{
    /**
     * @var \McMatters\ChromeTester\Browser
     */
    protected static $instance;

    /**
     * @var \Symfony\Component\Process\Process
     */
    protected static $process;

    /**
     * @var \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected static $driver;

    /**
     * @var string|null
     */
    protected static $chromeBinary;

    /**
     * @var int
     */
    protected static $attempts = 5;

    /**
     * @var int
     */
    protected static $sleep = 5000000;

    /**
     * @var string
     */
    protected static $chromeAddress = 'http://localhost:9515';

    /**
     * @return void
     */
    public function __destruct()
    {
        self::$instance = null;
        self::$driver = null;
        self::$process->stop();
    }

    /**
     * @param array $options
     * @param array $chromeArguments
     *
     * @return static
     *
     * @throws \Throwable
     */
    public static function make(array $options = [], array $chromeArguments = [])
    {
        if (null === self::$instance) {
            self::$instance = new static($options, $chromeArguments);
        }

        return self::$instance;
    }

    /**
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    public function getChromeDriver(): RemoteWebDriver
    {
        return self::$driver;
    }

    /**
     * @param string $binary
     *
     * @return void
     */
    public static function setChromeBinary(string $binary): void
    {
        self::$chromeBinary = $binary;
    }

    /**
     * @param int $attempts
     *
     * @return void
     */
    public static function setAttempts(int $attempts): void
    {
        self::$attempts = $attempts;
    }

    /**
     * Browser constructor.
     *
     * @param array $options
     * @param array $chromeArguments
     *
     * @throws \Throwable
     */
    protected function __construct(array $options = [], array $chromeArguments = [])
    {
        self::setOptions($options);
        self::$process = new Process([self::getBinaryPath()], dirname(__DIR__));
        self::$process->enableOutput()->start();
        self::$driver = self::createWebDriver($chromeArguments);
    }

    /**
     * @param array $options
     *
     * @return void
     */
    protected static function setOptions(array $options = []): void
    {
        if (array_key_exists('chromeBinary', $options)) {
            self::$chromeBinary = $options['chromeBinary'];
        }

        if (array_key_exists('attempts', $options)) {
            self::$attempts = $options['attempts'];
        }

        if (array_key_exists('sleep', $options)) {
            self::$sleep = $options['sleep'];
        }

        if (array_key_exists('chromeAddress', $options)) {
            self::$chromeAddress = $options['chromeAddress'];
        }
    }

    /**
     * @return string
     */
    protected static function getBinaryPath(): string
    {
        if (null !== self::$chromeBinary) {
            return self::$chromeBinary;
        }

        if (PHP_OS === 'Darwin') {
            return dirname(__DIR__).'/bin/chromedriver-mac';
        }

        return strncasecmp(PHP_OS, 'WIN', 3) === 0
            ? dirname(__DIR__).'/bin/chromedriver-win.exe'
            : dirname(__DIR__).'/bin/chromedriver-linux';
    }

    /**
     * @param array $arguments
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     *
     * @throws \Throwable
     */
    protected static function createWebDriver(array $arguments = []): RemoteWebDriver
    {
        $arguments = $arguments ?? [
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
        ];

        $options = (new ChromeOptions())->addArguments($arguments);

        return self::attemptToConnectWebDriver($options);
    }

    /**
     * @param $options
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     *
     * @throws \Throwable
     */
    protected static function attemptToConnectWebDriver(
        ChromeOptions $options
    ): RemoteWebDriver {
        $attempts = self::$attempts;

        do {
            try {
                return RemoteWebDriver::create(
                    self::$chromeAddress,
                    DesiredCapabilities::chrome()->setCapability(
                        ChromeOptions::CAPABILITY,
                        $options
                    )
                );
            } catch (Throwable $e) {
                if (!$attempts) {
                    throw $e;
                }

                usleep(self::$sleep);

                $attempts--;
            }
        } while (0 !== $attempts);

        throw new RuntimeException('Cannot connect to remote chrome');
    }
}
