<?php
session_start();
require_once __DIR__ . '/../config.php';

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['username'])) {
    header('Location: login');
    exit();
}

// YENİ: Hakem Atamasını İptal Etme için AJAX isteği kontrolü
if (isset($_POST['action']) && $_POST['action'] === 'unassign_reviewer' && isset($_POST['project_id']) && isset($_POST['reviewer_id'])) {
    header('Content-Type: application/json');
    $projectIdToUnassign = (int)$_POST['project_id'];
    $reviewerIdToUnassign = (int)$_POST['reviewer_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE project_id = :project_id AND reviewer_id = :reviewer_id");
        $stmt->bindParam(':project_id', $projectIdToUnassign, PDO::PARAM_INT);
        $stmt->bindParam(':reviewer_id', $reviewerIdToUnassign, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Hakem ataması başarıyla iptal edildi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Atama kaydı bulunamadı veya silinemedi.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
    exit();
}

// Manuel Hakem Arama için AJAX isteği kontrolü
if (isset($_GET['search_expertise']) && isset($_GET['project_id'])) {
    header('Content-Type: application/json');
    $searchTerm = '%' . trim($_GET['search_expertise']) . '%';
    $projectId = (int)$_GET['project_id'];
    $searchType = isset($_GET['search_type']) && $_GET['search_type'] === 'name' ? 'name' : 'expertise'; // Arama türünü al, varsayılan 'expertise'
    $results = [];

    try {
        // Projeye zaten atanmış hakemleri bul
        $assignedStmt = $pdo->prepare("SELECT reviewer_id FROM assignments WHERE project_id = :project_id");
        $assignedStmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $assignedStmt->execute();
        $assignedReviewerIds = $assignedStmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Arama türüne göre sorguyu dinamik olarak oluştur
        $sql = "SELECT id, name, expertise FROM reviewers WHERE ";
        if ($searchType === 'name') {
            $sql .= "name LIKE :searchTerm ORDER BY name";
        } else { // 'expertise' veya tanımsızsa
            $sql .= "expertise LIKE :searchTerm ORDER BY name";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $foundReviewers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($foundReviewers as $reviewer) {
            $results[] = [
                'id' => $reviewer['id'],
                'name' => $reviewer['name'],
                'expertise' => $reviewer['expertise'],
                'is_assigned_to_current_project' => in_array($reviewer['id'], $assignedReviewerIds)
            ];
        }
        echo json_encode(['status' => 'success', 'data' => $results]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
    exit();
}

// YENİ: Yapay Zeka Hakem Önerileri için AJAX isteği kontrolü
if (isset($_GET['get_ai_recommendations_for_project'])) {
    header('Content-Type: application/json');

    // Kullanıcının seçtiği modeli al (veya varsayılan olarak fasttext)
    $selectedModel = isset($_GET['model']) && $_GET['model'] === 'minilm' ? 'minilm' : 'fasttext';
    error_log("[AI Recs PHP AJAX] Selected model: " . $selectedModel);

    $csvFile_AI = '';
    if ($selectedModel === 'minilm') {
        $csvFile_AI = 'C:/Users/xleno/OneDrive/Desktop/yapayzekamodeli/minilm12v2_top10_similarity_results.csv'; 
    } else { // fasttext veya tanımsızsa fasttext kullanılır
        $csvFile_AI = 'C:/Users/xleno/OneDrive/Desktop/yapayzekamodeli/fasttext_top3_similarity_results.csv';
    }
    error_log("[AI Recs PHP AJAX] Using CSV file: " . $csvFile_AI);

    // --- AI ÖNERİ BLOĞU İÇİN GEREKLİ VERİLERİ YENİDEN YÜKLE ---
    $projectReviewersMap_AI = [];
    if (file_exists($csvFile_AI) && ($handle_AI = fopen($csvFile_AI, 'r')) !== FALSE) {
        fgetcsv($handle_AI); // Başlık satırını atla
        while (($data_AI = fgetcsv($handle_AI, 1000, ",")) !== FALSE) {
            if (isset($data_AI[0]) && isset($data_AI[1]) && isset($data_AI[2])) {
                $projeIdCsv_AI = trim($data_AI[0]);
                if (!is_numeric($projeIdCsv_AI)) continue;
                $projeId_AI = (int)$projeIdCsv_AI;
                if (!isset($projectReviewersMap_AI[$projeId_AI])) {
                    $projectReviewersMap_AI[$projeId_AI] = [];
                }
                $projectReviewersMap_AI[$projeId_AI][] = [
                    'reviewer_id' => (int)trim($data_AI[1]),
                    'similarity' => (float)trim($data_AI[2])
                ];
            }
        }
        fclose($handle_AI);
    }

    $allReviewers_AI = [];
    $reviewersStmt_AI = $pdo->query("SELECT id, name, expertise FROM reviewers");
    foreach ($reviewersStmt_AI as $row_AI) {
        $allReviewers_AI[$row_AI['id']] = [
            'id' => $row_AI['id'],
            'name' => $row_AI['name'],
            'expertise' => $row_AI['expertise']
        ];
    }

    $assignments_AI = [];
    $assignStmt_AI = $pdo->query("SELECT reviewer_id, project_id FROM assignments");
    foreach ($assignStmt_AI as $row_AI) {
        $assignments_AI[$row_AI['project_id']][$row_AI['reviewer_id']] = true;
    }
    // --- VERİ YÜKLEME SONU ---

    $projectIdToGetRecsFor = (int)$_GET['get_ai_recommendations_for_project'];
    $recommendationsOutput = []; 

    error_log("[AI Recs PHP AJAX] Processing project ID: " . $projectIdToGetRecsFor);
    // error_log("[AI Recs PHP AJAX] projectReviewersMap_AI dump: " . print_r($projectReviewersMap_AI, true)); // Test için gerekirse açılabilir

    if (isset($projectReviewersMap_AI[$projectIdToGetRecsFor])) {
        error_log("[AI Recs PHP AJAX] Found data in projectReviewersMap_AI for project ID " . $projectIdToGetRecsFor . ": " . print_r($projectReviewersMap_AI[$projectIdToGetRecsFor], true));
        foreach ($projectReviewersMap_AI[$projectIdToGetRecsFor] as $rec) {
            $reviewerId = $rec['reviewer_id'];
            $similarity = $rec['similarity'];

            if (isset($allReviewers_AI[$reviewerId])) {
                $reviewerDetails = $allReviewers_AI[$reviewerId];
                $isAssignedToThisProject = isset($assignments_AI[$projectIdToGetRecsFor][$reviewerId]) && $assignments_AI[$projectIdToGetRecsFor][$reviewerId] === true;
                
                $recommendationsOutput[] = [
                    'id' => $reviewerDetails['id'],
                    'name' => $reviewerDetails['name'],
                    'expertise' => $reviewerDetails['expertise'],
                    'similarity_score' => $similarity, 
                    'is_assigned' => $isAssignedToThisProject 
                ];
            } else {
                error_log("[AI Recs PHP AJAX] Reviewer ID " . $reviewerId . " from CSV not found in allReviewers_AI array for project ID " . $projectIdToGetRecsFor);
            }
        }
    } else {
        error_log("[AI Recs PHP AJAX] No data found in projectReviewersMap_AI for project ID " . $projectIdToGetRecsFor);
    }

    usort($recommendationsOutput, function($a, $b) {
        return $b['similarity_score'] <=> $a['similarity_score'];
    });

    error_log("[AI Recs PHP AJAX] Final recommendationsOutput for project ID " . $projectIdToGetRecsFor . ": " . print_r($recommendationsOutput, true));

    echo json_encode(['status' => 'success', 'recommendations' => $recommendationsOutput]);
    exit();
}

$username = $_SESSION['username'];
$firstLetter = strtoupper($username[0]);

// Ajax isteği kontrolü - Proje arama
if (isset($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.keywords, 
            (SELECT COUNT(*) FROM assignments WHERE project_id = p.id) as assigned_count
            FROM projects p
            WHERE p.title LIKE :searchTerm
            ORDER BY p.id DESC
        ");
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sonuçları JSON olarak döndür
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $searchResults]);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// Eğer bir atama formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reviewer_id']) && isset($_POST['project_id'])) {
    $reviewerId = (int)$_POST['reviewer_id'];
    $projectId = (int)$_POST['project_id'];
    $assignedAt = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("INSERT INTO assignments (reviewer_id, project_id, assigned_at) VALUES (?, ?, ?)");
        $stmt->execute([$reviewerId, $projectId, $assignedAt]);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Ajax isteği için JSON yanıtı
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit();
        } else {
            // Normal form gönderimi
            header('Location: assignment');
            exit();
        }
    } catch (PDOException $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        } else {
            die("Atama hatası: " . $e->getMessage());
        }
    }
}

// Projeleri getir (sayfalama ile)
try {
    $itemsPerPage = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

    // Tab'a göre filtreleme koşulunu belirle
    $condition = ($tab === 'assigned') ? ">= 2" : "< 2";

    // Sayfalama için toplam sayımı al
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM projects p 
        WHERE (SELECT COUNT(*) FROM assignments WHERE project_id = p.id) $condition
    ");
    $countStmt->execute();
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);
    $offset = ($page - 1) * $itemsPerPage;

    // Tab'a göre projeleri getir
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.keywords, 
        (SELECT COUNT(*) FROM assignments WHERE project_id = p.id) as assigned_count
        FROM projects p
        HAVING assigned_count $condition
        ORDER BY p.id DESC LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Toplam proje, hakem ve atama sayıları
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM projects");
    $totalProjects = $stmt1->fetchColumn();

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM reviewers");
    $totalReviewers = $stmt2->fetchColumn();

    $stmt3 = $pdo->query("SELECT COUNT(*) FROM assignments");
    $totalAssignments = $stmt3->fetchColumn();
    
    // Sistemdeki tüm bekleyen ve atanmış projelerin sayısını al
    $pendingProjectsStmt = $pdo->query("
        SELECT COUNT(*) FROM projects p 
        WHERE (SELECT COUNT(*) FROM assignments WHERE project_id = p.id) < 2
    ");
    $totalPendingProjects = $pendingProjectsStmt->fetchColumn();
    
    $assignedProjectsStmt = $pdo->query("
        SELECT COUNT(*) FROM projects p 
        WHERE (SELECT COUNT(*) FROM assignments WHERE project_id = p.id) >= 2
    ");
    $totalAssignedProjects = $assignedProjectsStmt->fetchColumn();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Veritabanından hakem bilgilerini al
$reviewersStmt = $pdo->query("SELECT id, name, expertise FROM reviewers");
$allReviewers = [];
foreach ($reviewersStmt as $row) {
    $allReviewers[$row['id']] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'expertise' => $row['expertise']
    ];
}

// Atamaları çek
$assignments = [];
$assignStmt = $pdo->query("SELECT reviewer_id, project_id FROM assignments");
foreach ($assignStmt as $row) {
    $assignments[$row['project_id']][$row['reviewer_id']] = true;
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAP Sistemi - Atamalar</title>
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
            --success-color: #1db954;
            --warning-color: #ff9800;
            --danger-color: #ff5722; /* Koyu tema için danger color */
            /* Açık tema için ek değişkenler */
            --light-primary-color: #f1f3f5;
            --light-secondary-color: #ffffff;
            --light-text-color: #212529;
            --light-grey-color: #6c757d;
            --light-card-bg: #ffffff;
            --light-accent-color: #0d6efd;
            --light-success-color: #198754; /* Bootstrap success */
            --light-warning-color: #ffc107; /* Bootstrap warning */
            --light-danger-color: #dc3545; /* Bootstrap danger */
        }

        /* Genel body stilleri tema değişkenlerini kullanacak */
        body {
            background-color: var(--primary-color);
            color: var(--text-color);
        }

        /* Navbar stilleri tema değişkenlerini kullanacak */
        .navbar {
            background: var(--primary-color); /* Navbar arka planı için primary color */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand,
        .navbar .nav-link {
            color: var(--text-color); /* Navbar yazı rengi */
        }
        .navbar .dropdown-menu {
            background-color: var(--secondary-color); /* Dropdown arka planı */
            color: var(--text-color);
        }
        .navbar .dropdown-item {
            color: var(--text-color);
        }
        .navbar .dropdown-item:hover {
            background-color: var(--accent-color);
            color: var(--light-text-color); /* Vurgu rengi üzerinde açık tema metin rengi */
        }

        /* Kart stilleri tema değişkenlerini kullanacak */
        .stats-card, .content-card, .sidebar, .project-item, .reviewer-card {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--accent-color); /* Kenarlık rengi için */
        }

        /* Modal stilleri tema değişkenlerini kullanacak */
        .modal-content {
            background-color: var(--secondary-color); /* Modal için secondary color daha uygun olabilir */
            color: var(--text-color);
        }
        .modal-header, .modal-footer {
            border-color: rgba(255, 255, 255, 0.1); /* Koyu temada açık renk kenarlık */
        }
        /* Açık tema modal kenarlığı */
        body[data-bs-theme="light"] .modal-header,
        body[data-bs-theme="light"] .modal-footer {
            border-color: rgba(0, 0, 0, 0.1);
        }

        /* Butonlar ve diğer UI elemanları için genel stiller */
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--light-text-color); /* Genellikle açık renk metin */
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .btn-secondary {
             background-color: var(--grey-color);
             border-color: var(--grey-color);
             color: var(--text-color);
        }
        .page-link {
            background-color: var(--card-bg);
            border-color: var(--accent-color);
            color: var(--text-color);
        }
        .page-item.active .page-link {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--light-text-color);
        }
        .nav-tabs .nav-link {
            color: var(--grey-color);
        }
        .nav-tabs .nav-link.active {
            color: var(--accent-color);
            background-color: rgba(var(--accent-color-rgb), 0.1); /* accent-color'ın rgba versiyonu */
            border-bottom-color: var(--accent-color);
        }
        .keyword-badge {
            background-color: rgba(var(--accent-color-rgb), 0.1);
            color: var(--accent-color);
        }
        .matching-score {
            /* Renkler JavaScript içinde dinamik olarak ayarlanmaya devam edebilir,
               ancak temel arkaplan ve metin için değişken kullanılabilir. */
        }
        
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        .navbar {
            padding: 15px 0;
            position: relative;
            z-index: 100;
        }
        
        .navbar-brand {
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
        
        .project-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .project-item {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .project-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .project-id {
            display: inline-block;
            background: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .project-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .project-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 12px;
        }
        
        .keyword-badge {
            display: inline-block;
            background: rgba(var(--accent-color-rgb), 0.1);
            color: var(--accent-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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
        
        .page-item.disabled .page-link {
            background: rgba(32, 32, 32, 0.5);
            color: var(--grey-color);
        }
        
        /* Modal styling */
        .modal-content {
            background: var(--secondary-color);
            color: var(--text-color);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
        }
        
        .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
        }
        
        .btn-close {
            background: rgba(255, 255, 255, 0.5);
            opacity: 0.8;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        .reviewer-card {
            background: var(--card-bg);
            color: var(--text-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .reviewer-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .reviewer-info {
            flex: 1;
        }
        
        .reviewer-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .reviewer-expertise {
            font-size: 0.9rem;
            color: var(--grey-color);
            margin-bottom: 10px; /* space below expertise */
        }
        
        .reviewer-info .similarity-text {
            font-size: 0.9rem;
            color: var(--grey-color);
            margin-top: 0;
            margin-bottom: 5px;
            line-height: 1.5;
        }
        
        .reviewer-score {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .score-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(71, 118, 230, 0.1);
            color: var(--accent-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .assign-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .assign-btn:hover {
            background: #3a5fc8;
            transform: translateY(-2px);
        }
        
        .assign-btn.assigned {
            background: var(--success-color);
        }
        
        .match-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .match-high {
            background: var(--success-color);
        }
        
        .match-medium {
            background: var(--warning-color);
        }
        
        .match-low {
            background: rgba(255, 87, 34, 0.15);
            color: var(--danger-color);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            float: right;
        }
        
        .status-badge.assigned {
            background: rgba(29, 185, 84, 0.1);
            color: var(--success-color);
        }
        
        .status-badge.pending {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }
        
        .status-badge.warning {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }
        
        .matching-score {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .matching-high {
            background: rgba(29, 185, 84, 0.15);
            color: var(--success-color);
        }
        
        .matching-medium {
            background: rgba(255, 152, 0, 0.15);
            color: var(--warning-color);
        }
        
        .matching-low {
            background: rgba(255, 87, 34, 0.15);
            color: var(--danger-color);
        }
        
        /* Kaydırma çubuğunu gizleme */
        body::-webkit-scrollbar, html::-webkit-scrollbar {
            display: none;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-input {
            background: rgba(32, 32, 32, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-color);
            padding: 10px 15px;
            font-size: 0.9rem;
        }
        
        .search-input:focus {
            border-color: var(--accent-color);
            background: rgba(32, 32, 32, 0.9);
            box-shadow: 0 0 0 0.2rem rgba(71, 118, 230, 0.25);
            color: var(--text-color);
        }
        
        /* Placeholder stilini daha belirgin hale getir */
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            font-size: 1rem;
            opacity: 1;
        }

        /* Sekme stilleri */
        .nav-tabs {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-tabs .nav-link {
            color: var(--grey-color);
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            padding: 10px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: var(--accent-color);
            border-color: rgba(71, 118, 230, 0.3);
            background: rgba(71, 118, 230, 0.05);
        }

        .nav-tabs .nav-link.active {
            color: var(--accent-color);
            background: rgba(71, 118, 230, 0.1);
            border-bottom: 2px solid var(--accent-color);
        }

        .nav-tabs .nav-link .badge {
            margin-left: 5px;
            border-radius: 20px;
            font-size: 0.7rem;
            padding: 3px 6px;
        }

        /* Tab içerik bölgesi */
        .tab-content {
            margin-top: 20px;
        }

        /* Tab panellerini kaydır */
        .tab-pane {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .tab-pane.active {
            opacity: 1;
            transform: translateY(0);
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
                <h1 class="page-title">Hakem Atamaları</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Atamalar</li>
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
                                    <a href="assignment" class="active">
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
                    
                    <!-- Sekmeli Navigasyon -->
                    <ul class="nav nav-tabs mb-4" id="assignmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-projects" 
                                    type="button" role="tab" aria-controls="pending-projects" aria-selected="true">
                                <i class="fas fa-clock me-1"></i> Atama Bekleyen Projeler
                                <?php if ($totalPendingProjects > 0): ?>
                                <span class="badge bg-danger"><?php echo $totalPendingProjects; ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assigned-tab" data-bs-toggle="tab" data-bs-target="#assigned-projects" 
                                    type="button" role="tab" aria-controls="assigned-projects" aria-selected="false">
                                <i class="fas fa-check-circle me-1"></i> Atama Yapılmış Projeler
                                <?php if ($totalAssignedProjects > 0): ?>
                                <span class="badge bg-success"><?php echo $totalAssignedProjects; ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="assignmentTabsContent">
                        <!-- Atama Bekleyen Projeler Sekmesi -->
                        <div class="tab-pane fade show active" id="pending-projects" role="tabpanel" aria-labelledby="pending-tab">
                            <!-- Projects List for Pending -->
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="card-title mb-0"><i class="fas fa-tasks"></i> Atama Bekleyen Projeler</h2>
                                    <div class="search-container">
                                        <input type="text" id="pending-project-search" class="form-control search-input" placeholder="Proje ara..." autocomplete="off">
                                    </div>
                                </div>
                                
                                <?php 
                                $pendingProjects = array_filter($projects, function($project) {
                                    return $project['assigned_count'] < 2;
                                });
                                
                                if (count($pendingProjects) > 0): ?>
                                    <ul class="project-list" id="pending-project-list">
                                        <?php foreach ($pendingProjects as $project): ?>
                                            <li class="project-item"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#assignModal"
                                                data-project-id="<?php echo $project['id']; ?>"
                                                data-project-title="<?php echo htmlspecialchars($project['title']); ?>"
                                                data-project-keywords="<?php echo htmlspecialchars($project['keywords']); ?>"
                                                data-assigned-count="<?php echo $project['assigned_count']; ?>"
                                            >
                                                <span class="project-id">#<?php echo htmlspecialchars($project['id']); ?></span>
                                                
                                                <?php if ($project['assigned_count'] > 0): ?>
                                                    <span class="status-badge warning">
                                                        <i class="fas fa-exclamation-circle"></i> <?php echo $project['assigned_count']; ?>/2 Hakem
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge pending">
                                                        <i class="fas fa-clock"></i> Atama Bekliyor
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <h3 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                                                <div class="project-keywords">
                                                    <?php 
                                                    $keywords = explode(',', $project['keywords']);
                                                    foreach ($keywords as $keyword) {
                                                        if (trim($keyword) !== '') {
                                                            echo '<span class="keyword-badge">' . htmlspecialchars(trim($keyword)) . '</span>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                        <p>Tüm projelere 2 hakem ataması yapılmıştır.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Atama Yapılmış Projeler Sekmesi -->
                        <div class="tab-pane fade <?php echo ($tab === 'assigned') ? 'show active' : ''; ?>" id="assigned-projects" role="tabpanel" aria-labelledby="assigned-tab">
                            <!-- Projects List for Assigned -->
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="card-title mb-0"><i class="fas fa-check-circle"></i> Atama Yapılmış Projeler</h2>
                                    <div class="search-container">
                                        <input type="text" id="assigned-project-search" class="form-control search-input" placeholder="Proje ara..." autocomplete="off">
                                    </div>
                                </div>
                                
                                <?php 
                                // Eğer 'assigned' tabındaysak, doğrudan projeleri kullan
                                // Değilse, tüm tam atanmış projeleri alabilmek için yeni bir sorgu yap
                                if ($tab === 'assigned') {
                                    $assignedProjects = $projects;
                                } else {
                                    $assignedStmt = $pdo->prepare("
                                        SELECT p.id, p.title, p.keywords, 
                                        (SELECT COUNT(*) FROM assignments WHERE project_id = p.id) as assigned_count
                                        FROM projects p
                                        HAVING assigned_count >= 2
                                        ORDER BY p.id DESC LIMIT 10
                                    ");
                                    $assignedStmt->execute();
                                    $assignedProjects = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);
                                }
                                
                                if (count($assignedProjects) > 0): ?>
                                    <ul class="project-list" id="assigned-project-list">
                                        <?php foreach ($assignedProjects as $project): ?>
                                            <li class="project-item"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#assignModal"
                                                data-project-id="<?php echo $project['id']; ?>"
                                                data-project-title="<?php echo htmlspecialchars($project['title']); ?>"
                                                data-project-keywords="<?php echo htmlspecialchars($project['keywords']); ?>"
                                                data-assigned-count="<?php echo $project['assigned_count']; ?>"
                                            >
                                                <span class="project-id">#<?php echo htmlspecialchars($project['id']); ?></span>
                                                <span class="status-badge assigned">
                                                    <i class="fas fa-check-circle"></i> <?php echo $project['assigned_count']; ?>/2 Hakem
                                                </span>
                                                <h3 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                                                <div class="project-keywords">
                                                    <?php 
                                                    $keywords = explode(',', $project['keywords']);
                                                    foreach ($keywords as $keyword) {
                                                        if (trim($keyword) !== '') {
                                                            echo '<span class="keyword-badge">' . htmlspecialchars(trim($keyword)) . '</span>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-exclamation-circle fa-3x mb-3 text-warning"></i>
                                        <p>Henüz hiçbir projeye 2 hakem ataması tamamlanmamıştır.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

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
    
    <!-- Reviewer Assignment Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">
                        <i class="fas fa-users text-primary me-2"></i>
                        <span id="modal-project-title">Proje için Hakem Önerileri</span>
                    </h5>
                    <div id="modal-assignment-status" class="ms-3"></div>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6>Proje Anahtar Kelimeleri:</h6>
                        <div id="modal-project-keywords" class="project-keywords mb-3"></div>
                    </div>
                    
                    <h5 class="mt-4">Önerilen Hakemler (Yapay Zeka)</h5>
                    <div id="reviewer-list" class="mb-4">
                        <!-- Reviewer cards will be loaded here by AI -->
                    </div>

                    <hr>

                    <h5 class="mt-4">Manuel Hakem Arama</h5>
                    <div class="input-group mb-1">
                        <label class="input-group-text" for="manualSearchType">Arama Türü:</label>
                        <select class="form-select" id="manualSearchType" style="flex-grow: 0.3;">
                            <option value="expertise" selected>Uzmanlığa Göre</option>
                            <option value="name">İsme Göre</option>
                        </select>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" id="manualReviewerSearchInput" class="form-control" placeholder="Uzmanlık alanı girin...">
                        <button class="btn btn-outline-secondary" type="button" id="manualReviewerSearchBtn"><i class="fas fa-search"></i> Ara</button>
                    </div>
                    <div id="manualReviewerSearchResults">
                        <!-- Manual search results will be loaded here -->
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="form-check me-auto" style="display: none;" id="assignmentConfirmationContainer">
                        <input class="form-check-input" type="checkbox" value="" id="assignmentConfirmationCheckbox">
                        <label class="form-check-label" for="assignmentConfirmationCheckbox">
                            Hakem atamasını onaylıyorum.
                        </label>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" id="confirmAssignmentBtn" style="display: none;">Onaylı Ata</button> 
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    const assignments = <?php echo json_encode($assignments); ?>;
    document.addEventListener('DOMContentLoaded', function() {
        const root = document.documentElement;

        const applyTheme = (theme) => {
            root.setAttribute('data-bs-theme', theme);
            if (theme === 'dark') {
                root.style.setProperty('--primary-color', '#121212');
                root.style.setProperty('--secondary-color', '#202020');
                root.style.setProperty('--text-color', '#ffffff');
                root.style.setProperty('--grey-color', '#757575');
                root.style.setProperty('--card-bg', 'rgba(32, 32, 32, 0.8)');
                root.style.setProperty('--accent-color', '#4776E6');
                root.style.setProperty('--accent-color-rgb', '71, 118, 230');
                root.style.setProperty('--modal-border-color', 'rgba(255, 255, 255, 0.1)');
                root.style.setProperty('--success-color', '#1db954');
                root.style.setProperty('--warning-color', '#ff9800');
                root.style.setProperty('--danger-color', '#ff5722');
                } else {
                root.style.setProperty('--primary-color', '#f1f3f5');
                root.style.setProperty('--secondary-color', '#ffffff');
                root.style.setProperty('--text-color', '#212529');
                root.style.setProperty('--grey-color', '#6c757d');
                root.style.setProperty('--card-bg', '#ffffff');
                root.style.setProperty('--accent-color', '#0d6efd');
                root.style.setProperty('--accent-color-rgb', '13, 110, 253');
                root.style.setProperty('--modal-border-color', 'rgba(0, 0, 0, 0.1)');
                root.style.setProperty('--success-color', 'var(--light-success-color)');
                root.style.setProperty('--warning-color', 'var(--light-warning-color)');
                root.style.setProperty('--danger-color', 'var(--light-danger-color)');
            }
            
            // Modal içindeki dinamik elementler için tema uygulaması
            // Bu kısım modal içeriği yüklendikten sonra ayrıca çağrılabilir veya gözden geçirilebilir.
            const modalReviewerCards = document.querySelectorAll('#assignModal .reviewer-card');
            modalReviewerCards.forEach(card => {
                card.style.backgroundColor = root.style.getPropertyValue('--card-bg');
                card.style.color = root.style.getPropertyValue('--text-color');
            });
            const modalKeywordBadges = document.querySelectorAll('#assignModal .keyword-badge');
            modalKeywordBadges.forEach(badge => {
                // CSS zaten rgba(var(--accent-color-rgb), 0.1) kullandığı için JS'de tekrar ayarlanmasına gerek olmayabilir
                // badge.style.backgroundColor = `rgba(${root.style.getPropertyValue('--accent-color-rgb')}, 0.1)`;
                // badge.style.color = root.style.getPropertyValue('--accent-color');
            });
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
            
        // Modal işlemleri için gerekli kodlar
        const assignModal = document.getElementById('assignModal');
        const assignmentConfirmationContainer = document.getElementById('assignmentConfirmationContainer');
        const assignmentConfirmationCheckbox = document.getElementById('assignmentConfirmationCheckbox');
        const confirmAssignmentBtn = document.getElementById('confirmAssignmentBtn');
        let currentProjectAssignButton = null; 

        function loadReviewerListsInModal(projectIdToLoad) {
            const modalAssignmentStatus = document.getElementById('modal-assignment-status');
            let currentAssignedCount = 0;
            if (assignments[projectIdToLoad]) {
                currentAssignedCount = Object.keys(assignments[projectIdToLoad]).length;
            }

            if (modalAssignmentStatus) {
                if (currentAssignedCount >= 2) {
                    modalAssignmentStatus.innerHTML = `<span class="badge bg-success">${currentAssignedCount}/2 Hakem Atandı</span>`;
                } else if (currentAssignedCount > 0) {
                    modalAssignmentStatus.innerHTML = `<span class="badge bg-warning text-dark">${currentAssignedCount}/2 Hakem Atandı</span>`;
                } else {
                    modalAssignmentStatus.innerHTML = `<span class="badge bg-secondary">0/2 Hakem Atandı</span>`;
                }
            }

            const reviewerList = document.getElementById('reviewer-list');
            reviewerList.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Yapay zeka önerileri yükleniyor...</p></div>';
            
            const manualReviewerSearchResultsDiv = document.getElementById('manualReviewerSearchResults');
            manualReviewerSearchResultsDiv.innerHTML = '';

            // YENİ: localStorage'dan seçili modeli al
            const selectedAiModel = localStorage.getItem('selectedAiModel') || 'fasttext';
            console.log('[AI Fetch Reload] Selected AI Model from localStorage:', selectedAiModel);

            const aiFetchUrl = `public/assignment.php?get_ai_recommendations_for_project=${projectIdToLoad}&model=${selectedAiModel}`;
            console.log('[AI Fetch Reload] Requesting URL:', aiFetchUrl);

            fetch(aiFetchUrl)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    console.log('[AI Fetch Reload] Data received:', data);
                    reviewerList.innerHTML = ''; 
                    // YENİ: Model ismini göstermek için bir başlık ekleyebiliriz (isteğe bağlı)
                    const modelTitle = document.createElement('p');
                    modelTitle.className = 'text-muted small mb-2';
                    modelTitle.innerHTML = `<i>Kullanılan Model: ${selectedAiModel === 'minilm' ? 'MiniLM L12 v2' : 'FastText'}</i>`;
                    reviewerList.appendChild(modelTitle);

                    let maxSuggestions = parseInt(localStorage.getItem('reviewerSuggestionCount')) || (selectedAiModel === 'minilm' ? 10 : 5); // Modele göre varsayılan öneri sayısı
                    if (isNaN(maxSuggestions) || maxSuggestions < 1) maxSuggestions = (selectedAiModel === 'minilm' ? 10 : 5);
                    if (selectedAiModel === 'minilm' && maxSuggestions > 10) maxSuggestions = 10;
                    if (selectedAiModel === 'fasttext' && maxSuggestions > 5) maxSuggestions = 5; // FastText için max 5 diyelim (CSV'de 3 var ama genel bir sınır)

                    const recommendations = data.recommendations || [];
                    const actualRecommendations = recommendations.slice(0, maxSuggestions);

                    if (actualRecommendations.length > 0) {
                        actualRecommendations.forEach(reviewer => {
                            // createReviewerCard'a projenin GÜNCEL hakem sayısını (currentAssignedCount) ver
                            const card = createReviewerCard(reviewer, projectIdToLoad, currentAssignedCount, reviewer.is_assigned, false, assignments, currentProjectAssignButton);
                            reviewerList.appendChild(card);
                        });
                    } else {
                        reviewerList.innerHTML = '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x mb-3 text-info"></i><p>Bu proje için yapay zeka önerisi bulunamadı.</p></div>';
                    }
                    const currentTheme = localStorage.getItem('theme') || 'dark';
                    applyThemeForModalElements(currentTheme, assignModal);
                })
                .catch(error => {
                    console.error('Yapay zeka hakem önerileri (yeniden) alınırken hata:', error);
                    reviewerList.innerHTML = '<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-2x mb-3 text-danger"></i><p>Yapay zeka önerileri alınamadı.</p></div>';
                });
        }

        if (assignModal) {
            assignModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                currentProjectAssignButton = button; 
                let currentProjectId = button.getAttribute('data-project-id'); 
                if (currentProjectId && currentProjectId.includes(':')) {
                    currentProjectId = currentProjectId.split(':')[0];
                }

                const projectTitle = button.getAttribute('data-project-title');
                const projectKeywords = button.getAttribute('data-project-keywords');
                
                assignModal.dataset.currentProjectId = currentProjectId; // Proje ID'sini modal'a kaydet

                document.getElementById('modal-project-title').textContent = projectTitle;
                const keywordsContainer = document.getElementById('modal-project-keywords');
                keywordsContainer.innerHTML = '';
                if (projectKeywords) {
                    projectKeywords.split(',').forEach(keyword => {
                        if (keyword.trim() !== '') {
                            const badge = document.createElement('span');
                            badge.className = 'keyword-badge';
                            badge.textContent = keyword.trim();
                            keywordsContainer.appendChild(badge);
                        }
                    });
                }
                
                // Hakem listelerini yükle/yenile
                loadReviewerListsInModal(currentProjectId);

                const manualSearchBtn = document.getElementById('manualReviewerSearchBtn');
                const manualSearchInput = document.getElementById('manualReviewerSearchInput');
                const manualSearchType = document.getElementById('manualSearchType'); // Yeni eklendi
                const manualReviewerSearchResultsDiv = document.getElementById('manualReviewerSearchResults'); // Daha önce tanımlanmış olabilir, scope kontrolü

                // Arama türü değiştikçe placeholder'ı güncelle
                if (manualSearchType && manualSearchInput) {
                    manualSearchType.addEventListener('change', function() {
                        if (this.value === 'expertise') {
                            manualSearchInput.placeholder = 'Uzmanlık alanı girin...';
                        } else {
                            manualSearchInput.placeholder = 'Hakem adı girin...';
                        }
                    });
                }

                manualSearchBtn.onclick = function() { 
                    const searchTerm = manualSearchInput.value.trim();
                    const searchType = manualSearchType.value; // Seçilen arama türünü al
                    const modalProjectId = assignModal.dataset.currentProjectId;

                    if (!modalProjectId) {
                        console.error("Manuel arama için Modal Proje ID bulunamadı!");
                        manualReviewerSearchResultsDiv.innerHTML = '<div class="alert alert-danger">Proje ID alınamadı. Lütfen modalı kapatıp tekrar açın.</div>';
                        return;
                    }

                    if (searchTerm === '') {
                        manualReviewerSearchResultsDiv.innerHTML = `<div class="alert alert-warning">Lütfen bir ${searchType === 'expertise' ? 'uzmanlık alanı' : 'hakem adı'} girin.</div>`;
                        return;
                    }
                    // const manualReviewerSearchResultsDiv = document.getElementById('manualReviewerSearchResults'); // Zaten yukarıda tanımlı
                    manualReviewerSearchResultsDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Aranıyor...</p></div>';

                    // URL'ye search_type parametresini ekle
                    const manualSearchUrl = `public/assignment.php?search_expertise=${encodeURIComponent(searchTerm)}&project_id=${modalProjectId}&search_type=${searchType}`;
                    console.log('[Manual Search] Requesting URL:', manualSearchUrl);

                    fetch(manualSearchUrl)
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => { throw new Error(`HTTP error! status: ${response.status}, body: ${text}`); });
                            }
                            return response.json();
                        })
                        .then(result => {
                            console.log('[Manual Search] Data received:', result);
                            manualReviewerSearchResultsDiv.innerHTML = '';
                            if (result.status === 'success' && result.data.length > 0) {
                                let countForManualCard = assignments[modalProjectId] ? Object.keys(assignments[modalProjectId]).length : 0;
                                result.data.forEach(reviewer => {
                                    const card = createReviewerCard(reviewer, modalProjectId, countForManualCard, reviewer.is_assigned_to_current_project, true, assignments, currentProjectAssignButton);
                                    manualReviewerSearchResultsDiv.appendChild(card);
                                });
                            } else if (result.status === 'success' && result.data.length === 0) {
                                manualReviewerSearchResultsDiv.innerHTML = '<div class="alert alert-info">Bu uzmanlık alanında hakem bulunamadı.</div>';
                            } else {
                                manualReviewerSearchResultsDiv.innerHTML = `<div class="alert alert-danger">Arama sırasında bir hata oluştu: ${result.message || 'Bilinmeyen hata'}</div>`;
                            }
                            const currentTheme = localStorage.getItem('theme') || 'dark';
                            applyThemeForModalElements(currentTheme, assignModal);
                        })
                        .catch(error => {
                            console.error('Manuel hakem arama hatası:', error);
                            manualReviewerSearchResultsDiv.innerHTML = '<div class="alert alert-danger">Arama sırasında bir ağ/işleme hatası oluştu.</div>';
                        });
                };
                 
                assignModal.addEventListener('hidden.bs.modal', function () {
                    manualSearchInput.value = '';
                    manualSearchType.value = 'expertise'; // Arama türünü varsayılana döndür
                    manualSearchInput.placeholder = 'Uzmanlık alanı girin...'; // Placeholder'ı varsayılana döndür
                    // manualReviewerSearchResultsDiv.innerHTML = ''; // Zaten loadReviewerListsInModal içinde temizleniyor
                    assignModal.removeAttribute('data-current-project-id'); // Modal kapanınca ID'yi temizle
                }, { once: true }); 
            });
        }

        // Hakem kartı oluşturma fonksiyonu (hem AI hem de manuel arama için ortak)
        // isManualSearch parametresi, similarity_score gösterilip gösterilmeyeceğini belirler.
        // assignments parametresi, genel atama durumunu kontrol etmek için eklendi.
        // modalTriggerButton, hangi projenin "Ata" butonuna tıklandığını bilmek için eklendi.
        function createReviewerCard(reviewer, projectId, initialAssignedCount, isAssigned, isManualSearch = false, currentAssignments, modalTriggerButton) {
            const card = document.createElement('div');
            card.className = 'reviewer-card';
            if (isAssigned) {
                card.classList.add('assigned-reviewer');
            }

            // Projeye kaç hakem atanmış, global 'assignments' objesinden kontrol et
            let currentAssignedCountForProject = 0;
            if (currentAssignments[projectId]) {
                currentAssignedCountForProject = Object.keys(currentAssignments[projectId]).length;
            }
             // Kart içeriğini oluştur
            let cardContent = `
                <div class="reviewer-info">
                    <h6 class="reviewer-name"><i class="fas fa-user me-2"></i>${reviewer.name}</h6>
                    <p class="reviewer-expertise"><i class="fas fa-briefcase me-2"></i><strong>Uzmanlık:</strong> ${reviewer.expertise}</p>
                    ${!isManualSearch && reviewer.similarity_score !== undefined ? `<p class="similarity-text"><i class="fas fa-percentage me-2"></i><strong>Benzerlik:</strong> ${(reviewer.similarity_score * 100).toFixed(2)}%</p>` : ''}
                </div>
                <div class="reviewer-actions">`;

            if (isAssigned) {
                // Atanmışsa "Atamayı İptal Et" butonu
                const unassignButtonId = `unassign-btn-${projectId}-${reviewer.id}`;
                cardContent += `<button class="btn btn-sm btn-danger unassign-btn" id="${unassignButtonId}" data-reviewer-id="${reviewer.id}" data-project-id="${projectId}"><i class="fas fa-user-minus me-2"></i>İptal Et</button>`;
            } else if (currentAssignedCountForProject >= 2) {
                cardContent += '<button class="btn btn-sm btn-secondary disabled assign-btn"><i class="fas fa-ban me-2"></i>Limit Dolu</button>';
            } else {
                // "Ata" butonu için benzersiz ID oluştur
                const assignButtonId = `assign-btn-${projectId}-${reviewer.id}`;
                cardContent += `<button class="btn btn-sm btn-primary assign-btn" id="${assignButtonId}" data-reviewer-id="${reviewer.id}" data-project-id="${projectId}"><i class="fas fa-user-plus me-2"></i>Ata</button>`;
            }
            cardContent += '</div>';
            card.innerHTML = cardContent;

            // "Ata" butonuna event listener ekle (eğer varsa)
            const assignBtn = card.querySelector('.assign-btn:not(.disabled)');
            if (assignBtn) {
                assignBtn.addEventListener('click', function(event) {
                    event.preventDefault(); 
                    assignmentConfirmationContainer.style.display = 'block';
                    confirmAssignmentBtn.style.display = 'inline-block';
                    assignmentConfirmationCheckbox.checked = false; 
                    confirmAssignmentBtn.dataset.reviewerId = this.dataset.reviewerId;
                    confirmAssignmentBtn.dataset.projectId = this.dataset.projectId;
                });
            }

            // YENİ: "Atamayı İptal Et" butonuna event listener ekle (eğer varsa)
            const unassignBtn = card.querySelector('.unassign-btn');
            if (unassignBtn) {
                unassignBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    const reviewerId = this.dataset.reviewerId;
                    const projectId = this.dataset.projectId;
                    unassignReviewer(reviewerId, projectId, this);
                });
            }
            return card;
        }
        
        // "Onaylı Ata" butonuna tıklama olayı
        confirmAssignmentBtn.addEventListener('click', function() {
            if (!assignmentConfirmationCheckbox.checked) {
                alert('Lütfen hakem atamasını onaylayın.');
                return;
            }

            const reviewerId = this.dataset.reviewerId;
            const projectId = this.dataset.projectId;
            // const modalTriggerButtonId = this.dataset.modalTriggerButtonId; // Kaldırıldı

            // Onay kutusunu ve "Onaylı Ata" butonunu gizle
            assignmentConfirmationContainer.style.display = 'none';
            this.style.display = 'none';
            assignmentConfirmationCheckbox.checked = false;

            assignReviewer(reviewerId, projectId); // modalTriggerButton argümanı kaldırıldı
        });

        // Hakem atama fonksiyonu
        function assignReviewer(reviewerId, projectId) { // modalTriggerButton parametresi kaldırıldı
            const formData = new FormData();
            formData.append('reviewer_id', reviewerId);
            formData.append('project_id', projectId);

            fetch('public/assignment.php', { // URL 'public/assignment.php' olarak güncellendi
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // AJAX isteği olduğunu belirtmek için başlık eklendi
                },
                body: formData
            })
            .then(response => {
                console.log('[Assign Reviewer] Fetch response status:', response.status);
                console.log('[Assign Reviewer] Fetch response ok:', response.ok);
                return response.text().then(text => {
                    console.log('[Assign Reviewer] Fetch response text content:', text); // Yanıt metnini logla
                    if (!response.ok) {
                        // Sunucudan gelen hatanın metnini logla
                        console.error('[Assign Reviewer] Server error response body:', text);
                        throw new Error(`HTTP error! status: ${response.status}. Response: ${text.substring(0, 200)}...`);
                    }
                    try {
                        return JSON.parse(text); // Metni JSON olarak ayrıştırmayı dene
                    } catch (e) {
                        console.error('[Assign Reviewer] Failed to parse response text as JSON:', text);
                        throw new Error(`Failed to parse server response as JSON. First 200 chars: ${text.substring(0,200)}`);
                    }
                });
            })
            .then(data => {
                console.log('Atama sonucu:', data);
                if (data.status === 'success') {
                    // Global assignments objesini güncelle
                    if (!assignments[projectId]) {
                        assignments[projectId] = {};
                    }
                    assignments[projectId][reviewerId] = true;

                    // Atanan hakem sayısını güncelle
                    let assignedCountForProject = Object.keys(assignments[projectId]).length;
                    
                    // Modal içindeki hakem kartını güncelle (Atandı olarak işaretle)
                    const allReviewerCards = assignModal.querySelectorAll('.reviewer-card');
                    allReviewerCards.forEach(card => {
                        const btn = card.querySelector(`.assign-btn[data-reviewer-id="${reviewerId}"][data-project-id="${projectId}"]`);
                        if (btn) {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-success', 'disabled');
                            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Atandı';
                            btn.disabled = true;
                        }
                        // Eğer bu projeye 2 hakem atandıysa diğer "Ata" butonlarını devre dışı bırak
                        if (assignedCountForProject >= 2) {
                            const otherAssignBtns = card.querySelectorAll(`.assign-btn[data-project-id="${projectId}"]:not(.disabled)`);
                            otherAssignBtns.forEach(otherBtn => {
                                otherBtn.classList.remove('btn-primary');
                                otherBtn.classList.add('btn-secondary', 'disabled');
                                otherBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Limit Dolu';
                                otherBtn.disabled = true;
                            });
                        }
                    });

                    // Ana sayfadaki proje listesindeki ilgili proje öğesini güncelle
                    // modalTriggerButton parametresi yerine global currentProjectAssignButton kullanılacak
                    if (currentProjectAssignButton) { 
                        // currentProjectAssignButton, show.bs.modal'da set edilen, tıklanan ana "Hakem Ata" <li> öğesi
                        const projectListItem = currentProjectAssignButton; // Bu zaten <li> öğesi

                        if (projectListItem) {
                            // Proje listesindeki <li> öğesinin data-assigned-count'unu güncelle
                            projectListItem.setAttribute('data-assigned-count', assignedCountForProject);
                            
                            const statusBadge = projectListItem.querySelector('.status-badge');
                            // const assignButtonInList = projectListItem.querySelector('.assign-project-btn'); // Bu class'a sahip bir buton <li> içinde yoktu.

                            if (statusBadge) {
                                statusBadge.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${assignedCountForProject}/2 Hakem`;
                                statusBadge.classList.remove('pending', 'assigned');
                                statusBadge.classList.add('warning');
                            }
                            
                            // Proje listesindeki "Hakem Ata" butonunun data-assigned-count'unu güncelle
                            // if(assignButtonInList) { // Bu blok kaldırıldı, çünkü ilgili buton yok ve data-attribute direkt <li> üzerinde güncellendi.
                            //    assignButtonInList.setAttribute('data-assigned-count', assignedCountForProject);
                            //}

                            // Modal başlığındaki atama durumunu güncelle
                            const modalAssignmentStatus = document.getElementById('modal-assignment-status');
                            if (modalAssignmentStatus) {
                                modalAssignmentStatus.innerHTML = `<span class="badge bg-warning text-dark">${assignedCountForProject}/2 Hakem Atandı</span>`;
                            }


                            if (assignedCountForProject >= 2) {
                                if (statusBadge) {
                                    statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> 2/2 Hakem';
                                    statusBadge.classList.remove('warning', 'pending');
                                    statusBadge.classList.add('assigned');
                                }
                                if (modalAssignmentStatus) {
                                     modalAssignmentStatus.innerHTML = '<span class="badge bg-success">2/2 Hakem Atandı</span>';
                                }
                                alert('Bu projeye 2 hakem ataması başarıyla tamamlandı!');
                                const modalInstance = bootstrap.Modal.getInstance(assignModal);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                                
                               
                                setTimeout(() => { // Küçük bir gecikme ile sayfayı yenile
                                    location.reload(); // En basit çözüm için şimdilik sayfayı yeniliyoruz.
                                }, 1000);


                            } else {
                                alert(`Hakem başarıyla atandı. Bu projeye ${assignedCountForProject} hakem atandı.`);
                                // Modal açık kalacak, diğer "Ata" butonları güncellendi.
                                // Onay kutusunu ve "Onaylı Ata" butonunu gizle (tekrar gizlendiğinden emin ol)
                                assignmentConfirmationContainer.style.display = 'none';
                                confirmAssignmentBtn.style.display = 'none';
                                assignmentConfirmationCheckbox.checked = false;
                            }
                        } else {
                             // Eğer proje listesi öğesi bulunamazsa genel bir mesaj ver
                             // Bu durum currentProjectAssignButton null ise oluşur ki bu da beklenmedik bir durumdur.
                             console.error("Proje listesi öğesi (currentProjectAssignButton) bulunamadı!");
                             alert(`Hakem başarıyla atandı. Bu projeye ${assignedCountForProject} hakem atandı.`);
                             if (assignedCountForProject >= 2) {
                                alert('Bu projeye 2 hakem ataması başarıyla tamamlandı!');
                                const modalInstance = bootstrap.Modal.getInstance(assignModal);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                                 setTimeout(() => { location.reload(); }, 500);
                             }
                        }
                    } else {
                        // Eğer currentProjectAssignButton (proje listesi öğesi) yoksa, sadece genel bir mesaj ver.
                        // Bu durum normalde show.bs.modal tetiklenmeden assignReviewer çağrılırsa olabilir, ki bu da beklenmez.
                        console.error("currentProjectAssignButton tanımlı değil! UI ana listede güncellenemedi.");
                        alert(`Hakem başarıyla atandı.`);
                        if (assignedCountForProject >= 2) {
                            alert('Bu projeye 2 hakem ataması başarıyla tamamlandı!');
                            const modalInstance = bootstrap.Modal.getInstance(assignModal);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                             setTimeout(() => { location.reload(); }, 500);
                        }
                    }
                } else {
                    alert('Atama hatası: ' + (data.message || 'Bilinmeyen bir sorun oluştu. Lütfen konsolu kontrol edin.'));
                    console.error('[Assign Reviewer] Assignment error from server:', data.message);
                    // Hata durumunda onay mekanizmasını sıfırla
                    assignmentConfirmationContainer.style.display = 'none';
                    confirmAssignmentBtn.style.display = 'none';
                    assignmentConfirmationCheckbox.checked = false;
                }
            })
            .catch(error => {
                console.error('Atama sırasında ağ/fetch hatası:', error);
                // Kullanıcıya gösterilen alert mesajı daha genel olabilir veya error.message'ı içerebilir.
                alert('Atama yapılırken bir sunucu/ağ hatası oluştu. Lütfen konsolu kontrol edin. Hata: ' + error.message);
                 // Hata durumunda onay mekanizmasını sıfırla
                assignmentConfirmationContainer.style.display = 'none';
                confirmAssignmentBtn.style.display = 'none';
                assignmentConfirmationCheckbox.checked = false;
            });
        }

        // YENİ: Hakem Atamasını İptal Etme Fonksiyonu
        function unassignReviewer(reviewerId, projectId, buttonElement) {
            if (!confirm(`Bu hakemin (${reviewerId}) proje (${projectId}) üzerindeki atamasını gerçekten iptal etmek istiyor musunuz?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'unassign_reviewer');
            formData.append('project_id', projectId);
            formData.append('reviewer_id', reviewerId);

            fetch('public/assignment.php', { 
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Atama iptal sonucu:', data);
                if (data.status === 'success') {
                    if (assignments[projectId] && assignments[projectId][reviewerId]) {
                        delete assignments[projectId][reviewerId];
                    }
                    let assignedCountForProject = assignments[projectId] ? Object.keys(assignments[projectId]).length : 0;

                    if (currentProjectAssignButton && currentProjectAssignButton.getAttribute('data-project-id') === projectId) {
                        currentProjectAssignButton.setAttribute('data-assigned-count', assignedCountForProject);
                        const statusBadge = currentProjectAssignButton.querySelector('.status-badge');
                        if (statusBadge) {
                            if (assignedCountForProject < 2 && assignedCountForProject > 0) {
                                statusBadge.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${assignedCountForProject}/2 Hakem`;
                                statusBadge.classList.remove('pending', 'assigned');
                                statusBadge.classList.add('warning');
                            } else if (assignedCountForProject === 0) {
                                statusBadge.innerHTML = '<i class="fas fa-clock"></i> Atama Bekliyor';
                                statusBadge.classList.remove('warning', 'assigned');
                                statusBadge.classList.add('pending');
                            } else { 
                                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> ${assignedCountForProject}/2 Hakem'; // Should be 2/2 if >=2
                                statusBadge.classList.remove('warning', 'pending');
                                statusBadge.classList.add('assigned');
                            }
                        }
                    }
                    
                    if (assignedCountForProject >= 1) {
                        alert(data.message || `Hakem ataması iptal edildi. Projede şimdi ${assignedCountForProject} hakem atanmış durumda.`);
                        if (assignModal.dataset.currentProjectId === projectId) {
                            loadReviewerListsInModal(projectId); // Modal içeriğini yenile
                        }
                    } else { 
                        alert(data.message || 'Tüm hakem atamaları iptal edildi. Proje atama bekliyor ve sayfa yenilenecek.');
                        const modalInstance = bootstrap.Modal.getInstance(assignModal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        location.reload();
                    }

                } else {
                    alert('Atama iptal hatası: ' + (data.message || 'Bilinmeyen bir sorun oluştu.'));
                }
            })
            .catch(error => {
                console.error('Atama iptali sırasında ağ/fetch hatası:', error);
                alert('Atama iptal edilirken bir ağ hatası oluştu. Lütfen konsolu kontrol edin.');
            });
        }

        // Modal içindeki dinamik elementlere tema uygulamak için ayrı bir fonksiyon
        const applyThemeForModalElements = (theme, modalElement) => {
            const modalCards = modalElement.querySelectorAll('.reviewer-card');
            const modalKeywordBadges = modalElement.querySelectorAll('.keyword-badge');
            const modalMatchingScores = modalElement.querySelectorAll('.matching-score');
            const modalButtons = modalElement.querySelectorAll('.assign-btn');

            // CSS değişkenleri zaten root elementte tanımlı, bu yüzden elementlerin
            // CSS'leri bu değişkenleri kullanacak şekilde ayarlandıysa tekrar JS ile stil vermeye gerek yok.
            // Ancak bazı özel durumlar için veya emin olmak adına burada da stil ayarlanabilir.
            
            // Örnek: Hakem kartları
            // modalCards.forEach(card => {
            //     card.style.backgroundColor = root.style.getPropertyValue('--card-bg');
            //     card.style.color = root.style.getPropertyValue('--text-color');
            // });

            // Butonların teması (btn-primary, btn-success vs. Bootstrap sınıfları ve CSS değişkenleriyle yönetiliyor)
            modalButtons.forEach(button => {
                if (!button.classList.contains('assigned')) {
                     button.classList.remove('btn-outline-light', 'btn-outline-dark');
                     if (theme === 'dark') {
                         button.classList.add('btn-primary'); // Koyu temada accent color
                     } else {
                         button.classList.add('btn-primary'); // Açık temada da Bootstrap primary
                     }
                } else {
                     button.classList.add('btn-success'); // Atanmış butonlar her zaman yeşil
                }
            });

            // Eşleşme skorları için renkler zaten dinamik olarak atanıyor (matching-high vs.)
            // ve CSS'de bu sınıflar için tema değişkenleri kullanılabilir.
        };

        // YENİ EKLENEN KOD BAŞLANGICI: Proje Arama İşlevselliği
        function createProjectListItem(project) {
            const listItem = document.createElement('li');
            listItem.className = 'project-item';
            listItem.setAttribute('data-bs-toggle', 'modal');
            listItem.setAttribute('data-bs-target', '#assignModal');
            listItem.setAttribute('data-project-id', project.id);
            listItem.setAttribute('data-project-title', project.title);
            listItem.setAttribute('data-project-keywords', project.keywords);
            listItem.setAttribute('data-assigned-count', project.assigned_count);

            let statusBadgeHtml = '';
            const assignedCount = parseInt(project.assigned_count, 10);

            if (assignedCount >= 2) {
                statusBadgeHtml = `<span class="status-badge assigned">
                                        <i class="fas fa-check-circle"></i> ${assignedCount}/2 Hakem
                                   </span>`;
            } else if (assignedCount > 0) {
                statusBadgeHtml = `<span class="status-badge warning">
                                        <i class="fas fa-exclamation-circle"></i> ${assignedCount}/2 Hakem
                                   </span>`;
            } else {
                statusBadgeHtml = `<span class="status-badge pending">
                                        <i class="fas fa-clock"></i> Atama Bekliyor
                                   </span>`;
            }

            let keywordsHtml = '';
            if (project.keywords) {
                const keywordsArray = project.keywords.split(',');
                keywordsArray.forEach(keyword => {
                    const trimmedKeyword = keyword.trim();
                    if (trimmedKeyword !== '') {
                        const badgeSpan = document.createElement('span');
                        badgeSpan.className = 'keyword-badge';
                        badgeSpan.textContent = trimmedKeyword;
                        keywordsHtml += badgeSpan.outerHTML;
                    }
                });
            }

            const projectIdSpan = document.createElement('span');
            projectIdSpan.className = 'project-id';
            projectIdSpan.textContent = `#${project.id}`;

            const projectTitleH3 = document.createElement('h3');
            projectTitleH3.className = 'project-title';
            projectTitleH3.textContent = project.title;

            const projectKeywordsDiv = document.createElement('div');
            projectKeywordsDiv.className = 'project-keywords';
            projectKeywordsDiv.innerHTML = keywordsHtml;

            listItem.appendChild(projectIdSpan);
            listItem.innerHTML += statusBadgeHtml; 
            listItem.appendChild(projectTitleH3);
            listItem.appendChild(projectKeywordsDiv);

            return listItem;
        }

        function handleProjectSearch(event) {
            const searchInput = event.target;
            const searchTerm = searchInput.value.trim();
            const targetListId = searchInput.id === 'pending-project-search' ? 'pending-project-list' : 'assigned-project-list';
            const projectListElement = document.getElementById(targetListId);

            const isPendingSearch = targetListId === 'pending-project-list';

            if (searchTerm.length > 2 || searchTerm.length === 0) {
                projectListElement.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Aranıyor...</div>';

                fetch(`public/assignment.php?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(result => {
                        projectListElement.innerHTML = ''; 
                        if (result.status === 'success' && result.data) {
                            let foundItems = false;
                            result.data.forEach(project => {
                                const meetsCondition = isPendingSearch ? (parseInt(project.assigned_count, 10) < 2) : (parseInt(project.assigned_count, 10) >= 2);
                                if (meetsCondition) {
                                    const listItem = createProjectListItem(project);
                                    projectListElement.appendChild(listItem);
                                    foundItems = true;
                                }
                            });
                            if (!foundItems) {
                                projectListElement.innerHTML = '<div class="text-center py-3"><i class="fas fa-info-circle"></i> Bu kritere uygun proje bulunamadı.</div>';
                            }
                        } else {
                            projectListElement.innerHTML = `<div class="text-center py-3 text-danger"><i class="fas fa-exclamation-triangle"></i> Arama hatası: ${result.message || 'Bilinmeyen hata'}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Proje arama hatası:', error);
                        projectListElement.innerHTML = '<div class="text-center py-3 text-danger"><i class="fas fa-exclamation-triangle"></i> Arama sırasında bir ağ hatası oluştu.</div>';
                    });
            } else if (searchTerm.length > 0 && searchTerm.length <=2) {
                 // Arama terimi çok kısaysa bir mesaj gösterilebilir veya mevcut liste korunabilir.
                 // Şimdilik bir şey yapmıyoruz, kullanıcı 3 karakter girdiğinde arama tetiklenecek.
                 // Eğer boş değilse ve 3 karakterden kısaysa eski sonuçlar kalır.
                 // İstenirse buraya "Daha uzun bir terim girin" mesajı eklenebilir.
            }
        }

        const pendingSearchInput = document.getElementById('pending-project-search');
        const assignedSearchInput = document.getElementById('assigned-project-search');

        if (pendingSearchInput) {
            pendingSearchInput.addEventListener('input', handleProjectSearch);
        }
        if (assignedSearchInput) {
            assignedSearchInput.addEventListener('input', handleProjectSearch);
        }
        // YENİ EKLENEN KOD SONU

    });
    </script>

</body>
</html>