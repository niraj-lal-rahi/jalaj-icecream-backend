<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogViewerController extends Controller
{
    /**
     * StorageHelper instance
     */
    protected StorageHelper $storageHelper;

    public function __construct(StorageHelper $storageHelper)
    {
        $this->storageHelper = $storageHelper;
        // Require authentication - can add permission middleware here later
        // $this->middleware('permission:logs.view');
    }

    /**
     * Display list of log files
     */
    public function index(): View
    {
        try {
            $logFiles = $this->storageHelper->getLogFiles();
            $storageFiles = $this->storageHelper->getStorageFiles();

            return view('admin.logs.index', [
                'logFiles' => $logFiles,
                'storageFiles' => $storageFiles,
            ]);
        } catch (\Exception $e) {
            return view('admin.logs.index', [
                'logFiles' => [],
                'storageFiles' => [],
                'error' => 'Failed to load logs: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Display a specific log file with pagination
     */
    public function show(Request $request, string $filename): View
    {
        try {
            $page = $request->query('page', 1);
            $logData = $this->storageHelper->readLogFile($filename, $page);

            return view('admin.logs.show', [
                'logData' => $logData,
            ]);
        } catch (\InvalidArgumentException $e) {
            return view('admin.logs.show', [
                'error' => $e->getMessage(),
                'logData' => null,
            ]);
        } catch (\Exception $e) {
            return view('admin.logs.show', [
                'error' => 'Error reading log file: ' . $e->getMessage(),
                'logData' => null,
            ]);
        }
    }

    /**
     * Display a file from storage directory
     */
    public function viewFile(Request $request, string $path): View
    {
        try {
            $page = $request->query('page', 1);
            $fileData = $this->storageHelper->readFile($path, $page);

            return view('admin.logs.file', [
                'fileData' => $fileData,
            ]);
        } catch (\InvalidArgumentException $e) {
            return view('admin.logs.file', [
                'error' => $e->getMessage(),
                'fileData' => null,
            ]);
        } catch (\Exception $e) {
            return view('admin.logs.file', [
                'error' => 'Error reading file: ' . $e->getMessage(),
                'fileData' => null,
            ]);
        }
    }

    /**
     * Download a file from storage
     */
    public function downloadFile(string $path)
    {
        try {
            return $this->storageHelper->downloadFile($path);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.logs.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Clear (truncate) a log file
     */
    public function clearLog(Request $request, string $filename)
    {
        try {
            $this->storageHelper->clearLogFile($filename);

            return redirect()->route('admin.logs.index')
                ->with('success', "✅ Log file '{$filename}' has been cleared successfully!");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.logs.index')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('admin.logs.index')
                ->with('error', 'Error clearing log file: ' . $e->getMessage());
        }
    }
}

