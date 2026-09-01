<?php
declare(strict_types=1);

namespace General\Service;

use Laminas\Cache\Storage\StorageFactory;
use Laminas\Cache\Storage\StorageInterface;

class RedisCacheService
{
    /**
     * Cache storage adapters indexed by namespace
     *
     * @var array<string, StorageInterface>
     */
    private array $adapters = [];
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Retrieve or create a Redis storage adapter for a given namespace.
     */
    public function getAdapter(string $namespace = 'dyxi_general'): ?StorageInterface
    {
        if (isset($this->adapters[$namespace])) {
            return $this->adapters[$namespace];
        }

        try {
            $host = getenv('REDIS_HOST') ?: ($this->config['redis']['host'] ?? '127.0.0.1');
            $port = (int)(getenv('REDIS_PORT') ?: ($this->config['redis']['port'] ?? 6379));

            $adapterConfig = [
                'adapter' => [
                    'name' => 'redis',
                    'options' => [
                        'server' => [
                            'host' => $host,
                            'port' => $port,
                        ],
                        'ttl' => 86400,
                        'namespace' => $namespace,
                    ],
                ],
                'plugins' => [
                    'exception_handler' => [
                        'throw_exceptions' => false,
                    ],
                    'serializer',
                ],
            ];

            $adapter = StorageFactory::factory($adapterConfig);
            $this->adapters[$namespace] = $adapter;
            return $adapter;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get item from Redis cache for specified namespace.
     */
    public function get(string $key, string $namespace = 'dyxi_general')
    {
        $adapter = $this->getAdapter($namespace);
        if ($adapter === null) {
            return null;
        }

        try {
            if ($adapter->hasItem($key)) {
                return $adapter->getItem($key);
            }
        } catch (\Throwable $e) {}

        return null;
    }

    /**
     * Set item in Redis cache for specified namespace.
     */
    public function set(string $key, $value, int $ttl = 86400, string $namespace = 'dyxi_general'): bool
    {
        $adapter = $this->getAdapter($namespace);
        if ($adapter === null) {
            return false;
        }

        try {
            $adapter->getOptions()->setTtl($ttl);
            return $adapter->setItem($key, $value);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if item exists in Redis cache.
     */
    public function has(string $key, string $namespace = 'dyxi_general'): bool
    {
        $adapter = $this->getAdapter($namespace);
        if ($adapter === null) {
            return false;
        }

        try {
            return $adapter->hasItem($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete item from Redis cache.
     */
    public function delete(string $key, string $namespace = 'dyxi_general'): bool
    {
        $adapter = $this->getAdapter($namespace);
        if ($adapter === null) {
            return false;
        }

        try {
            return $adapter->removeItem($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Flush all items in a namespace.
     */
    public function clearNamespace(string $namespace): bool
    {
        $adapter = $this->getAdapter($namespace);
        if ($adapter === null) {
            return false;
        }

        try {
            return $adapter->flush();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Execute Redis Hit / Miss Caching flow:
     * - On HIT: returns cached data.
     * - On MISS: executes $fallback(), stores result in Redis under $namespace, and returns data.
     */
    public function getOrSet(string $key, callable $fallback, int $ttl = 86400, string $namespace = 'dyxi_general')
    {
        $cached = $this->get($key, $namespace);
        if ($cached !== null) {
            return $cached;
        }

        // Cache MISS: execute fallback
        $result = $fallback();

        if ($result !== null) {
            $this->set($key, $result, $ttl, $namespace);
        }

        return $result;
    }
}
