<?php

namespace Tests;

use App\Cache;
use App\StorageInterface;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private Cache $cache;
    private int $time;
    public function setUp(): void
    {
        $this->time = time();

        $data = [
            "test1" => ["data" => "data", "expire" => null],
            "test2" => ["data" => "data", "expire" => $this->time + 1],
            "test3" => ["data" => "data", "expire" => $this->time + 2],
            "expired1" => ["data" => "data", "expire" => $this->time],
            "expired2" => ["data" => "data", "expire" => $this->time - 1],
            "expired3" => ["data" => "data", "expire" => $this->time - 1],
            "expired4" => ["data" => "data", "expire" => $this->time - 3],
        ];

        $expires = [
            $this->time + 1 => ["test2"],
            $this->time + 2 => ["test3"],
            $this->time     => ["expired1"],
            $this->time - 1 => ["expired2", "expired3"],
            $this->time - 3 => ["expired4"],
        ];

        $this->cache = new Cache($data, $expires);
    }

    public function testDeleteExpiredCache()
    {
        $time = $this->time;

        $expiredList = [
            $time,
            $time - 1,
            $time - 3,
            $time - 2,
        ];

        $deletedCacheKeyList = [
            "expired1",
            "expired2",
            "expired3",
            "expired4",
        ];

        $freshExpires = [
            $time + 1 => ["test2"],
            $time + 2 => ["test3"],
        ];

        $expected = [$deletedCacheKeyList, $freshExpires];

        $this->assertEquals($expected, $this->cache->deleteExpiredCache($expiredList));
    }

    public function testFindExpiredFromNonCheckedTime()
    {
        $time = $this->time;

        $expected = [
            $time - 3,
            $time - 1,
            $time,
        ];

        $NonCheckedTime = range($time - 5, $time);

        $this->assertEquals($expected, $this->cache->findExpiredFromNonCheckedTime($NonCheckedTime));
    }

    public function testFindExpired()
    {
        $time = $this->time;

        $expectations = [
            $time - 3,
            $time - 1,
            $time,
        ];

        $result = $this->cache->findExpired();

        array_map(
            fn($expectation) => $this->assertContains($expectation, $result),
            $expectations
        );
    }

    public function testGet()
    {
        $data  = ["test" => ["data" => "data", "expire" => null]];
        $cache = new Cache($data, []);

        $this->assertJsonStringEqualsJsonString(json_encode(["data" => "data", "expire" => null]), $cache->get("test"));
        $this->assertJsonStringEqualsJsonString(json_encode(""), $cache->get("NotExistsKey"));
        $this->assertJsonStringEqualsJsonString(json_encode(""), $cache->get(""));
    }

    public function testSet()
    {
        $cache = new Cache([], []);

        $cache->set("key1", "data");
        $cache->set("key2", "data2", null);
        $cache->set("key3", "data3", 123456);
        $cache->set("key4", "", 123456);
        $cache->set("key5", "old data", 123456);
        $cache->set("key5", "fresh data", 123456);

        $this->assertJsonStringEqualsJsonString(json_encode(["data" => "data", "expire" => null]), $cache->get("key1"));
        $this->assertJsonStringEqualsJsonString(json_encode(["data" => "data2", "expire" => null]), $cache->get("key2"));
        $this->assertJsonStringEqualsJsonString(json_encode(["data" => "data3", "expire" => time()+123456]), $cache->get("key3"));
        $this->assertJsonStringEqualsJsonString(json_encode(["data" => "", "expire" => time()+123456]), $cache->get("key4"));
        $this->assertJsonStringEqualsJsonString(json_encode(["data" => "fresh data", "expire" => time()+123456]), $cache->get("key5"));
    }

    public function testConvertDataFromStorageToCacheData()
    {
        $stub = new class implements StorageInterface {
            public function getAllCache(): iterable
            {
                yield ["test", json_encode(["data" => "data", "expire" => 123])];
                yield ["test2", json_encode(["data" => "2134535data", "expire" => 1454523])];
            }
        };

        $expected = [
            "test" => ["data" => "data", "expire" => 123],
            "test2" => ["data" => "2134535data", "expire" => 1454523],
        ];

        $this->assertEquals($expected, Cache::convertDataFromStorageToCacheData($stub));
    }

    public function testConvertDataFromStorageToCacheDataWhereStorageDataIsNotJSON()
    {
        $stub = new class implements StorageInterface {
            public function getAllCache(): iterable
            {
                yield ["test", serialize(["data" => "data", "expire" => 123])];
                yield ["test", "' [[]'"];
            }
        };

        $this->expectException(JsonException::class);
        Cache::convertDataFromStorageToCacheData($stub);

        $this->expectException(JsonException::class);
        Cache::convertDataFromStorageToCacheData($stub);
    }

    public function testConvertDataFromStorageToCacheDataWhereIncorrectDataStruct()
    {
        $stub = new class implements StorageInterface {
            public function getAllCache(): iterable
            {
                yield ["test4", json_encode(["IncorrectStruct"])];
            }
        };

        $this->expectException(InvalidArgumentException::class);
        Cache::convertDataFromStorageToCacheData($stub);
    }
}
