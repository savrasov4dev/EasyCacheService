<?php

namespace App;

interface CacheI
{
    public function get(string $key): string;
    
    public function set(string $key, string $data, ?int $expire = null): array;
    
    public function deleteExpiredCache(array $expiredList): array;

    public function findExpired(): array;

    public function findExpiredFromNonCheckedTime(array $NonCheckedTime): array;

}