<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch($_GET['action']) {
        case 'register':
            if (isset($data['username']) && isset($data['email']) && isset($data['password'])) {
                $response = $auth->register($data['username'], $data['email'], $data['password']);
            }
            break;
            
        case 'login':
            if (isset($data['username']) && isset($data['password'])) {
                $response = $auth->login($data['username'], $data['password']);
            }
            break;
            
        case 'logout':
            $response = $auth->logout();
            break;
    }
}

echo json_encode($response);
?>
