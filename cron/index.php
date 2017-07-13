<?php

date_default_timezone_set('America/Phoenix');

$startTime = microtime(true);
$startMemory = memory_get_usage();

require_once __DIR__ . '/../vendor/autoload.php';

use Aura\Di\ContainerBuilder;
use Aura\Sql\ExtendedPdo;

// load the config for the application
$config_path = __DIR__ . '/../config.json';

$handle = @fopen($config_path, 'r');
if ($handle === false) {
    throw new RuntimeException('Could not load config');
}
$config = fread($handle, filesize($config_path));
fclose($handle);

$config = json_decode($config);
$last_json_error = json_last_error();
if ($last_json_error !== JSON_ERROR_NONE) {
    throw new RuntimeException('Could not parse config - JSON error detected');
}

$builder = new ContainerBuilder();
$di = $builder->newInstance($builder::AUTO_RESOLVE);

// global time object
$di->set('datetime', new DateTime(
    'now',
    new DateTimeZone('America/Phoenix')
));

// set up db and models
$di->set('dbal', $di->lazyNew(
    'Aura\Sql\ExtendedPdo',
    (array) $config->database
));
$di->types['Aura\Sql\ExtendedPdo'] = $di->lazyGet('dbal');

$di->set('typeModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Type'));

// set up clients
$di->set('blogClient', $di->lazyNew(
    'GuzzleHttp\Client',
    [
        'baseUri' => $config->blog->baseUri,
        'headers' => [
            'User-Agent' => 'lifestream-service/1.0',
        ],
    ]
));

// switch to determine which cron to run
$opts = getopt('s:');
if (!$opts['s']) {
    throw new Exception('Must specify a -s flag to determine which cron to run');
}

use Jacobemerick\LifestreamService\Cron;

switch ($opts['s']) {
    case 'blog':
        $cron = new Cron\Blog($di);
        break;
    default:
        throw new Exception('Unrecognized cron passed in');
        break;
}

$cron->run();
