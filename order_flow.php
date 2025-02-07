<?php
require_once 'inc/header.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Rolleri session'dan al
$userRoles = $_SESSION['user_roles'] ?? [];
$isAdmin = in_array('admin', $userRoles);

// QR kod oluşturma fonksiyonu
function generateOrderQR($orderNumber) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($orderNumber);
}

try {
    $db = Database::getInstance();
    
    $sql = "SELECT o.*, 
            o.laser_status,
            o.packaging_status,
            o.shipping_status,
            u.username as isleyen
            FROM orders o
            LEFT JOIN users u ON o.processed_by = u.id
            WHERE o.in_flow = 1 
            AND (o.status != 'completed' OR o.status IS NULL)";
    
    $params = [];

    // Filtreleri uygula
    if (!empty($_GET['order_number'])) {
        $sql .= " AND o.order_number LIKE :order_number";
        $params[':order_number'] = '%' . $_GET['order_number'] . '%';
    }

    if (!empty($_GET['customer'])) {
        $sql .= " AND o.customer_name LIKE :customer";
        $params[':customer'] = '%' . $_GET['customer'] . '%';
    }

    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'laser':
                $sql .= " AND o.laser_status = 1";
                break;
            case 'packaging':
                $sql .= " AND o.packaging_status = 1";
                break;
            case 'shipping':
                $sql .= " AND o.shipping_status = 1";
                break;
            case 'pending':
                $sql .= " AND o.laser_status = 0";
                break;
        }
    }

    if (!empty($_GET['date_start'])) {
        $sql .= " AND DATE(o.created_at) >= :date_start";
        $params[':date_start'] = $_GET['date_start'];
    }

    if (!empty($_GET['date_end'])) {
        $sql .= " AND DATE(o.created_at) <= :date_end";
        $params[':date_end'] = $_GET['date_end'];
    }

    $sql .= " ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Sipariş Akışı Hatası: ' . $e->getMessage());
    $orders = [];
}

// Tamamlanan siparişleri kontrol et ve taşı
function checkAndMoveCompletedOrders($db) {
    try {
        // Tamamlanan siparişleri bul
        $sql = "UPDATE orders 
                SET in_flow = 0, 
                    completed_at = NOW(),
                    status = 'completed'
                WHERE laser_status = 1 
                AND packaging_status = 1 
                AND shipping_status = 1 
                AND in_flow = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount(); // Taşınan sipariş sayısını döndür
    } catch (Exception $e) {
        error_log("Sipariş taşıma hatası: " . $e->getMessage());
        return 0;
    }
}

