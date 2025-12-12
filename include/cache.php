<?php
/**
 * Simple Cache Helper for Mikhmon
 * Cache Mikrotik API responses to improve dashboard performance
 */

class SimpleCache {
    private $cacheDir;
    private $defaultTTL = 30; // Default cache time: 30 seconds

    public function __construct($cacheDir = null) {
        if ($cacheDir === null) {
            $cacheDir = dirname(__DIR__) . '/cache';
        }

        $this->cacheDir = $cacheDir;

        // Create cache directory if not exists
        if (!file_exists($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        // Create .htaccess to protect cache directory
        $htaccessFile = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Deny from all\n");
        }
    }

    /**
     * Get cache file path for a key
     */
    private function getCacheFile($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * Get data from cache
     * @param string $key Cache key
     * @return mixed|null Returns cached data or null if not found/expired
     */
    public function get($key) {
        $cacheFile = $this->getCacheFile($key);

        if (!file_exists($cacheFile)) {
            return null;
        }

        $data = @file_get_contents($cacheFile);
        if ($data === false) {
            return null;
        }

        $cached = @unserialize($data);
        if ($cached === false) {
            @unlink($cacheFile);
            return null;
        }

        // Check if cache expired
        if (isset($cached['expire']) && $cached['expire'] < time()) {
            @unlink($cacheFile);
            return null;
        }

        return isset($cached['data']) ? $cached['data'] : null;
    }

    /**
     * Set data to cache
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds (default: 30)
     * @return bool Success status
     */
    public function set($key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->defaultTTL;
        }

        $cacheFile = $this->getCacheFile($key);

        $cached = array(
            'expire' => time() + $ttl,
            'data' => $data
        );

        $serialized = serialize($cached);
        return @file_put_contents($cacheFile, $serialized, LOCK_EX) !== false;
    }

    /**
     * Delete cache by key
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        return true;
    }

    /**
     * Clear all cache files
     * @return int Number of files deleted
     */
    public function clear() {
        $count = 0;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.cache');
            foreach ($files as $file) {
                if (@unlink($file)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Clear expired cache files
     * @return int Number of files deleted
     */
    public function clearExpired() {
        $count = 0;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.cache');
            foreach ($files as $file) {
                $data = @file_get_contents($file);
                if ($data !== false) {
                    $cached = @unserialize($data);
                    if ($cached !== false && isset($cached['expire']) && $cached['expire'] < time()) {
                        if (@unlink($file)) {
                            $count++;
                        }
                    }
                }
            }
        }
        return $count;
    }
}

/**
 * Helper function to get cached Mikrotik API data
 * @param RouterosAPI $API Mikrotik API instance
 * @param string $command API command
 * @param array $params API parameters
 * @param int $ttl Cache time in seconds
 * @return mixed API response
 */
function getCachedApiData($API, $command, $params = array(), $ttl = 30) {
    static $cache = null;

    if ($cache === null) {
        $cache = new SimpleCache();
    }

    // Create unique cache key
    $cacheKey = 'api_' . md5($command . serialize($params));

    // Try to get from cache
    $cachedData = $cache->get($cacheKey);
    if ($cachedData !== null) {
        return $cachedData;
    }

    // Fetch from API
    try {
        $data = $API->comm($command, $params);

        // Cache the result
        if ($data !== false && $data !== null) {
            $cache->set($cacheKey, $data, $ttl);
        }

        return $data;
    } catch (Exception $e) {
        // Return cached data even if expired, better than nothing
        return $cachedData;
    }
}
