<?php
// Çıktı tamponlamasını başlat
ob_start();

require_once 'inc/header.php';

// Yetkilendirme kontrolü
if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();

// Sipariş istatistiklerini getir
$sql = "SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as bekleyen,
    SUM(CASE WHEN status = 'cutting' THEN 1 ELSE 0 END) as lazer_kesimde,
    SUM(CASE WHEN status = 'production' THEN 1 ELSE 0 END) as uretimde,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as tamamlanan
FROM orders";

$stats = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

// Son siparişleri getir
$sql = "SELECT o.*, u.username as isleyen
        FROM orders o
        LEFT JOIN users u ON o.processed_by = u.id
        ORDER BY o.created_at DESC 
        LIMIT 10";

$orders = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Dashboard</h1>
        <div class="date"><?= date('d.m.Y') ?></div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <!-- Bekleyen Siparişler -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-warning">BEKLEYEN SİPARİŞLER</h6>
                    <h2><?= $stats['bekleyen'] ?? 0 ?></h2>
                    <div class="icon-bg">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lazer Kesimde -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-info">LAZER KESİMDE</h6>
                    <h2><?= $stats['lazer_kesimde'] ?? 0 ?></h2>
                    <div class="icon-bg">
                        <i class="fas fa-cut"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Üretimde -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-primary">ÜRETİMDE</h6>
                    <h2><?= $stats['uretimde'] ?? 0 ?></h2>
                    <div class="icon-bg">
                        <i class="fas fa-industry"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tamamlanan -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-success">TAMAMLANAN</h6>
                    <h2><?= $stats['tamamlanan'] ?? 0 ?></h2>
                    <div class="icon-bg">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Son Siparişler -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Son Siparişler</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>SİPARİŞ NO</th>
                                    <th>MÜŞTERİ</th>
                                    <th>DURUM</th>
                                    <th>İŞLEYEN</th>
                                    <th>TARİH</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_number'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($order['customer_name'] ?? '') ?></td>
                                    <td>
                                        <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?>">
                                            <?= getStatusText($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($order['isleyen'] ?? '') ?></td>
                                    <td><?= isset($order['created_at']) ? date('d.m.Y H:i', strtotime($order['created_at'])) : '' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bildirimler -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bildirimler</h5>
                </div>
                <div class="card-body">
                    <div class="text-center text-muted">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <p>Yeni bildirim bulunmuyor.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Eklenen Bölüm: Performans Metrikleri ve Grafikler -->
    <div class="row mt-4">
        <!-- Sipariş İstatistikleri -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sipariş İstatistikleri</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateChart('weekly')">Haftalık</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary active" onclick="updateChart('monthly')">Aylık</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateChart('yearly')">Yıllık</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="orderStatsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Performans Metrikleri -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Performans Metrikleri</h5>
                </div>
                <div class="card-body">
                    <div class="metric-item mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Ortalama İşlem Süresi</span>
                            <span class="text-primary">2.5 saat</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-primary" style="width: 75%"></div>
                        </div>
                    </div>
                    <div class="metric-item mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Müşteri Memnuniyeti</span>
                            <span class="text-success">92%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: 92%"></div>
                        </div>
                    </div>
                    <div class="metric-item mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Zamanında Teslim</span>
                            <span class="text-info">85%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-info" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="metric-item">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Kapasite Kullanımı</span>
                            <span class="text-warning">78%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-warning" style="width: 78%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js kütüphanesini ekleyelim -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sipariş istatistikleri grafiği
const ctx = document.getElementById('orderStatsChart').getContext('2d');
let orderStatsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
        datasets: [{
            label: 'Tamamlanan Siparişler',
            data: [65, 59, 80, 81, 56, 55],
            borderColor: '#28a745',
            tension: 0.1
        }, {
            label: 'Bekleyen Siparişler',
            data: [28, 48, 40, 19, 86, 27],
            borderColor: '#ffc107',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Grafik güncelleme fonksiyonu
function updateChart(period) {
    // Burada AJAX ile sunucudan yeni verileri alabilirsiniz
    // Şimdilik örnek veriler kullanıyoruz
    const data = {
        weekly: {
            labels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
            completed: [12, 19, 15, 17, 14, 9, 3],
            pending: [5, 8, 6, 9, 12, 4, 1]
        },
        monthly: {
            labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
            completed: [65, 59, 80, 81, 56, 55],
            pending: [28, 48, 40, 19, 86, 27]
        },
        yearly: {
            labels: ['2019', '2020', '2021', '2022', '2023'],
            completed: [540, 615, 675, 732, 810],
            pending: [280, 348, 410, 389, 425]
        }
    };

    // Aktif butonu güncelle
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Grafik verilerini güncelle
    orderStatsChart.data.labels = data[period].labels;
    orderStatsChart.data.datasets[0].data = data[period].completed;
    orderStatsChart.data.datasets[1].data = data[period].pending;
    orderStatsChart.update();
}
</script>

<style>
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
.badge {
    padding: 5px 10px;
    border-radius: 20px;
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
</style>

<?php 
require_once 'inc/footer.php';
ob_end_flush();
?> 