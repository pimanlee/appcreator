<?php
function generateApk($appName, $logoPath, $userId) {
    $androidPath = 'C:/Users/Administrator/AppData/Local/Android/Sdk';
    
    // Create user-specific directory
    $userProjectsDir = __DIR__ . '/user_projects/' . $userId;
    if (!file_exists($userProjectsDir)) {
        mkdir($userProjectsDir, 0777, true);
    }
    
    // Create project-specific directory using timestamp to ensure uniqueness
    $timestamp = time();
    $projectDir = $userProjectsDir . '/' . preg_replace('/[^a-zA-Z0-9]/', '_', $appName) . '_' . $timestamp;
    
    // Create all necessary directories
    $paths = [
        $projectDir . '/app/src/main/java/com/example/app',
        $projectDir . '/app/src/main/res/mipmap',
        $projectDir . '/app/src/main/res/values',
        $projectDir . '/assets' // Directory for app assets like logos
    ];
    
    foreach ($paths as $path) {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }
    
    // Create settings.gradle first (repository configuration must be in settings)
    $settingsGradle = <<<EOT
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.PREFER_SETTINGS)
    repositories {
        google()
        mavenCentral()
    }
}
rootProject.name = "AppBuilder"
include ':app'
EOT;
    file_put_contents($projectDir . '/settings.gradle', $settingsGradle);

    // Create keystore directory if it doesn't exist
    $keystoreDir = $projectDir . '/keystore';
    if (!file_exists($keystoreDir)) {
        mkdir($keystoreDir, 0777, true);
    }

    // Define keystore parameters - use relative path for Gradle
    $keystoreFile = 'keystore/release-key.jks';
    $keystoreFullPath = $projectDir . '/' . $keystoreFile;
    $keystorePassword = 'android123';
    $keyAlias = 'release';
    $keyPassword = 'android123';

    // Create keystore if it doesn't exist
    if (!file_exists($keystoreFullPath)) {
        $keytoolCmd = 'keytool -genkey -v -keystore "' . $keystoreFullPath . '" -keyalg RSA -keysize 2048 -validity 10000 ' .
                     '-alias ' . $keyAlias . ' -storepass ' . $keystorePassword . ' -keypass ' . $keyPassword . ' ' .
                     '-dname "CN=Android App, OU=Android, O=Android, L=Android, S=Android, C=US"';
        
        exec($keytoolCmd . ' 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            return [
                'success' => false,
                'message' => 'Failed to create keystore: ' . implode("\n", $output)
            ];
        }
    }

    // Create gradle.properties with relative keystore path
    $gradleProperties = <<<EOT
android.useAndroidX=true
android.enableJetifier=true
android.suppressUnsupportedCompileSdk=34
org.gradle.jvmargs=-Xmx2048m -Dfile.encoding=UTF-8

# Signing config
RELEASE_STORE_FILE=../{$keystoreFile}
RELEASE_STORE_PASSWORD={$keystorePassword}
RELEASE_KEY_ALIAS={$keyAlias}
RELEASE_KEY_PASSWORD={$keyPassword}
EOT;
    file_put_contents($projectDir . '/gradle.properties', $gradleProperties);

    // Create build.gradle in project root
    $projectGradle = <<<EOT
buildscript {
    ext {
        kotlin_version = '1.6.10'
        gradle_version = '7.3.3'
    }
    repositories {
        google()
        mavenCentral()
    }
    dependencies {
        classpath 'com.android.tools.build:gradle:7.2.0'
        classpath "org.jetbrains.kotlin:kotlin-gradle-plugin:\$kotlin_version"
    }
}

task clean(type: Delete) {
    delete rootProject.buildDir
}
EOT;
    file_put_contents($projectDir . '/build.gradle', $projectGradle);

    // Create local.properties with SDK location
    $localProperties = "sdk.dir=" . str_replace('\\', '/', $androidPath) . "\n";
    file_put_contents($projectDir . '/local.properties', $localProperties);

    // Create build.gradle in app directory
    $appGradle = <<<EOT
