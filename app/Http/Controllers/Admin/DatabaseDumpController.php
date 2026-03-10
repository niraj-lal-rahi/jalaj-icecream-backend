<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class DatabaseDumpController extends Controller
{
    protected $dumpsStoragePath = 'storage/dumps';

    public function __construct()
    {
        // Ensure dumps directory exists
        if (!file_exists($this->dumpsStoragePath)) {
            mkdir($this->dumpsStoragePath, 0755, true);
        }
    }

    /**
     * List all database dumps
     */
    public function index()
    {
        try {
            $dumps = [];

            if (file_exists($this->dumpsStoragePath)) {
                $files = scandir($this->dumpsStoragePath, SCANDIR_SORT_DESCENDING);

                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                        $filePath = $this->dumpsStoragePath . DIRECTORY_SEPARATOR . $file;
                        $dumps[] = [
                            'name' => $file,
                            'size' => filesize($filePath),
                            'size_formatted' => $this->formatBytes(filesize($filePath)),
                            'created_at' => filectime($filePath),
                            'created_at_formatted' => Carbon::createFromTimestamp(filectime($filePath))->format('M d, Y H:i:s'),
                        ];
                    }
                }
            }

            return view('admin.database-dumps.index', compact('dumps'));
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')->with('error', 'Failed to load dumps: ' . $e->getMessage());
        }
    }

    /**
     * Create a new database dump
     */
    public function create()
    {
        try {
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME', 'root');
            $password = env('DB_PASSWORD', '');
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', 3306);

            // Generate filename with current timestamp
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "{$database}_{$timestamp}.sql";
            $filePath = $this->dumpsStoragePath . DIRECTORY_SEPARATOR . $filename;

            // Build mysqldump command
            $passwordOption = !empty($password) ? "-p\"{$password}\"" : '';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows command
                $command = "mysqldump -h {$host} -P {$port} -u {$username} {$passwordOption} {$database} > \"{$filePath}\"";
            } else {
                // Linux/Mac command
                $command = "mysqldump -h {$host} -P {$port} -u {$username} {$passwordOption} {$database} > \"{$filePath}\"";
            }

            // Execute the dump command
            $output = null;
            $returnVar = null;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode(' ', $output) : 'Unknown error';
                throw new \Exception("Database dump failed: {$errorMsg}");
            }

            if (!file_exists($filePath)) {
                throw new \Exception("Dump file was not created");
            }

            return redirect()->route('admin.database-dumps.index')
                ->with('success', "Database dumped successfully! File: {$filename}");
        } catch (\Exception $e) {
            return redirect()->route('admin.database-dumps.index')
                ->with('error', 'Failed to create dump: ' . $e->getMessage());
        }
    }

    /**
     * Download a database dump
     */
    public function download($filename)
    {
        try {
            // Security: prevent directory traversal
            $filename = basename($filename);
            $filePath = $this->dumpsStoragePath . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($filePath)) {
                abort(404, 'Dump file not found');
            }

            // Verify it's a .sql file
            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'sql') {
                abort(403, 'Invalid file type');
            }

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/sql',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.database-dumps.index')
                ->with('error', 'Failed to download file: ' . $e->getMessage());
        }
    }

    /**
     * Delete a database dump
     */
    public function delete($filename)
    {
        try {
            // Security: prevent directory traversal
            $filename = basename($filename);
            $filePath = $this->dumpsStoragePath . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($filePath)) {
                return redirect()->route('admin.database-dumps.index')
                    ->with('error', 'Dump file not found');
            }

            // Verify it's a .sql file
            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'sql') {
                return redirect()->route('admin.database-dumps.index')
                    ->with('error', 'Invalid file type');
            }

            unlink($filePath);

            return redirect()->route('admin.database-dumps.index')
                ->with('success', "Dump deleted: {$filename}");
        } catch (\Exception $e) {
            return redirect()->route('admin.database-dumps.index')
                ->with('error', 'Failed to delete dump: ' . $e->getMessage());
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
