<?php
session_start();

// If accessing the root URL and user is logged in, redirect to dashboard
if ($_SERVER['REQUEST_URI'] == '/appbuilder/' || $_SERVER['REQUEST_URI'] == '/appbuilder/index.php') {
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

// For the build page, require active session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple APK Builder</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Create New App</h1>
        <a href="dashboard.php" class="btn" style="background-color: #2196F3; color: white;">Back to Dashboard</a>
    </div>
    <?php
    require_once('generate_apk.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $apkName = $_POST["apk_name"];
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($_FILES["apk_logo"]["name"], PATHINFO_EXTENSION));
        
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
            $targetFile = $targetDir . basename($_FILES["apk_logo"]["name"]);
            if (move_uploaded_file($_FILES["apk_logo"]["tmp_name"], $targetFile)) {
                $result = generateApk($apkName, $targetFile, $_SESSION['user_id']);
                
                if (!$result['success']) {
                    echo "<div class='error'>" . $result['message'] . "</div>";
                } else {
                    echo "<div class='success'>" . $result['message'] . "</div>";
                    echo "<div class='info'>Your app has been created successfully! <a href='dashboard.php'>View your apps</a></div>";
                }
                
                // Clean up the temporary upload
                unlink($targetFile);
            } else {
                echo "<div class='error'>Sorry, there was an error uploading your file.</div>";
            }
        }
    }
    ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="apk_name">APK Name:</label>
            <input type="text" id="apk_name" name="apk_name" required pattern="[a-zA-Z0-9\s]+" title="Only letters, numbers and spaces allowed">
        </div>
        <div class="form-group">
            <label for="apk_logo">APK Logo (JPG, PNG):</label>
            <input type="file" id="apk_logo" name="apk_logo" accept="image/jpeg,image/png" required>
        </div>
        <button type="submit">Generate APK</button>
    </form>
</body>
</html>
