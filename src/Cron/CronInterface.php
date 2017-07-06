<?php

namespace Jacobemerick\LifestreamService\Cron;

use Interop\Container\ContainerInterface as Container;

interface CronInterface
{
    public function __construct(Container $container);
    public function run();
}
