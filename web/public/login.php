<?php
session_start();
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = trim($_POST['username'] ?? '');
    $input_password = trim($_POST['password'] ?? '');

    // Kullanıcı adı veya şifre boş mu?
    if (empty($input_username) || empty($input_password)) {
        echo "<script>alert('Kullanıcı adı ve şifre boş olamaz.'); window.location.href='login';</script>";
        exit();
    }

    // Kullanıcıyı veritabanından çek
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $input_username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Şifre doğrulama (HASH karşılaştırması)
        if (password_verify($input_password, $user['password'])) {
            // Giriş başarılı
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Yönlendirme - ADMIN PANEL
            header("Location: admin_panel"); 
            exit();
        } else {
            // Şifre yanlış
            echo "<script>alert('Şifre hatalı.'); window.location.href='login';</script>";
            exit();
        }
    } else {
        // Kullanıcı bulunamadı
        echo "<script>alert('Kullanıcı adı hatalı.'); window.location.href='login';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAP Sistemi - Giriş Yap</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #121212;
            --secondary-color: #202020;
            --accent-color: #4776E6;
            --text-color: #ffffff;
            --grey-color: #757575;
            --card-bg: rgba(32, 32, 32, 0.8);
        }
        
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--primary-color);
            color: var(--text-color);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Kaydırma çubuğunu gizleme */
        body::-webkit-scrollbar, html::-webkit-scrollbar {
            display: none;
        }
        
        .navbar {
            background: rgba(18, 18, 18, 0.9);
            padding: 15px 0;
            position: relative;
            z-index: 100;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        
        .navbar-brand {
            color: var(--text-color);
            font-weight: 700;
            font-size: 22px;
            text-decoration: none;
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px); /* Subtract navbar height */
            position: relative;
            z-index: 10;
            padding: 40px 0;
        }
        
        .login-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: var(--grey-color);
            font-size: 1rem;
        }
        
        .logo-element {
            width: 60px;
            height: 60px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            box-shadow: 0 5px 15px rgba(71, 118, 230, 0.4);
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(71, 118, 230, 0.25);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 15px;
            color: var(--grey-color);
        }
        
        .input-with-icon {
            padding-left: 45px;
        }
        
        .login-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(71, 118, 230, 0.3);
        }
        
        .login-btn:hover {
            background: #3563cc;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(71, 118, 230, 0.4);
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 25px 20px;
                margin: 0 15px;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">BAP Hakem Atama Modülü</a>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-element">
                    <i class="fas fa-search"></i>
                </div>
                <h1 class="login-title">Sisteme Giriş</h1>
                <p class="login-subtitle">BAP Hakem Atama Modülü hesabınıza giriş yapın</p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label class="form-label" for="username">Kullanıcı Adı</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control input-with-icon" id="username" name="username" placeholder="Kullanıcı adınız" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Şifre</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
               
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt me-2"></i> Giriş Yap
                </button>
                
              
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>