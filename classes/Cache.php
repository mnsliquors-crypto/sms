<?php
/**
 * Simple file-based caching service for ERP performance optimization.
 */
class Cache {
    private $cache_dir;
    private $expiry;

    public function __construct($expiry = 3600) {
        $this->cache_dir = __DIR__ . '/../temp/cache/';
        $this->expiry = $expiry;
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0777, true);
        }
    }

    /**
     * Get data from cache.
     */
    public function get($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        if (is_file($file) && (time() - filemtime($file) < $this->expiry)) {
            return unserialize(file_get_contents($file));
        }
        return null;
    }

    /**
     * Save data to cache.
     */
    public function set($key, $data) {
        $file = $this->cache_dir . md5($key) . '.cache';
        return file_put_contents($file, serialize($data));
    }

    /**
     * Delete a specific cache key.
     */
    public function delete($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Clear all cache files.
     */
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }
}
