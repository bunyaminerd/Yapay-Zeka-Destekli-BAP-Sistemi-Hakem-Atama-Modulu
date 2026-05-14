<?php
session_start();
require_once __DIR__ . '/../config.php'; // Veritabanı bağlantı dosyası

// Eğer kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['username'])) {
    header('Location: login');
    exit();
}

// Eğer formdan POST verisi geldiyse proje veya hakem kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) && isset($_POST['keywords'])) {
        // Proje ekleme işlemi
        $title = trim($_POST['title']);
        $keywords = trim($_POST['keywords']);

        try {
            // Şu anki en yüksek proje id'sini bul
            $stmtMax = $pdo->query("SELECT MAX(id) FROM projects");
            $maxId = (int) $stmtMax->fetchColumn();
            $newId = $maxId + 1;

            // Yeni proje kaydet (manuel id vererek)
            $stmt = $pdo->prepare("INSERT INTO projects (id, title, keywords) VALUES (?, ?, ?)");
            $stmt->execute([$newId, $title, $keywords]);
            $successMessage = "Proje başarıyla kaydedildi!";
        } catch (PDOException $e) {
            $errorMessage = "Veritabanı hatası (Proje): " . $e->getMessage();
        }
    } elseif (isset($_POST['name']) && isset($_POST['expertise'])) {
        // Hakem ekleme işlemi
        $name = trim($_POST['name']);
        $expertise = trim($_POST['expertise']);
        $past_projects = isset($_POST['past_projects']) ? trim($_POST['past_projects']) : '';

        try {
            // Şu anki en yüksek hakem id'sini bul
            $stmtMax = $pdo->query("SELECT MAX(id) FROM reviewers");
            $maxId = (int) $stmtMax->fetchColumn();
            $newId = $maxId + 1;

            // Yeni hakem kaydet (manuel id vererek)
            $stmt = $pdo->prepare("INSERT INTO reviewers (id, name, expertise, past_projects) VALUES (?, ?, ?, ?)");
            $stmt->execute([$newId, $name, $expertise, $past_projects]);
            $successMessage = "Hakem başarıyla kaydedildi!";
        } catch (PDOException $e) {
            $errorMessage = "Veritabanı hatası (Hakem): " . $e->getMessage();
        }
    }
}

// Kullanıcı bilgilerini çekelim
$username = $_SESSION['username'];
$firstLetter = strtoupper($username[0]);

