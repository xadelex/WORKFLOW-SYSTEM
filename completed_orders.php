<?php
require_once 'inc/header.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// QR kod oluşturma fonksiyonu
function generateOrderQR($orderNumber) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($orderNumber);
}

try {
    $db = Database::getInstance();
    
    // Filtreleme parametreleri
    $search = $_GET['search'] ?? '';
    $date_start = $_GET['date_start'] ?? '';
    $date_end = $_GET['date_end'] ?? '';
    
    // Önce veritabanındaki durumu kontrol edelim
    $checkSql = "SELECT COUNT(*) as total FROM orders WHERE status = 'completed'";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute();
    $totalCompleted = $checkStmt->fetch(PDO::FETCH_ASSOC)['total'];
    error_log("Toplam tamamlanan sipariş sayısı: " . $totalCompleted);

    // Debug için tüm siparişleri kontrol et
    $debugSql = "SELECT id, order_number, status, in_flow, completed_at FROM orders";
    $debugStmt = $db->prepare($debugSql);
    $debugStmt->execute();
    $allOrders = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Tüm siparişler: " . print_r($allOrders, true));

    // Tamamlanan siparişleri kontrol et
    $completedSql = "SELECT id, order_number, status, in_flow, completed_at 
                     FROM orders 
                     WHERE status = 'completed'";
    $completedStmt = $db->prepare($completedSql);
    $completedStmt->execute();
    $completedOrders = $completedStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Tamamlanan siparişler: " . print_r($completedOrders, true));

    // Ana sorgu
    $sql = "SELECT o.*, 
            u.username as isleyen,
            o.order_number,
            o.customer_name,
            o.completed_at,
            o.product_details
            FROM orders o
            LEFT JOIN users u ON o.processed_by = u.id
            WHERE o.status = 'completed'";
    
    // Filtreleri ekle
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (o.order_number LIKE :search OR o.customer_name LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($date_start)) {
        $sql .= " AND DATE(o.completed_at) >= :date_start";
        $params[':date_start'] = $date_start;
    }

    if (!empty($date_end)) {
        $sql .= " AND DATE(o.completed_at) <= :date_end";
        $params[':date_end'] = $date_end;
    }

    $sql .= " ORDER BY o.completed_at DESC";

    error_log("SQL Sorgusu: " . $sql);
    error_log("Parametreler: " . print_r($params, true));
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Bulunan Sipariş Sayısı: " . count($orders));
    if (count($orders) > 0) {
        error_log("İlk Sipariş: " . print_r($orders[0], true));
    }

} catch (Exception $e) {
    error_log('Tamamlanan Siparişler Listesi Hatası: ' . $e->getMessage());
    $orders = [];
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Tamamlanan Siparişler</h6>
        </div>

        <!-- Filtreler -->
        <div class="card-body border-bottom">
            <form method="get" class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Arama</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Sipariş No veya Müşteri">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="date_start" class="form-control" value="<?= $date_start ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="date_end" class="form-control" value="<?= $date_end ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tablo -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="completedOrdersTable">
                    <thead>
                        <tr>
                            <th>SİPARİŞ NO</th>
                            <th>QR KOD</th>
                            <th>MÜŞTERİ</th>
                            <th>İŞLEYEN</th>
                            <th>TAMAMLANMA TARİHİ</th>
                            <th>İŞLEMLER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr data-order-id="<?= $order['id'] ?>">
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td>
                                <img src="<?= generateOrderQR($order['order_number']) ?>" 
                                     alt="QR Code" 
                                     width="50" 
                                     height="50"
                                     class="qr-code">
                            </td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= htmlspecialchars($order['isleyen']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($order['completed_at'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" 
                                            class="btn btn-sm btn-info view-order" 
                                            data-order='<?= json_encode([
                                                'order_number' => $order['order_number'],
                                                'customer_name' => $order['customer_name'],
                                                'customer_phone' => $order['customer_phone'] ?? '',
                                                'customer_address' => $order['customer_address'] ?? '',
                                                'product_details' => $order['product_details'] ?? '',
                                                'completed_at' => $order['completed_at'],
                                                'isleyen' => $order['isleyen']
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>'
                                            title="Önizle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-success print-order"
                                            title="Yazdır">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Event listeners'ları önce tanımla
    initializeEventListeners();
    
    // Sonra DataTables'ı başlat
    initializeDataTable();
    
    function initializeEventListeners() {
        // Sipariş görüntüleme
        $(document).on('click', '.view-order', function() {
            try {
                const orderData = $(this).data('order');
                console.log('Ham sipariş verisi:', $(this).attr('data-order')); // Debug için
                console.log('Parse edilmiş sipariş verisi:', orderData); // Debug için
                
                Swal.fire({
                    title: 'Sipariş Detayları',
                    html: `
                        <div class="text-left">
                            <p><strong>Sipariş No:</strong> ${orderData.order_number || 'Belirtilmemiş'}</p>
                            <p><strong>Müşteri:</strong> ${orderData.customer_name || 'Belirtilmemiş'}</p>
                            <p><strong>Telefon:</strong> ${orderData.customer_phone || 'Belirtilmemiş'}</p>
                            <p><strong>Adres:</strong> ${orderData.customer_address || 'Belirtilmemiş'}</p>
                            <p><strong>Ürün Detayları:</strong> ${orderData.product_details || 'Belirtilmemiş'}</p>
                            <p><strong>Tamamlanma Tarihi:</strong> ${formatDate(orderData.completed_at) || 'Belirtilmemiş'}</p>
                            <p><strong>İşleyen:</strong> ${orderData.isleyen || 'Belirtilmemiş'}</p>
                        </div>
                    `,
                    width: '600px',
                    confirmButtonText: 'Kapat'
                });
            } catch (error) {
                console.error('Sipariş verisi parse edilirken hata:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Sipariş detayları görüntülenirken bir hata oluştu.'
                });
            }
        });

        // Yazdırma işlemi
        $(document).on('click', '.print-order', function() {
            const orderId = $(this).closest('tr').data('order-id');
            console.log('Yazdırılacak sipariş ID:', orderId); // Debug için
            
            if (orderId) {
                window.open(SITE_URL + '/print_order.php?id=' + orderId, '_blank');
            } else {
                console.error('Sipariş ID bulunamadı');
            }
        });
    }
    
    function initializeDataTable() {
        $('#completedOrdersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
            },
            order: [[4, 'desc']], // Tamamlanma tarihine göre sırala
            responsive: true
        });
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});
</script>

<?php require_once 'inc/footer.php'; ?> 