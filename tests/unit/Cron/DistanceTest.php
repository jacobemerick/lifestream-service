<?php

namespace Jacobemerick\LifestreamService\Cron;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class DistanceTest extends TestCase
{

    public function testIsInstanceOfDistance()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertInstanceOf(Distance::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }
}