// Her sayfa yüklendiğinde kontrol et
$movedOrders = checkAndMoveCompletedOrders($db);
if ($movedOrders > 0) {
    // Opsiyonel: Kullanıcıya bilgi ver
    echo "<script>toastr.success('$movedOrders adet sipariş tamamlandı.');</script>";
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0">Sipariş Akışı</h6>
                <small class="text-muted">Toplam: <span class="badge badge-info"><?= count($orders) ?></span> sipariş</small>
            </div>
            <div>
                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-success" id="selectedComplete">
                    <i class="fas fa-check-double"></i> Seçilenleri Tamamla
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="card-body border-bottom">
            <form method="get" class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Sipariş No</label>
                        <input type="text" name="order_number" class="form-control" value="<?= htmlspecialchars($_GET['order_number'] ?? '') ?>" placeholder="Sipariş No">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Müşteri</label>
                        <input type="text" name="customer" class="form-control" value="<?= htmlspecialchars($_GET['customer'] ?? '') ?>" placeholder="Müşteri Adı">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Durum</label>
                        <select name="status" class="form-control">
                            <option value="">Tümü</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Beklemede</option>
                            <option value="laser" <?= ($_GET['status'] ?? '') == 'laser' ? 'selected' : '' ?>>Lazer Tamamlandı</option>
                            <option value="packaging" <?= ($_GET['status'] ?? '') == 'packaging' ? 'selected' : '' ?>>Paketleme Tamamlandı</option>
                            <option value="shipping" <?= ($_GET['status'] ?? '') == 'shipping' ? 'selected' : '' ?>>Sevkiyat Tamamlandı</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="date_start" class="form-control" value="<?= $_GET['date_start'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="date_end" class="form-control" value="<?= $_GET['date_end'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrele
                            </button>
                            <a href="<?= SITE_URL ?>/order_flow.php" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Sıfırla
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <!-- İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Bekleyen Siparişler</h6>
                            <h3 class="card-text text-primary"><?= count(array_filter($orders, fn($o) => !$o['laser_status'])) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Lazer Tamamlanan</h6>
                            <h3 class="card-text text-info"><?= count(array_filter($orders, fn($o) => $o['laser_status'])) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Paketleme Tamamlanan</h6>
                            <h3 class="card-text text-warning"><?= count(array_filter($orders, fn($o) => $o['packaging_status'])) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Sevkiyat Tamamlanan</h6>
                            <h3 class="card-text text-success"><?= count(array_filter($orders, fn($o) => $o['shipping_status'])) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="flowTable">
                    <thead>
                        <tr>
                            <?php if ($isAdmin): ?>
                            <th>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAll" title="Tümünü Seç">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                            </th>
                            <?php endif; ?>
                            <th>Sipariş No</th>
                            <th>QR Kod</th>
                            <th>Müşteri</th>
                            <th>Ürün Detayı</th>
                            <?php if ($isAdmin): ?>
                                <th>Lazer İşlemi</th>
                                <th>Paketleme</th>
                                <th>Sevkiyat</th>
                            <?php else: ?>
                                <?php if (in_array('lazerci', $userRoles)): ?>
                                    <th>Lazer İşlemi</th>
                                <?php endif; ?>
                                <?php if (in_array('paketlemeci', $userRoles)): ?>
                                    <th>Paketleme</th>
                                <?php endif; ?>
                                <?php if (in_array('sevkiyatci', $userRoles)): ?>
                                    <th>Sevkiyat</th>
                                <?php endif; ?>
                            <?php endif; ?>
                            <th>İşleyen</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <?php if ($isAdmin): ?>
                            <td>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input select-order" 
                                           data-order-id="<?= $order['id'] ?>" 
                                           id="order_<?= $order['id'] ?>">
                                    <label class="form-check-label" for="order_<?= $order['id'] ?>"></label>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td>
                                <img src="<?= generateOrderQR($order['order_number']) ?>" 
                                     alt="QR Code" 
                                     width="50" 
                                     height="50"
                                     class="qr-code"
                                     data-order-number="<?= htmlspecialchars($order['order_number']) ?>">
                            </td>
                            <td><?= htmlspecialchars($order['customer_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($order['product_details'] ?? '') ?></td>
                            
                            <?php if ($isAdmin): ?>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm w-100 status-btn <?= getStatusClass($order['laser_status']) ?>"
                                        data-order-id="<?= $order['id'] ?>"
                                        data-status-type="laser"
                                        data-current-status="<?= $order['laser_status'] ?>">
                                    <?= $order['laser_status'] ? 'Tamamlandı' : 'Beklemede' ?>
                                </button>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm w-100 status-btn <?= getStatusClass($order['packaging_status']) ?>"
                                        data-order-id="<?= $order['id'] ?>"
                                        data-status-type="packaging"
                                        data-current-status="<?= $order['packaging_status'] ?>">
                                    <?= $order['packaging_status'] ? 'Tamamlandı' : 'Beklemede' ?>
                                </button>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm w-100 status-btn <?= getStatusClass($order['shipping_status']) ?>"
                                        data-order-id="<?= $order['id'] ?>"
                                        data-status-type="shipping"
                                        data-current-status="<?= $order['shipping_status'] ?>">
                                    <?= $order['shipping_status'] ? 'Tamamlandı' : 'Beklemede' ?>
                                </button>
                            </td>
                            <?php else: ?>
                                <?php if (in_array('lazerci', $userRoles)): ?>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm w-100 status-btn <?= getStatusClass($order['laser_status']) ?>"
                                            data-order-id="<?= $order['id'] ?>"
                                            data-status-type="laser"
                                            data-current-status="<?= $order['laser_status'] ?>">
                                        <?= $order['laser_status'] ? 'Tamamlandı' : 'Beklemede' ?>
                                    </button>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('paketlemeci', $userRoles)): ?>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm w-100 status-btn <?= getStatusClass($order['packaging_status']) ?>"
                                            data-order-id="<?= $order['id'] ?>"
                                            data-status-type="packaging"
                                            data-current-status="<?= $order['packaging_status'] ?>">
                                        <?= $order['packaging_status'] ? 'Tamamlandı' : 'Beklemede' ?>
                                    </button>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('sevkiyatci', $userRoles)): ?>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm w-100 status-btn <?= getStatusClass($order['shipping_status']) ?>"
                                            data-order-id="<?= $order['id'] ?>"
                                            data-status-type="shipping"
                                            data-current-status="<?= $order['shipping_status'] ?>">
                                        <?= $order['shipping_status'] ? 'Tamamlandı' : 'Beklemede' ?>
                                    </button>
                                </td>
                                <?php endif; ?>
                            <?php endif; ?>

                            <td><?= htmlspecialchars($order['isleyen'] ?? '') ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- QR Kod Okuyucu Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Kod Okut</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="qr-reader"></div>
            </div>
        </div>
    </div>
</div>

<!-- QR Kod okuma için gerekli JS -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
$(document).ready(function() {
    // DataTables'ı kaldıralım ve normal tablo kullanalım
    // const table = $('#flowTable').DataTable({...}); 

    // Tümünü seç checkbox'ı
    $('#selectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.select-order').prop('checked', isChecked);
        updateSelectedCount();
    });

    // Tekil checkbox'lar
    $('.select-order').on('change', function() {
        updateSelectAllCheckbox();
        updateSelectedCount();
    });

    // Tümü seçili mi kontrolü
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.select-order').length;
        const checkedCheckboxes = $('.select-order:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    }

    // Seçili sipariş sayısını güncelle
    function updateSelectedCount() {
        const selectedCount = $('.select-order:checked').length;
        $('#selectedComplete').html(`<i class="fas fa-check-double"></i> Seçilenleri Tamamla (${selectedCount})`);
    }

    // Seçili siparişleri tamamla
    $(document).on('click', '#selectedComplete', function() {
        const selectedIds = [];
        $('.select-order:checked').each(function() {
            selectedIds.push($(this).data('order-id'));
        });

        if (selectedIds.length === 0) {
            toastr.warning('Lütfen en az bir sipariş seçin');
            return;
        }

        Swal.fire({
            title: 'Emin misiniz?',
            text: `${selectedIds.length} adet siparişi tamamlanmış olarak işaretlemek istiyor musunuz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Tamamla',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: SITE_URL + '/ajax/complete_orders.php',
                    type: 'POST',
                    data: { order_ids: selectedIds },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(`${selectedIds.length} sipariş tamamlandı`);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.error || 'Bir hata oluştu');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX hatası:', error);
                        toastr.error('İşlem sırasında bir hata oluştu');
                    }
                });
            }
        });
    });

    // QR Kod Tarayıcı butonu ekle
    <?php if (!$isAdmin): ?>
        <?php if (in_array('lazerci', $userRoles)): ?>
            addScanButton('laser');
        <?php endif; ?>
        <?php if (in_array('paketlemeci', $userRoles)): ?>
            addScanButton('packaging');
        <?php endif; ?>
        <?php if (in_array('sevkiyatci', $userRoles)): ?>
            addScanButton('shipping');
        <?php endif; ?>
    <?php endif; ?>

    function addScanButton(type) {
        const button = $('<button>')
            .addClass('btn btn-primary ml-2')
            .text('QR Kod Okut')
            .attr('data-type', type)
            .on('click', initQRScanner);
        $('.card-header').append(button);
    }

    function initQRScanner() {
        const type = $(this).data('type');
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { fps: 10, qrbox: 250 }
        );

        $('#qrScannerModal').modal('show');

        html5QrcodeScanner.render((decodedText) => {
            // QR kod okunduğunda
            updateOrderStatus(decodedText, type);
            $('#qrScannerModal').modal('hide');
            html5QrcodeScanner.clear();
        });
    }

    function updateOrderStatus(orderNumber, type) {
        $.ajax({
            url: SITE_URL + '/ajax/update_flow_status.php',
            type: 'POST',
            data: {
                order_number: orderNumber,
                status_type: type,
                scanned: true
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Durum güncellendi');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.error || 'Bir hata oluştu');
                }
            }
        });
    }
});
</script>

<?php require_once 'inc/footer.php'; ?> 