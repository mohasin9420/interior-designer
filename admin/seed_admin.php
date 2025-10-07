<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';

$pdo = getPDO();
$username = 'admin';
$email = 'admin@example.com';
$passwordHash = password_hash('admin123', PASSWORD_DEFAULT);

$pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') DEFAULT 'admin',
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = :u OR email = :e');
$stmt->execute([':u' => $username, ':e' => $email]);
$exists = (int)$stmt->fetchColumn() > 0;

if (!$exists) {
    $ins = $pdo->prepare('INSERT INTO admin_users (username, email, password, role) VALUES (:u, :e, :p, :r)');
    $ins->execute([':u' => $username, ':e' => $email, ':p' => $passwordHash, ':r' => 'admin']);
    echo "Seeded admin user: admin / admin123\n";
} else {
    echo "Admin user already exists.\n";
}
