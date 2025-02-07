<?php
require_once 'inc/header.php';

if (!$auth->isLoggedIn() || !$auth->hasRole(['admin', 'warehouse'])) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();

// Stok kartlarını getir
$sql = "SELECT sc.*, 
               COALESCE(SUM(CASE WHEN sm.movement_type = 'in' THEN sm.quantity ELSE -sm.quantity END), 0) as current_stock
        FROM stock_cards sc
        LEFT JOIN stock_movements sm ON sc.id = sm.stock_card_id
        GROUP BY sc.id
        ORDER BY sc.product_name";

$stock_cards = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Stok İşlemleri</h1>
        <div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#stockCardModal">
                <i class="fas fa-plus"></i> Yeni Stok Kartı
            </button>
            <button class="btn btn-success" data-toggle="modal" data-target="#stockMovementModal">
                <i class="fas fa-exchange-alt"></i> Stok Hareketi
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="stockTable">
                    <thead>
                        <tr>
                            <th>Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th>Marka</th>
                            <th>Mevcut Stok</th>
                            <th>Son Güncelleme</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_cards as $card): ?>
                        <tr>
                            <td><?= htmlspecialchars($card['stock_code']) ?></td>
                            <td><?= htmlspecialchars($card['product_name']) ?></td>
                            <td><?= htmlspecialchars($card['brand']) ?></td>
                            <td>
                                <span class="badge badge-<?= $card['current_stock'] <= 0 ? 'danger' : 'success' ?>">
                                    <?= number_format($card['current_stock']) ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($card['updated_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info edit-stock" data-id="<?= $card['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-stock" data-id="<?= $card['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stok Kartı Modal -->
<div class="modal fade" id="stockCardModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stok Kartı</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="stockCardForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="stock_id">
                    
                    <div class="form-group">
                        <label>Stok Kodu</label>
                        <input type="text" class="form-control" name="stock_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ürün Adı</label>
                        <input type="text" class="form-control" name="product_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Marka</label>
                        <input type="text" class="form-control" name="brand">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stok Hareketi Modal -->
<div class="modal fade" id="stockMovementModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stok Hareketi</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="stockMovementForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Stok Kartı</label>
                        <select class="form-control" name="stock_card_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($stock_cards as $card): ?>
                            <option value="<?= $card['id'] ?>">
                                <?= htmlspecialchars($card['stock_code'] . ' - ' . $card['product_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Hareket Tipi</label>
                        <select class="form-control" name="movement_type" required>
                            <option value="in">Giriş</option>
                            <option value="out">Çıkış</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Miktar</label>
                        <input type="number" class="form-control" name="quantity" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable
    const table = $('#stockTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        }
    });

    // Stok kartı formu
    $('#stockCardForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: SITE_URL + '/ajax/stock/save_stock_card.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#stockCardModal').modal('hide');
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.error || 'Bir hata oluştu');
                }
            }
        });
    });

    // Stok hareketi formu
    $('#stockMovementForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: SITE_URL + '/ajax/stock/save_stock_movement.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#stockMovementModal').modal('hide');
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.error || 'Bir hata oluştu');
                }
            }
        });
    });

    // Stok kartı düzenleme
    $('.edit-stock').on('click', function() {
        const id = $(this).data('id');
        
        $.get(SITE_URL + '/ajax/stock/get_stock_card.php', { id: id }, function(response) {
            if (response.success) {
                const card = response.data;
                $('#stock_id').val(card.id);
                $('input[name="stock_code"]').val(card.stock_code);
                $('input[name="product_name"]').val(card.product_name);
                $('input[name="brand"]').val(card.brand);
                $('#stockCardModal').modal('show');
            }
        });
    });

    // Stok kartı silme
    $('.delete-stock').on('click', function() {
        const id = $(this).data('id');
        
        if (confirm('Bu stok kartını silmek istediğinize emin misiniz?')) {
            $.post(SITE_URL + '/ajax/stock/delete_stock_card.php', { id: id }, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.error || 'Bir hata oluştu');
                }
            });
        }
    });
});
</script>

<?php require_once 'inc/footer.php'; ?> 