<?php
// Prevent direct access without a file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("HTTP/1.0 404 Not Found");
    exit('File not specified');
}

// Clean the filename to prevent directory traversal
$filename = basename($_GET['file']);
$filepath = __DIR__ . '/builds/' . $filename;

// Verify the file exists and is within the builds directory
if (!file_exists($filepath) || !is_file($filepath)) {
    header("HTTP/1.0 404 Not Found");
    exit('File not found');
}

// Get the file size
$filesize = filesize($filepath);

// Set the content type and headers for download
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Clear output buffer
ob_clean();
flush();

// Read the file and output it in chunks
if ($handle = fopen($filepath, 'rb')) {
    while (!feof($handle) && connection_status() == 0) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
}

exit();
