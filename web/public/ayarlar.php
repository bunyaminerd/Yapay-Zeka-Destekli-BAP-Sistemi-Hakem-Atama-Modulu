<?php
session_start();
require_once __DIR__ . '/../config.php';

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['username'])) {
    header('Location: login');
    exit();
}

$username = $_SESSION['username'];
$firstLetter = strtoupper(mb_substr($username, 0, 1, 'UTF-8'));

// Diğer sayfalardan istatistikler buraya da eklenebilir, şimdilik sade tutalım.
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAP Sistemi - Ayarlar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #121212; /* Koyu tema varsayılanı */
            --secondary-color: #202020;
            --accent-color: #4776E6;
            --text-color: #ffffff;
            --grey-color: #757575;
            --card-bg: rgba(32, 32, 32, 0.8);
            --success-color: #1db954;
            --warning-color: #ff9800;
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

        .sidebar-container {
            position: sticky;
            top: 20px;
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

        .setting-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .setting-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .setting-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .theme-buttons .btn {
            margin-right: 10px;
            min-width: 120px;
        }

        .slider-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .slider-container input[type="range"] {
            flex-grow: 1;
        }

        .slider-container span {
            min-width: 20px; /* Ensure the number doesn't shift the layout */
            text-align: right;
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
                           <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="login.php?logout=true"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
            <div class="dashboard-header">
                <h1 class="page-title">Sistem Ayarları</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_panel">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Ayarlar</li>
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
                                    <a href="ayarlar" class="active">
                                        <i class="fas fa-cog"></i> Ayarlar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-9">
                    <div class="content-card">
                        <h2 class="card-title"><i class="fas fa-palette"></i> Tema Ayarları</h2>
                        
                        <div class="setting-section">
                            <h5 class="setting-title"><i class="fas fa-palette me-2"></i>Tema Ayarları</h5>
                            <p class="text-muted small mb-3">Sistem genelinde kullanılacak temayı seçin.</p>
                            <div class="theme-buttons">
                                <button id="set-light-theme-btn" class="btn btn-outline-light" onclick="setTheme('light')"><i class="fas fa-sun me-2"></i>Açık Tema</button>
                                <button id="set-dark-theme-btn" class="btn btn-dark" onclick="setTheme('dark')"><i class="fas fa-moon me-2"></i>Koyu Tema</button>
                            </div>
                        </div>

                        <div class="setting-section">
                            <h5 class="setting-title"><i class="fas fa-users-cog me-2"></i>Hakem Tavsiye Sayısı Hassasiyeti</h5>
                            <p class="text-muted small mb-3">Atamalar sayfasında her bir proje için önerilecek maksimum hakem sayısını belirleyin.</p>
                            <div class="slider-container">
                                <input type="range" class="form-range" min="1" max="10" id="reviewerSuggestionCount" oninput="updateSliderValue(this.value)" onchange="saveReviewerSuggestionCount(this.value)">
                                <span id="reviewerSuggestionCountValue">5</span>
                            </div>
                        </div>

                        <!-- YENİ: Yapay Zeka Model Seçimi -->
                        <div class="setting-section">
                            <h5 class="setting-title"><i class="fas fa-brain me-2"></i>Yapay Zeka Model Seçimi</h5>
                            <p class="text-muted small mb-3">Hakem önerileri için kullanılacak yapay zeka modelini seçin.</p>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="aiModel" id="fastTextModel" value="fasttext" checked>
                                <label class="form-check-label" for="fastTextModel">
                                    FastText (Varsayılan)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="aiModel" id="miniLmModel" value="minilm">
                                <label class="form-check-label" for="miniLmModel">
                                    MiniLM L12 v2
                                </label>
                            </div>
                        </div>
                        <!-- YENİ BİTİŞ -->

                        <!-- Gelecekteki ayarlar buraya eklenebilir -->
                        <!--
                        <div class="setting-section">
                            <h3 class="setting-title">Bildirim Ayarları</h3>
                            <p>E-posta bildirimlerini yönetin.</p>
                            // ... ayar seçenekleri ...
                        </div>
                        -->

                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // applyUserInterfaceTheme'i global scope'ta tanımla, böylece DOMContentLoaded dışından erişilebilir.
    let applyMainThemeLogic;

    document.addEventListener('DOMContentLoaded', function() {
        const darkThemeBtn = document.getElementById('set-dark-theme-btn');
        const lightThemeBtn = document.getElementById('set-light-theme-btn');
        const root = document.documentElement;

        const updateThemeButtons = (theme) => {
            if (!lightThemeBtn || !darkThemeBtn) return; // Butonlar yoksa çık

            if (theme === 'dark') {
                // Koyu tema aktif
                // Koyu Tema Butonu (aktif): btn-primary stilini almalı
                darkThemeBtn.classList.add('active', 'btn-primary');
                darkThemeBtn.classList.remove('btn-outline-light', 'btn-outline-dark', 'btn-dark'); // Eski/çakışan sınıfları temizle
                darkThemeBtn.style.color = ''; // Renkleri btn-primary belirlesin
                darkThemeBtn.style.borderColor = ''; // Kenarlık rengini btn-primary belirlesin

                // Açık Tema Butonu (pasif): btn-outline-light stilini almalı (koyu arka planda açık renk yazı)
                lightThemeBtn.classList.remove('active', 'btn-primary', 'btn-dark'); // Eski/çakışan sınıfları temizle
                lightThemeBtn.classList.add('btn-outline-light');
                lightThemeBtn.style.color = ''; // Renkleri btn-outline-light belirlesin
                lightThemeBtn.style.borderColor = ''; // Kenarlık rengini btn-outline-light belirlesin

            } else { // Açık tema aktif
                // Açık Tema Butonu (aktif): btn-primary stilini almalı
                lightThemeBtn.classList.add('active', 'btn-primary');
                lightThemeBtn.classList.remove('btn-outline-light', 'btn-outline-dark', 'btn-dark'); // Eski/çakışan sınıfları temizle
                lightThemeBtn.style.color = ''; // Renkleri btn-primary belirlesin
                lightThemeBtn.style.borderColor = ''; // Kenarlık rengini btn-primary belirlesin
                
                // Koyu Tema Butonu (pasif): btn-outline-dark stilini almalı (açık arka planda koyu renk yazı)
                darkThemeBtn.classList.remove('active', 'btn-primary', 'btn-dark'); // *** ÖNEMLİ: btn-dark sınıfını kaldır ***
                darkThemeBtn.classList.add('btn-outline-dark');
                darkThemeBtn.style.color = '#212529'; // Okunurluk için metin rengini ayarla
                darkThemeBtn.style.borderColor = '#212529'; // Okunurluk için kenarlık rengini ayarla
            }
        };

        // Bu, DOMContentLoaded içindeki ana ve kapsamlı applyTheme fonksiyonudur.
        applyMainThemeLogic = (theme) => {
            // localStorage.setItem('theme', theme); // setTheme fonksiyonu bunu zaten yapıyor.
            root.setAttribute('data-bs-theme', theme);
            updateThemeButtons(theme);

            const navbar = document.querySelector('.navbar');
            const navbarBrand = document.querySelector('.navbar-brand');
            const navLinks = document.querySelectorAll('.navbar .nav-link'); 
            const navbarDropdownMenus = document.querySelectorAll('.navbar .dropdown-menu');
            const allCards = document.querySelectorAll('.stats-card, .content-card, .sidebar, .project-item, .reviewer-item, #reviewer-list .reviewer-card, .modal-content'); 
            const pageLinks = document.querySelectorAll('.page-link');
            const searchInputs = document.querySelectorAll('.search-input');
            const navTabsLinks = document.querySelectorAll('.nav-tabs .nav-link');
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            const breadcrumbLinks = document.querySelectorAll('.breadcrumb-item a');
            const breadcrumbActive = document.querySelector('.breadcrumb-item.active');
            const keywordBadges = document.querySelectorAll('.keyword-badge, .expertise-badge');
            const statusBadges = document.querySelectorAll('.status-badge');
            const matchingScores = document.querySelectorAll('.matching-score');
            const formCheckLabels = document.querySelectorAll('.form-check-label'); // Ayarlar sayfasına özel
            const textMutedElements = document.querySelectorAll('.text-muted.small'); // Ayarlar sayfasına özel


            if (theme === 'dark') {
                root.style.setProperty('--primary-color', '#121212');
                root.style.setProperty('--secondary-color', '#202020');
                root.style.setProperty('--text-color', '#ffffff');
                root.style.setProperty('--grey-color', '#757575');
                root.style.setProperty('--card-bg', 'rgba(32, 32, 32, 0.8)');
                root.style.setProperty('--success-color', '#1db954');
                root.style.setProperty('--warning-color', '#ff9800');
                root.style.setProperty('--accent-color', '#4776E6'); 

                if (navbar) {
                    navbar.style.background = 'rgba(18, 18, 18, 0.9)';
                    navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.3)';
                }
                if (navbarBrand) navbarBrand.style.color = '#ffffff';
                navLinks.forEach(link => link.style.color = '#ffffff');
                navbarDropdownMenus.forEach(menu => {
                    menu.classList.add('dropdown-menu-dark');
                    menu.style.backgroundColor = ''; 
                    menu.querySelectorAll('.dropdown-item').forEach(item => item.style.color = '');
                    menu.querySelectorAll('.dropdown-header').forEach(header => header.style.color = '');
                });
                 allCards.forEach(card => { card.style.backgroundColor = 'var(--card-bg)'; card.style.color = 'var(--text-color)';});
                 if(sidebarLinks) sidebarLinks.forEach(link => {
                    link.style.color = 'var(--text-color)';
                     if(link.classList.contains('active')){
                        link.style.backgroundColor = 'rgba(71, 118, 230, 0.1)';
                        link.style.color = 'var(--accent-color)';
                    } else {
                        link.style.backgroundColor = 'transparent';
                    }
                 });
                 if(breadcrumbLinks) breadcrumbLinks.forEach(bLink => bLink.style.color = 'var(--accent-color)');
                 if(breadcrumbActive) breadcrumbActive.style.color = 'var(--grey-color)';
                 if(formCheckLabels) formCheckLabels.forEach(label => label.style.color = 'var(--text-color)');
                 if(textMutedElements) textMutedElements.forEach(el => el.style.color = 'var(--grey-color)');


            } else { // Açık Tema için güncellenmiş stiller
                root.style.setProperty('--primary-color', '#f1f3f5'); 
                root.style.setProperty('--secondary-color', '#ffffff'); 
                root.style.setProperty('--text-color', '#212529');    
                root.style.setProperty('--grey-color', '#495057'); // Güncellenmiş değer
                root.style.setProperty('--card-bg', '#ffffff'); 
                root.style.setProperty('--success-color', '#198754');
                root.style.setProperty('--warning-color', '#ffc107');
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
                    menu.querySelectorAll('.dropdown-item').forEach(item => item.style.color = '#212529');
                    menu.querySelectorAll('.dropdown-header').forEach(header => header.style.color = '#6c757d');
                });

                allCards.forEach(card => {
                    card.style.backgroundColor = 'var(--card-bg)'; 
                    card.style.color = 'var(--text-color)'; 
                    if (card.classList.contains('project-item') || card.classList.contains('reviewer-item')) {
                        card.style.borderLeftColor = 'var(--accent-color)';
                    }
                    if(card.classList.contains('modal-content')) {
                         card.style.border = '1px solid #dee2e6'; 
                    }
                });

                if(sidebarLinks) sidebarLinks.forEach(link => {
                    link.style.color = 'var(--text-color)';
                    if(link.classList.contains('active')){
                        link.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
                        link.style.color = 'var(--accent-color)';
                    } else {
                         link.style.backgroundColor = 'transparent';
                    }
                });
                
                if(pageLinks) pageLinks.forEach(pl => { /* ... */ });
                if(searchInputs) searchInputs.forEach(si => { /* ... */ });
                if(navTabsLinks) navTabsLinks.forEach(tl => { /* ... */ });
                if(breadcrumbLinks) breadcrumbLinks.forEach(bLink => bLink.style.color = 'var(--accent-color)');
                if(breadcrumbActive) breadcrumbActive.style.color = 'var(--grey-color)';
                if(keywordBadges) keywordBadges.forEach(kb => { /* ... */ });
                if(statusBadges) statusBadges.forEach(sb => { /* ... */ });
                if(matchingScores) matchingScores.forEach(ms => { /* ... */ });
                if(formCheckLabels) formCheckLabels.forEach(label => label.style.color = 'var(--text-color)');
                if(textMutedElements) textMutedElements.forEach(el => el.style.color = 'var(--grey-color)');
            }
        };

        // const darkThemeBtn ve lightThemeBtn yukarıda zaten tanımlı.
        // applyTheme, darkThemeBtn ve lightThemeBtn'ye DOMContentLoaded scope'u üzerinden erişir.

        const savedTheme = localStorage.getItem('theme') || 'dark'; // Varsayılan olarak koyu tema
        applyMainThemeLogic(savedTheme);

        // Mevcut darkThemeBtn ve lightThemeBtn event listener'larını kaldırıyoruz,
        // çünkü butonların onclick="setTheme('tema')" özelliği var.
        // if (darkThemeBtn && lightThemeBtn) {
        //     darkThemeBtn.addEventListener('click', (e) => { /* ... */ });
        //     lightThemeBtn.addEventListener('click', (e) => { /* ... */ });
        // }

        const reviewerSlider = document.getElementById('reviewerSuggestionCount');
        if (reviewerSlider) {
            const savedReviewerCount = localStorage.getItem('reviewerSuggestionCount') || 5;
            reviewerSlider.value = savedReviewerCount;
            updateSliderValue(savedReviewerCount); // Bu fonksiyon global olmalı
            reviewerSlider.addEventListener('input', function() { updateSliderValue(this.value); });
            reviewerSlider.addEventListener('change', function() { saveReviewerSuggestionCount(this.value); });
        }
        
        loadAiModelSelection(); // Bu fonksiyon global olmalı
        const modelRadios = document.getElementsByName('aiModel');
        modelRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                saveAiModelSelection(this.value); // Bu fonksiyon global olmalı
            });
        });
    }); // DOMContentLoaded sonu

    // Global setTheme fonksiyonu, şimdi DOMContentLoaded içinde tanımlanan applyMainThemeLogic'i çağıracak.
    function setTheme(theme) {
        localStorage.setItem('theme', theme);
        if (typeof applyMainThemeLogic === 'function') {
            applyMainThemeLogic(theme);
        }
    }

    function updateSliderValue(value) {
        const el = document.getElementById('reviewerSuggestionCountValue');
        if (el) el.textContent = value;
    }

    function saveReviewerSuggestionCount(value) {
        localStorage.setItem('reviewerSuggestionCount', value);
    }

    function saveAiModelSelection(model) {
        localStorage.setItem('selectedAiModel', model);
        // alert("Yapay zeka modeli '" + model + "' olarak ayarlandı."); // İsteğe bağlı
    }

    function loadAiModelSelection() {
        const savedModel = localStorage.getItem('selectedAiModel') || 'fasttext'; 
        const modelRadios = document.getElementsByName('aiModel');
        for (let i = 0; i < modelRadios.length; i++) {
            if (modelRadios[i].value === savedModel) {
                modelRadios[i].checked = true;
                break;
            }
        }
    }
    </script>
</body>
</html> 