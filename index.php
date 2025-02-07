<?php
require_once 'inc/header.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Ana sayfaya yönlendir
header('Location: ' . SITE_URL . '/dashboard.php');
exit; 