<?php
require_once 'inc/header.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Önce composer autoload'u dahil edelim
require_once 'vendor/autoload.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// QR kod oluşturma fonksiyonu
function generateOrderQR($orderNumber) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($orderNumber);
}

try {
    $db = Database::getInstance();  // Database nesnesini al

    // Filtreleme parametreleri
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $date_start = $_GET['date_start'] ?? '';
    $date_end = $_GET['date_end'] ?? '';

    // SQL sorgusu
    $sql = "SELECT o.*, 
            u.username as isleyen
            FROM orders o
            LEFT JOIN users u ON o.processed_by = u.id
            WHERE (o.status != 'completed' OR o.status IS NULL)";

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

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Siparişler Listesi Hatası: ' . $e->getMessage());
    $orders = [];
}
?>

<style>
/* Durum badge'ine padding ekle */
.badge {
    padding: 8px 12px;
    margin-left: 10px; /* Soldan boşluk */
}

/* Filtreleme butonu için düzenleme */
.btn-primary.btn-block {
    margin-right: 10px; /* Soldan boşluk */
    float: right; /* Sağa yasla */
}

/* Form label'ları için düzenleme */
.form-group label {
    margin-left: 10px; /* Soldan boşluk */
}

/* Durum select kutusu için düzenleme */
.order-status-select {
    margin-right: 10px; /* Sağdan boşluk */
    width: auto !important; /* Genişliği içeriğe göre ayarla */
    display: inline-block !important; /* Yanyana gösterim */
}

/* Mevcut stiller */
.card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.card-header {
    background: none;
    border-bottom: 1px solid #eee;
}
.icon-bg {
    position: absolute;
    right: 20px;
    bottom: 20px;
    opacity: 0.2;
    font-size: 3rem;
}
.metric-item {
    padding: 10px 0;
}
.progress {
    background-color: #eee;
}
.btn-group .btn {
    font-size: 0.8rem;
}
.btn-group .btn.active {
    background-color: #6c757d;
    color: white;
}

/* Sipariş detay modalı için stiller */
.modal-dialog {
    max-width: 800px; /* Modal genişliği */
}

.modal-content {
    border-radius: 10px;
}

.modal-header {
    background: #6f42c1;
    color: white;
    border-radius: 10px 10px 0 0;
}

.modal-header .close {
    color: white;
}

.modal-body {
    padding: 20px;
}

/* Sipariş detayları için grid yapısı */
.order-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.order-details .form-group {
    margin-bottom: 15px;
}

.order-details label {
    font-weight: 600;
    color: #666;
    margin-bottom: 5px;
    display: block;
}

.order-details .form-control {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}

/* Ürün detayları bölümü */
.product-details {
    grid-column: 1 / -1;
    margin-top: 20px;
}

.product-details textarea {
    min-height: 100px;
}

