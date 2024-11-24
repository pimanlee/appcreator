<?php
session_start();

// Require login for build page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create New App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .info {
            color: blue;
            margin-bottom: 10px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-btn {
            background-color: #2196F3;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Create New App</h1>
        <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>

    <?php
    require_once('generate_apk.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $targetDir = __DIR__ . '/uploads/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $apkName = $_POST["apk_name"];
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($_FILES["apk_logo"]["name"], PATHINFO_EXTENSION));
        
        // Generate a unique filename
        $targetFile = $targetDir . uniqid('logo_') . '.' . $imageFileType;
        
        // Check if image file is actual image
        if(isset($_FILES["apk_logo"])) {
            $check = getimagesize($_FILES["apk_logo"]["tmp_name"]);
            if($check === false) {
                echo "<div class='error'>File is not an image.</div>";
                $uploadOk = 0;
            }
        }
        
        // Check file size (5MB max)
        if ($_FILES["apk_logo"]["size"] > 5000000) {
            echo "<div class='error'>Sorry, your file is too large (max 5MB).</div>";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            echo "<div class='error'>Sorry, only JPG, JPEG & PNG files are allowed.</div>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["apk_logo"]["tmp_name"], $targetFile)) {
                $result = generateApk($apkName, $targetFile, $_SESSION['user_id']);
                
                if (!$result['success']) {
                    echo "<div class='error'>" . $result['message'] . "</div>";
                    // Clean up the uploaded file if APK generation failed
                    if (file_exists($targetFile)) {
                        unlink($targetFile);
                    }
                } else {
                    echo "<div class='success'>" . $result['message'] . "</div>";
                    echo "<div class='info'>Your app has been created successfully! <a href='dashboard.php'>View your apps</a></div>";
                }
                
                // Clean up the temporary upload
                if (file_exists($targetFile)) {
                    unlink($targetFile);
                }
            } else {
                echo "<div class='error'>Sorry, there was an error uploading your file.</div>";
            }
        }
    }
    ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="apk_name">App Name:</label>
            <input type="text" id="apk_name" name="apk_name" required>
        </div>
        
        <div class="form-group">
            <label for="apk_logo">App Logo (JPG, JPEG, or PNG, max 5MB):</label>
            <input type="file" id="apk_logo" name="apk_logo" required>
        </div>
        
        <button type="submit">Build APK</button>
    </form>
</body>
</html>
