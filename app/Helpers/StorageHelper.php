<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class StorageHelper
{
    /**
     * Generate a URL for a file in storage with caching for performance.
     *
     * @param string $diskName The disk name (e.g., 'idcloudhost', 'public')
     * @param string $filePath The file path relative to the disk
     * @return string The URL to the file
     */
    public static function getFileUrl(string $diskName, string $filePath): string
    {
        if (empty($filePath)) {
            return '';
        }
        
        // Generate a unique cache key based on disk and path
        $cacheKey = "file_url_{$diskName}_{$filePath}";
        
        // Return cached URL if available (cache for 24 hours)
        return Cache::remember($cacheKey, 86400, function () use ($diskName, $filePath) {
            // For public disk, use a simple path
            if ($diskName === 'public') {
                return '/storage/' . $filePath;
            }

            // For S3-compatible storage like idcloudhost
            if ($diskName === 'idcloudhost') {
                $bucket = config('filesystems.disks.idcloudhost.bucket');
                $endpoint = config('filesystems.disks.idcloudhost.endpoint');
                $region = config('filesystems.disks.idcloudhost.region');
                
                if ($endpoint) {
                    // If endpoint is defined, use it
                    $baseUrl = rtrim($endpoint, '/') . '/' . $bucket;
                } else {
                    // Otherwise construct S3 URL
                    $baseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com";
                }
                
                return $baseUrl . '/' . $filePath;
            }

            // Default fallback for unknown disks
            return '/storage/' . $filePath;
        });
    }
    
    /**
     * Check if a file exists on a specific disk with caching
     *
     * @param string $diskName The disk name
     * @param string $filePath The file path
     * @return bool Whether the file exists
     */
    public static function fileExists(string $diskName, string $filePath): bool
    {
        $cacheKey = "file_exists_{$diskName}_{$filePath}";
        
        return Cache::remember($cacheKey, 3600, function () use ($diskName, $filePath) {
            return file_exists(self::getFileUrl($diskName, $filePath));
        });
    }
}