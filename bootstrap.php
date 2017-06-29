<?php

date_default_timezone_set('America/Phoenix');

$startTime = microtime(true);
$startMemory = memory_get_usage();

require_once __DIR__ . '/vendor/autoload.php';

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

// set up db and models
$di->set('dbal', $di->lazyNew(
    'Aura\Sql\ExtendedPdo',
    (array) $config->database
));
$di->types['Aura\Sql\ExtendedPdo'] = $di->lazyGet('dbal');

$di->set('commentModel', $di->lazyNew('Jacobemerick\CommentService\Model\Comment'));
$di->set('commentBodyModel', $di->lazyNew('Jacobemerick\CommentService\Model\CommentBody'));
$di->set('commentDomainModel', $di->lazyNew('Jacobemerick\CommentService\Model\CommentDomain'));
$di->set('commentLocationModel', $di->lazyNew('Jacobemerick\CommentService\Model\CommentLocation'));
$di->set('commentPathModel', $di->lazyNew('Jacobemerick\CommentService\Model\CommentPath'));
$di->set('commentRequestModel', $di->lazyNew('Jacobemerick\CommentService\Model\CommentRequest'));
$di->set('commentThreadModel', $di->lazyNew('Jacobemerick\CommentService\Model\CommentThread'));
$di->set('commenterModel', $di->lazyNew('Jacobemerick\CommentService\Model\Commenter'));

// set up serializers
$di->set('commentSerializer', $di->lazyNew('Jacobemerick\CommentService\Serializer\Comment'));
$di->set('commenterSerializer', $di->lazyNew('Jacobemerick\CommentService\Serializer\Commenter'));

// set up notification handler
$di->set('notificationHandler', $di->lazyNew(
    'Jacobemerick\CommentService\Helper\NotificationHandler',
    [
        'mail' => $di->lazyGet('mail'),
        'commenterModel' => $di->lazyGet('commenterModel'),
    ]
));

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
                'stream' => __DIR__ . '/logs/default.log',
                'level' => Monolog\Logger::DEBUG,
            ]
        ),
    ]
));

// set up mailer
$di->set('mail', $di->lazyNew(
    'Jacobemerick\Archangel\Archangel',
    [],
    [
        'setLogger' => $di->lazyGet('logger'),
    ]
));

// global time object
$di->set('datetime', new DateTime(
    'now',
    new DateTimeZone('America/Phoenix')
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

// controllers
use Jacobemerick\CommentService\Controller;

$talus->addController('createComment', function ($req, $res) use ($di) {
    return (new Controller\Comment($di))->createComment($req, $res);
});
$talus->addController('getComment', function ($req, $res) use ($di) {
    return (new Controller\Comment($di))->getComment($req, $res);
});
$talus->addController('getComments', function ($req, $res) use ($di) {
    return (new Controller\Comment($di))->getComments($req, $res);
});

$talus->addController('getCommenter', function ($req, $res) use ($di) {
    return (new Controller\Commenter($di))->getCommenter($req, $res);
});
$talus->addController('getCommenters', function ($req, $res) use ($di) {
    return (new Controller\Commenter($di))->getCommenters($req, $res);
});

// middleware
use Jacobemerick\CommentService\Middleware;

$talus->addMiddleware(new Middleware\Authentication(
    $config->auth->username,
    $config->auth->password
));

$talus->run();

$di->get('logger')->addInfo('Runtime stats', [
    'request' => $_SERVER['REQUEST_URI'],
    'time' => (microtime(true) - $startTime),
    'memory' => (memory_get_usage() - $startMemory),
]);
