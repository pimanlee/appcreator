CREATE TABLE IF NOT EXISTS user_apps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    app_name VARCHAR(255) NOT NULL,
    app_path VARCHAR(255) NOT NULL,
    logo_path VARCHAR(255) NOT NULL,
    apk_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;