<?php
declare(strict_types=1);

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        // Auto-login as admin user
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        
        if ($admin) {
            login_user($admin);
        } else {
            // Create a default admin if none exists
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', :password, 'admin')");
            $stmt->execute([':password' => password_hash('admin123', PASSWORD_DEFAULT)]);
            
            $admin = [
                'id' => $pdo->lastInsertId(),
                'username' => 'admin',
                'role' => 'admin'
            ];
            login_user($admin);
        }
    }
}

function login_user(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'] ?? 'editor';
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
