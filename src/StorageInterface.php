<?php

namespace App;

interface StorageInterface
{
    public function getAllCache(): iterable;
}