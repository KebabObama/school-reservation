<?php
require_once __DIR__ . '/db.php';

function get_all_users(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT id, email, name, surname FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}