plugins {
    id 'com.android.application'
    id 'kotlin-android'
}

android {
    namespace 'com.example.app'
    compileSdk 34

    defaultConfig {
        applicationId "com.example.app"
        minSdk 21
        targetSdk 34
        versionCode 1
        versionName "1.0"
    }

    signingConfigs {
        release {
            storeFile file(RELEASE_STORE_FILE)
            storePassword RELEASE_STORE_PASSWORD
            keyAlias RELEASE_KEY_ALIAS
            keyPassword RELEASE_KEY_PASSWORD
        }
    }

    buildTypes {
        release {
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
            signingConfig signingConfigs.release
            shrinkResources true
        }
    }
    
    compileOptions {
        sourceCompatibility JavaVersion.VERSION_1_8
        targetCompatibility JavaVersion.VERSION_1_8
    }
    
    kotlinOptions {
        jvmTarget = '1.8'
    }

    buildFeatures {
        viewBinding true
    }
}

dependencies {
    implementation "org.jetbrains.kotlin:kotlin-stdlib:\$kotlin_version"
    implementation 'androidx.core:core-ktx:1.9.0'
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'androidx.constraintlayout:constraintlayout:2.1.4'
    implementation 'com.google.android.material:material:1.9.0'
}
EOT;
    file_put_contents($projectDir . '/app/build.gradle', $appGradle);

    // Create proguard-rules.pro with basic rules
    $proguardRules = <<<EOT
# Default ProGuard rules for Android
-keepattributes *Annotation*
-keepattributes SourceFile,LineNumberTable
-keep public class * extends java.lang.Exception
-keep class com.example.app.** { *; }
EOT;
    file_put_contents($projectDir . '/app/proguard-rules.pro', $proguardRules);

    // Create MainActivity.kt
    $mainActivity = <<<EOT
package com.example.app

import android.app.Activity
import android.os.Bundle
import android.widget.TextView

class MainActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val textView = TextView(this)
        textView.text = "Hello from App Builder!"
        setContentView(textView)
    }
}
EOT;
    file_put_contents($projectDir . '/app/src/main/java/com/example/app/MainActivity.kt', $mainActivity);

    // Create AndroidManifest.xml
    $manifest = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.example.app">
    <application
        android:label="@string/app_name"
        android:icon="@mipmap/ic_launcher">
        <activity
            android:name=".MainActivity"
            android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
EOT;
    file_put_contents($projectDir . '/app/src/main/AndroidManifest.xml', $manifest);

    // Create strings.xml
    $strings = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">$appName</string>
</resources>
EOT;
    file_put_contents($projectDir . '/app/src/main/res/values/strings.xml', $strings);

    // Clean up and recreate mipmap directories
    $base_mipmap_dir = $projectDir . '/app/src/main/res';
    $old_mipmap = $base_mipmap_dir . '/mipmap';
    if (file_exists($old_mipmap)) {
        array_map('unlink', glob("$old_mipmap/*.*"));
        rmdir($old_mipmap);
    }

    // Process and copy the launcher icon
    $icon_sizes = [
        'mdpi' => 48,
        'hdpi' => 72,
        'xhdpi' => 96,
        'xxhdpi' => 144,
        'xxxhdpi' => 192
    ];

    // Create a default-density icon as well
    $default_size = 192; // Using xxxhdpi as default
    $default_dir = $base_mipmap_dir . '/mipmap';
    if (!file_exists($default_dir)) {
        mkdir($default_dir, 0777, true);
    }

    // Process default icon
    $source = imagecreatefromstring(file_get_contents($logoPath));
    if ($source === false) {
        throw new Exception("Failed to load the logo image");
    }

    // Create default icon
    $resized = imagecreatetruecolor($default_size, $default_size);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefilledrectangle($resized, 0, 0, $default_size, $default_size, $transparent);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $default_size, $default_size, imagesx($source), imagesy($source));
    imagepng($resized, $default_dir . '/ic_launcher.png', 9);
    imagedestroy($resized);

    // Process density-specific icons
    foreach ($icon_sizes as $density => $size) {
        $density_dir = $base_mipmap_dir . '/mipmap-' . $density;
        if (!file_exists($density_dir)) {
            mkdir($density_dir, 0777, true);
        }
        
        // Create a new image with the required dimensions
        $resized = imagecreatetruecolor($size, $size);
        
        // Preserve transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $size, $size, $transparent);
        
        // Resize the image
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $size, $size, imagesx($source), imagesy($source));
        
        // Save the resized image
        imagepng($resized, $density_dir . '/ic_launcher.png', 9);
        
        // Clean up
        imagedestroy($resized);
    }

    // Clean up source image
    imagedestroy($source);

    // Create gradle-wrapper.properties
    $wrapperProps = <<<EOT
