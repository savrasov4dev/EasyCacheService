<?php

namespace App;

interface StorageI
{
    public function getCache(string $fileName): string;

    public function getAllCache(): iterable;

    public function getExpiresJsonFile(): string;

    public function putCache(string $cacheKey, string $data): void;

    public function putExpiresJSON(string $expiresJSON): void;

    public function deleteCache(array $cacheKeyList): void;
}