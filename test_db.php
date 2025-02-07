<?php
require_once 'config/config.php';
require_once 'inc/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Veritabanı bağlantısı başarılı!<br>";
    
    $stmt = $db->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    echo "Kullanıcı listesi:<br>";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}, Active: {$user['is_active']}<br>";
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
} 