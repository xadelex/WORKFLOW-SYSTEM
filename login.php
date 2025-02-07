<?php
// Çıktı tamponlamasını başlat
ob_start();

// Proje kök dizinini belirle
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)));

// Gerekli dosyaları include et
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/inc/functions.php';
require_once ROOT_PATH . '/inc/Database.php';
require_once ROOT_PATH . '/inc/Auth.php';

$auth = new Auth();

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if ($auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}

$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        if ($auth->login($username, $password)) {
            // Kullanıcı rollerini kontrol et ve session'a kaydet
            $_SESSION['roles'] = $auth->getUserRoles($_SESSION['user_id']);
            
            // Admin kullanıcısına warehouse rolünü ekle
            if ($_SESSION['role'] === 'admin' && !in_array('warehouse', $_SESSION['roles'])) {
                $_SESSION['roles'][] = 'warehouse';
            }
            
            header('Location: ' . SITE_URL . '/dashboard.php');
            exit;
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre';
        }
    } catch (Exception $e) {
        // Hata mesajını loglayalım
        error_log("Login Error: " . $e->getMessage());
        
        // Detaylı hata mesajını gösterelim (geliştirme aşamasında)
        $error = 'Giriş yapılırken bir hata oluştu: ' . $e->getMessage();
        
        // Canlı ortamda sadece genel hata mesajı gösterilmeli
        // $error = 'Giriş yapılırken bir hata oluştu';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Giriş Yap - <?= SITE_NAME ?></title>
    
    <!-- CSS Dosyaları -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    
    <!-- JavaScript Dosyaları -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?= SITE_URL ?>/img/logo.png" alt="Logo" class="login-logo">
                <h4 class="text-center mt-3">Giriş Yap</h4>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               required 
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                        <label class="custom-control-label" for="remember">Beni Hatırla</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i>Giriş Yap
                </button>
            </form>
        </div>
    </div>

    <style>
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .login-logo {
        max-width: 200px;
        height: auto;
        margin: 0 auto;
        display: block;
    }

    .login-form {
        margin-top: 2rem;
    }

    .btn-login {
        padding: 12px;
        font-size: 16px;
        font-weight: 500;
    }

    .alert {
        border-radius: 8px;
        padding: 1rem;
    }

    /* Mobil için özel stiller */
    @media (max-width: 576px) {
        .login-card {
            padding: 1.5rem;
        }

        .login-logo {
            max-width: 150px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .btn-login {
            padding: 10px;
        }
    }

    /* Tablet için özel stiller */
    @media (min-width: 577px) and (max-width: 768px) {
        .login-card {
            max-width: 450px;
        }
    }

    /* Dark mode desteği */
    @media (prefers-color-scheme: dark) {
        .login-card {
            background: #1a202c;
            color: #fff;
        }

        .form-control {
            background: #2d3748;
            border-color: #4a5568;
            color: #fff;
        }

        .input-group-text {
            background: #2d3748;
            border-color: #4a5568;
            color: #fff;
        }
    }
    </style>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

// Çıktı tamponlamasını bitir ve gönder
ob_end_flush();
?> 
