<?php
session_start();

// Require login for dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once('config/database.php');

// Function to recursively delete a directory
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle app deletion
if (isset($_POST['delete_app'])) {
    $appId = $_POST['app_id'];
    $userId = $_SESSION['user_id'];

    // Get app path before deletion
    $stmt = $conn->prepare("SELECT app_path FROM user_apps WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $appId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($app = $result->fetch_assoc()) {
        $appPath = $app['app_path'];
        
        // Delete the app folder if it exists
        if (is_dir($appPath)) {
            deleteDirectory($appPath);
        }
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM user_apps WHERE id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $appId, $userId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
    $stmt->close();
    
    // Redirect to refresh the page
    header('Location: dashboard.php');
    exit;
}

// Get user's apps
$user_id = $_SESSION['user_id'];
$apps = [];
$error_message = null;

$stmt = $conn->prepare("SELECT * FROM user_apps WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt === false) {
    $error_message = "Database error: " . $conn->error;
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $apps = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>App Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .app-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .app-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .app-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-download {
            background-color: #4CAF50;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .create-new {
            background-color: #2196F3;
            color: white;
            padding: 10px 20px;
        }
        .user-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Apps</h1>
        <div class="user-nav">
            <a href="build.php" class="btn create-new">Create New App</a>
            <a href="?logout" class="logout-btn">Logout</a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php elseif (isset($_SESSION['success'])): ?>
        <div class="success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="app-grid">
        <?php if ($apps && $apps->num_rows > 0): ?>
            <?php while ($app = $apps->fetch_assoc()): ?>
                <div class="app-card">
                    <?php
                    $logoPath = $app['logo_path'];
                    // If path is relative, make it absolute
                    if (!preg_match('/^https?:\/\//', $logoPath) && !preg_match('/^[A-Za-z]:\\\/', $logoPath)) {
                        $logoPath = './' . $logoPath;
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="App Logo" class="app-logo" onerror="this.src='assets/default-app-icon.png'">
                    <h3><?php echo htmlspecialchars($app['app_name']); ?></h3>
                    <p>Created: <?php echo date('M j, Y', strtotime($app['created_at'])); ?></p>
                    <div class="app-actions">
                        <a href="<?php echo htmlspecialchars('./' . $app['apk_path']); ?>" class="btn btn-download" download>Download APK</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" name="delete_app" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this app?')">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No apps created yet. <a href="build.php">Create your first app</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
