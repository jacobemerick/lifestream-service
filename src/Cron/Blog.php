<?php

namespace Jacobemerick\LifestreamService\Cron;

use Interop\Container\ContainerInterface as Container;

class Blog implements CronInterface
{

    /** @var Container */
    protected $container;

    /**
     * @param Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function run()
    {
        // fetch blog rss
        // loop through items, check against guid
        // if !guid, insert into table
    }
}
