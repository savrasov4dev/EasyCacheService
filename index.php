<?php

use App\Cache;
use App\Service;
use App\Storage;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";

$storage = new Storage(CACHE_STORAGE_DIR, CACHE_EXPIRES_JSON);
$expires = json_decode($storage->getExpiresJsonFile(), true);
$cache   = new Cache(Cache::convertDataFromStorageToCacheData($storage), $expires);

[$deletedCacheKeyList, $expires] = $cache->deleteExpiredCache($cache->findExpired());

$storage->deleteCache($deletedCacheKeyList);
$storage->putExpiresJSON(json_encode($expires));

$service = new Service(
    SOCKET_DOMAIN,
    SOCKET_TYPE,
    SOCKET_PROTOCOL,
    SOCKET_ADDRESS,
    SOCKET_PORT,
    SOCKET_MAX_READ_LENGTH,
    $cache,
    $storage
);

$service->run();
