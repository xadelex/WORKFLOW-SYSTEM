<?php
require_once 'config/config.php';
require_once 'inc/Database.php';
require_once 'inc/functions.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Sipariş ID'sini al
$orderId = $_GET['id'] ?? 0;

// QR kod oluşturma fonksiyonu
function generateOrderQR($orderNumber) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($orderNumber);
}

try {
    $db = Database::getInstance();
    
    // Siparişi getir
    $sql = "SELECT o.*, u.username as isleyen
            FROM orders o
            LEFT JOIN users u ON o.processed_by = u.id
            WHERE o.id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Sipariş bulunamadı');
    }

} catch (Exception $e) {
    error_log('Sipariş Yazdırma Hatası: ' . $e->getMessage());
    exit('Hata: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sipariş #<?= htmlspecialchars($order['order_number']) ?></title>
    <style>
        @page {
            size: 100mm 100mm;
            margin: 5mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            margin: 10px;
            padding: 0;
            width: 90mm;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #000;
        }
        .header h1 {
            font-size: 10pt;
            margin: 0 0 5px 0;
        }
        .header h2 {
            font-size: 10pt;
            margin: 0;
        }
        .qr-section {
            text-align: center;
            margin: 10px 0;
            padding: 0 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
        }
        .qr-code {
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto;
        }
        .info-section {
            margin: 10px 0;
            padding: 0 5px;
        }
        .info-row {
            margin: 5px 0;
            display: flex;
            align-items: flex-start;
        }
        .info-label {
            font-weight: bold;
            width: 70px;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
        }
        .product-details {
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .product-details .info-label {
            display: block;
            margin-bottom: 5px;
        }
        .product-details .info-value {
            margin-left: 5px;
            font-weight: normal;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SİPARİŞ DETAYI</h1>
        <h2>#<?= htmlspecialchars($order['order_number']) ?></h2>
    </div>

    <div class="qr-section">
        <img src="<?= generateOrderQR($order['order_number']) ?>" 
             alt="QR Kod" 
             class="qr-code">
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Müşteri:</span>
            <span class="info-value"><?= htmlspecialchars($order['customer_name']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Tel:</span>
            <span class="info-value"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Adres:</span>
            <span class="info-value"><?= htmlspecialchars($order['customer_address'] ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Marka:</span>
            <span class="info-value"><?= htmlspecialchars($order['brand'] ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Tarih:</span>
            <span class="info-value"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
        </div>
    </div>

    <div class="product-details">
        <div class="info-row">
            <span class="info-label">Ürün Detayları:</span>
            <span class="info-value">
                <?= nl2br(htmlspecialchars($order['product_details'] ?? '')) ?>
            </span>
        </div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" class="btn btn-primary">Yazdır</button>
        <button onclick="window.close()" class="btn btn-secondary">Kapat</button>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 