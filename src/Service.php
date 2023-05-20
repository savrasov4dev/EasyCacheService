<?php

namespace App;

use Socket;

class Service
{
    private Socket $socket;
    public function __construct(
        private readonly int      $domain,
        private readonly int      $type,
        private readonly int      $protocol,
        private readonly string   $address,
        private readonly ?string  $port,
        private readonly int      $maxReadLength,
        private readonly CacheI   $cache,
        private readonly StorageI $storage,
    )
    {
        extension_loaded('sockets') OR die("The sockets extension is not loaded.\n");

        $socket = socket_create($this->domain, $this->type, $this->protocol) OR die(
            $this->getError($socket, "Unable to create $this->domain socket:")
        );

        switch ($this->port) {
            case null:
                if (file_exists($this->address)) {
                    unlink($this->address);
                }
                socket_bind($socket, $this->address) OR die($this->getError($socket, "Unable to bind to '$this->address':"));
                break;
            default:
                socket_bind($socket, $this->address, $this->port) OR die(
                    $this->getError($socket, "Unable to bind to '$this->address:$this->port':")
                );
                break;
        }

        socket_listen($socket, 5) OR die($this->getError($socket, "Unable to listen:"));

        $this->socket = $socket;
    }

    public function run(): void
    {
        $previousIterationTime = time();

        while (true) {

            $currentTime = time();

            if ($currentTime != $previousIterationTime) {

                $nonCheckedTime = range($previousIterationTime, $currentTime);

                [$deletedCacheKeyList, $expires] = $this->cache->deleteExpiredCache(
                    $this->cache->findExpiredFromNonCheckedTime($nonCheckedTime)
                );

                $this->storage->deleteCache($deletedCacheKeyList);
                $this->storage->putExpiresJSON(json_encode($expires));

                $previousIterationTime = $currentTime;
            }

            if (false === ($connect = socket_accept($this->socket))) {
                $error = $this->getError($this->socket, "Unable to accept:");
                break;
            }

            $request = socket_read($connect, $this->maxReadLength);

            if ($request === false) {
                echo $this->getError($connect, "Unable to read:");
                socket_close($connect);
                continue;
            }

            ['method' => $method, 'key' => $key, 'data' => $data, 'expire' => $expire] = json_decode($request, true);

            $key      = md5($key);
            $response = "Method '$method' not found!";

            if (method_exists($this, $method)) {
                $response = $this->$method($key, $data, $expire);
            }

            $length = strlen($response);

            do {
                if (false === ($sent = socket_write($connect, $response, $length))) {
                    echo $this->getError($connect, "Unable to write:");
                    break;
                }

                if ($sent === $length) {
                    break;
                }

                $response = substr($response, $sent);
                $length  -= $sent;

            } while (true);

            socket_close($connect);
        }

        socket_close($this->socket);

        echo $error;
    }

    private function get(string $key): string
    {
        return $this->cache->get($key);
    }

    private function set(string $key, string $data, ?int $expire): string
    {
        $expires = $this->cache->set($key, $data, $expire);
        $this->storage->putCache($key, $this->cache->get($key));
        $this->storage->putExpiresJSON(json_encode($expires));

        return 'OK';
    }

    private function getError(Socket $socket, string $text): string
    {
        return $text . "\n" . socket_strerror(socket_last_error($socket)) . "\n";
    }
}