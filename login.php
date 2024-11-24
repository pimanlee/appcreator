<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppBuilder - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-switch {
            text-align: center;
            margin: 15px 0;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #007bff;
            font-size: 2.5em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h1>AppBuilder</h1>
                <p class="text-muted">Build your dream app</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="needs-validation">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="needs-validation" style="display: none;">
                <div class="mb-3">
                    <label for="reg-username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="reg-username" required>
                </div>
                <div class="mb-3">
                    <label for="reg-email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="reg-email" required>
                </div>
                <div class="mb-3">
                    <label for="reg-password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="reg-password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>

            <div class="form-switch">
                <a href="#" id="switchForm">Don't have an account? Register here</a>
            </div>

            <div id="alertBox" class="alert" style="display: none;" role="alert"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const switchLink = document.getElementById('switchForm');
            const alertBox = document.getElementById('alertBox');

            let isLoginForm = true;

            switchLink.addEventListener('click', function(e) {
                e.preventDefault();
                isLoginForm = !isLoginForm;
                loginForm.style.display = isLoginForm ? 'block' : 'none';
                registerForm.style.display = isLoginForm ? 'none' : 'block';
                switchLink.textContent = isLoginForm ? 
                    "Don't have an account? Register here" : 
                    "Already have an account? Login here";
            });

            function showAlert(message, type) {
                alertBox.className = `alert alert-${type}`;
                alertBox.textContent = message;
                alertBox.style.display = 'block';
                setTimeout(() => alertBox.style.display = 'none', 5000);
            }

            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    const response = await fetch('api/auth.php?action=login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            username: document.getElementById('username').value,
                            password: document.getElementById('password').value
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        window.location.href = 'index.php';
                    } else {
                        showAlert(data.message, 'danger');
                    }
                } catch (error) {
                    showAlert('An error occurred. Please try again.', 'danger');
                }
            });

            registerForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    const response = await fetch('api/auth.php?action=register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            username: document.getElementById('reg-username').value,
                            email: document.getElementById('reg-email').value,
                            password: document.getElementById('reg-password').value
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showAlert('Registration successful! Please login.', 'success');
                        setTimeout(() => {
                            switchLink.click();
                        }, 2000);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                } catch (error) {
                    showAlert('An error occurred. Please try again.', 'danger');
                }
            });
        });
    </script>
</body>
</html>
