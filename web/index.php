<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kütahya Sağlık Bilimleri Üniversitesi - BAP Sistemi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <style>
        :root {
            --primary-color: #005e85; /* KSBÜ Mavi (Mevcut) */
            --secondary-color: #6bbd45; /* KSBÜ Yeşil (Mevcut) */
            --accent-color: #e94e24; /* Vurgu Rengi (Turuncu) - Gerekirse değiştirilebilir */
            --text-color: #333333;
            --light-text: #ffffff;
            --light-gray: #f5f5f5;
            --dark-gray: #6c757d;
            --border-color: #dee2e6;
        }
        
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: #fff;
            overflow-x: hidden;
            width: 100%;
            min-height: 100vh;
        }

        /* Üst bilgi çubuğu */
        .top-bar {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 5px 0;
            font-size: 0.85rem;
        }
        
        .top-bar a {
            color: var(--light-text);
            text-decoration: none;
            margin-left: 15px;
        }
        
        .top-bar a:hover {
            text-decoration: underline;
        }
        
        /* Ana Navigasyon */
        .navbar {
            background-color: var(--light-text);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 8px 0;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            padding: 0;
            margin-right: 30px;
        }
        
        .navbar-brand img {
            height: 150px;
            width: 300px;
            max-width: 100%;
            object-fit: contain;
        }
        
        .nav-link {
            color: var(--text-color);
            font-weight: 500;
            padding: 10px 15px !important;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }
        
        .navbar-toggler {
            border: none;
            outline: none;
        }
        
        .login-btn {
            background: var(--primary-color); /* Ana KSBÜ Rengi */
            color: white;
            border: none;
            padding: 10px 25px; 
            border-radius: 5px; 
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        
        .login-btn:hover {
            background: #004c6d; /* Ana rengin biraz koyusu */
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
        }
        
        /* Ana içerik */
        .content-wrapper {
            padding-top: 20px;
            padding-bottom: 60px;
        }
        
        /* Hero Banner */
        .hero-section {
            background: linear-gradient(120deg, rgba(0,94,133,0.9) 0%, rgba(107,189,69,0.9) 100%), url('https://via.placeholder.com/1920x800');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            border-radius: 0;
            margin-bottom: 60px;
            text-align: center;
            position: relative;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            line-height: 1.6;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 1.15rem;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }
        
        .hero-btn:hover {
            background-color: var(--accent-color);
            opacity: 0.9;
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }
        
        /* Bölümler */
        .section {
            padding: 60px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .section-title {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 40px;
            position: relative;
            color: var(--primary-color);
            text-align: center;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color); /* KSBÜ Mavisi ile değiştirildi */
            border-radius: 2px;
        }
        
        /* Özellik kartları */
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 35px;
            height: 100%;
            transition: all 0.35s ease-in-out;
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
            border-color: var(--secondary-color);
        }
        
        .feature-icon {
            font-size: 40px;
            margin-bottom: 20px;
            color: var(--secondary-color); /* KSBÜ Yeşili ile değiştirildi */
            display: inline-block;
            background-color: rgba(107, 189, 69, 0.1); /* İkon arkaplanı KSBÜ yeşiline uyumlu hale getirildi */
            width: 80px;
            height: 80px;
            line-height: 80px;
            text-align: center;
            border-radius: 50%;
        }
        
        .feature-title {
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.3rem;
            color: var(--primary-color); /* Başlıklar ana renkte kalabilir */
        }
        
        .feature-text {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        /* Süreç adımları */
        .process-step {
            position: relative;
            padding: 30px 35px;
            background: white;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.35s ease-in-out;
        }
        
        .process-step:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.07);
            border-color: var(--primary-color);
            transform: translateY(-5px);
        }
        
        .step-number {
            position: absolute;
            top: -20px;
            left: 30px;
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: white;
        }
        
        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            margin-top: 10px;
            color: var(--primary-color);
        }
        
        .step-description {
            color: #555;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        
        /* CTA Bölümü */
        .cta-section {
            background: linear-gradient(120deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 10px;
            padding: 60px 40px;
            margin-top: 60px;
            text-align: center;
            color: white;
        }
        
        .cta-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
        }
        
        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .cta-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            color: white;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background-color: #333;
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-logo {
            margin-bottom: 20px;
        }
        
        .footer-logo img {
            height: 60px;
        }
        
        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .footer-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--secondary-color); /* Footer başlık çizgisi KSBÜ Yeşili */
            border-radius: 1.5px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--secondary-color); /* Link hover KSBÜ Yeşili */
            padding-left: 5px;
        }
        
        .footer-contact p {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
        }
        
        .footer-contact i {
            margin-right: 10px;
            color: var(--secondary-color); /* İletişim ikonları KSBÜ Yeşili */
            font-size: 18px;
            margin-top: 4px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
        }
        
        .social-icons {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
        }
        
        .social-icons li {
            margin-right: 15px;
        }
        
        .social-icons a {
            display: block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background-color: var(--secondary-color); /* Sosyal medya ikon hover KSBÜ Yeşili */
            transform: translateY(-3px);
        }
        
        /* Responsive tasarım ayarlamaları */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .footer {
                text-align: center;
            }
            
            .footer-title:after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .social-icons {
                justify-content: center;
                margin-bottom: 20px;
            }
            
            .footer-contact p {
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .hero-section {
                padding: 60px 0;
            }
            
            .step-number {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Üst bilgi çubuğu -->
    <div class="top-bar d-none d-md-block">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <span><i class="fas fa-phone me-2"></i> 0 (274) 260 00 43</span>
                    <span class="ms-3"><i class="fas fa-envelope me-2"></i> info@ksbu.edu.tr</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ana navigasyon -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="logo1.png" alt="Kütahya Sağlık Bilimleri Üniversitesi Logo" style="height: 150px; width: 300px; max-width: 100%; object-fit: contain;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="login" class="login-btn">BAP Sistemi Giriş</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="content-wrapper">
        <!-- Hero Banner -->
        <div class="hero-section">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title" data-aos="fade-up">BAP Bilimsel Araştırma Projeleri</h1>
                    <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                        Kütahya Sağlık Bilimleri Üniversitesi Bilimsel Araştırma Projeleri için yapay zeka destekli, otomatik hakem eşleştirme sistemi ile projelerinizi en uygun hakemlere atayın.
                    </p>
                    <a href="#features" class="hero-btn" data-aos="fade-up" data-aos-delay="200">
                        Daha Fazla Bilgi <i class="fas fa-arrow-down ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container">
            <!-- Özellikler Bölümü -->
            <section id="features" class="section" data-aos="fade-up">
                <h2 class="section-title">BAP Sistemi Özellikleri</h2>
                <div class="row g-4">
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h3 class="feature-title">Doğal Dil İşleme</h3>
                            <p class="feature-text">Gelişmiş metin analizi teknolojileri (Word Embeddings) sayesinde proje özetleriniz ve hakemlerinizin uzmanlık alanları derinlemesine anlaşılır, böylece en doğru eşleşmelerin temeli atılır.</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <h3 class="feature-title">Otomatik Eşleştirme</h3>
                            <p class="feature-text">Akıllı eşleştirme algoritmamız (Cosine Similarity), projeleriniz için %90'ın üzerinde bir isabet oranıyla en uygun hakemleri saniyeler içinde belirleyerek size zaman kazandırır.</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="feature-card text-center">
                            <div class="feature-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h3 class="feature-title">Veri Analizi</h3>
                            <p class="feature-text">Farklı yapay zeka modelleri (MiniLM, FastText) ile yapılan karşılaştırmalı analizler sayesinde, sistemin sunduğu hakem önerilerinin performansını ve şeffaflığını değerlendirebilirsiniz.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Çalışma Süreci Bölümü -->
            <section id="process" class="section" data-aos="fade-up">
                <h2 class="section-title">Çalışma Süreci</h2>
                <div class="row">
                    <div class="col-lg-10 mx-auto">
                        <div class="process-step" data-aos="fade-up">
                            <div class="step-number">1</div>
                            <h3 class="step-title">Veri Analizi ve Hazırlık</h3>
                            <p class="step-description">Proje önerileri ve hakem profilleri analiz edilerek vektör temsillerine dönüştürülür. Bu aşamada doğal dil işleme teknikleri kullanılarak metin içeriklerinden anlamlı bilgiler çıkarılır.</p>
                        </div>
                        
                        <div class="process-step" data-aos="fade-up" data-aos-delay="100">
                            <div class="step-number">2</div>
                            <h3 class="step-title">Benzerlik Hesaplama</h3>
                            <p class="step-description">Proje ve hakem vektörleri arasında Cosine Similarity kullanılarak benzerlik skorları hesaplanır. Bu skorlar, projeler ile hakemlerin uzmanlık alanları arasındaki yakınlığı belirler.</p>
                        </div>
                        
                        <div class="process-step" data-aos="fade-up" data-aos-delay="200">
                            <div class="step-number">3</div>
                            <h3 class="step-title">Optimum Eşleştirme</h3>
                            <p class="step-description">Benzerlik skorlarını temel alan bir algoritma ile projeler, en uygun hakemlere atanır. Bu süreçte sadece adil bir atama olması dikkate alınır.</p>
                        </div>
                        
                        <div class="process-step" data-aos="fade-up" data-aos-delay="300">
                            <div class="step-number">4</div>
                            <h3 class="step-title">Sonuçların İncelenmesi ve Onay</h3>
                            <p class="step-description">Sistem tarafından önerilen eşleştirmeler, BAP yöneticileri tarafından incelenebilir ve gerektiğinde manuel düzenlemeler yapılabilir. Onaylanan atamalar, hakemlere otomatik olarak bildirilir.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- CTA Bölümü -->
            <section class="cta-section" data-aos="fade-up">
                <h2 class="cta-title">Sistemi Hemen Deneyin</h2>
                <p class="cta-text">Kütahya Sağlık Bilimleri Üniversitesi BAP Hakem Atama Modülü ile proje değerlendirme süreçlerinizi hızlandırın ve kalitesini artırın.</p>
                <a href="login" class="cta-button"><i class="fas fa-sign-in-alt me-2"></i> BAP Sistemine Giriş</a>
            </section>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="footer-logo">
                        <img src="logo1.png" alt="KSBÜ Logo" class="img-fluid" style="max-height: 60px; filter: brightness(0) invert(1);">
                    </div>
                    <p>Kütahya Sağlık Bilimleri Üniversitesi, sağlık bilimleri alanında eğitim, araştırma ve topluma hizmet misyonuyla çalışmalarını sürdürmektedir.</p>
                </div>
                
                <div class="col-lg-6 col-md-12">
                    <h3 class="footer-title">İletişim</h3>
                    <div class="footer-contact">
                        <p>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Kütahya Sağlık Bilimleri Üniversitesi Germiyan Yerleşkesi Afyon Yolu üzeri 7. km KÜTAHYA</span>
                        </p>
                        <p>
                            <i class="fas fa-phone-alt"></i>
                            <span>0 (274) 260 00 43</span>
                        </p>
                        <p>
                            <i class="fas fa-envelope"></i>
                            <span>info@ksbu.edu.tr</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© 2023 Kütahya Sağlık Bilimleri Üniversitesi. Tüm Hakları Saklıdır.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
    </script>
</body>
</html>