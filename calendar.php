<?php
require_once 'inc/header.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <button class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#eventModal">
                        <i class="fas fa-plus mr-2"></i>Yeni Etkinlik
                    </button>

                    <div class="event-types mb-4">
                        <h6 class="font-weight-bold mb-3">Etkinlik Türleri</h6>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="event1" checked>
                            <label class="custom-control-label text-success" for="event1">Yeni Tema</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="event2" checked>
                            <label class="custom-control-label text-info" for="event2">Etkinliklerim</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="event3" checked>
                            <label class="custom-control-label text-warning" for="event3">Toplantılar</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="event4" checked>
                            <label class="custom-control-label text-danger" for="event4">Yeni Tema</label>
                        </div>
                    </div>

                    <div class="upcoming-events">
                        <h6 class="font-weight-bold mb-3">Yaklaşan Etkinlikler</h6>
                        <div class="upcoming-event mb-3">
                            <small class="text-muted d-block">Bugün</small>
                            <div class="d-flex align-items-center">
                                <div class="event-dot bg-success mr-2"></div>
                                <div>
                                    <strong class="d-block">Yeni Tema Yayını</strong>
                                    <small class="text-muted">09:00 - 10:30</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="calendar-toolbar mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-outline-secondary mr-2" id="prevMonth">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="btn btn-outline-secondary mr-3" id="nextMonth">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <button class="btn btn-outline-primary" id="today">Bugün</button>
                            </div>
                            <h4 class="current-date mb-0">Şubat 2024</h4>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary active">Ay</button>
                                <button class="btn btn-outline-secondary">Hafta</button>
                                <button class="btn btn-outline-secondary">Gün</button>
                                <button class="btn btn-outline-secondary">Liste</button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Etkinlik Ekleme Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Etkinlik</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <div class="form-group">
                        <label>Etkinlik Başlığı</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Etkinlik Türü</label>
                        <select name="event_type" class="form-control">
                            <option value="theme" data-color="#1cc88a">Yeni Tema</option>
                            <option value="event" data-color="#36b9cc">Etkinlik</option>
                            <option value="meeting" data-color="#f6c23e">Toplantı</option>
                            <option value="other" data-color="#e74a3b">Diğer</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Başlangıç Tarihi</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Başlangıç Saati</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bitiş Tarihi</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bitiş Saati</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="saveEvent">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Etkinlik Detay Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Etkinlik Detayı</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="event-detail-content">
                    <h4 id="eventTitle" class="mb-3"></h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Başlangıç:</strong>
                            <p id="eventStart"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Bitiş:</strong>
                            <p id="eventEnd"></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Tür:</strong>
                        <p id="eventType"></p>
                    </div>
                    <div>
                        <strong>Açıklama:</strong>
                        <p id="eventDescription"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="deleteEvent">Sil</button>
                <button type="button" class="btn btn-primary" id="editEvent">Düzenle</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS ve JS -->
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@4.4.0/main.min.css' rel='stylesheet' />
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.4.0/main.min.css' rel='stylesheet' />
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.4.0/main.min.css' rel='stylesheet' />
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/list@4.4.0/main.min.css' rel='stylesheet' />

