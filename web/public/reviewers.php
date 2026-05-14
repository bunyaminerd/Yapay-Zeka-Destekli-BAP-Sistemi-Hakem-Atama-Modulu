<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login');
    exit();
}

$username = $_SESSION['username'];
$firstLetter = strtoupper($username[0]);

// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get reviewers from database with pagination
try {
    // Count total reviewers
    $countStmt = $pdo->query("SELECT COUNT(*) FROM reviewers");
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Get reviewers for current page
    $stmt = $pdo->prepare("SELECT id, name, expertise, past_projects FROM reviewers ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviewers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM projects");
    $totalProjects = $stmt1->fetchColumn();

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM reviewers");
    $totalReviewers = $stmt2->fetchColumn();

    $stmt3 = $pdo->query("SELECT COUNT(*) FROM assignments");
    $totalAssignments = $stmt3->fetchColumn();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAP Sistemi - Hakemler</title>
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
            --light-primary-color: #f1f3f5;
            --light-secondary-color: #ffffff;
            --light-text-color: #212529;
            --light-grey-color: #6c757d;
            --light-card-bg: #ffffff;
            --light-accent-color: #0d6efd;
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
            min-height: calc(100vh - 70px); /* Subtract navbar height */
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
        
        .reviewer-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .reviewer-item {
            background: rgba(32, 32, 32, 0.5);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--accent-color);
        }
        
        .reviewer-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .reviewer-id {
            display: inline-block;
            background: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .reviewer-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .reviewer-expertise {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 12px;
        }
        
        .expertise-badge {
            display: inline-block;
            background: rgba(71, 118, 230, 0.1);
            color: var(--accent-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .past-projects {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--grey-color);
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
        
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        
        .page-link {
            background: rgba(32, 32, 32, 0.8);
            border-color: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
        }
        
        .page-link:hover {
            background: rgba(71, 118, 230, 0.1);
            color: var(--accent-color);
        }
        
        .page-item.active .page-link {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .page-item.disabled .page-link {
            background: rgba(32, 32, 32, 0.5);
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
                <h1 class="page-title">Hakemler</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Hakemler</li>
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
                                    <a href="admin_panel">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a href="projects">
                                        <i class="fas fa-file-alt"></i> Projeler
                                    </a>
                                </li>
                                <li>
                                    <a href="reviewers" class="active">
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
                    
                    <!-- Reviewers List -->
                    <div class="content-card">
                        <h2 class="card-title"><i class="fas fa-users"></i> Hakem Listesi</h2>
                        
                        <?php if (count($reviewers) > 0): ?>
                            <ul class="reviewer-list">
                                <?php foreach ($reviewers as $reviewer): ?>
                                    <li class="reviewer-item">
                                        <span class="reviewer-id">#<?php echo htmlspecialchars($reviewer['id']); ?></span>
                                        <h3 class="reviewer-name"><?php echo htmlspecialchars($reviewer['name']); ?></h3>
                                        <div class="reviewer-expertise">
                                            <?php 
                                            $expertiseAreas = explode(',', $reviewer['expertise']);
                                            foreach ($expertiseAreas as $area) {
                                                if (trim($area) !== '') {
                                                    echo '<span class="expertise-badge">' . htmlspecialchars(trim($area)) . '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="past-projects">
                                            <strong>Geçmiş Projeler:</strong> <?php echo htmlspecialchars($reviewer['past_projects']); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-slash fa-3x mb-3 text-muted"></i>
                                <p>Henüz hakem bulunmamaktadır.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pagination with limited page numbers -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" <?php echo ($page <= 1) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Önceki</a>
                                    </li>
                                    
                                    <?php
                                    // Show limited number of pages around current page
                                    $startPage = max(1, $page - 5);
                                    $endPage = min($totalPages, $page + 5);
                                    
                                    // Always show first page
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }
                                    
                                    // Show page numbers
                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        echo '<li class="page-item ' . (($page == $i) ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                                    }
                                    
                                    // Always show last page
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" <?php echo ($page >= $totalPages) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Sonraki</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
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
            const allCards = document.querySelectorAll('.stats-card, .content-card, .sidebar, .project-item, .reviewer-item');
            const pageLinks = document.querySelectorAll('.page-link');
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            const breadcrumbLinks = document.querySelectorAll('.breadcrumb-item a');
            const breadcrumbActive = document.querySelector('.breadcrumb-item.active');
            const expertiseBadges = document.querySelectorAll('.expertise-badge');

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

                expertiseBadges.forEach(badge => {
                    badge.style.backgroundColor = 'rgba(71, 118, 230, 0.1)';
                    badge.style.color = '#4776E6';
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
                    if (card.classList.contains('reviewer-item')) {
                        card.style.borderLeftColor = '#0d6efd';
                    }
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

                expertiseBadges.forEach(badge => {
                    badge.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
                    badge.style.color = '#0d6efd';
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
    });
    </script>
</body>
</html>