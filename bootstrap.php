<?php

date_default_timezone_set('America/Phoenix');

$startTime = microtime(true);
$startMemory = memory_get_usage();

require_once __DIR__ . '/vendor/autoload.php';

use Aura\Di\ContainerBuilder;
use Aura\Sql\ExtendedPdo;
use AvalancheDevelopment\Talus\Talus;

// load the config for the application
$config_path = __DIR__ . '/config.json';

$handle = @fopen($config_path, 'r');
if ($handle === false) {
    throw new RuntimeException("Could not load config");
}
$config = fread($handle, filesize($config_path));
fclose($handle);

$config = json_decode($config);
$last_json_error = json_last_error();
if ($last_json_error !== JSON_ERROR_NONE) {
    throw new RuntimeException("Could not parse config - JSON error detected");
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

// set up serializers
$di->set('typeSerializer', $di->lazyNew('Jacobemerick\LifestreamService\Serializer\Type'));

// set up logger
$di->set('logger', $di->lazyNew(
    'Monolog\Logger',
    [
        'name' => 'default',
    ],
    [
        'pushHandler' => $di->lazyNew(
            'Monolog\Handler\StreamHandler',
            [
                'stream' => __DIR__ . '/logs/api.log',
                'level' => Monolog\Logger::DEBUG,
            ]
        ),
    ]
));

// set up swagger
$handle = fopen(__DIR__ . '/swagger.json', 'r');
$swagger = '';
while (!feof($handle)) {
    $swagger .= fread($handle, 8192);
}
fclose($handle);

$swagger = json_decode($swagger, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    var_dump('oh noes the swagz is badz');
    exit;
}

$talus = new Talus($swagger);
$talus->setLogger($di->get('logger'));

// controllers
use Jacobemerick\LifestreamService\Controller;

$talus->addController('getTypes', function ($req, $res) use ($di) {
    return (new Controller\Type($di))->getTypes($req, $res);
});

$talus->run();

$di->get('logger')->addInfo('Runtime stats', [
    'request' => $_SERVER['REQUEST_URI'],
    'time' => (microtime(true) - $startTime),
    'memory' => (memory_get_usage() - $startMemory),
]);