<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@4.4.0/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.4.0/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.4.0/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/list@4.4.0/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@4.4.0/main.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dark mode kontrolü
    function isDarkMode() {
        return document.body.classList.contains('dark-theme');
    }

    // Takvim ayarları
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: ['dayGrid', 'timeGrid', 'list', 'interaction'],
        header: false,
        locale: 'tr',
        editable: true,
        eventLimit: true,
        defaultView: 'dayGridMonth',
        themeSystem: 'standard',
        views: {
            timeGridWeek: {
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' }
            },
            timeGridDay: {
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' }
            },
            listWeek: {
                titleFormat: { year: 'numeric', month: 'long' }
            }
        },
        // Dark mode için takvim renkleri
        eventBackgroundColor: isDarkMode() ? '#4e73df' : '#4e73df',
        eventBorderColor: isDarkMode() ? '#4e73df' : '#4e73df',
        eventTextColor: isDarkMode() ? '#fff' : '#fff',
        events: function(info, successCallback, failureCallback) {
            $.ajax({
                url: SITE_URL + '/ajax/get_events.php',
                type: 'GET',
                success: function(response) {
                    var events = response.map(function(event) {
                        return {
                            id: event.id,
                            title: event.title,
                            start: event.start,
                            end: event.end,
                            color: event.color,
                            description: event.description,
                            event_type: event.event_type
                        };
                    });
                    successCallback(events);
                },
                error: function() {
                    failureCallback();
                }
            });
        },
        dateClick: function(info) {
            // Tıklanan tarihi form alanlarına yerleştir
            $('#eventForm')[0].reset();
            $('input[name="start_date"]').val(info.dateStr);
            $('input[name="end_date"]').val(info.dateStr);
            $('#eventModal').modal('show');
        },
        eventClick: function(info) {
            var event = info.event;
            
            // Detay modalını doldur
            $('#eventTitle').text(event.title);
            $('#eventStart').text(formatDateTime(event.start));
            $('#eventEnd').text(formatDateTime(event.end));
            $('#eventType').text(event.extendedProps.event_type);
            $('#eventDescription').text(event.extendedProps.description || 'Açıklama yok');
            
            // Düzenle butonuna tıklandığında
            $('#editEvent').off('click').on('click', function() {
                $('#eventDetailModal').modal('hide');
                
                // Form alanlarını doldur
                var form = $('#eventForm');
                form.find('[name="title"]').val(event.title);
                form.find('[name="event_type"]').val(event.extendedProps.event_type);
                form.find('[name="description"]').val(event.extendedProps.description);
                
                // Tarih ve saat alanlarını doldur
                var start = event.start;
                var end = event.end;
                form.find('[name="start_date"]').val(formatDate(start));
                form.find('[name="start_time"]').val(formatTime(start));
                form.find('[name="end_date"]').val(formatDate(end));
                form.find('[name="end_time"]').val(formatTime(end));
                
                // Event ID'sini sakla
                form.data('event-id', event.id);
                
                $('#eventModal').modal('show');
            });
            
            // Sil butonuna tıklandığında
            $('#deleteEvent').off('click').on('click', function() {
                Swal.fire({
                    title: 'Emin misiniz?',
                    text: "Bu etkinlik kalıcı olarak silinecek!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Evet, sil!',
                    cancelButtonText: 'İptal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: SITE_URL + '/ajax/delete_event.php',
                            method: 'POST',
                            data: { event_id: event.id },
                            success: function(response) {
                                if (response.success) {
                                    calendar.refetchEvents();
                                    $('#eventDetailModal').modal('hide');
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Başarılı!',
                                        text: 'Etkinlik silindi.',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                }
                            }
                        });
                    }
                });
            });
            
            $('#eventDetailModal').modal('show');
        }
    });

    calendar.render();
    updateCurrentDate();

    // Dark mode değişikliğini dinle
    document.querySelector('.theme-toggle').addEventListener('click', function() {
        setTimeout(function() {
            calendar.destroy();
            calendar = new FullCalendar.Calendar(calendarEl, calendar.getOption());
            calendar.render();
            updateCurrentDate();
        }, 100);
    });

    // Görünüm değiştirme butonları
    $('.btn-group .btn').on('click', function() {
        $(this).addClass('active').siblings().removeClass('active');
        var view = $(this).text().toLowerCase();
        switch(view) {
            case 'ay':
                calendar.changeView('dayGridMonth');
                break;
            case 'hafta':
                calendar.changeView('timeGridWeek');
                break;
            case 'gün':
                calendar.changeView('timeGridDay');
                break;
            case 'liste':
                calendar.changeView('listWeek');
                break;
        }
        updateCurrentDate();
    });

    // Önceki/Sonraki/Bugün butonları
    $('#prevMonth').on('click', function() {
        calendar.prev();
        updateCurrentDate();
    });

    $('#nextMonth').on('click', function() {
        calendar.next();
        updateCurrentDate();
    });

    $('#today').on('click', function() {
        calendar.today();
        updateCurrentDate();
    });

    // Başlıktaki tarihi güncelle
    function updateCurrentDate() {
        var date = calendar.getDate();
        var view = calendar.view.type;
        var formattedDate;

        switch(view) {
            case 'timeGridDay':
                formattedDate = new Intl.DateTimeFormat('tr-TR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }).format(date);
                break;
            case 'timeGridWeek':
                var start = calendar.view.activeStart;
                var end = calendar.view.activeEnd;
                formattedDate = new Intl.DateTimeFormat('tr-TR', {
                    day: 'numeric'
                }).format(start) + ' - ' + 
                new Intl.DateTimeFormat('tr-TR', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }).format(new Date(end - 86400000)); // Son günü göster
                break;
            default:
                formattedDate = new Intl.DateTimeFormat('tr-TR', {
                    year: 'numeric',
                    month: 'long'
                }).format(date);
        }
        
        $('.current-date').text(formattedDate);
    }

    // Otomatik yenileme için interval
    setInterval(function() {
        calendar.refetchEvents();
    }, 30000); // Her 30 saniyede bir yenile

    // Etkinlik kaydetme işlemi
    $('#saveEvent').on('click', function() {
        var form = $('#eventForm');
        var formData = new FormData(form[0]);
        
        // Başlangıç ve bitiş tarih/saatlerini birleştir
        var startDateTime = formData.get('start_date') + 'T' + formData.get('start_time');
        var endDateTime = formData.get('end_date') + 'T' + formData.get('end_time');
        
        // Seçilen etkinlik türünün rengini al
        var eventType = form.find('select[name="event_type"]').find(':selected');
        var color = eventType.data('color');

        $.ajax({
            url: SITE_URL + '/ajax/save_event.php',
            method: 'POST',
            data: {
                title: formData.get('title'),
                event_type: formData.get('event_type'),
                start_date: startDateTime,
                end_date: endDateTime,
                description: formData.get('description'),
                color: color
            },
            success: function(response) {
                if (response.success) {
                    // Modalı kapat
                    $('#eventModal').modal('hide');
                    form[0].reset();
                    
                    // Takvimi hemen yenile
                    calendar.refetchEvents();
                    
                    // Başarı mesajı
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Etkinlik başarıyla kaydedildi.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        didClose: () => {
                            // Toast kapandıktan sonra tekrar yenile
                            calendar.refetchEvents();
                        }
                    });

                    // 1 saniye sonra tekrar yenile (bazı tarayıcılarda gerekebilir)
                    setTimeout(function() {
                        calendar.refetchEvents();
                    }, 1000);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.message || 'Etkinlik kaydedilirken bir hata oluştu.',
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Sunucu hatası oluştu.',
                });
            }
        });
    });

    // Yardımcı fonksiyonlar
    function formatDateTime(date) {
        return new Intl.DateTimeFormat('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function formatTime(date) {
        return date.toTimeString().slice(0,5);
    }
});
</script>

