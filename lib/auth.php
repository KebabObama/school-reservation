<?php
require_once __DIR__ . '/db.php';

function login(string $email, string $password): void {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
    } else {
        // For simplicity, no error shown here. You can enhance this.
    }
}

function register(string $email, string $name, string $surname, string $password): void {
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, name, surname, password_hash, is_verified) VALUES (?, ?, ?, ?, 0)');
    $stmt->execute([$email, $name, $surname, $hash]);
}

function logout(): void {
    session_destroy();
}