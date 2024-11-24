<?php
class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function register($username, $email, $password) {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Check if username or email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Create user folder
        $app_folder = 'users/' . $username . '_' . uniqid();
        if (!mkdir(__DIR__ . '/../' . $app_folder, 0777, true)) {
            return ['success' => false, 'message' => 'Could not create user folder'];
        }
        
        // Hash password and insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, app_folder) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $app_folder);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password, app_folder FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['app_folder'] = $user['app_folder'];
                return ['success' => true, 'message' => 'Login successful'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    public function getUserFolder() {
        if ($this->isLoggedIn()) {
            return $_SESSION['app_folder'];
        }
        return null;
    }
}
?>