// Veritabanından toplam proje, hakem ve atama sayılarını çekelim
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $totalProjects = $stmt->fetchColumn();

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM reviewers");
    $totalReviewers = $stmt2->fetchColumn();

    $stmt3 = $pdo->query("SELECT COUNT(*) FROM assignments");
    $totalAssignments = $stmt3->fetchColumn();
} catch (PDOException $e) {
    die("Veritabanı hatası (İstatistikler): " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAP Sistemi - Admin Panel</title>
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
        
        .content-wrapper {
            position: relative;
            z-index: 10;
            padding: 40px 0;
            min-height: calc(100vh - 70px); 
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--grey-color);
        }
        
        .stats-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            box-shadow: 0 5px 15px rgba(71, 118, 230, 0.4);
        }
        
        .stats-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--grey-color);
        }
        
        .stats-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stats-description {
            font-size: 0.9rem;
            color: var(--grey-color);
        }
        
        .content-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
            color: var(--accent-color);
        }
        
        .data-table {
            width: 100%;
            color: var(--text-color);
        }
        
        .data-table th {
            background-color: rgba(18, 18, 18, 0.5);
            padding: 12px 15px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(71, 118, 230, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-badge.success {
            background-color: rgba(46, 213, 115, 0.2);
            color: #2ed573;
        }
        
        .status-badge.pending {
            background-color: rgba(255, 164, 46, 0.2);
            color: #ffa42e;
        }
        
        .status-badge.danger {
            background-color: rgba(255, 71, 87, 0.2);
            color: #ff4757;
        }
        
        .btn-custom {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            background: #3563cc;
            transform: translateY(-2px);
        }
        
        .decorative-shape {
            position: absolute;
            opacity: 0.2;
            z-index: -1;
        }
        
        .shape-1 {
            top: -20px;
            right: -20px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--accent-color);
            filter: blur(30px);
        }
        
        /* Fixed sidebar */
        .sidebar-container {
            position: sticky;
            top: 20px; /* Give some space from the top */
        }
        
        .sidebar {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            height: auto;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(71, 118, 230, 0.1);
            color: var(--accent-color);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            margin-right: 15px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .user-role {
            font-size: 0.85rem;
            color: var(--grey-color);
        }
        
        @media (max-width: 992px) {
            .sidebar {
                margin-bottom: 30px;
            }
            
            .sidebar-container {
                position: relative;
                top: 0;
            }
        }
        /* Kaydırma çubuğunu gizleme */
body::-webkit-scrollbar, html::-webkit-scrollbar {
    display: none;
}
.modal-content {
    background-color: var(--secondary-color) !important;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  }
  
  .modal-header, .modal-footer {
    border-color: rgba(255, 255, 255, 0.1);
  }
  
  .form-control {
    background-color: rgba(18, 18, 18, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-color) !important;
    border-radius: 10px;
    padding: 12px 15px;
  }
  
  .form-control:focus {
    box-shadow: 0 0 0 3px rgba(71, 118, 230, 0.3);
    border-color: var(--accent-color);
  }
  
  .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.7);
  }
  
  .btn-secondary {
    background-color: rgba(255, 255, 255, 0.1);
    border: none;
    color: var(--text-color);
    padding: 8px 20px;
    border-radius: 8px;
  }
  
  .btn-secondary:hover {
    background-color: rgba(255, 255, 255, 0.2);
  }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">BAP Hakem Atama Modülü</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                           <i class="fas fa-user-circle me-1"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="login"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <div class="dashboard-header">
                <h1 class="page-title">Yönetim Paneli</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Admin Panel</li>
                    </ol>
                </nav>
            </div>
            
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="sidebar-container">
                        <div class="sidebar">
                            <div class="user-profile">
                                <div class="user-avatar"><?php echo $firstLetter; ?></div>
                                <div class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                                    <div class="user-role">Yönetici</div>
                                </div>
                            </div>
                            
                            <ul class="sidebar-menu">
                                <li>
                                    <a href="admin_panel" class="active">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a href="projects">
                                        <i class="fas fa-file-alt"></i> Projeler
                                    </a>
                                </li>
                                <li>
                                    <a href="reviewers">
                                        <i class="fas fa-users"></i> Hakemler
                                    </a>
                                </li>
                                <li>
                                    <a href="assignment">
                                        <i class="fas fa-tasks"></i> Atamalar
                                    </a>
                                </li>
                                <li>
                                    <a href="usage_guide">
                                        <i class="fas fa-question-circle"></i> Kullanım Talimatları
                                    </a>
                                </li>
                                <li>
                                    <a href="ayarlar">
                                        <i class="fas fa-cog"></i> Ayarlar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-4">
                            <div class="stats-card">
                                <div class="stats-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3 class="stats-title">Toplam Proje</h3>
                                <div class="stats-value"><?php echo $totalProjects; ?></div>
                                <p class="stats-description">Sistemdeki toplam Proje sayısı</p>
                                <div class="decorative-shape shape-1"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="stats-card">
                                <div class="stats-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stats-title">Toplam Hakem</h3>
                                <div class="stats-value"><?php echo $totalReviewers; ?></div>
                                <p class="stats-description">Sistemdeki kayıtlı hakem sayısı</p>
                                <div class="decorative-shape shape-1"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="stats-card">
                                <div class="stats-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h3 class="stats-title">Atanmış Proje</h3>
                                <div class="stats-value"><?php echo $totalAssignments; ?></div>
                                <p class="stats-description">Hakeme atanmış Proje sayısı</p>
                                <div class="decorative-shape shape-1"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="content-card">
                        <h2 class="card-title"><i class="fas fa-bolt"></i> Hızlı İşlemler</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-custom w-100 py-3">
                                    <i class="fas fa-plus-circle me-2"></i> Yeni Proje Ekle
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-custom w-100 py-3">
                                    <i class="fas fa-user-plus me-2"></i> Hakem Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="addProjectModalLabel">Yeni Proje Ekle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addProjectForm">
          <div class="mb-3">
            <label for="projectTitle" class="form-label">Proje Başlığı</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" id="projectTitle" placeholder="Proje başlığını giriniz" required>
          </div>
          <div class="mb-3">
            <label for="projectKeywords" class="form-label">Anahtar Kelimeler</label>
            <textarea class="form-control bg-dark text-white border-secondary" id="projectKeywords" rows="3" placeholder="Anahtar kelimeleri virgülle ayırarak giriniz" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="submit" form="addProjectForm" class="btn btn-custom">
          <i class="fas fa-save me-2"></i>Kaydet
        </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="addReviewerModal" tabindex="-1" aria-labelledby="addReviewerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="addReviewerModalLabel">Yeni Hakem Ekle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addReviewerForm">
          <div class="mb-3">
            <label for="reviewerName" class="form-label">Hakem Adı</label>
            <input type="text" class="form-control bg-dark text-white border-secondary" id="reviewerName" placeholder="Hakem adını giriniz" required>
          </div>
          <div class="mb-3">
            <label for="reviewerExpertise" class="form-label">Uzmanlık Alanları</label>
            <textarea class="form-control bg-dark text-white border-secondary" id="reviewerExpertise" rows="2" placeholder="Uzmanlık alanlarını giriniz" required></textarea>
          </div>
          <div class="mb-3">
            <label for="reviewerPastProjects" class="form-label">Geçmiş Projeler</label>
            <textarea class="form-control bg-dark text-white border-secondary" id="reviewerPastProjects" rows="2" placeholder="Geçmiş projeleri giriniz (İsteğe Bağlı)"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="submit" form="addReviewerForm" class="btn btn-custom">
          <i class="fas fa-save me-2"></i>Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

    
  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const root = document.documentElement;
        const applyTheme = (theme) => {
            root.setAttribute('data-bs-theme', theme);
            document.body.style.backgroundColor = theme === 'dark' ? '#121212' : '#f1f3f5';
            document.body.style.color = theme === 'dark' ? '#ffffff' : '#212529';

            const navbar = document.querySelector('.navbar');
            const navbarBrand = document.querySelector('.navbar-brand');
            const navLinks = document.querySelectorAll('.navbar .nav-link');
            const navbarDropdownMenus = document.querySelectorAll('.navbar .dropdown-menu');
            const allCards = document.querySelectorAll('.stats-card, .content-card, .sidebar');
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            const breadcrumbLinks = document.querySelectorAll('.breadcrumb-item a');
            const breadcrumbActive = document.querySelector('.breadcrumb-item.active');
            const customButtons = document.querySelectorAll('.btn-custom');
            const modalContent = document.querySelectorAll('.modal-content');

            if (theme === 'dark') {
                root.style.setProperty('--primary-color', '#121212');
                root.style.setProperty('--secondary-color', '#202020');
                root.style.setProperty('--text-color', '#ffffff');
                root.style.setProperty('--grey-color', '#757575');
                root.style.setProperty('--card-bg', 'rgba(32, 32, 32, 0.8)');
                root.style.setProperty('--accent-color', '#4776E6');

                if (navbar) {
                    navbar.style.background = 'rgba(18, 18, 18, 0.9)';
                    navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.3)';
                }
                if (navbarBrand) navbarBrand.style.color = '#ffffff';
                navLinks.forEach(link => link.style.color = '#ffffff');
                navbarDropdownMenus.forEach(menu => {
                    menu.classList.add('dropdown-menu-dark');
                });

                allCards.forEach(card => {
                    card.style.backgroundColor = 'rgba(32, 32, 32, 0.8)';
                    card.style.color = '#ffffff';
                });

                customButtons.forEach(btn => {
                    btn.style.backgroundColor = 'rgba(71, 118, 230, 0.1)';
                    btn.style.color = '#4776E6';
                    btn.style.border = '1px solid #4776E6';
                });

                modalContent.forEach(modal => {
                    modal.style.backgroundColor = '#202020';
                    modal.style.color = '#ffffff';
                    modal.style.border = '1px solid #303030';
                });

            } else {
                root.style.setProperty('--primary-color', '#f1f3f5');
                root.style.setProperty('--secondary-color', '#ffffff');
                root.style.setProperty('--text-color', '#212529');
                root.style.setProperty('--grey-color', '#6c757d');
                root.style.setProperty('--card-bg', '#ffffff');
                root.style.setProperty('--accent-color', '#0d6efd');

                if (navbar) {
                    navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                    navbar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
                if (navbarBrand) navbarBrand.style.color = '#212529';
                navLinks.forEach(link => link.style.color = '#212529');
                navbarDropdownMenus.forEach(menu => {
                    menu.classList.remove('dropdown-menu-dark');
                    menu.style.backgroundColor = '#ffffff';
                });

                allCards.forEach(card => {
                    card.style.backgroundColor = '#ffffff';
                    card.style.color = '#212529';
                });

                sidebarLinks.forEach(link => {
                    link.style.color = '#212529';
                    if (link.classList.contains('active')) {
                        link.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
                        link.style.color = '#0d6efd';
                    }
                });

                if (breadcrumbLinks) breadcrumbLinks.forEach(bLink => bLink.style.color = '#0d6efd');
                if (breadcrumbActive) breadcrumbActive.style.color = '#6c757d';

                customButtons.forEach(btn => {
                    btn.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
                    btn.style.color = '#0d6efd';
                    btn.style.border = '1px solid #0d6efd';
                });

                modalContent.forEach(modal => {
                    modal.style.backgroundColor = '#ffffff';
                    modal.style.color = '#212529';
                    modal.style.border = '1px solid #dee2e6';
                });
            }
        };

        // Tema değişikliğini uygula
        const savedTheme = localStorage.getItem('theme') || 'dark';
        applyTheme(savedTheme);

        // Tema değişikliğini dinle
        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                applyTheme(e.newValue);
            }
        });

        // Hakem ekleme butonuna tıklayınca modal aç
        const addReviewerButton = document.querySelector('.btn-custom:has(.fa-user-plus)');
        if (addReviewerButton) {
            addReviewerButton.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('addReviewerModal'));
                modal.show();
            });
        }

        // Hakem ekleme formu submit olduğunda
        const reviewerForm = document.getElementById('addReviewerForm');
        reviewerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const name = document.getElementById('reviewerName').value.trim();
            const expertise = document.getElementById('reviewerExpertise').value.trim();
            const pastProjects = document.getElementById('reviewerPastProjects').value.trim();
            const formData = new FormData();
            formData.append('name', name);
            formData.append('expertise', expertise);
            formData.append('past_projects', pastProjects);

            // Modalı kapat ve kullanıcıyı bilgilendir
            const reviewerModal = bootstrap.Modal.getInstance(document.getElementById('addReviewerModal'));
            reviewerModal.hide();
            reviewerForm.reset();
            alert('Yeni eklenen hakem verileri arka planda işleniyor, yapay zeka tavsiyelerinin görüntülenmesi 1-2 dakika kadar sürebilir.');

            // 1. PHP'ye kaydet
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('PHP cevabı:', data);
                // PHP kaydı başarılı veya başarısız olsa da Flask'a gönder
                // 2. Flask API'ye gönder
                return fetch('http://localhost:5000/add_reviewer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name,
                        expertise: expertise,
                        past_projects: pastProjects
                    })
                });
            })
            .then(response => response.json())
            .then(data => {
                console.log('Flask cevabı:', data);
                // Flask işlemi arka planda tamamlandı, burada ek bir kullanıcı bildirimi gerekmiyor.
                // Sayfa yenileme kaldırıldı.
            })
            .catch(error => {
                console.error('Hakem eklenirken hata oluştu:', error);
                // Kullanıcı zaten bilgilendirildi, ancak konsola hata basmaya devam edilebilir
                // veya farklı bir hata yönetimi yapılabilir.
                // alert('Hakem eklenirken bir hata oluştu!'); // Bu satır isteğe bağlı olarak kalabilir veya kaldırılabilir.
            });
        });

        // Proje ekleme butonuna tıklayınca modal aç
        const addProjectButton = document.querySelector('.btn-custom:has(.fa-plus-circle)');
        if (addProjectButton) {
            addProjectButton.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('addProjectModal'));
                modal.show();
            });
        }

        // Proje ekleme formu submit olduğunda
        const projectForm = document.getElementById('addProjectForm');
        projectForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const title = document.getElementById('projectTitle').value.trim();
            const keywords = document.getElementById('projectKeywords').value.trim();
            const formData = new FormData();
            formData.append('title', title);
            formData.append('keywords', keywords);

            // Modalı kapat ve kullanıcıyı bilgilendir
            const projectModal = bootstrap.Modal.getInstance(document.getElementById('addProjectModal'));
            projectModal.hide();
            projectForm.reset();
            alert('Yeni eklenen proje verileri arka planda işleniyor, yapay zeka tavsiyelerinin görüntülenmesi 1-2 dakika kadar sürebilir.');

            // 1. PHP'ye kaydet
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('PHP cevabı:', data);
                // PHP kaydı başarılı veya başarısız olsa da Flask'a gönder
                // 2. Flask API'ye gönder
                return fetch('http://localhost:5000/add_project', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: title,
                        keywords: keywords
                    })
                });
            })
            .then(response => response.json())
            .then(data => {
                console.log('Flask cevabı:', data);
                // Flask işlemi arka planda tamamlandı, burada ek bir kullanıcı bildirimi gerekmiyor.
                // Sayfa yenileme kaldırıldı.
            })
            .catch(error => {
                console.error('Proje eklenirken hata oluştu:', error);
                 // Kullanıcı zaten bilgilendirildi, ancak konsola hata basmaya devam edilebilir
                // veya farklı bir hata yönetimi yapılabilir.
                // alert('Proje eklenirken bir hata oluştu!'); // Bu satır isteğe bağlı olarak kalabilir veya kaldırılabilir.
            });
        });
    });
</script>



</body>

</html>