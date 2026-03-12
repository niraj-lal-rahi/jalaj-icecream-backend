<?php
/**
 * Diagnostic test for WhatsApp attachment path handling
 */

// Simulate Laravel's storage_path and path handling
$storagePath = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app';
$relativePath = 'whatsapp-temp/test_file.csv';

// Old way (broken on Windows)
$oldPath = $storagePath . '/' . $relativePath;

// New way (fixed for Windows)
$newPath = $storagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

echo "📋 Path Handling Test\n";
echo "====================\n\n";

echo "Storage Path: " . $storagePath . "\n";
echo "Relative Path: " . $relativePath . "\n\n";

echo "❌ OLD (broken on Windows):\n";
echo "   " . $oldPath . "\n";
echo "   Has mixed separators: " . (strpos($oldPath, '/') !== false && strpos($oldPath, '\\') !== false ? "YES ❌" : "NO") . "\n\n";

echo "✅ NEW (fixed for Windows):\n";
echo "   " . $newPath . "\n";
echo "   Has mixed separators: " . (strpos($newPath, '/') !== false && strpos($newPath, '\\') !== false ? "YES ❌" : "NO") . "\n\n";

echo "Directory Separator: '" . DIRECTORY_SEPARATOR . "'\n";
echo "Platform: " . php_uname() . "\n";
