<?php

namespace App;

use InvalidArgumentException;
use JsonException;

class Cache
{
    /**
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public static function convertDataFromStorageToCacheData(StorageInterface $storage): array
    {
        $cache = [];

        foreach ($storage->getAllCache() as $storageData) {
            [$key, $JSON] = $storageData;

            $decode = json_decode($JSON, true);

            if (strlen($JSON) > 0 && $decode === null) {
                throw new JsonException("Data from $key is not JSON");
            }

            if (!(isset($decode['data']) && isset($decode['expire']))) {
                throw new InvalidArgumentException("Not found in data: key 'data' or key 'expire'.");
            }

            ['data' => $data, 'expire' => $expire] = $decode;

            $cache[$key] = ['data' => $data, 'expire' => $expire];
        }

        return $cache;
    }
    public function __construct(
        private array $cache,
        private array $expires
    ) {}
    
    public function get(string $key): string
    {
        return json_encode($this->cache[$key] ?? '');
    }

    public function getExpires(): array
    {
        return $this->expires;
    }

    public function set(string $key, string $data, ?int $expire = null): void
    {
        [$state, $oldExpire] = $this->defineStateAndOldExpire($expire, $key);

        switch ($state) {
            // 0 - no update
            // 1 - update cache and delete from expires
            case 1:
                $this->deleteFromExpires($oldExpire, $key);
                $this->cache[$key]['expire'] = null;
                break;
            // 2 - update cache and add to expires and delete from expires
            case 2:
                $this->deleteFromExpires($oldExpire, $key);
                $this->addNewExpire($expire, $key);
                break;
            // 3,4 - add to cache and add to expires
            case 3:
            case 4:
                $this->addNewExpire($expire, $key);
                break;
            // 5 - add to cache
            case 5:
                $this->cache[$key]['expire'] = null;
                break;
        }

        $this->cache[$key]['data'] = $data;
    }

    public function deleteExpiredCache(array $expiredList): array
    {
        $deletedCacheKeyList = [];

        foreach ($expiredList as $expired) {
            if (isset($this->expires[$expired])) {
                foreach ($this->expires[$expired] as $cacheKey) {
                    if (isset($this->cache[$cacheKey])) {
                        unset($this->cache[$cacheKey]);
                        $deletedCacheKeyList[] = $cacheKey;
                        $this->deleteFromExpires($expired, $cacheKey);
                    }
                }
            }
        }

        return [$deletedCacheKeyList, $this->expires];
    }

    public function findExpired(): array
    {
        $currentTime = time();
        $expired = [];

        foreach ($this->expires as $expire => $cacheKey) {
            if ($currentTime >= $expire) {
                $expired[] = $expire;
            }
        }

        return $expired;
    }

    public function findExpiredFromNonCheckedTime(array $NonCheckedTime): array
    {
        $expiredList = [];

        foreach ($NonCheckedTime as $time) {
            if (isset($this->expires[$time])) {
                $expiredList[] = $time;
            }
        }

        return $expiredList;
    }

    private function defineStateAndOldExpire(?int $expire, string $key): array
    {
        $oldExpire = null;

        if (isset($this->cache[$key])) {

            $oldExpire = $this->cache[$key]['expire'];
            $state     = 0;

            if (is_int($oldExpire) && $expire === null) {
                $state = 1;
            } elseif (is_int($oldExpire) && is_int($expire)) {
                $state = 2;
            } elseif ($oldExpire === null && is_int($expire)) {
                $state = 3;
            }
        } else {
            if (is_int($expire)) {
                $state = 4;
            } else {
                $state = 5;
            }
        }

        return [$state, $oldExpire];
    }

    private function addNewExpire(int $expire, string $cacheKey): void
    {
        $newExpire = time() + $expire;

        $this->cache[$cacheKey]['expire'] = $newExpire;

        if (!isset($this->expires[$newExpire])) {
            $this->expires[$newExpire] = [];
        }

        $this->expires[$newExpire][] = $cacheKey;
    }

    private function deleteFromExpires(int $expired, string $cacheKey): void
    {
        if (count($this->expires[$expired]) > 1) {
            unset($this->expires[$expired][array_search($cacheKey, $this->expires[$expired])]);
        } else {
            unset($this->expires[$expired]);
        }
    }
}