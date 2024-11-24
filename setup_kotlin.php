<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$toolsDir = __DIR__ . '/tools';
$kotlinDir = $toolsDir . '/kotlinc';
$kotlincPath = $kotlinDir . '/bin/kotlinc.bat';

// Create tools directory if it doesn't exist
if (!file_exists($toolsDir)) {
    if (!mkdir($toolsDir, 0777, true)) {
        die("Failed to create tools directory at: $toolsDir\n");
    }
    echo "Created tools directory at: $toolsDir\n";
}

// Clean up existing kotlinc directory if it exists
if (file_exists($kotlinDir)) {
    echo "Removing existing Kotlin installation...\n";
    array_map('unlink', glob("$kotlinDir/*.*"));
    rmdir($kotlinDir);
}

// URL for Kotlin compiler
$kotlinUrl = 'https://github.com/JetBrains/kotlin/releases/download/v1.8.0/kotlin-compiler-1.8.0.zip';
$zipFile = $toolsDir . '/kotlin-compiler.zip';

// Download Kotlin compiler
echo "Downloading Kotlin compiler from: $kotlinUrl\n";
$ch = curl_init($kotlinUrl);
$fp = fopen($zipFile, 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$success = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

if (!$success || $httpCode !== 200) {
    die("Failed to download Kotlin compiler. HTTP Code: $httpCode\n");
}

if (!file_exists($zipFile)) {
    die("Failed to save Kotlin compiler zip file\n");
}

echo "Successfully downloaded Kotlin compiler\n";

// Extract the ZIP file
echo "Extracting Kotlin compiler to: $toolsDir\n";
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($toolsDir);
    $zip->close();
    echo "Kotlin compiler extracted successfully\n";
} else {
    die("Failed to extract Kotlin compiler\n");
}

// Clean up
unlink($zipFile);
echo "Cleaned up temporary files\n";

// Verify Kotlin installation
if (!file_exists($kotlincPath)) {
    die("Kotlin compiler not found at: $kotlincPath\n");
}

// Test Kotlin compiler
$testDir = $toolsDir . '/test';
if (!file_exists($testDir)) {
    mkdir($testDir, 0777, true);
}

$testFile = $testDir . '/Test.kt';
file_put_contents($testFile, '
fun main() {
    println("Hello from Kotlin!")
}
');

echo "Testing Kotlin compiler...\n";
$command = '"' . $kotlincPath . '" -include-runtime -d "' . $testDir . '/test.jar" "' . $testFile . '" 2>&1';
exec($command, $output, $returnVar);

if ($returnVar !== 0) {
    echo "Warning: Kotlin compiler test failed:\n";
    echo implode("\n", $output) . "\n";
} else {
    echo "Kotlin compiler test successful!\n";
}

// Clean up test files
unlink($testFile);
if (file_exists($testDir . '/test.jar')) {
    unlink($testDir . '/test.jar');
}
rmdir($testDir);

echo "Setup complete! Kotlin compiler is ready to use.\n";
echo "Kotlin compiler path: $kotlincPath\n";
?>
