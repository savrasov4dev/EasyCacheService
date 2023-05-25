<?php

namespace App;

readonly class Storage implements StorageInterface
{
    public function __construct(
        private string $cacheDir,
        private string $expiresJsonFile
    ) {}

    public function getCache(string $fileName): string
    {
        $data = file_get_contents($this->cacheDir . "/" . $fileName);

        return $data !== false ? $data : '';
    }

    public function getAllCache(): iterable
    {
        $fd = opendir($this->cacheDir);

        while (false !== ($fileName = readdir($fd))) {
            if (!in_array($fileName, ['.', '..'])) {
                yield [$fileName, $this->getCache($fileName)];
            }
        }
    }

    public function getExpiresJsonFile(): string
    {
        $expiresJSON = file_exists($this->expiresJsonFile)
            ? file_get_contents($this->expiresJsonFile)
            : false;

        return $expiresJSON !== false ? $expiresJSON : '[]';
    }

    public function putCache(string $cacheKey, string $data): void
    {
        file_put_contents($this->cacheDir . "/" . $cacheKey, $data);
    }

    public function putExpiresJSON(string $expiresJSON): void
    {
        file_put_contents($this->expiresJsonFile, $expiresJSON);
    }

    public function deleteCache(array $cacheKeyList): void
    {
        foreach ($cacheKeyList as $cacheKey) {
            $cacheFile = $this->cacheDir . "/" . $cacheKey;
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        }
    }
}