distributionBase=GRADLE_USER_HOME
distributionPath=wrapper/dists
distributionUrl=https\://services.gradle.org/distributions/gradle-7.3.3-bin.zip
zipStoreBase=GRADLE_USER_HOME
zipStorePath=wrapper/dists
EOT;
    
    // Ensure the gradle/wrapper directory exists
    mkdir($projectDir . '/gradle/wrapper', 0777, true);
    file_put_contents($projectDir . '/gradle/wrapper/gradle-wrapper.properties', $wrapperProps);

    // Download the Gradle wrapper files directly
    $wrapperJarUrl = "https://raw.githubusercontent.com/gradle/gradle/v7.3.3/gradle/wrapper/gradle-wrapper.jar";
    $wrapperJar = file_get_contents($wrapperJarUrl);
    file_put_contents($projectDir . '/gradle/wrapper/gradle-wrapper.jar', $wrapperJar);

    // Create gradlew.bat
    $gradlewBat = <<<EOT
@rem
@rem Copyright 2015 the original author or authors.
@rem
@rem Licensed under the Apache License, Version 2.0 (the "License");
@rem you may not use this file except in compliance with the License.
@rem You may obtain a copy of the License at
@rem
@rem      https://www.apache.org/licenses/LICENSE-2.0
@rem
@rem Unless required by applicable law or agreed to in writing, software
@rem distributed under the License is distributed on an "AS IS" BASIS,
@rem WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
@rem See the License for the specific language governing permissions and
@rem limitations under the License.
@rem

@if "%DEBUG%" == "" @echo off
@rem ##########################################################################
@rem
@rem  Gradle startup script for Windows
@rem
@rem ##########################################################################

@rem Set local scope for the variables with windows NT shell
if "%OS%"=="Windows_NT" setlocal

set DIRNAME=%~dp0
if "%DIRNAME%" == "" set DIRNAME=.
set APP_BASE_NAME=%~n0
set APP_HOME=%DIRNAME%

@rem Resolve any "." and ".." in APP_HOME to make it shorter.
for %%i in ("%APP_HOME%") do set APP_HOME=%%~fi

@rem Add default JVM options here. You can also use JAVA_OPTS and GRADLE_OPTS to pass JVM options to this script.
set DEFAULT_JVM_OPTS="-Xmx64m" "-Xms64m"

@rem Find java.exe
if defined JAVA_HOME goto findJavaFromJavaHome

set JAVA_EXE=java.exe
%JAVA_EXE% -version >NUL 2>&1
if "%ERRORLEVEL%" == "0" goto execute

echo.
echo ERROR: JAVA_HOME is not set and no 'java' command could be found in your PATH.
echo.
echo Please set the JAVA_HOME variable in your environment to match the
echo location of your Java installation.

goto fail

:findJavaFromJavaHome
set JAVA_HOME=%JAVA_HOME:"=%
set JAVA_EXE=%JAVA_HOME%/bin/java.exe

