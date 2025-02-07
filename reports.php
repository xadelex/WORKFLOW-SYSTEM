<?php
require_once 'inc/header.php';
require_once 'inc/ReportManager.php';

if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$reportManager = new ReportManager();

// Tarih filtreleri
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Raporları al
$dailyStats = $reportManager->getDailyStats();
$performance = $reportManager->getOperatorPerformance($startDate, $endDate);
$delayedOrders = $reportManager->getDelayedOrders();
$monthlyStats = $reportManager->getMonthlyStats();
?>

<div class="container-fluid">
    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Rapor Filtreleri</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" value="<?= $startDate ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" value="<?= $endDate ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Durum</label>
                        <select class="form-control" name="status" id="status">
                            <option value="">Tümü</option>
                            <option value="pending">Beklemede</option>
                            <option value="in_production">Üretimde</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="cancelled">İptal</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> Filtrele
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Günlük İstatistikler -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Günlük İstatistikler</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="stat-box">
                        <div class="stat-number"><?= $dailyStats['total_orders'] ?></div>
                        <div class="stat-label">Toplam Sipariş</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-box bg-secondary">
                        <div class="stat-number"><?= $dailyStats['pending'] ?></div>
                        <div class="stat-label">Bekleyen</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-box bg-info">
                        <div class="stat-number"><?= $dailyStats['laser_processing'] ?></div>
                        <div class="stat-label">Lazer İşleminde</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-box bg-warning">
                        <div class="stat-number"><?= $dailyStats['packaging'] ?></div>
                        <div class="stat-label">Paketlemede</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-box bg-primary">
                        <div class="stat-number"><?= $dailyStats['shipping'] ?></div>
                        <div class="stat-label">Sevkiyatta</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-box bg-success">
                        <div class="stat-number"><?= $dailyStats['completed'] ?></div>
                        <div class="stat-label">Tamamlanan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Siparişler Tablosu -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Siparişler</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
                            <th>Durum</th>
                            <th>Oluşturulma</th>
                            <th>Son İşlem</th>
                            <th>İşleyen</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Personel Performansı -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Personel Performansı</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>PERSONEL</th>
                            <th>İŞLENEN SİPARİŞ</th>
                            <th>ORTALAMA İŞLEM SÜRESİ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($performance as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['full_name']) ?></td>
                            <td><?= $p['total_processed'] ?></td>
                            <td><?= round($p['avg_process_time'] / 60, 1) ?> saat</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script>
$(document).ready(function() {
    // DataTable'ı başlat
    const table = $('#reportsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: SITE_URL + '/ajax/get_reports.php',
            type: 'POST',
            data: function(d) {
                return {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    status: $('#status').val()
                };
            }
        },
        columns: [
            { data: 'order_number' },
            { data: 'customer_name' },
            { 
                data: 'status',
                render: function(data) {
                    const badges = {
                        'pending': 'warning',
                        'laser_processing': 'info',
                        'packaging': 'warning',
                        'shipping': 'primary',
                        'completed': 'success',
                        'cancelled': 'danger'
                    };
                    const labels = {
                        'pending': 'Beklemede',
                        'laser_processing': 'Lazer İşleminde',
                        'packaging': 'Paketlemede',
                        'shipping': 'Sevkiyatta',
                        'completed': 'Tamamlandı',
                        'cancelled': 'İptal'
                    };
                    return `<span class="badge badge-${badges[data] || 'secondary'}">${labels[data] || data}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('DD.MM.YYYY HH:mm');
                }
            },
            { 
                data: 'updated_at',
                render: function(data) {
                    return data ? moment(data).format('DD.MM.YYYY HH:mm') : '';
                }
            },
            { data: 'processed_by' }
        ],
        order: [[3, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        }
    });

    // İstatistikleri güncelle
    function updateStats() {
        $.ajax({
            url: SITE_URL + '/ajax/get_report_stats.php',
            type: 'POST',
            data: {
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                status: $('#status').val()
            },
            success: function(response) {
                if (response.success) {
                    // Günlük istatistikleri güncelle
                    $('.stat-number').each(function() {
                        const key = $(this).closest('.stat-box').data('stat');
                        $(this).text(response.stats[key] || 0);
                    });

                    // Personel performans tablosunu güncelle
                    const performanceTable = $('.table:not(#reportsTable)');
                    let html = '';
                    response.performance.forEach(p => {
                        html += `
                            <tr>
                                <td>${p.full_name}</td>
                                <td>${p.total_processed}</td>
                                <td>${(p.avg_process_time / 60).toFixed(1)} saat</td>
                            </tr>
                        `;
                    });
                    performanceTable.find('tbody').html(html);
                }
            }
        });
    }

    // Filtreleme formunu işle
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
        updateStats();

        // URL'i güncelle
        const params = new URLSearchParams({
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            status: $('#status').val()
        });
        window.history.pushState({}, '', `${window.location.pathname}?${params}`);
    });

    // Sayfa yüklendiğinde URL'deki parametreleri form'a doldur
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('start_date')) $('#start_date').val(urlParams.get('start_date'));
    if (urlParams.has('end_date')) $('#end_date').val(urlParams.get('end_date'));
    if (urlParams.has('status')) $('#status').val(urlParams.get('status'));
});
</script>

<?php require_once 'inc/footer.php'; ?> 