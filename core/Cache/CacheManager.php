<?php

namespace Core\Cache;

use Core\Logging\Logger;

/**
 * Cache Manager
 * 
 * Multi-tier caching system with Redis, APCu, and file fallback
 */
class CacheManager
{
    private array $config;
    private Logger $logger;
    private $redis;
    private $prefix;

    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->prefix = $config['prefix'] ?? 'it_hub_';
        
        $this->initializeRedis();
    }

    /**
     * Initialize Redis connection
     */
    private function initializeRedis(): void
    {
        if (!extension_loaded('redis')) {
            $this->logger->warning('Redis extension not loaded, using file cache');
            return;
        }

        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                $this->config['redis']['host'] ?? '127.0.0.1',
                $this->config['redis']['port'] ?? 6379,
                $this->config['redis']['timeout'] ?? 2.0
            );

            if (!empty($this->config['redis']['password'])) {
                $this->redis->auth($this->config['redis']['password']);
            }

            $this->redis->select($this->config['redis']['database'] ?? 0);
            
        } catch (\Exception $e) {
            $this->logger->error('Redis connection failed', ['error' => $e->getMessage()]);
            $this->redis = null;
        }
    }

    /**
     * Get cached value
     */
    public function get(string $key, $default = null)
    {
        $prefixedKey = $this->prefix . $key;

        // Try Redis first
        if ($this->redis) {
            try {
                $value = $this->redis->get($prefixedKey);
                if ($value !== false) {
                    return $this->deserialize($value);
                }
            } catch (\Exception $e) {
                $this->logger->error('Redis get failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        // Try APCu
        if (function_exists('apcu_fetch')) {
            $value = apcu_fetch($prefixedKey);
            if ($value !== false) {
                return $this->deserialize($value);
            }
        }

        // Try file cache
        $value = $this->getFileCache($key);
        if ($value !== null) {
            return $value;
        }

        return $default;
    }

    /**
     * Set cached value
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $prefixedKey = $this->prefix . $key;
        $serialized = $this->serialize($value);
        $success = false;

        // Set in Redis
        if ($this->redis) {
            try {
                $success = $this->redis->setex($prefixedKey, $ttl, $serialized);
            } catch (\Exception $e) {
                $this->logger->error('Redis set failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        // Set in APCu
        if (function_exists('apcu_store')) {
            apcu_store($prefixedKey, $serialized, $ttl);
            $success = true;
        }

        // Set in file cache
        $this->setFileCache($key, $serialized, $ttl);

        return $success;
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        $prefixedKey = $this->prefix . $key;
        $success = false;

        // Delete from Redis
        if ($this->redis) {
            try {
                $success = $this->redis->del($prefixedKey) > 0;
            } catch (\Exception $e) {
                $this->logger->error('Redis delete failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        // Delete from APCu
        if (function_exists('apcu_delete')) {
            apcu_delete($prefixedKey);
            $success = true;
        }

        // Delete from file cache
        $this->deleteFileCache($key);

        return $success;
    }

    /**
     * Delete multiple keys by pattern
     */
    public function deletePattern(string $pattern): int
    {
        $deleted = 0;
        $prefixedPattern = $this->prefix . $pattern;

        // Delete from Redis
        if ($this->redis) {
            try {
                $keys = $this->redis->keys($prefixedPattern);
                if (!empty($keys)) {
                    $deleted = $this->redis->del($keys);
                }
            } catch (\Exception $e) {
                $this->logger->error('Redis pattern delete failed', ['pattern' => $pattern, 'error' => $e->getMessage()]);
            }
        }

        // Delete from APCu
        if (function_exists('apcu_iterator')) {
            $iterator = new \APCUIterator('/^' . preg_quote($prefixedPattern, '/') . '/');
            foreach ($iterator as $item) {
                apcu_delete($item['key']);
                $deleted++;
            }
        }

        // Delete from file cache
        $deleted += $this->deleteFileCachePattern($pattern);

        return $deleted;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        $prefixedKey = $this->prefix . $key;

        // Check Redis
        if ($this->redis) {
            try {
                return $this->redis->exists($prefixedKey) > 0;
            } catch (\Exception $e) {
                $this->logger->error('Redis exists check failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        // Check APCu
        if (function_exists('apcu_exists')) {
            return apcu_exists($prefixedKey);
        }

        // Check file cache
        return $this->hasFileCache($key);
    }

    /**
     * Increment value
     */
    public function increment(string $key, int $value = 1): int
    {
        $prefixedKey = $this->prefix . $key;

        if ($this->redis) {
            try {
                return $this->redis->incrBy($prefixedKey, $value);
            } catch (\Exception $e) {
                $this->logger->error('Redis increment failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        // Fallback to manual implementation
        $current = $this->get($key, 0);
        $newValue = $current + $value;
        $this->set($key, $newValue);

        return $newValue;
    }

    /**
     * Remember value (get or set with callback)
     */
    public function remember(string $key, callable $callback, int $ttl = 3600)
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    /**
     * Get file cache
     */
    private function getFileCache(string $key)
    {
        $file = $this->getCacheFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }

        $data = include $file;
        
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set file cache
     */
    private function setFileCache(string $key, $value, int $ttl): void
    {
        $file = $this->getCacheFilePath($key);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        file_put_contents($file, '<?php return ' . var_export($data, true) . ';');
    }

    /**
     * Delete file cache
     */
    private function deleteFileCache(string $key): void
    {
        $file = $this->getCacheFilePath($key);
        
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Delete file cache by pattern
     */
    private function deleteFileCachePattern(string $pattern): int
    {
        $deleted = 0;
        $cacheDir = $this->config['file_cache']['path'] ?? storage_path('cache');
        
        if (!is_dir($cacheDir)) {
            return $deleted;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getBasename())) {
                unlink($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Check if file cache exists
     */
    private function hasFileCache(string $key): bool
    {
        $file = $this->getCacheFilePath($key);
        
        if (!file_exists($file)) {
            return false;
        }

        $data = include $file;
        return $data['expires'] >= time();
    }

    /**
     * Get cache file path
     */
    private function getCacheFilePath(string $key): string
    {
        $cacheDir = $this->config['file_cache']['path'] ?? storage_path('cache');
        $hash = md5($key);
        $subdir = substr($hash, 0, 2);
        
        return $cacheDir . '/' . $subdir . '/' . $hash . '.php';
    }

    /**
     * Serialize value
     */
    private function serialize($value): string
    {
        return serialize($value);
    }

    /**
     * Deserialize value
     */
    private function deserialize(string $value)
    {
        return unserialize($value);
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        $success = false;

        // Clear Redis
        if ($this->redis) {
            try {
                $success = $this->redis->flushDB() > 0;
            } catch (\Exception $e) {
                $this->logger->error('Redis clear failed', ['error' => $e->getMessage()]);
            }
        }

        // Clear APCu
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
            $success = true;
        }

        // Clear file cache
        $cacheDir = $this->config['file_cache']['path'] ?? storage_path('cache');
        if (is_dir($cacheDir)) {
            $this->deleteDirectory($cacheDir);
            $success = true;
        }

        return $success;
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [
            'redis' => false,
            'apcu' => false,
            'file_cache' => false
        ];

        if ($this->redis) {
            try {
                $info = $this->redis->info();
                $stats['redis'] = [
                    'used_memory' => $info['used_memory'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? '0B',
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0
                ];
            } catch (\Exception $e) {
                $this->logger->error('Redis stats failed', ['error' => $e->getMessage()]);
            }
        }

        if (function_exists('apcu_cache_info')) {
            $info = apcu_cache_info();
            $stats['apcu'] = [
                'num_slots' => $info['num_slots'] ?? 0,
                'memory_size' => $info['mem_size'] ?? 0,
                'hits' => $info['hits'] ?? 0,
                'misses' => $info['misses'] ?? 0
            ];
        }

        $cacheDir = $this->config['file_cache']['path'] ?? storage_path('cache');
        if (is_dir($cacheDir)) {
            $stats['file_cache'] = [
                'files' => $this->countFiles($cacheDir),
                'size' => $this->getDirectorySize($cacheDir)
            ];
        }

        return $stats;
    }

    /**
     * Count files in directory
     */
    private function countFiles(string $dir): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get directory size
     */
    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
