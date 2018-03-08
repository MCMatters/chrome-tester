<?php

declare(strict_types = 1);

namespace McMatters\ChromeTester;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use const null, PHP_OS;
use function array_merge, dirname, strncasecmp, usleep;

/**
 * Class Browser
 *
 * @package McMatters\ChromeTester
 */
class Browser
{
    /**
     * @var Browser
     */
    protected static $instance;

    /**
     * @var Process
     */
    protected static $process;

    /**
     * @var RemoteWebDriver
     */
    protected static $driver;

    /**
     * @var string
     */
    protected static $chromeBinary = '';

    /**
     * @var int
     */
    protected static $attempts = 5;

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
     * @param array $arguments
     *
     * @return static
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws Throwable
     */
    public static function make(array $arguments = [])
    {
        if (null === self::$instance) {
            self::$instance = new static($arguments);
        }

        return self::$instance;
    }

    /**
     * @return RemoteWebDriver
     */
    public function getChromeDriver(): RemoteWebDriver
    {
        return self::$driver;
    }

    /**
     * @param string $binary
     *
     * @return Browser
     */
    public static function setChromeBinary(string $binary): Browser
    {
        self::$chromeBinary = $binary;

        return self::$instance;
    }

    /**
     * @param int $attempts
     *
     * @return Browser
     */
    public static function setAttempts(int $attempts): Browser
    {
        self::$attempts = $attempts;

        return self::$instance;
    }

    /**
     * Browser constructor.
     *
     * @param array $arguments
     *
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws Throwable
     */
    protected function __construct(array $arguments = [])
    {
        self::$process = new Process([self::getBinaryPath()], dirname(__DIR__));
        self::$process->start();
        self::$driver = self::createWebDriver($arguments);
    }

    /**
     * @return string
     */
    protected static function getBinaryPath(): string
    {
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
     * @return RemoteWebDriver
     * @throws Throwable
     */
    protected static function createWebDriver(array $arguments = []): RemoteWebDriver
    {
        $arguments = array_merge(
            $arguments,
            [
                '--disable-gpu',
                '--headless',
                '--no-sandbox',
            ]
        );

        $options = (new ChromeOptions())
            ->addArguments($arguments)
            ->setBinary(self::$chromeBinary);

        return self::attemptToConnectWebDriver($options);
    }

    /**
     * @param $options
     *
     * @return RemoteWebDriver
     * @throws Throwable
     */
    protected static function attemptToConnectWebDriver(
        ChromeOptions $options
    ): RemoteWebDriver {
        $attempts = self::$attempts;

        do {
            try {
                return RemoteWebDriver::create(
                    'http://localhost:9515',
                    DesiredCapabilities::chrome()->setCapability(
                        ChromeOptions::CAPABILITY,
                        $options
                    )
                );
            } catch (Throwable $e) {
                if (!$attempts) {
                    throw $e;
                }

                usleep(50000);

                $attempts--;
            }
        } while (0 !== $attempts);

        throw new RuntimeException('Cannot connect to remote chrome');
    }
}
