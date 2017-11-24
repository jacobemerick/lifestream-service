<?php

date_default_timezone_set('America/Phoenix');

require_once __DIR__ . '/../vendor/autoload.php';

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

use Aura\Sql\ExtendedPdo;

$pdoOld = new ExtendedPdo(
    $config->databaseOld->dsn,
    $config->databaseOld->username,
    $config->databaseOld->password
);

$pdoNew = new ExtendedPdo(
    $config->database->dsn,
    $config->database->username,
    $config->database->password
);

/**
 * Blog
 */
try {
    $pdoNew->exec("TRUNCATE `blog`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `blog`";
$insertQuery = "
    INSERT INTO `blog`
        (`id`, `permalink`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :permalink, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $oldBlog) {
    $result = $insertSth->execute([
        'id' => $oldBlog['id'],
        'permalink' => $oldBlog['permalink'],
        'datetime' => $oldBlog['datetime'],
        'metadata' => $oldBlog['metadata'],
        'created_at' => $oldBlog['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}
