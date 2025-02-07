<?php
require_once 'inc/header.php';

if (!$auth->isLoggedIn() || !$auth->hasRole(['admin', 'warehouse'])) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();

// Stok hareketlerini getir
$sql = "SELECT 
            sm.*,
            sc.stock_code,
            sc.product_name,
            sc.brand,
            COALESCE(u.full_name, u.username) as user_name
        FROM stock_movements sm
        LEFT JOIN stock_cards sc ON sm.stock_card_id = sc.id
        LEFT JOIN users u ON sm.processed_by = u.id
        ORDER BY sm.created_at DESC";

$movements = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Kritik stok seviyesindeki ürünler
$sql = "SELECT sc.*,
               COALESCE(SUM(CASE WHEN sm.movement_type = 'in' THEN sm.quantity ELSE -sm.quantity END), 0) as current_stock
        FROM stock_cards sc
        LEFT JOIN stock_movements sm ON sc.id = sm.stock_card_id
        GROUP BY sc.id
        HAVING current_stock <= 10
        ORDER BY current_stock ASC";

$critical_stocks = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Stok Raporları</h1>
        <div>
            <button class="btn btn-primary" onclick="exportReport()">
                <i class="fas fa-file-excel"></i> Excel'e Aktar
            </button>
            <button class="btn btn-info" onclick="printReport()">
                <i class="fas fa-print"></i> Yazdır
            </button>
        </div>
    </div>

    <!-- Kritik Stok Seviyeleri -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Kritik Stok Seviyeleri</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th>Marka</th>
                            <th>Mevcut Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($critical_stocks)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Kritik seviyede stok bulunmamaktadır.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($critical_stocks as $stock): ?>
                            <tr>
                                <td><?= htmlspecialchars($stock['stock_code']) ?></td>
                                <td><?= htmlspecialchars($stock['product_name']) ?></td>
                                <td><?= htmlspecialchars($stock['brand']) ?></td>
                                <td>
                                    <span class="badge badge-danger">
                                        <?= number_format($stock['current_stock']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stok Hareketleri -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Stok Hareketleri</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="movementsTable">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th>Hareket</th>
                            <th>Miktar</th>
                            <th>Açıklama</th>
                            <th>İşleyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i', strtotime($movement['created_at'])) ?></td>
                            <td><?= htmlspecialchars($movement['stock_code']) ?></td>
                            <td><?= htmlspecialchars($movement['product_name']) ?></td>
                            <td>
                                <span class="badge badge-<?= $movement['movement_type'] == 'in' ? 'success' : 'danger' ?>">
                                    <?= $movement['movement_type'] == 'in' ? 'Giriş' : 'Çıkış' ?>
                                </span>
                            </td>
                            <td><?= number_format($movement['quantity']) ?></td>
                            <td><?= htmlspecialchars($movement['description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($movement['user_name']) ?></td>
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
    $('#movementsTable').DataTable({
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        }
    });
});

function exportReport() {
    window.location.href = SITE_URL + '/ajax/stock/export_report.php';
}

function printReport() {
    window.print();
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .card-header {
        background: none !important;
        padding: 0 !important;
    }
    .badge-success {
        color: #28a745 !important;
        background: none !important;
        border: 1px solid #28a745 !important;
    }
    .badge-danger {
        color: #dc3545 !important;
        background: none !important;
        border: 1px solid #dc3545 !important;
    }
}
</style>

<?php require_once 'inc/footer.php'; ?> 