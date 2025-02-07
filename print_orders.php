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

// QR kod oluşturma fonksiyonu
function generateOrderQR($orderNumber) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($orderNumber);
}

// Sipariş ID'lerini al
$orderIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($orderIds)) {
    die('Sipariş seçilmedi');
}

try {
    $db = Database::getInstance();
    
    // Siparişleri getir
    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
    $sql = "SELECT o.*, u.username as isleyen
            FROM orders o
            LEFT JOIN users u ON o.processed_by = u.id
            WHERE o.id IN ($placeholders)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($orderIds);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Hata: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Siparişler</title>
    <style>
        @page {
            size: 100mm 100mm;
            margin: 5mm;
            page-break-after: always;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            background: white;
        }

        .order-container {
            width: 90mm;
            height: 90mm;
            padding: 2mm;
            box-sizing: border-box;
            page-break-after: always;
            page-break-inside: avoid;
        }

        /* Her sipariş için yeni sayfa */
        .order-container:not(:last-child) {
            page-break-after: always;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }

        .header h1 {
            font-size: 10pt;
            margin: 0 0 1mm 0;
            padding: 0;
        }

        .header p {
            font-size: 8pt;
            margin: 0;
        }

        .qr-code {
            text-align: center;
            margin: 2mm 0;
            padding-bottom: 2mm;
            border-bottom: 1px solid #000;
        }

        .qr-code img {
            width: 15mm;
            height: 15mm;
            margin-bottom: 2mm;
        }

        .details {
            margin-bottom: 2mm;
            font-size: 7pt;
            margin-top: 3mm;
        }

        .details-row {
            display: flex;
            margin-bottom: 1mm;
        }

        .details-label {
            font-weight: bold;
            width: 20mm;
        }

        .details-value {
            flex: 1;
        }

        .product-details {
            font-size: 7pt;
            border-top: 1px solid #000;
            padding-top: 2mm;
            max-height: 25mm;
            overflow: hidden;
        }

        @media print {
            html, body {
                width: 100mm;
                height: 100mm;
                margin: 0;
                padding: 0;
            }
            
            .order-container {
                margin: 0;
                page-break-after: always;
                page-break-inside: avoid;
            }
            
            .no-print {
                display: none !important;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        .badge {
            display: inline-block;
            padding: 0.5mm 1mm;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 1mm;
        }
        .badge-warning { background: #ffeeba; }
        .badge-info { background: #bee5eb; }
        .badge-primary { background: #b8daff; }
        .badge-success { background: #c3e6cb; }
    </style>
</head>
<body>
    <?php foreach ($orders as $order): ?>
    <div class="order-container">
        <div class="header">
            <h1>SİPARİŞ DETAYI</h1>
            <p><strong>#<?= htmlspecialchars($order['order_number']) ?></strong></p>
        </div>

        <div class="qr-code">
            <img src="<?= generateOrderQR($order['order_number']) ?>" 
                 alt="QR Code">
        </div>

        <div class="details">
            <div class="details-row">
                <div class="details-label">Müşteri:</div>
                <div class="details-value"><?= htmlspecialchars($order['customer_name']) ?></div>
            </div>
            <div class="details-row">
                <div class="details-label">Tel:</div>
                <div class="details-value"><?= htmlspecialchars($order['customer_phone']) ?></div>
            </div>
            <div class="details-row">
                <div class="details-label">Adres:</div>
                <div class="details-value"><?= htmlspecialchars($order['customer_address']) ?></div>
            </div>
            <?php if (!empty($order['brand'])): ?>
            <div class="details-row">
                <div class="details-label">Marka:</div>
                <div class="details-value"><?= htmlspecialchars($order['brand']) ?></div>
            </div>
            <?php endif; ?>
            <div class="details-row">
                <div class="details-label">Tarih:</div>
                <div class="details-value"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
        </div>

        <div class="product-details">
            <strong>Ürün Detayları:</strong><br>
            <?= nl2br(htmlspecialchars($order['product_details'])) ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="no-print" style="position: fixed; bottom: 10px; left: 0; right: 0; text-align: center;">
        <button onclick="window.print()" style="padding: 5px 10px;">Yazdır</button>
        <button onclick="window.close()" style="padding: 5px 10px;">Kapat</button>
    </div>

    <script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
    </script>
</body>
</html> 