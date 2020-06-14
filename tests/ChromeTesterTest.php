<?php

declare(strict_types=1);

namespace McMatters\ChromeTester\Tests;

use McMatters\ChromeTester\Browser;
use PHPUnit\Framework\TestCase;

/**
 * Class ChromeTesterTest
 *
 * @package McMatters\ChromeTester\Tests
 */
class ChromeTesterTest extends TestCase
{
    /**
     * @return void
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Throwable
     */
    public function testChromeTester()
    {
        $chrome = Browser::make()->getChromeDriver();

        $this->assertNotNull($chrome);
        $this->assertEquals('Google', $chrome->get('https://google.com')->getTitle());
    }
}
