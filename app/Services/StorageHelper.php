<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StorageHelper
{
    /**
     * Base directory for storage operations (security boundary)
     */
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = storage_path();
    }

    /**
     * Get path to logs directory
     *
     * @return string
     */
    public function getLogsDirectory(): string
    {
        return storage_path('logs');
    }

    /**
     * Get all log files from storage/logs directory
     *
     * @return array Array of file metadata with keys: name, path, size, modified
     */
    public function getLogFiles(): array
    {
        $logDir = $this->getLogsDirectory();
        $files = [];

        if (!is_dir($logDir)) {
            return $files;
        }

        $contents = scandir($logDir);

        foreach ($contents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $filePath = $logDir . DIRECTORY_SEPARATOR . $item;

            if (is_file($filePath)) {
                $files[] = [
                    'name' => $item,
                    'path' => $item, // Relative path for safety
                    'size' => filesize($filePath),
                    'size_formatted' => $this->formatBytes(filesize($filePath)),
                    'modified' => filemtime($filePath),
                    'modified_date' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
            }
        }

        // Sort by modified date (newest first)
        usort($files, function ($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $files;
    }

    /**
     * Get all files from a storage subdirectory (recursively)
     *
     * @param string $directory Subdirectory of storage/ (e.g., 'app', 'reports')
     * @return array
     */
    public function getStorageFiles(string $directory = ''): array
    {
        $baseDir = $this->basePath;
        if (!empty($directory)) {
            $baseDir = $baseDir . DIRECTORY_SEPARATOR . trim($directory, DIRECTORY_SEPARATOR);
        }

        // Security check: ensure path is within storage directory
        if (!$this->isPathSafe($baseDir)) {
            return [];
        }

        $files = [];

        if (!is_dir($baseDir)) {
            return $files;
        }

        $contents = scandir($baseDir);

        foreach ($contents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $item;

            if (is_file($fullPath)) {
                $relativePath = str_replace($this->basePath . DIRECTORY_SEPARATOR, '', $fullPath);
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'full_path' => $fullPath,
                    'size' => filesize($fullPath),
                    'size_formatted' => $this->formatBytes(filesize($fullPath)),
                    'modified' => filemtime($fullPath),
                    'modified_date' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    'extension' => pathinfo($item, PATHINFO_EXTENSION),
                ];
            }
        }

        // Sort by modified date descending
        usort($files, function ($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $files;
    }

    /**
     * Read a log file safely with pagination support
     *
     * @param string $filename Filename only (e.g., 'laravel.log')
     * @param int $page Page number (1-based)
     * @param int $linesPerPage Lines per page
     * @return array Array with keys: 'lines', 'total_lines', 'page', 'total_pages', 'filename', 'full_path'
     * @throws InvalidArgumentException
     */
    public function readLogFile(string $filename, int $page = 1, int $linesPerPage = 50): array
    {
        // Security: only allow filenames, no paths
        if (Str::contains($filename, ['/', '\\', '..'])) {
            throw new InvalidArgumentException('Invalid filename format');
        }

        $filePath = $this->getLogsDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new InvalidArgumentException("Log file not found: {$filename}");
        }

        // Check file size (warn if > 5MB)
        $fileSize = filesize($filePath);
        if ($fileSize > 5 * 1024 * 1024) {
            $warning = "File size is " . $this->formatBytes($fileSize) . " (large file)";
        } else {
            $warning = null;
        }

        // Read the file
        $allLines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($allLines === false) {
            $allLines = [];
        }

        $totalLines = count($allLines);
        $totalPages = ceil($totalLines / $linesPerPage);

        // Ensure page is valid
        $page = max(1, min($page, $totalPages ?: 1));

        // Get lines for current page (reverse to show newest first)
        $allLines = array_reverse($allLines);
        $startLine = ($page - 1) * $linesPerPage;
        $pageLines = array_slice($allLines, $startLine, $linesPerPage);

        return [
            'filename' => $filename,
            'full_path' => $filePath,
            'lines' => $pageLines,
            'total_lines' => $totalLines,
            'page' => $page,
            'total_pages' => $totalPages,
            'size' => $fileSize,
            'size_formatted' => $this->formatBytes($fileSize),
            'modified_date' => date('Y-m-d H:i:s', filemtime($filePath)),
            'warning' => $warning,
        ];
    }

    /**
     * Read a generic file from storage
     *
     * @param string $relativePath Path relative to storage/ (e.g., 'app/whatsapp-temp/file.txt')
     * @param int $page Page number for pagination
     * @param int $linesPerPage Lines per page
     * @return array
     * @throws InvalidArgumentException
     */
    public function readFile(string $relativePath, int $page = 1, int $linesPerPage = 50): array
    {
        // Validate path to prevent directory traversal
        if (!$this->isPathSafe($this->basePath . DIRECTORY_SEPARATOR . $relativePath)) {
            throw new InvalidArgumentException('Invalid file path');
        }

        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new InvalidArgumentException("File not found: {$relativePath}");
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileSize = filesize($filePath);

        // For binary files, don't try to read as text
        $binaryExtensions = ['zip', 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'exe', 'dll', 'bin'];
        if (in_array(strtolower($extension), $binaryExtensions)) {
            return [
                'filename' => basename($filePath),
                'path' => $relativePath,
                'size' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'is_binary' => true,
                'extension' => $extension,
                'message' => "Binary file. Download to view.",
            ];
        }

        // Read text file
        $allLines = @file($filePath, FILE_IGNORE_NEW_LINES);
        if ($allLines === false) {
            $allLines = [];
        }

        $totalLines = count($allLines);
        $totalPages = ceil($totalLines / $linesPerPage);
        $page = max(1, min($page, $totalPages ?: 1));

        $startLine = ($page - 1) * $linesPerPage;
        $pageLines = array_slice($allLines, $startLine, $linesPerPage);

        return [
            'filename' => basename($filePath),
            'path' => $relativePath,
            'lines' => $pageLines,
            'total_lines' => $totalLines,
            'page' => $page,
            'total_pages' => $totalPages,
            'size' => $fileSize,
            'size_formatted' => $this->formatBytes($fileSize),
            'modified_date' => date('Y-m-d H:i:s', filemtime($filePath)),
            'extension' => $extension,
            'is_binary' => false,
        ];
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Check if a path is safe (within storage directory)
     * Prevents directory traversal attacks
     *
     * @param string $path Full path to check
     * @return bool
     */
    protected function isPathSafe(string $path): bool
    {
        // Normalize paths
        $basePath = realpath($this->basePath);
        $checkPath = realpath($path);

        if ($basePath === false || $checkPath === false) {
            return false;
        }

        // Ensure the checked path starts with the base path
        return strpos($checkPath, $basePath) === 0;
    }

    /**
     * Get file content as string (for small text files)
     *
     * @param string $filename Filename from logs directory
     * @param int $maxBytes Maximum bytes to read (default 1MB)
     * @return string|null
     */
    public function readLogFileAsString(string $filename, int $maxBytes = 1024 * 1024): ?string
    {
        if (Str::contains($filename, ['/', '\\', '..'])) {
            return null;
        }

        $filePath = $this->getLogsDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath)) {
            return null;
        }

        if (filesize($filePath) > $maxBytes) {
            return null; // File too large
        }

        return @file_get_contents($filePath);
    }

    /**
     * Download a file to client
     *
     * @param string $relativePath Path relative to storage/
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadFile(string $relativePath)
    {
        if (!$this->isPathSafe($this->basePath . DIRECTORY_SEPARATOR . $relativePath)) {
            throw new InvalidArgumentException('Invalid file path');
        }

        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new InvalidArgumentException("File not found: {$relativePath}");
        }

        return response()->download($filePath);
    }

    /**
     * Clear (truncate) a log file
     *
     * @param string $filename Filename from logs directory (e.g., 'laravel.log')
     * @return bool True if successful
     * @throws InvalidArgumentException
     */
    public function clearLogFile(string $filename): bool
    {
        // Security: only allow filenames, no paths
        if (Str::contains($filename, ['/', '\\', '..'])) {
            throw new InvalidArgumentException('Invalid filename format');
        }

        $filePath = $this->getLogsDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new InvalidArgumentException("Log file not found: {$filename}");
        }

        // Check if file is writable
        if (!is_writable($filePath)) {
            throw new InvalidArgumentException("Log file is not writable: {$filename}");
        }

        // Truncate the file (clear all content)
        $handle = @fopen($filePath, 'w');
        if ($handle === false) {
            throw new InvalidArgumentException("Cannot open file for writing: {$filename}");
        }

        fclose($handle);

        return true;
    }
}

