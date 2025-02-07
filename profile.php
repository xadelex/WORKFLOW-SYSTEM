<?php
require_once 'inc/header.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Kullanıcı bilgilerini al
$userId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Profil resmi yükleme işlemi
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadDir = 'uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileInfo = pathinfo($_FILES['avatar']['name']);
                $extension = strtolower($fileInfo['extension']);
                
                // İzin verilen dosya türleri
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($extension, $allowedTypes)) {
                    throw new Exception('Sadece JPG, PNG ve GIF dosyaları yüklenebilir.');
                }

                // Yeni dosya adı oluştur
                $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $newFileName;

                // Dosyayı taşı
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                    try {
                        // Veritabanını güncelle
                        $updateStmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                        $updateStmt->execute([$targetPath, $userId]);
                        
                        // Session'ı güncelle
                        $_SESSION['avatar'] = $targetPath;
                        
                        // Başarı mesajı
                        $_SESSION['success'] = 'Profil fotoğrafı başarıyla güncellendi.';
                        
                    } catch (PDOException $e) {
                        throw new Exception('Veritabanı güncelleme hatası: ' . $e->getMessage());
                    }
                } else {
                    throw new Exception('Dosya yükleme hatası.');
                }
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        // Diğer profil bilgilerini güncelle
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['email'],
            $userId
        ]);

        $_SESSION['success'] = 'Profil bilgileri başarıyla güncellendi.';
        header('Location: ' . SITE_URL . '/profile.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-circle mr-2"></i>
                        Profil Bilgileri
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="profileForm" enctype="multipart/form-data">
                        <!-- Profil Resmi -->
                        <div class="form-group text-center mb-4">
                            <div class="avatar-wrapper">
                                <img src="<?= !empty($user['avatar']) ? SITE_URL . '/' . $user['avatar'] : SITE_URL . '/img/avatar.png' ?>" 
                                     alt="Profil Resmi" 
                                     class="profile-avatar mb-3" 
                                     id="avatarPreview">
                                <div class="avatar-edit">
                                    <label for="avatarUpload" class="btn btn-primary btn-sm">
                                        <i class="fas fa-camera"></i> Fotoğraf Değiştir
                                    </label>
                                    <input type="file" 
                                           id="avatarUpload" 
                                           name="avatar" 
                                           class="d-none" 
                                           accept="image/*"
                                           onchange="previewImage(this)">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="font-weight-bold">
                                <i class="fas fa-user mr-2"></i>Ad Soyad
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="font-weight-bold">
                                <i class="fas fa-envelope mr-2"></i>E-posta
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username" class="font-weight-bold">
                                <i class="fas fa-user mr-2"></i>Kullanıcı Adı
                            </label>
                            <input type="text" class="form-control bg-light" id="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>" readonly>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Kullanıcı adı değiştirilemez.
                            </small>
                        </div>

                        <hr class="my-4">
                        
                        <h6 class="mb-3 font-weight-bold">
                            <i class="fas fa-key mr-2"></i>Şifre Değiştir
                        </h6>

                        <div class="form-group">
                            <label for="current_password">Mevcut Şifre</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Şifreniz en az 6 karakter olmalıdır.
                            </small>
                        </div>

                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save mr-2"></i>Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profil kartı stilleri */
.container-fluid {
    padding: 0 50px;
    margin: 0 auto;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,.08) !important;
    margin-bottom: 30px;
    background-color: #fff;
    max-width: none;
}

.card-header {
    border-bottom: 1px solid #eee;
    border-top-left-radius: 10px !important;
    border-top-right-radius: 10px !important;
    padding: 25px 40px;
}

.card-body {
    padding: 50px !important;
}

/* Form elemanları için grid sistemi */
.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -20px;
    margin-left: -20px;
}

.form-group {
    margin-bottom: 30px;
    padding: 0 20px;
    flex: 0 0 100%;
}

/* İki sütunlu form grupları için */
@media (min-width: 768px) {
    .form-group.col-md-6 {
        flex: 0 0 33.333%;
        max-width: 33.333%;
    }
}

.form-control {
    border-radius: 5px;
    border: 1px solid #ddd;
    padding: 12px 15px;
    height: auto;
    font-size: 15px;
    width: 100%;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.form-control[readonly] {
    background-color: #f8f9fa;
}

.btn-primary {
    border-radius: 5px;
    padding: 12px 30px;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,.2);
}

.alert {
    border: none;
    border-radius: 5px;
    padding: 15px 20px;
    margin-bottom: 25px;
}

.alert-dismissible .close {
    padding: 15px 20px;
}

/* Label stilleri */
label {
    font-size: 15px;
    color: #495057;
    margin-bottom: 8px;
}

/* Yardım metinleri */
.form-text {
    font-size: 13px;
    margin-top: 8px;
}

/* Ayraç stilleri */
hr {
    margin: 30px 0;
    border-color: #eee;
}

/* Başlık stilleri */
h6 {
    font-size: 16px;
    margin-bottom: 20px;
}

/* İkon stilleri */
.fa, .fas {
    width: 20px;
    text-align: center;
}

/* Responsive düzenlemeler */
@media (max-width: 1200px) {
    .container-fluid {
        padding: 0 30px;
    }
    
    .card-body {
        padding: 40px !important;
    }
}

@media (max-width: 991px) {
    .container-fluid {
        padding: 0 20px;
    }
    
    .card-body {
        padding: 30px !important;
    }
}

@media (max-width: 767px) {
    .container-fluid {
        padding: 0 15px;
    }
    
    .card-body {
        padding: 20px !important;
    }
    
    .form-group {
        padding: 0;
    }
}

.avatar-wrapper {
    position: relative;
    display: inline-block;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.avatar-edit {
    margin-top: 10px;
}

.avatar-edit label {
    cursor: pointer;
}

.avatar-edit label:hover {
    background-color: #0056b3;
}
</style>

<script>
$(document).ready(function() {
    // Form doğrulama
    $('#profileForm').on('submit', function(e) {
        const currentPassword = $('#current_password').val();
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();

        // Şifre alanları kontrol
        if (currentPassword || newPassword || confirmPassword) {
            if (!currentPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Mevcut şifrenizi girmelisiniz.'
                });
                return;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Yeni şifreler eşleşmiyor.'
                });
                return;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Yeni şifre en az 6 karakter olmalıdır.'
                });
                return;
            }
        }
    });
});

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            $('#avatarPreview').attr('src', e.target.result);
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'inc/footer.php'; ?> 