if exist "%JAVA_EXE%" goto execute

echo.
echo ERROR: JAVA_HOME is set to an invalid directory: %JAVA_HOME%
echo.
echo Please set the JAVA_HOME variable in your environment to match the
echo location of your Java installation.

goto fail

:execute
@rem Setup the command line

set CLASSPATH=%APP_HOME%\\gradle\\wrapper\\gradle-wrapper.jar

@rem Execute Gradle
"%JAVA_EXE%" %DEFAULT_JVM_OPTS% %JAVA_OPTS% %GRADLE_OPTS% "-Dorg.gradle.appname=%APP_BASE_NAME%" -classpath "%CLASSPATH%" org.gradle.wrapper.GradleWrapperMain %*

:end
@rem End local scope for the variables with windows NT shell
if "%ERRORLEVEL%"=="0" goto mainEnd

:fail
rem Set variable GRADLE_EXIT_CONSOLE if you need the _script_ return code instead of
rem the _cmd.exe /c_ return code!
if  not "" == "%GRADLE_EXIT_CONSOLE%" exit 1
exit /b 1

:mainEnd
if "%OS%"=="Windows_NT" endlocal

:omega
EOT;
    file_put_contents($projectDir . '/gradlew.bat', $gradlewBat);

    // Build release APK
    $buildCmd = 'cmd /c "' . $projectDir . '\gradlew.bat" assembleRelease';
    chdir($projectDir);
    exec($buildCmd . ' 2>&1', $output, $returnVar);
    
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to build APK: ' . implode("\n", $output)
        ];
    }

    // Check if the APK was actually created
    $apkPath = $projectDir . '/app/build/outputs/apk/release/app-release.apk';
    if (!file_exists($apkPath)) {
        return [
            'success' => false,
            'message' => 'Release APK file was not created despite successful build. Expected path: ' . $apkPath
        ];
    }

    // After successful build, store app information in database
    require_once('config/database.php');
    
    // Copy the logo to the assets directory
    $logoFileName = 'app_logo.' . pathinfo($logoPath, PATHINFO_EXTENSION);
    $assetsDir = $projectDir . '/assets';
    $permanentLogoPath = $assetsDir . '/' . $logoFileName;
    
    // Ensure source file exists before copying
    if (!file_exists($logoPath)) {
        return [
            'success' => false,
            'message' => 'Source logo file not found: ' . $logoPath
        ];
    }
    
    // Create assets directory if it doesn't exist
    if (!file_exists($assetsDir)) {
        mkdir($assetsDir, 0777, true);
    }
    
    // Copy logo file
    if (!copy($logoPath, $permanentLogoPath)) {
        return [
            'success' => false,
            'message' => 'Failed to copy logo file to project directory'
        ];
    }
    
    // Store relative paths for better portability
    $relativeProjectPath = 'user_projects/' . $userId . '/' . basename($projectDir);
    $relativeLogoPath = $relativeProjectPath . '/assets/' . $logoFileName;
    $relativeApkPath = $relativeProjectPath . '/app/build/outputs/apk/release/app-release.apk';
    
    // Ensure the logo file exists in the specified path
    if (!file_exists($permanentLogoPath)) {
        return [
            'success' => false,
            'message' => 'Logo file was not properly saved: ' . $permanentLogoPath
        ];
    }
    
    $stmt = $conn->prepare("INSERT INTO user_apps (user_id, app_name, app_path, logo_path, apk_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt === false) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ];
    }
    
    $stmt->bind_param("issss", $userId, $appName, $projectDir, $relativeLogoPath, $relativeApkPath);
    if (!$stmt->execute()) {
        return [
            'success' => false,
            'message' => 'Failed to store app information in database: ' . $stmt->error
        ];
    }
    $stmt->close();
    
    return [
        'success' => true,
        'message' => 'APK built successfully!',
        'apk_path' => $relativeApkPath
    ];
}
?>
