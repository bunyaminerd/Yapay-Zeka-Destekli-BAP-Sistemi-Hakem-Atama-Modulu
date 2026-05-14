<?php
session_start();
require_once __DIR__ . '/../config.php'; // Veritabanı bağlantı dosyası

// Eğer kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['username'])) {
    header('Location: login');
    exit();
}

// Kullanıcı bilgilerini çekelim
$username = $_SESSION['username'];
$firstLetter = strtoupper(mb_substr($username, 0, 1, 'UTF-8'));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAP Sistemi - Kullanım Talimatları</title>
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

        .guide-section {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .guide-section:last-child {
            border-bottom: none;
        }

        .guide-subtitle {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .step-item {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .step-item .step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 20px;
            height: 20px;
            background-color: var(--accent-color);
            border-radius: 50%;
            color: white;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .guide-list {
            padding-left: 20px;
        }

        .guide-list li {
            margin-bottom: 10px;
        }

        .guide-tip {
            background: rgba(71, 118, 230, 0.1);
            border-left: 3px solid var(--accent-color);
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }

        .guide-tip i {
            color: var(--accent-color);
            margin-right: 8px;
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
                <h1 class="page-title">Kullanım Talimatları</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_panel">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Kullanım Talimatları</li>
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
                                    <a href="usage_guide" class="active">
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
                    <!-- Guide Content -->
                    <div class="content-card">
                        <h2 class="card-title"><i class="fas fa-book-open"></i> Kullanım Talimatları</h2>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">1. Giriş ve Genel Bakış</h3>
                            <p>BAP Hakem Atama Modülü, Bilimsel Araştırma Projelerine (BAP) uygun hakemlerin atanması sürecini kolaylaştırmak için tasarlanmıştır. Sistem, proje ve hakem bilgilerini yönetmenize, yapay zeka destekli ve manuel olarak hakem atamaları yapmanıza olanak tanır.</p>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">2. Genel Arayüz ve Navigasyon</h3>
                            <p>Sistem arayüzü ana olarak iki bölümden oluşur:</p>
                            <ul class="guide-list">
                                <li><strong>Sol Kenar Çubuğu (Sidebar):</strong> Sistemin ana modüllerine hızlı erişim sağlar.
                                    <ul>
                                        <li><strong><i class="fas fa-tachometer-alt"></i> Dashboard (Admin Paneli):</strong> Sisteme genel bakış, istatistikler ve yeni proje/hakem ekleme formlarını içerir.</li>
                                        <li><strong><i class="fas fa-file-alt"></i> Projeler:</strong> Kayıtlı tüm projeleri listeler.</li>
                                        <li><strong><i class="fas fa-users"></i> Hakemler:</strong> Kayıtlı tüm hakemleri listeler.</li>
                                        <li><strong><i class="fas fa-tasks"></i> Atamalar:</strong> Projelere hakem atama işlemlerinin yapıldığı ana modüldür.</li>
                                        <li><strong><i class="fas fa-question-circle"></i> Kullanım Talimatları:</strong> Bu sayfadır.</li>
                                        <li><strong><i class="fas fa-cog"></i> Ayarlar:</strong> Sistem görünümü (tema) ve yapay zeka öneri ayarlarını yapılandırmanızı sağlar.</li>
                                    </ul>
                                </li>
                                <li><strong>Üst Navigasyon Çubuğu (Navbar):</strong> Sağ üst köşede kullanıcı adınızı ve çıkış yapma seçeneğini içerir.</li>
                            </ul>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">3. Admin Paneli (Dashboard)</h3>
                            <p>Admin paneli, sisteme ilk giriş yaptığınızda veya kenar çubuğundan "Dashboard" seçeneğine tıkladığınızda erişebileceğiniz ana yönetim ekranıdır.</p>
                            <ul class="guide-list">
                                <li><strong>İstatistikler:</strong> Sistemdeki toplam proje, toplam hakem ve yapılmış toplam atama sayılarını gösteren kartlar bulunur.</li>
                                <li><strong>Yeni Proje Ekleme:</strong>
                                    <ol>
                                        <li>"Yeni Proje Ekle" bölümündeki forma proje başlığını ve anahtar kelimelerini (virgülle ayırarak) girin.</li>
                                        <li>"Proje Kaydet" butonuna tıklayın. Proje sisteme eklenecektir.</li>
                                    </ol>
                                </li>
                                <li><strong>Yeni Hakem Ekleme:</strong>
                                    <ol>
                                        <li>"Yeni Hakem Ekle" bölümündeki forma hakemin adını, soyadını, uzmanlık alanlarını (virgülle ayırarak) ve (isteğe bağlı olarak) daha önce değerlendirdiği proje bilgilerini girin.</li>
                                        <li>"Hakem Kaydet" butonuna tıklayın. Hakem sisteme eklenecektir.</li>
                                    </ol>
                                </li>
                            </ul>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">4. Projeler Sayfası</h3>
                            <p>"Projeler" menüsünden erişilir. Bu sayfada sistemde kayıtlı tüm projeler listelenir.</p>
                            <ul class="guide-list">
                                <li>Projeler sayfalama kullanılarak listelenir. Sayfalar arasında geçiş yapabilirsiniz.</li>
                                <li>Her proje için ID, başlık ve anahtar kelimeler görüntülenir.</li>
                            </ul>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">5. Hakemler Sayfası</h3>
                            <p>"Hakemler" menüsünden erişilir. Bu sayfada sistemde kayıtlı tüm hakemler listelenir.</p>
                            <ul class="guide-list">
                                <li>Hakemler sayfalama kullanılarak listelenir. Sayfalar arasında geçiş yapabilirsiniz.</li>
                                <li>Her hakem için ID, isim, uzmanlık alanları ve (varsa) geçmişte değerlendirdiği projeler hakkında bilgi görüntülenir.</li>
                            </ul>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">6. Hakem Atama İşlemleri (Atamalar Sayfası)</h3>
                            <p>"Atamalar" menüsünden erişilen bu modül, projelere hakem atamak için kullanılır.</p>
                            <ul class="guide-list">
                                <li><strong>Sekmeler:</strong>
                                    <ul>
                                        <li><strong>Atama Bekleyen Projeler:</strong> Henüz 2 hakem atanmamış projeler listelenir. Proje yanında kaç hakem atandığı (örn: 1/2) veya "Atama Bekliyor" bilgisi gösterilir.</li>
                                        <li><strong>Atama Yapılmış Projeler:</strong> En az 2 hakem atanmış projeler listelenir.</li>
                                    </ul>
                                </li>
                                <li><strong>Proje Arama:</strong> Her iki sekmede de bulunan arama kutusuna proje başlığından bir kelime yazarak hızlıca proje arayabilirsiniz.</li>
                                <li><strong>Hakem Atama Modalı'nı Açma:</strong> Listelenen projelerden herhangi birine tıklayarak o projeye özel hakem atama modalını açabilirsiniz.</li>
                            </ul>
                            
                            <h4 class="guide-subtitle mt-3">6.1. Hakem Atama Modalı</h4>
                            <p>Bir projeye tıkladığınızda açılan bu modal, o projeye hakem atamanız için çeşitli araçlar sunar.</p>
                            <ul class="guide-list">
                                <li><strong>Proje Bilgileri:</strong> Modalın üst kısmında proje başlığı ve atanmış hakem sayısı (örn: 1/2 Hakem Atandı) görüntülenir. Altında ise projenin anahtar kelimeleri yer alır.</li>
                                <li><strong>Yapay Zeka Hakem Önerileri:</strong>
                                    <ul>
                                        <li>Sistem, projenin anahtar kelimeleri ile hakemlerin uzmanlık alanları arasındaki benzerliği analiz ederek potansiyel hakemler önerir.</li>
                                        <li>Önerilen her hakemin yanında bir "Benzerlik Skoru" (yüzde olarak) gösterilir. Bu skor, hakemin uzmanlık alanlarının projeyle ne kadar eşleştiğini belirtir.</li>
                                        <li>Kullanılan yapay zeka modeli (FastText veya MiniLM) ve gösterilecek öneri sayısı "Ayarlar" sayfasından değiştirilebilir.</li>
                                        <li>Önerilen bir hakemi atamak için yanındaki "Ata" butonuna tıklayın.</li>
                                    </ul>
                                </li>
                                <li><strong>Manuel Hakem Arama:</strong>
                                    <ol>
                                        <li><strong>Arama Türü Seçimi:</strong> "Arama Türü" dropdown menüsünden "Uzmanlığa Göre" veya "İsme Göre" seçeneklerinden birini seçin.</li>
                                        <li>Arama kutusuna seçtiğiniz türe göre (uzmanlık alanı veya hakem adı) arama terimini girin.</li>
                                        <li>"Ara" butonuna tıklayın.</li>
                                        <li>Arama sonuçları aşağıda listelenecektir. Uygun gördüğünüz hakemi yanındaki "Ata" butonuna tıklayarak atayabilirsiniz.</li>
                                    </ol>
                                </li>
                                <li><strong>Atama ve İptal İşlemleri:</strong>
                                    <ul>
                                        <li>Bir hakemi "Ata" butonuna tıkladıktan sonra, "Hakem atamasını onaylıyorum." kutucuğunu işaretleyip "Onaylı Ata" butonuna basmanız gerekmektedir.</li>
                                        <li>Bir projeye en fazla 2 hakem atanabilir. Limit dolduğunda diğer "Ata" butonları pasif hale gelir.</li>
                                        <li>Eğer bir hakem projeye zaten atanmışsa, kartında "Atandı" yazar ve yanında "İptal Et" butonu çıkar. Bu butona tıklayarak ve onay vererek atamayı iptal edebilirsiniz.</li>
                                        <li>Bir projeye 2 hakem atandığında modal otomatik olarak kapanabilir ve sayfa yenilenebilir.</li>
                                    </ul>
                                </li>
                            </ul>
                            <div class="guide-tip">
                                <strong><i class="fas fa-info-circle"></i> İpucu:</strong> Atama modalında hem yapay zeka önerilerini hem de manuel aramayı kullanarak en uygun hakemleri bulmaya çalışın.
                            </div>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">7. Ayarlar Sayfası</h3>
                            <p>"Ayarlar" menüsünden erişilir. Bu sayfada sistemin bazı davranışlarını kişiselleştirebilirsiniz.</p>
                            <ul class="guide-list">
                                <li><strong>Görünüm Ayarları (Tema Seçimi):</strong>
                                    <ul>
                                        <li>"Açık Mod" veya "Koyu Mod" butonlarına tıklayarak sistemin genel tema rengini değiştirebilirsiniz. Seçiminiz tarayıcınızda saklanır.</li>
                                    </ul>
                                </li>
                                <li><strong>Yapay Zeka Ayarları:</strong>
                                    <ul>
                                        <li><strong>Model Seçimi:</strong> "Hakem Öneri Modeli" dropdown menüsünden "FastText" veya "MiniLM L12 v2" algoritmalarından birini seçebilirsiniz. Bu, "Atamalar" sayfasındaki yapay zeka önerilerinin hangi model kullanılarak üretileceğini belirler.</li>
                                        <li><strong>Öneri Sayısı:</strong> "Maksimum Hakem Önerisi" kaydırıcısını kullanarak yapay zeka tarafından aynı anda en fazla kaç hakemin önerileceğini (1 ile 10 arası) ayarlayabilirsiniz.</li>
                                        <li>Bu ayarlar da tarayıcınızda saklanır ve atama modalındaki önerileri etkiler.</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <div class="guide-section">
                            <h3 class="guide-subtitle">8. Çıkış Yapma</h3>
                            <p>Sistemden güvenli bir şekilde çıkış yapmak için:</p>
                            <ol class="guide-list">
                                <li>Sağ üst köşede bulunan kullanıcı adınıza tıklayarak dropdown menüyü açın.</li>
                                <li>"Çıkış Yap" seçeneğine tıklayın. Giriş sayfasına yönlendirileceksiniz.</li>
                            </ol>
                        </div>

                        <p class="mt-4">Bu kılavuzun BAP Hakem Atama Modülü'nü etkin bir şekilde kullanmanıza yardımcı olacağını umuyoruz. Herhangi bir sorunla karşılaşırsanız veya ek yardıma ihtiyacınız olursa lütfen sistem yöneticinize başvurun.</p>
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
            const allCards = document.querySelectorAll('.content-card, .sidebar');
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            const breadcrumbLinks = document.querySelectorAll('.breadcrumb-item a');
            const breadcrumbActive = document.querySelector('.breadcrumb-item.active');
            const guideSection = document.querySelectorAll('.guide-section');
            const guideTips = document.querySelectorAll('.guide-tip');

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

                guideSection.forEach(section => {
                    section.style.borderBottomColor = 'rgba(255, 255, 255, 0.1)';
                });

                guideTips.forEach(tip => {
                    tip.style.backgroundColor = 'rgba(71, 118, 230, 0.1)';
                    tip.style.borderLeftColor = '#4776E6';
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

                guideSection.forEach(section => {
                    section.style.borderBottomColor = 'rgba(0, 0, 0, 0.1)';
                });

                guideTips.forEach(tip => {
                    tip.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
                    tip.style.borderLeftColor = '#0d6efd';
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