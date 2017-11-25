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
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'permalink' => $row['permalink'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Blog Comments
 */
try {
    $pdoNew->exec("TRUNCATE `blog_comment`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `blog_comment`";
$insertQuery = "
    INSERT INTO `blog_comment`
        (`id`, `permalink`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :permalink, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'permalink' => $row['permalink'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Book
 */
try {
    $pdoNew->exec("TRUNCATE `book`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `goodread`";
$insertQuery = "
    INSERT INTO `book`
        (`id`, `permalink`, `book_id`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :permalink, :book_id, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'permalink' => $row['permalink'],
        'book_id' => $row['book_id'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Code
 */
try {
    $pdoNew->exec("TRUNCATE `code`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `github`";
$insertQuery = "
    INSERT INTO `code`
        (`id`, `event_id`, `type`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :event_id, :type, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'event_id' => $row['event_id'],
        'type' => $row['type'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Distance
 */
try {
    $pdoNew->exec("TRUNCATE `distance`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `dailymile`";
$insertQuery = "
    INSERT INTO `distance`
        (`id`, `entry_id`, `type`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :entry_id, :type, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'entry_id' => $row['entry_id'],
        'type' => $row['type'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Distance
 */
try {
    $pdoNew->exec("TRUNCATE `distance`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `dailymile`";
$insertQuery = "
    INSERT INTO `distance`
        (`id`, `entry_id`, `type`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :entry_id, :type, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'entry_id' => $row['entry_id'],
        'type' => $row['type'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Twitter
 */
try {
    $pdoNew->exec("TRUNCATE `twitter`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `twitter`";
$insertQuery = "
    INSERT INTO `twitter`
        (`id`, `tweet_id`, `datetime`, `metadata`, `created_at`, `updated_at`)
    VALUES
        (:id, :tweet_id, :datetime, :metadata, :created_at, :updated_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'tweet_id' => $row['tweet_id'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}

/**
 * Video
 */
try {
    $pdoNew->exec("TRUNCATE `video`");
} catch (Exception $e) {
    throw new Exception($pdoNew->errorInfo());
}

$selectQuery = "
    SELECT *
    FROM `youtube`";
$insertQuery = "
    INSERT INTO `video`
        (`id`, `video_id`, `datetime`, `metadata`, `created_at`)
    VALUES
        (:id, :video_id, :datetime, :metadata, :created_at)";
$insertSth = $pdoNew->prepare($insertQuery);
foreach ($pdoOld->yieldAll($selectQuery) as $row) {
    $result = $insertSth->execute([
        'id' => $row['id'],
        'video_id' => $row['video_id'],
        'datetime' => $row['datetime'],
        'metadata' => $row['metadata'],
        'created_at' => $row['created_at'],
    ]);
    if (!$result) {
        throw new Exception($insertSth->errorInfo());
    }
}