<style>
/* Takvim Stilleri */
.calendar-toolbar {
    padding: 1rem 0;
}

.current-date {
    font-weight: 600;
    color: #2d3748;
}

.event-types .custom-control-label {
    font-weight: 500;
}

.event-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.upcoming-event {
    padding: 10px;
    border-radius: 5px;
    background: #f8f9fa;
}

.fc-day-grid-event {
    border-radius: 3px;
    padding: 2px 5px;
}

.fc-day-grid-event .fc-content {
    white-space: nowrap;
    overflow: hidden;
}

/* Dark theme için */
body.dark-theme .current-date {
    color: #fff;
}

body.dark-theme .upcoming-event {
    background: #2d3748;
}

.fc-timeGrid-view .fc-time-grid .fc-slats td {
    height: 2.5em;
}

.fc-timeGrid-view .fc-day-grid {
    z-index: 2;
}

.fc-time-grid-event {
    border-radius: 3px;
}

.fc-list-item {
    cursor: pointer;
    padding: 8px 14px;
}

.fc-list-item:hover {
    background: rgba(0,0,0,0.05);
}

.fc-list-item-time {
    width: 130px;
}

/* Dark theme için ek stiller */
body.dark-theme .fc-list-item:hover {
    background: rgba(255,255,255,0.1);
}

body.dark-theme .fc-list-heading td {
    background: #2d3748;
}

body.dark-theme .fc-list-item {
    background: #1a202c;
}

/* Dark theme için takvim stilleri */
body.dark-theme .fc-view-container {
    background-color: #1a202c;
}

body.dark-theme .fc-day-header {
    background-color: #2d3748;
    color: #fff;
}

body.dark-theme .fc-day {
    background-color: #1a202c;
    border-color: #2d3748;
}

body.dark-theme .fc-day-number {
    color: #fff;
}

body.dark-theme .fc-unthemed td.fc-today {
    background: #2d3748;
}
</style>

<?php require_once 'inc/footer.php'; ?> 