/* Sipariş geçmişi bölümü */
.order-history {
    grid-column: 1 / -1;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Modal footer */
.modal-footer {
    border-top: 1px solid #eee;
    padding: 15px;
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 10px;
    }
    
    .order-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid">
    <!-- Sipariş listesi -->
    <div class="card table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Siparişler</h6>
            <div class="d-flex gap-2">
                <div class="bulk-actions" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm" id="bulkDelete">
                        <i class="fas fa-trash"></i> Seçilenleri Sil
                    </button>
                    <button type="button" class="btn btn-info btn-sm" id="bulkPrint">
                        <i class="fas fa-print"></i> Seçilenleri Yazdır
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="bulkFlow">
                        <i class="fas fa-arrow-right"></i> Akışa Gönder
                    </button>
                </div>
                <button type="button" class="btn btn-sm btn-success" id="addOrderBtn">
                    <i class="fas fa-plus me-1"></i> Yeni Sipariş
                </button>
                <a href="<?= SITE_URL ?>/templates/siparis_sablonu.xlsx" class="btn btn-sm btn-info">
                    <i class="fas fa-download"></i> Excel Şablonu İndir
                </a>
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#importModal">
                    <i class="fas fa-file-excel"></i> Excel'den İçe Aktar
                </button>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Durum</label>
                        <select name="status" class="form-control">
                            <option value="">Tümü</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Beklemede</option>
                            <option value="cutting" <?= $status == 'cutting' ? 'selected' : '' ?>>Lazer Kesimde</option>
                            <option value="production" <?= $status == 'production' ? 'selected' : '' ?>>Üretimde</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Arama</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Sipariş No veya Müşteri">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="date_start" class="form-control" value="<?= $date_start ?>">
                    </div>
                </div>
                <div class="col-md-2">
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
                <table class="table table-hover" id="ordersTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>SİPARİŞ NO</th>
                            <th>QR KOD</th>
                            <th>MÜŞTERİ</th>
                            <th>ÜRÜN DETAYLARI</th>
                            <th>TARİH</th>
                            <th>İŞLEMLER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr data-order-id="<?= $order['id'] ?>">
                            <td>
                                <input type="checkbox" class="order-checkbox" value="<?= $order['id'] ?>">
                            </td>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td>
                                <img src="<?= generateOrderQR($order['order_number']) ?>" 
                                     alt="QR Code" 
                                     width="50" 
                                     height="50"
                                     class="qr-code">
                            </td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td>
                                <?php 
                                // Ürün detaylarını kısaltarak göster
                                $details = htmlspecialchars($order['product_details'] ?? '');
                                echo strlen($details) > 50 ? substr($details, 0, 50) . '...' : $details;
                                ?>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info view-order" 
                                            data-order='<?= json_encode([
                                                'order_number' => $order['order_number'],
                                                'customer_name' => $order['customer_name'],
                                                'customer_phone' => $order['customer_phone'] ?? '',
                                                'customer_address' => $order['customer_address'] ?? '',
                                                'product_details' => $order['product_details'] ?? '',
                                                'brand' => $order['brand'] ?? '',
                                                'created_at' => $order['created_at']
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning edit-order" 
                                            data-order-id="<?= $order['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success print-order">
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

<!-- Modal'ları include et -->
<?php 
require_once 'inc/modals/order_form.php';
require_once 'inc/modals/order_view.php';
require_once 'inc/modals/import_excel.php';
?>

<!-- Gerekli script'leri ekle -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="<?= SITE_URL ?>/js/orders.js"></script>

<script>
$(document).ready(function() {
    // Bootstrap form validasyonunu aktifleştir
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);

    // Yeni sipariş butonu tıklandığında
    $('#addOrderBtn').on('click', function() {
        $('#orderForm')[0].reset();
        $('#orderForm').removeClass('was-validated');
        $('#orderModal').modal('show');
    });

    // Form gönderildiğinde
    $('#orderForm').on('submit', function(e) {
        e.preventDefault();

        // Form validasyonu
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return false;
        }

        // Form verilerini al
        const formData = $(this).serialize();

        // Debug için form verilerini konsola yazdır
        console.log('Form verileri:', formData);

        // AJAX isteği gönder
        $.ajax({
            url: SITE_URL + '/orders.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                // Gönder butonunu devre dışı bırak
                $('#orderForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...');
            },
            success: function(response) {
                console.log('Başarılı yanıt:', response);
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: response.message || 'Sipariş başarıyla eklendi.',
                    showConfirmButton: false,
                    timer: 1500
                }).then(function() {
                    // Modal'ı kapat
                    $('#orderModal').modal('hide');
                    // Sayfayı yenile
                    location.reload();
                });
            },
            error: function(xhr, status, error) {
                // Hata detaylarını konsola yazdır
                console.error('AJAX Hatası:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });

                // Hata mesajını göster
                let errorMessage = 'Bir hata oluştu';

                if (xhr.responseText) {
                    try {
                        // Önce HTML etiketlerini temizle
                        const cleanResponse = xhr.responseText.replace(/<[^>]*>/g, '');
                        const response = JSON.parse(cleanResponse);
                        errorMessage = response.error || errorMessage;
                    } catch (e) {
                        console.error('JSON parse hatası:', e);
                        // HTML yanıtını göster
                        if (xhr.responseText.includes('error')) {
                            errorMessage = xhr.responseText.replace(/<[^>]*>/g, '').trim();
                        }
                    }
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: errorMessage,
                    showConfirmButton: true,
                    confirmButtonText: 'Tamam'
                }).then((result) => {
                    if (result.isConfirmed && xhr.status === 200) {
                        // Eğer kayıt başarılı olduysa sayfayı yenile
                        location.reload();
                    }
                });
            },
            complete: function() {
                // Gönder butonunu tekrar aktif et
                $('#orderForm button[type="submit"]').prop('disabled', false).html('Kaydet');
            }
        });
    });

    // Modal kapandığında formu sıfırla
    $('#orderModal').on('hidden.bs.modal', function() {
        $('#orderForm')[0].reset();
        $('#orderForm').removeClass('was-validated');
    });

    // Sipariş durumu değiştiğinde
    $('.order-status-select').on('change', function() {
        const $select = $(this);
        const orderId = $select.data('order-id');
        const newStatus = $select.val();
        const $row = $select.closest('tr');
        const $badge = $row.find('.badge');

        // Loading göster
        Swal.fire({
            title: 'Güncelleniyor...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // AJAX isteği
        $.ajax({
            url: SITE_URL + '/ajax/update_order_status.php',
            type: 'POST',
            data: {
                order_id: orderId,
                status: newStatus
            },
            success: function(response) {
                Swal.close();

                if (response.success) {
                    // Badge'i güncelle
                    $badge
                        .removeClass('badge-warning badge-info badge-primary badge-success')
                        .addClass('badge-' + getStatusBadgeClass(newStatus))
                        .text(getStatusText(newStatus));

                    // Başarılı mesajı göster
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Sipariş durumu güncellendi.',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Satırın arka plan rengini güncelle
                    updateRowStyle($row, newStatus);
                } else {
                    // Hata durumunda select'i eski değerine geri döndür
                    $select.val($badge.data('original-status'));
                    
                    // Hata mesajı göster
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error || 'Bir hata oluştu'
                    });
                }
            },
            error: function() {
                Swal.close();
                // Select'i eski değerine geri döndür
                $select.val($badge.data('original-status'));
                
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Sunucu ile iletişim kurulamadı'
                });
            }
        });
    });

    // Satır stilini güncelle
    function updateRowStyle(row, status) {
        row.removeClass('table-warning table-info table-primary table-success');
        
        switch(status) {
            case 'pending':
                row.addClass('table-warning');
                break;
            case 'cutting':
                row.addClass('table-info');
                break;
            case 'production':
                row.addClass('table-primary');
                break;
            case 'completed':
                row.addClass('table-success');
                break;
        }
    }

    // Toplu seçim işlemleri
    $('#selectAll').on('change', function() {
        $('.order-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActions();
    });

    $('.order-checkbox').on('change', function() {
        updateBulkActions();
    });

    function updateBulkActions() {
        const checkedCount = $('.order-checkbox:checked').length;
        if (checkedCount > 0) {
            $('.bulk-actions').show();
        } else {
            $('.bulk-actions').hide();
        }
    }

    // Toplu silme
    $('#bulkDelete').on('click', function() {
        const selectedIds = $('.order-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Emin misiniz?',
            text: `${selectedIds.length} siparişi silmek istediğinize emin misiniz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: SITE_URL + '/ajax/bulk_delete_orders.php',
                    type: 'POST',
                    data: { order_ids: selectedIds },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı!',
                                text: 'Seçilen siparişler silindi.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => location.reload());
                        }
                    }
                });
            }
        });
    });

    // Toplu yazdırma
    $('#bulkPrint').on('click', function() {
        const selectedIds = $('.order-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        // Yazdırma sayfasını yeni sekmede aç
        window.open(SITE_URL + '/print_orders.php?ids=' + selectedIds.join(','), '_blank');
    });

    // Akışa gönder
    $('#bulkFlow').on('click', function() {
        const selectedOrders = $('.order-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedOrders.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Uyarı',
                text: 'Lütfen en az bir sipariş seçin'
            });
            return;
        }
        
        // AJAX isteği
        $.ajax({
            url: SITE_URL + '/ajax/add_to_flow.php',
            type: 'POST',
            data: { order_ids: selectedOrders },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Bir hata oluştu'
                });
            }
        });
    });

    // DataTables ayarları
    $('#ordersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        },
        order: [[4, 'desc']] // Tarihe göre sırala
    });
});

// Durum badge renk sınıfını döndüren fonksiyon
function getStatusBadgeClass(status) {
    switch(status) {
        case 'pending':
            return 'warning';
        case 'cutting':
            return 'info';
        case 'production':
            return 'primary';
        case 'completed':
            return 'success';
        default:
            return 'secondary';
    }
}

// Durum metnini döndüren fonksiyon
function getStatusText(status) {
    switch(status) {
        case 'pending':
            return 'Beklemede';
        case 'cutting':
            return 'Lazer Kesimde';
        case 'production':
            return 'Üretimde';
        case 'completed':
            return 'Tamamlandı';
        default:
            return 'Bilinmiyor';
    }
}

// Tarih formatını düzenleyen fonksiyon
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
</script>

<?php require_once 'inc/footer.php'; ?>