<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
require '../includes/db.php';

// --- LOGIC HANDLERS ---

// 1. Update Text & Hero Video
if (isset($_POST['update_text'])) {
    foreach ($_POST['content'] as $key => $val) {
        $stmt = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?");
        $stmt->execute([$val, $key]);
    }
    
    // Check for Hero Video Upload during Text Save
    if (isset($_FILES['hero_video_upload']) && $_FILES['hero_video_upload']['error'] === 0) {
        $uploadDir = '../assets/videos/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

        $fileName = time() . '_' . basename($_FILES['hero_video_upload']['name']);
        $targetPath = $uploadDir . $fileName;
        $dbPath = 'assets/videos/' . $fileName;

        $allowed = ['mp4', 'webm'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES['hero_video_upload']['tmp_name'], $targetPath)) {
                // Ensure the row exists before updating
                $check = $pdo->query("SELECT count(*) FROM site_content WHERE content_key = 'hero_video'")->fetchColumn();
                if (!$check) {
                    $pdo->prepare("INSERT INTO site_content (content_key, content_value) VALUES ('hero_video', ?)")->execute([$dbPath]);
                } else {
                    $stmt = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'hero_video'");
                    $stmt->execute([$dbPath]);
                }
            }
        }
    }

    header("Location: index.php?page=content&msg=updated"); exit;
}

// 2. Upload Fish
if (isset($_POST['upload_fish']) && !empty($_FILES['image']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['image']['tmp_name']);
    $sql = "INSERT INTO gallery (title, scientific_name, origin, category, grade, image_data, status) VALUES (?,?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([$_POST['title'], $_POST['scientific'], $_POST['origin'], $_POST['category'], $_POST['grade'], $imgData]);
    header("Location: index.php?page=gallery&msg=added"); exit;
}

// 3. Edit Fish
if (isset($_POST['edit_fish'])) {
    $id = $_POST['id'];
    $sql = "UPDATE gallery SET title=?, scientific_name=?, origin=?, category=?, grade=? WHERE id=?";
    $params = [$_POST['title'], $_POST['scientific'], $_POST['origin'], $_POST['category'], $_POST['grade'], $id];
    
    if (!empty($_FILES['image']['tmp_name'])) {
        $sql = "UPDATE gallery SET title=?, scientific_name=?, origin=?, category=?, grade=?, image_data=? WHERE id=?";
        $params = [$_POST['title'], $_POST['scientific'], $_POST['origin'], $_POST['category'], $_POST['grade'], file_get_contents($_FILES['image']['tmp_name']), $id];
    }
    
    $pdo->prepare($sql)->execute($params);
    header("Location: index.php?page=gallery&msg=updated"); exit;
}

// 4. Toggle Fish Status
if (isset($_GET['toggle_fish'])) {
    $id = $_GET['toggle_fish'];
    $current = $pdo->query("SELECT status FROM gallery WHERE id=$id")->fetchColumn();
    $newStatus = $current ? 0 : 1;
    $pdo->prepare("UPDATE gallery SET status=? WHERE id=?")->execute([$newStatus, $id]);
    header("Location: index.php?page=gallery"); exit;
}

// 5. Delete Fish
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$_GET['del']]);
    header("Location: index.php?page=gallery&msg=deleted"); exit;
}

// 6. Update Gallery Settings
if (isset($_POST['update_gallery_settings'])) {
    $keys = ['gallery_title', 'gallery_text'];
    foreach ($keys as $key) {
        if(isset($_POST[$key])) {
            $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?")->execute([$_POST[$key], $key]);
        }
    }
    header("Location: index.php?page=gallery&msg=settings_updated"); exit;
}

// 7. Upload Network
if (isset($_POST['upload_network']) && !empty($_FILES['flag']['tmp_name'])) {
    $flagData = file_get_contents($_FILES['flag']['tmp_name']);
    $sql = "INSERT INTO network (country_name, flag_data, status) VALUES (?,?,1)";
    $pdo->prepare($sql)->execute([$_POST['country_name'], $flagData]);
    header("Location: index.php?page=network&msg=added"); exit;
}

// 8. Edit Network
if (isset($_POST['edit_network'])) {
    $id = $_POST['id'];
    $sql = "UPDATE network SET country_name=? WHERE id=?";
    $params = [$_POST['country_name'], $id];

    if (!empty($_FILES['flag']['tmp_name'])) {
        $sql = "UPDATE network SET country_name=?, flag_data=? WHERE id=?";
        $params = [$_POST['country_name'], file_get_contents($_FILES['flag']['tmp_name']), $id];
    }
    $pdo->prepare($sql)->execute($params);
    header("Location: index.php?page=network&msg=updated"); exit;
}

// 9. Toggle Network Status
if (isset($_GET['toggle_net'])) {
    $id = $_GET['toggle_net'];
    $current = $pdo->query("SELECT status FROM network WHERE id=$id")->fetchColumn();
    $newStatus = $current ? 0 : 1;
    $pdo->prepare("UPDATE network SET status=? WHERE id=?")->execute([$newStatus, $id]);
    header("Location: index.php?page=network"); exit;
}

// 10. Delete Network
if (isset($_GET['del_net'])) {
    $pdo->prepare("DELETE FROM network WHERE id=?")->execute([$_GET['del_net']]);
    header("Location: index.php?page=network&msg=deleted"); exit;
}

// 11. Update Network Settings
if (isset($_POST['update_network_settings'])) {
    $keys = ['network_title', 'network_subtitle'];
    foreach ($keys as $key) {
        if(isset($_POST[$key])) {
            $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?")->execute([$_POST[$key], $key]);
        }
    }
    header("Location: index.php?page=network&msg=settings_updated"); exit;
}

// 12. Upload Logo
if (isset($_POST['upload_logo']) && !empty($_FILES['logo']['tmp_name'])) {
    $logoData = file_get_contents($_FILES['logo']['tmp_name']);
    $exists = $pdo->query("SELECT count(*) FROM site_identity WHERE id = 1")->fetchColumn();
    if ($exists) {
        $pdo->prepare("UPDATE site_identity SET logo_data = ? WHERE id = 1")->execute([$logoData]);
    } else {
        $pdo->prepare("INSERT INTO site_identity (id, logo_data) VALUES (1, ?)")->execute([$logoData]);
    }
    header("Location: index.php?page=content&msg=logo_updated");
    exit;
}

// 14. Upload Services Image
if (isset($_POST['upload_services_image']) && !empty($_FILES['services_image_upload']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['services_image_upload']['tmp_name']);
    $stmt = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'services_img'");
    $stmt->execute([$imgData]);
    header("Location: index.php?page=content&msg=updated"); 
    exit;
}
// 15. Upload About Carousel Slide
if (isset($_POST['upload_about_slide']) && !empty($_FILES['slide_image']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['slide_image']['tmp_name']);
    $pdo->prepare("INSERT INTO about_carousel (image_data) VALUES (?)")->execute([$imgData]);
    header("Location: index.php?page=content&tab=about&msg=slide_added"); exit;
}

// 16. Delete About Carousel Slide
if (isset($_GET['del_about_slide'])) {
    $pdo->prepare("DELETE FROM about_carousel WHERE id=?")->execute([$_GET['del_about_slide']]);
    header("Location: index.php?page=content&tab=about&msg=slide_deleted"); exit;
}

// 17. Upload About Badge
if (isset($_POST['upload_about_badge']) && !empty($_FILES['badge_image']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['badge_image']['tmp_name']);
    $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'about_badge_img'")->execute([$imgData]);
    header("Location: index.php?page=content&tab=about&msg=badge_updated"); exit;
}

// Get Page Data
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$fishCount = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
$textCount = $pdo->query("SELECT COUNT(*) FROM site_content")->fetchColumn();
$netCount = $pdo->query("SELECT COUNT(*) FROM network")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>CExport Admin</title>
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.0/tinymce.min.js" referrerpolicy="origin"></script>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { bg: '#F4F7FE', card: '#FFFFFF', primary: '#00C2CB', secondary: '#001f2bff', textMain: '#009fa6' }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #E0F9FA; border-radius: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        .tox-tinymce { border-radius: 1rem !important; border: 1px solid #E5E7EB; !important; margin-top: 0.5rem; }
        
        .tab-btn.active, .gal-tab-btn.active, .net-tab-btn.active { background-color: #00C2CB; color: white; box-shadow: 0 4px 12px rgba(24, 209, 255, 0.3); }
        .tab-content, .gal-tab-content, .net-tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active, .gal-tab-content.active, .net-tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .toggle-checkbox:checked { right: 0; border-color: #00C2CB; }
        .toggle-checkbox:checked + .toggle-label { background-color: #00C2CB; }
    </style>
</head>
<body class="bg-bg text-textMain h-screen flex overflow-hidden font-sans">

    <aside class="w-72 bg-card flex flex-col border-r border-gray-100 hidden md:flex">
        <div class="p-8 flex items-center gap-3">
           <div class="flex items-center gap-2">
                <img id="nav-logo" src="../view_image.php?type=logo" alt="CExport Logo" class="h-12 w-auto object-contain drop-shadow-md transition-all duration-300 "><span class="font-extrabold text-secondary ml-5">CMS</span>
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-2 mt-4 m">
            <a href="?page=dashboard" class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='dashboard' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="layout-grid" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Dashboard</span>
            </a>
            <a href="?page=content" class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='content' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="file-text" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Site Content</span>
            </a>
            <a href="?page=gallery" class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='gallery' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="image" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Gallery Manager</span>
            </a>
            <a href="?page=network" class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='network' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="globe" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Global Network</span>
            </a>
        </nav>

        <div class="p-6">
            <div class="bg-gradient-to-br from-[#868CFF] to-[#00C2CB] rounded-3xl p-6 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white/20 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-white rounded-full mx-auto mb-3 flex items-center justify-center text-primary font-bold">A</div>
                    <h4 class="font-bold text-lg">Admin User</h4>
                    <a href="logout.php" class="inline-block mt-4 bg-white/20 hover:bg-white/30 backdrop-blur-md py-2 px-6 rounded-xl text-sm font-medium transition-colors">Log Out</a>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full relative overflow-y-auto">
        <header class="h-24 px-8 pt-5 pb-4 flex items-center justify-between bg-bg/50 backdrop-blur-md sticky top-0 z-20">
            <div><p class="text-secondary text-sm font-medium">Pages / <?= ucfirst($page) ?></p><h1 class="text-3xl font-bold text-textMain"><?= ucfirst($page) ?></h1></div>
            <div class="flex items-center gap-4">
                 <div class="bg-white px-4 py-2 rounded-full shadow-sm text-sm text-secondary flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4"></i> <?= date("F j, Y") ?></div>
                 <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold shadow-lg shadow-primary/20">AD</div>
            </div>
        </header>

        <div class="p-8 pb-20">
            <?php if(isset($_GET['msg'])): ?>
                <div class="mb-6 p-4 rounded-2xl bg-green-100 text-green-700 flex items-center gap-3 border border-green-200 shadow-sm"><i data-lucide="check-circle"></i> Action Successful</div>
            <?php endif; ?>

            <?php if ($page == 'dashboard'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow"><div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-primary"><i data-lucide="fish" class="w-7 h-7"></i></div><div><p class="text-secondary text-sm font-medium">Total Species</p><h3 class="text-2xl font-bold text-textMain"><?= $fishCount ?></h3></div></div>
                    <div class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow"><div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-primary"><i data-lucide="layers" class="w-7 h-7"></i></div><div><p class="text-secondary text-sm font-medium">Content Blocks</p><h3 class="text-2xl font-bold text-textMain"><?= $textCount ?></h3></div></div>
                    <div class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow"><div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-orange-500"><i data-lucide="globe" class="w-7 h-7"></i></div><div><p class="text-secondary text-sm font-medium">Active Countries</p><h3 class="text-2xl font-bold text-textMain"><?= $netCount ?></h3></div></div>
                    <div class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow"><div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-green-500"><i data-lucide="trending-up" class="w-7 h-7"></i></div><div><p class="text-secondary text-sm font-medium">Weekly Visits</p><h3 class="text-2xl font-bold text-textMain">3.2K</h3></div></div>
                </div>
                <div class="bg-card p-6 rounded-[20px] shadow-sm h-96 relative mb-8">
                    <div class="flex justify-between items-center mb-6"><h3 class="text-lg font-bold text-textMain">Visitor Analytics</h3></div>
                    <canvas id="myChart" class="w-full h-full"></canvas>
                </div>
            <?php endif; ?>

            <?php if ($page == 'content'): ?>
                <?php 
                // 1. Group Data Logic - Includes Social, Contact, and General
                $groups = ['hero' => [], 'about' => [], 'services' => [], 'social' => [], 'stats' => [], 'contact' => [], 'general' => []];
                
                $stmt = $pdo->query("SELECT * FROM site_content");
                while ($row = $stmt->fetch()) {
                    if (strpos($row['content_key'], 'gallery_') === 0 || strpos($row['content_key'], 'network_') === 0) { continue; }
                    
                    if (strpos($row['content_key'], 'hero_') === 0) { $groups['hero'][] = $row; }
                    elseif (strpos($row['content_key'], 'about_') === 0 || strpos($row['content_key'], 'tab_') === 0) { $groups['about'][] = $row; }
                    elseif (strpos($row['content_key'], 'services_') === 0 || strpos($row['content_key'], 'service_') === 0) { $groups['services'][] = $row; }
                    elseif (strpos($row['content_key'], 'social_') === 0) { $groups['social'][] = $row; }
                    elseif (strpos($row['content_key'], 'stats_') === 0) { $groups['stats'][] = $row; }
                    elseif (strpos($row['content_key'], 'contact_') === 0) { $groups['contact'][] = $row; }
                    else { $groups['general'][] = $row; }
                }
                ?>
                
                <div class="bg-card p-8 rounded-[20px] shadow-sm min-h-[600px]">
                    <h2 class="text-xl font-bold text-textMain mb-6 flex items-center gap-2">
                        <i data-lucide="edit-3" class="w-5 h-5 text-primary"></i> Edit Website Content
                    </h2>

                    <div class="flex flex-wrap gap-3 mb-8 border-b border-gray-100 pb-4">
                        <?php foreach(array_keys($groups) as $g): ?>
                            <?php if(empty($groups[$g]) && $g !== 'social' && $g !== 'contact') continue; ?>
                            <button onclick="switchTab('<?= $g ?>')" class="tab-btn <?= $g=='hero'?'active':'text-secondary' ?> px-5 py-2 rounded-xl text-sm font-bold transition-all" id="btn-<?= $g ?>"><?= ucfirst($g) ?></button>
                        <?php endforeach; ?>
                        <button onclick="switchTab('logo')" class="tab-btn text-secondary px-5 py-2 rounded-xl text-sm font-bold transition-all" id="btn-logo">Logo & Identity</button>
                    </div>

                    <!-- FIXED: Added enctype to allow file uploads (video) -->
                    <form method="post" enctype="multipart/form-data">
                        <?php foreach($groups as $groupKey => $fields): ?>
                            <div id="tab-<?= $groupKey ?>" class="tab-content <?= $groupKey === 'hero' ? 'active' : '' ?>">
                                
                                <?php 
                                    // Helper function to find value by key safely
                                    $findKey = function($k) use ($fields) {
                                        foreach($fields as $f) { if($f['content_key'] == $k) return $f['content_value']; }
                                        return '';
                                    };
                                ?>

                                <!-- === CUSTOM HERO TAB ORGANIZATION === -->
                                <?php if($groupKey === 'hero'): ?>
                                    <div class="space-y-8">
                                        <!-- 1. Hero Section Title -->
                                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="heading" class="w-5 h-5"></i></div>
                                                <h4 class="font-bold text-gray-700">1. Hero Section Title</h4>
                                            </div>
                                            <textarea name="content[hero_title]" class="tinymce w-full"><?= $findKey('hero_title') ?></textarea>
                                            <p class="text-xs text-gray-400 mt-2">Main headline of the website.</p>
                                        </div>

                                        <!-- 2. Hero Subtitle -->
                                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="align-left" class="w-5 h-5"></i></div>
                                                <h4 class="font-bold text-gray-700">2. Hero Subtitle</h4>
                                            </div>
                                            <textarea name="content[hero_subtitle]" class="tinymce w-full"><?= $findKey('hero_subtitle') ?></textarea>
                                            <p class="text-xs text-gray-400 mt-2">Text that appears below the main headline.</p>
                                        </div>

                                        <!-- 3. Hero Feature Title -->
                                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="p-2 bg-purple-100 text-purple-600 rounded-lg"><i data-lucide="star" class="w-5 h-5"></i></div>
                                                <h4 class="font-bold text-gray-700">3. Hero Feature Title</h4>
                                            </div>
                                            <textarea name="content[hero_feature_title]" class="tinymce w-full"><?= $findKey('hero_feature_title') ?></textarea>
                                            <p class="text-xs text-gray-400 mt-2">Header for the highlighted feature section.</p>
                                        </div>

                                        <!-- 4. Hero Feature Subtitle -->
                                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="p-2 bg-purple-100 text-purple-600 rounded-lg"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                                                <h4 class="font-bold text-gray-700">4. Hero Feature Subtitle</h4>
                                            </div>
                                            <textarea name="content[hero_feature_subtitle]" class="tinymce w-full"><?= $findKey('hero_feature_subtitle') ?></textarea>
                                            <p class="text-xs text-gray-400 mt-2">Description for the highlighted feature.</p>
                                        </div>

                                        <!-- Hero Video Upload REDESIGNED -->
                                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="p-2 bg-pink-100 text-pink-600 rounded-lg"><i data-lucide="video" class="w-5 h-5"></i></div>
                                                <h4 class="font-bold text-gray-700">Hero Background Video</h4>
                                            </div>
                                            
                                            <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors group relative overflow-hidden">
                                                
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6 relative z-10">
                                                    <div class="w-12 h-12 mb-3 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 group-hover:text-primary transition-colors">
                                                        <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                                                    </div>
                                                    <p class="mb-1 text-sm text-gray-500"><span class="font-semibold">Click to upload video</span> or drag and drop</p>
                                                    <p class="text-xs text-gray-400">MP4 or WEBM (Max 10MB)</p>
                                                </div>
                                                
                                                <input type="file" name="hero_video_upload" class="hidden" accept="video/mp4,video/webm" onchange="document.getElementById('video-file-name').textContent = this.files[0].name">
                                                
                                                <!-- Status Indicator -->
                                                 <?php if(!empty($findKey('hero_video'))): ?>
                                                    <div class="absolute bottom-3 right-3 bg-green-100 text-green-700 text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-bold shadow-sm">
                                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Video Active
                                                    </div>
                                                <?php endif; ?>
                                            </label>
                                            <p id="video-file-name" class="text-sm text-center text-primary mt-3 font-medium min-h-[1.25rem]"></p>
                                        </div>
                                    </div>

                                <!-- === SOCIAL MEDIA TAB === -->
                                <?php elseif($groupKey === 'social'): ?>
                                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8">
                                        <div class="flex items-center gap-3 mb-6">
                                            <div class="p-2 bg-blue-50 rounded-lg text-blue-600"><i data-lucide="share-2" class="w-6 h-6"></i></div>
                                            <h2 class="text-xl font-bold text-gray-800">Social Media Links</h2>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Facebook URL</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-3.5 text-gray-400"><i data-lucide="facebook" class="w-5 h-5"></i></span>
                                                    <input type="text" name="content[social_facebook]" value="<?= htmlspecialchars($findKey('social_facebook')) ?>" 
                                                        class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Instagram URL</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-3.5 text-gray-400"><i data-lucide="instagram" class="w-5 h-5"></i></span>
                                                    <input type="text" name="content[social_instagram]" value="<?= htmlspecialchars($findKey('social_instagram')) ?>" 
                                                        class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                                                </div>
                                            </div>

                                            <div class="col-span-1 md:col-span-2">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">WhatsApp Link (Full URL)</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-3.5 text-green-500"><i data-lucide="phone" class="w-5 h-5"></i></span>
                                                    <input type="text" name="content[social_whatsapp]" value="<?= htmlspecialchars($findKey('social_whatsapp')) ?>" 
                                                        placeholder="https://wa.me/919876543210"
                                                        class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                                                    <p class="text-xs text-gray-400 mt-1 ml-2">Format: https://wa.me/YOUR_NUMBER (include country code, no +)</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <!-- === CONTACT TAB === -->
                                <?php elseif($groupKey === 'contact'): ?>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                                        <div class="space-y-6">
                                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                                <label class="block text-xs font-bold text-secondary mb-2 uppercase">Section Title</label>
                                                <textarea name="content[contact_title]" class="tinymce w-full"><?= $findKey('contact_title') ?></textarea>
                                            </div>

                                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                                <label class="block text-xs font-bold text-secondary mb-2 uppercase">Description Text</label>
                                                <textarea name="content[contact_desc]" class="tinymce w-full"><?= $findKey('contact_desc') ?></textarea>
                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm h-full">
                                                <h4 class="font-bold text-gray-700 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                                    <i data-lucide="phone" class="w-5 h-5 text-blue-500"></i> Contact Details
                                                </h4>

                                                <div class="space-y-6">
                                                    <div>
                                                        <label class="block text-xs font-bold text-secondary mb-2">Phone Number</label>
                                                        <textarea name="content[contact_phone]" class="tinymce w-full"><?= $findKey('contact_phone') ?></textarea>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-bold text-secondary mb-2">Email Address</label>
                                                        <textarea name="content[contact_email]" class="tinymce w-full"><?= $findKey('contact_email') ?></textarea>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-bold text-secondary mb-2">Office Address</label>
                                                        <textarea name="content[contact_address]" class="tinymce w-full"><?= $findKey('contact_address') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    
                                    <!-- === STANDARD TABS LOGIC === -->
                                    <?php if($groupKey === 'services'): ?>
                                        <div class="mb-8 bg-white p-6 rounded-2xl border border-gray-200">
                                            <h4 class="text-primary font-bold mb-4">Services Section Image</h4>
                                            <div class="flex items-center gap-6">
                                                <img src="../view_image.php?type=content&key=services_img&t=<?= time() ?>" class="w-32 h-32 object-cover rounded-xl border border-gray-200">
                                                <div class="flex-1">
                                                    <label class="block text-xs font-bold text-secondary mb-2 uppercase">Upload New Image</label>
                                                    <input type="file" name="services_image_upload" class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-primary/10 file:text-primary">
                                                </div>
                                                <button type="submit" name="upload_services_image" formenctype="multipart/form-data" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold">Upload to DB</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($groupKey === 'about'): ?>
                                        <div class="bg-white p-6 rounded-2xl border border-gray-200 mb-6">
                                            <h4 class="text-primary font-bold mb-4">Image Carousel</h4>
                                            <div class="flex items-end gap-4 mb-6 border-b border-gray-100 pb-6">
                                                <div class="flex-1">
                                                    <label class="block text-xs font-bold text-secondary mb-2">ADD NEW SLIDE</label>
                                                    <input type="file" name="slide_image" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-primary/10 file:text-primary">
                                                </div>
                                                <button type="submit" name="upload_about_slide" formenctype="multipart/form-data" class="bg-primary text-white px-6 py-2 rounded-lg font-bold">Upload</button>
                                            </div>
                                            <div class="grid grid-cols-4 gap-4">
                                                <?php foreach($pdo->query("SELECT id FROM about_carousel") as $slide): ?>
                                                    <div class="relative group rounded-xl overflow-hidden aspect-video bg-gray-100 object-cover">
                                                        <img src="../view_image.php?type=about_slide&id=<?= $slide['id'] ?>" class="w-full h-full object-cover">
                                                        <a href="?del_about_slide=<?= $slide['id'] ?>" onclick="return confirm('Delete slide?')" class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-white object-cover">
                                                            <i data-lucide="trash-2"></i>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="bg-white p-6 rounded-2xl border border-gray-200 mb-6 flex items-center gap-6">
                                            <img src="../view_image.php?type=content&key=about_badge_img&t=<?= time() ?>" class="w-20 h-20 object-contain bg-gray-50 rounded-full border">
                                            <div class="flex-1">
                                                <h4 class="text-primary font-bold">Floating Badge Icon</h4>
                                                <p class="text-xs text-secondary mb-2">The circular icon at the bottom left (e.g., Koi Fish).</p>
                                                <div class="flex gap-2">
                                                    <input type="file" name="badge_image" class="text-sm">
                                                    <button type="submit" name="upload_about_badge" formenctype="multipart/form-data" class="bg-secondary text-white px-4 py-1 rounded text-xs font-bold">Update Icon</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-bg p-6 rounded-2xl border border-blue-100 mb-6">
                                            <h4 class="text-primary font-bold mb-4">Info Tabs (Vision, Mission, etc)</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <?php for($i=1; $i<=4; $i++): 
                                                    // Helper to safely get value from $fields array
                                                    $findTab = function($k) use ($fields) {
                                                        foreach($fields as $f) if($f['content_key'] == $k) return $f['content_value'];
                                                        return '';
                                                    };
                                                ?>
                                                    <div class="bg-white p-4 rounded-xl shadow-sm">
                                                        <span class="text-xs font-bold text-gray-400 uppercase">Tab <?= $i ?></span>
                                                        <input type="text" name="content[tab_<?= $i ?>_label]" value="<?= $findTab('tab_'.$i.'_label') ?>" class="w-full font-bold border-b border-gray-200 mb-2 focus:outline-none" placeholder="Label">
                                                        <input type="text" name="content[tab_<?= $i ?>_title]" value="<?= $findTab('tab_'.$i.'_title') ?>" class="w-full text-sm font-bold text-primary mb-2 focus:outline-none" placeholder="Heading">
                                                        <textarea name="content[tab_<?= $i ?>_text]" class="w-full text-xs text-gray-500 bg-gray-50 p-2 rounded h-20 focus:outline-none"><?= $findTab('tab_'.$i.'_text') ?></textarea>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="grid grid-cols-1 gap-6">
                                        <?php foreach($fields as $row): 
                                            // SKIP ITEMS HANDLED IN CUSTOM LAYOUTS
                                            // 1. Skip Hero Specifics (handled above)
                                            if(in_array($row['content_key'], ['hero_title', 'hero_subtitle', 'hero_feature_title', 'hero_feature_subtitle'])) continue;
                                            
                                            // 2. Skip Services/About Images
                                            if($row['content_key'] == 'services_img' || $row['content_key'] == 'about_badge_img') continue;
                                            
                                            // 3. Skip Tab Fields
                                            if(strpos($row['content_key'], 'tab_') === 0) continue; 
                                            
                                            // 4. Skip Social Fields (Handled in custom Social tab)
                                            if(strpos($row['content_key'], 'social_') === 0) continue; 
                                            
                                            // 5. Skip Contact Fields (Handled in custom Contact tab)
                                            if(strpos($row['content_key'], 'contact_') === 0) continue;
                                            
                                            $label = !empty($row['label']) ? $row['label'] : ucwords(str_replace('_', ' ', $row['content_key'])); 
                                        ?>
                                            <div>
                                                <label class='block text-xs font-bold text-secondary mb-2 uppercase tracking-wide'><?= $label ?></label>
                                                <textarea name='content[<?= $row['content_key'] ?>]' class='tinymce w-full shadow-sm border-gray-100'><?= $row['content_value'] ?></textarea>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php endif; ?>
                                
                                <div class="pt-8 mt-4">
                                    <button type="submit" name="update_text" class="bg-primary hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg transition-all transform hover:-translate-y-1 w-full md:w-auto">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>

                    <div id="tab-logo" class="tab-content hidden">
                        <div class="bg-bg p-8 rounded-2xl border border-gray-200 text-center max-w-2xl mx-auto">
                            <h3 class="text-textMain font-bold mb-6 text-lg">Site Identity</h3>
                            <form method="post" enctype="multipart/form-data">
                                <div class="bg-white p-6 inline-block rounded-xl shadow-sm border mb-6">
                                    <img src="../view_image.php?type=logo&t=<?= time() ?>" class="h-24 object-contain mx-auto" alt="Current Logo">
                                    <p class="text-xs text-secondary mt-2 font-medium">Current Logo / Favicon</p>
                                </div>
                                <div class="max-w-md mx-auto space-y-5">
                                    <div>
                                        <label class="block text-xs font-bold text-secondary mb-2 uppercase tracking-wide">Upload New Image (PNG/WEBP)</label>
                                        <div class="bg-white p-2 rounded-xl border-2 border-dashed border-gray-300 hover:border-primary transition-colors cursor-pointer group">
                                            <input type="file" name="logo" required class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer" accept="image/png, image/jpeg, image/webp">
                                        </div>
                                    </div>
                                    <button type="submit" name="upload_logo" class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg transition-all transform hover:-translate-y-1">
                                        Update Identity
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

            <?php if ($page == 'gallery'): 
                $galTitle = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'gallery_title'")->fetchColumn() ?: '';
                $galText = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'gallery_text'")->fetchColumn() ?: '';
            ?>
                <div class="flex gap-4 mb-6">
                     <button onclick="switchGalleryTab('inventory')" class="gal-tab-btn active px-6 py-3 rounded-xl bg-card text-textMain font-bold shadow-sm border border-transparent flex items-center gap-2 hover:border-primary transition-all" id="gbtn-inventory"><i data-lucide="grid"></i> Fish Inventory</button>
                     <button onclick="switchGalleryTab('settings')" class="gal-tab-btn px-6 py-3 rounded-xl bg-bg text-secondary font-bold border border-transparent flex items-center gap-2 hover:bg-card hover:text-textMain transition-all" id="gbtn-settings"><i data-lucide="settings"></i> Page Settings</button>
                </div>

                <div id="gtab-inventory" class="gal-tab-content active">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-1">
                            <div class="bg-card p-6 rounded-[20px] shadow-sm sticky top-28">
                                <h3 class="text-lg font-bold text-textMain mb-4">Add New Fish</h3>
                                <form method="post" enctype="multipart/form-data" class="space-y-4">
                                    <input type="text" name="title" placeholder="Title" class="w-full bg-bg p-3 rounded-xl outline-none" required>
                                    <input type="text" name="scientific" placeholder="Scientific Name" class="w-full bg-bg p-3 rounded-xl outline-none">
                                    <div class="grid grid-cols-2 gap-4">
                                        <input type="text" name="origin" placeholder="Origin" class="w-full bg-bg p-3 rounded-xl outline-none">
                                        <input type="text" name="grade" placeholder="Grade" class="w-full bg-bg p-3 rounded-xl outline-none">
                                    </div>
                                    <select name="category" class="w-full bg-bg p-3 rounded-xl outline-none"><option value="freshwater">Freshwater</option><option value="marine">Marine</option></select>
                                    <div class="bg-bg rounded-xl p-4 border-2 border-dashed border-gray-200 text-center cursor-pointer group">
                                        <input type="file" name="image" required class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                    </div>
                                    <button type="submit" name="upload_fish" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-colors shadow-lg">Add to Inventory</button>
                                </form>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <div class="bg-card rounded-[20px] shadow-sm overflow-hidden min-h-[500px]">
                                <div class="p-6 border-b border-gray-100 flex justify-between items-center"><h3 class="text-xl font-bold text-textMain">Active Gallery</h3><span class="text-xs font-bold bg-green-100 text-green-600 px-3 py-1 rounded-full"><?= $fishCount ?> Items</span></div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-gray-50 text-secondary text-xs uppercase font-bold">
                                            <tr><th class="p-4 pl-6">Name</th><th class="p-4">Details</th><th class="p-4">Status</th><th class="p-4 text-right pr-6">Action</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <?php foreach($pdo->query("SELECT * FROM gallery ORDER BY id DESC") as $fish): 
                                                // --- FIX START: Prepare Safe Data for JavaScript ---
                                                // 1. Create a copy of the row data
                                                $fishJS = $fish;
                                                // 2. Remove the heavy image BLOB data (prevents JSON errors)
                                                unset($fishJS['image_data']);
                                                // 3. Encode and Escape for HTML attribute safety
                                                $safeJson = htmlspecialchars(json_encode($fishJS), ENT_QUOTES, 'UTF-8');
                                                // --- FIX END ---
                                            ?>
                                            <tr class="hover:bg-gray-50 transition-colors group">
                                                <td class="p-4 pl-6 flex items-center gap-4">
                                                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-bg border border-gray-100 shrink-0">
                                                        <img src="../view_image.php?id=<?= $fish['id'] ?>" class="w-full h-full object-cover">
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-textMain text-sm"><?= htmlspecialchars($fish['title']) ?></p>
                                                        <p class="text-xs text-secondary"><?= htmlspecialchars($fish['scientific_name']) ?></p>
                                                    </div>
                                                </td>
                                                <td class="p-4 text-sm text-textMain">
                                                    <?= htmlspecialchars($fish['origin']) ?> <span class="text-secondary mx-1">•</span> <?= htmlspecialchars($fish['grade']) ?>
                                                </td>
                                                <td class="p-4">
                                                    <a href="?page=gallery&toggle_fish=<?= $fish['id'] ?>" class="relative inline-block w-10 align-middle select-none transition duration-200 ease-in">
                                                        <input type="checkbox" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer" <?= $fish['status'] ? 'checked right-0 border-primary' : 'left-0 border-gray-300' ?>/>
                                                        <label class="toggle-label block overflow-hidden h-5 rounded-full cursor-pointer <?= $fish['status'] ? 'bg-primary' : 'bg-gray-300' ?>"></label>
                                                    </a>
                                                </td>
                                                <td class="p-4 text-right pr-6 flex justify-end gap-2">
                                                    <button onclick='openEditFish(<?= $safeJson ?>)' class="text-secondary hover:text-primary p-2 bg-gray-100 rounded-lg">
                                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                    </button>
                                                    <a href="?del=<?= $fish['id'] ?>" onclick="return confirm('Delete?')" class="text-secondary hover:text-red-500 p-2 bg-gray-100 rounded-lg">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="gtab-settings" class="gal-tab-content hidden">
                    <div class="bg-card p-8 rounded-[20px] shadow-sm max-w-3xl">
                        <form method="post">
                            <div class="space-y-6">
                                <div><label class="block text-xs font-bold text-secondary mb-2 uppercase">Section Title</label><textarea name="gallery_title" class="tinymce w-full"><?= $galTitle ?></textarea></div>
                                <div><label class="block text-xs font-bold text-secondary mb-2 uppercase">Section Description</label><textarea name="gallery_text" class="tinymce w-full"><?= $galText ?></textarea></div>
                            </div>
                            <div class="pt-6 mt-6 border-t border-gray-100"><button type="submit" name="update_gallery_settings" class="bg-primary hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg">Update Page Content</button></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($page == 'network'): 
                $netTitle = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'network_title'")->fetchColumn() ?: '';
                $netSub = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'network_subtitle'")->fetchColumn() ?: '';
            ?>

            
                <div class="flex gap-4 mb-6">
                     <button onclick="switchNetworkTab('list')" class="net-tab-btn active px-6 py-3 rounded-xl bg-card text-textMain font-bold shadow-sm border border-transparent flex items-center gap-2 hover:border-primary transition-all" id="nbtn-list"><i data-lucide="globe"></i> Manage Countries</button>
                     <button onclick="switchNetworkTab('settings')" class="net-tab-btn px-6 py-3 rounded-xl bg-bg text-secondary font-bold border border-transparent flex items-center gap-2 hover:bg-card hover:text-textMain transition-all" id="nbtn-settings"><i data-lucide="settings"></i> Page Settings</button>
                </div>

                <div id="ntab-list" class="net-tab-content active">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-1">
                            <div class="bg-card p-6 rounded-[20px] shadow-sm sticky top-28">
                                <h3 class="text-lg font-bold text-textMain mb-4">Add Country</h3>
                                <form method="post" enctype="multipart/form-data" class="space-y-4">
                                    <input type="text" name="country_name" class="w-full bg-bg p-3 rounded-xl outline-none" required placeholder="e.g. USA">
                                    <div class="bg-bg rounded-xl p-4 border-2 border-dashed border-gray-200 text-center cursor-pointer">
                                        <input type="file" name="flag" required class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                    </div>
                                    <button type="submit" name="upload_network" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-colors shadow-lg">Add to Network</button>
                                </form>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <div class="bg-card rounded-[20px] shadow-sm overflow-hidden">
                                
                                <div class="p-6 border-b border-gray-100 flex justify-between items-center"><h3 class="text-xl font-bold text-textMain">Active Network</h3><span class="text-xs font-bold bg-green-100 text-green-600 px-3 py-1 rounded-full"><?= $netCount ?> Countries</span></div>
                                
                                <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <?php foreach($pdo->query("SELECT * FROM network ORDER BY id DESC") as $country): 
                                        // --- FIX: Prepare Safe Data for JavaScript ---
                                        $countryJS = $country;
                                        unset($countryJS['flag_data']); // Remove the heavy BLOB data
                                        $safeJson = htmlspecialchars(json_encode($countryJS), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="bg-bg p-4 rounded-xl flex flex-col items-center text-center relative group border <?= $country['status'] ? 'border-transparent' : 'border-red-200 bg-red-50' ?>">
                                        
                                        <div class="w-16 h-16 rounded-full overflow-hidden border-4 border-white shadow-sm mb-3">
                                            <img src="../view_image.php?type=network&id=<?= $country['id'] ?>" class="w-full h-full object-cover">
                                        </div>
                                        
                                        <h4 class="font-bold text-textMain"><?= htmlspecialchars($country['country_name']) ?></h4>
                                        
                                        <div class="flex items-center gap-2 mt-2">
                                            <a href="?page=network&toggle_net=<?= $country['id'] ?>" class="text-xs font-bold px-2 py-1 rounded <?= $country['status'] ? 'bg-green-100 text-green-600' : 'bg-red-200 text-red-600' ?>">
                                                <?= $country['status'] ? 'Active' : 'Inactive' ?>
                                            </a>
                                            
                                            <button onclick='openEditNetwork(<?= $safeJson ?>)' class="text-secondary hover:text-primary">
                                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                            </button>
                                            
                                            <a href="?del_net=<?= $country['id'] ?>" onclick="return confirm('Remove?')" class="text-secondary hover:text-red-500">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="ntab-settings" class="net-tab-content hidden">
                    <div class="bg-card p-8 rounded-[20px] shadow-sm max-w-3xl">
                        <form method="post">
                            <div class="space-y-6">
                                <div><label class="block text-xs font-bold text-secondary mb-2 uppercase">Title</label><textarea name="network_title" class="tinymce w-full"><?= $netTitle ?></textarea></div>
                                <div><label class="block text-xs font-bold text-secondary mb-2 uppercase">Subtitle</label><textarea name="network_subtitle" class="tinymce w-full"><?= $netSub ?></textarea></div>
                            </div>
                            <div class="pt-6 mt-6 border-t border-gray-100"><button type="submit" name="update_network_settings" class="bg-primary hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg">Update Network Text</button></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <div id="editFishModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl transform scale-95 transition-all">
            <div class="flex justify-between items-center mb-6"><h3 class="text-xl font-bold text-textMain">Edit Fish</h3><button onclick="closeModal('editFishModal')" class="text-secondary hover:text-primary"><i data-lucide="x"></i></button></div>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" id="edit_fish_id">
                <input type="text" name="title" id="edit_fish_title" class="w-full bg-bg p-3 rounded-xl outline-none" required placeholder="Title">
                <input type="text" name="scientific" id="edit_fish_sci" class="w-full bg-bg p-3 rounded-xl outline-none" placeholder="Scientific Name">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="origin" id="edit_fish_origin" class="w-full bg-bg p-3 rounded-xl outline-none" placeholder="Origin">
                    <input type="text" name="grade" id="edit_fish_grade" class="w-full bg-bg p-3 rounded-xl outline-none" placeholder="Grade">
                </div>
                <select name="category" id="edit_fish_cat" class="w-full bg-bg p-3 rounded-xl outline-none"><option value="freshwater">Freshwater</option><option value="marine">Marine</option></select>
                <div><label class="text-xs font-bold text-secondary block mb-1">New Image (Optional)</label><input type="file" name="image" class="w-full text-sm"></div>
                <div class="pt-4"><button type="submit" name="edit_fish" class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg">Save Changes</button></div>
            </form>
        </div>
    </div>

    <div id="editNetModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl">
            <div class="flex justify-between items-center mb-6"><h3 class="text-xl font-bold text-textMain">Edit Country</h3><button onclick="closeModal('editNetModal')" class="text-secondary hover:text-primary"><i data-lucide="x"></i></button></div>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" id="edit_net_id">
                <input type="text" name="country_name" id="edit_net_name" class="w-full bg-bg p-3 rounded-xl outline-none" required>
                <div><label class="text-xs font-bold text-secondary block mb-1">New Flag (Optional)</label><input type="file" name="flag" class="w-full text-sm"></div>
                <div class="pt-4"><button type="submit" name="edit_network" class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg">Save Changes</button></div>
            </form>
        </div>
    </div>

    <script>
    lucide.createIcons();

    // 2. Editor Configuration Object (Saved for re-use)
    const tinymceConfig = {
        selector: '.tinymce',
        license_key: 'gpl',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline | align lineheight | numlist bullist',
        height: 300,
        font_family_formats: 'Plus Jakarta Sans=Plus Jakarta Sans,sans-serif; Oswald=Oswald,sans-serif; Arial=arial,helvetica,sans-serif;',
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap'); body { font-family: 'Plus Jakarta Sans', sans-serif; }",
        setup: function (editor) {
            editor.on('change', function () {
                editor.save(); // Syncs data back to textarea automatically
            });
        }
    };

    // Initialize on load
    tinymce.init(tinymceConfig);

    // --- TAB LOGIC ---

    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');
        
        document.querySelectorAll('.tab-btn').forEach(btn => { 
            btn.classList.remove('active'); 
            btn.classList.add('text-secondary'); 
        });
        
        const activeBtn = document.getElementById('btn-' + tabId);
        activeBtn.classList.add('active'); 
        activeBtn.classList.remove('text-secondary');
    }

    function switchGalleryTab(tabName) {
        // Hide all
        document.getElementById('gtab-inventory').classList.remove('active');
        document.getElementById('gtab-inventory').classList.add('hidden');
        
        document.getElementById('gtab-settings').classList.remove('active');
        document.getElementById('gtab-settings').classList.add('hidden');
        
        // Button styles
        document.getElementById('gbtn-inventory').classList.remove('active', 'bg-card', 'text-textMain', 'shadow-sm');
        document.getElementById('gbtn-settings').classList.remove('active', 'bg-card', 'text-textMain', 'shadow-sm');
        document.getElementById('gbtn-inventory').classList.add('bg-bg', 'text-secondary');
        document.getElementById('gbtn-settings').classList.add('bg-bg', 'text-secondary');

        // Show target
        const targetTab = document.getElementById('gtab-' + tabName);
        targetTab.classList.add('active');
        targetTab.classList.remove('hidden');
        
        const targetBtn = document.getElementById('gbtn-' + tabName);
        targetBtn.classList.remove('bg-bg', 'text-secondary');
        targetBtn.classList.add('active', 'bg-card', 'text-textMain', 'shadow-sm');

        // Refresh TinyMCE
        if (tabName === 'settings') {
            setTimeout(() => {
                tinymce.remove('.tinymce');
                tinymce.init(tinymceConfig);
            }, 10);
        }
    }

    function switchNetworkTab(tabName) {
        // Hide all
        document.getElementById('ntab-list').classList.remove('active');
        document.getElementById('ntab-list').classList.add('hidden');
        
        document.getElementById('ntab-settings').classList.remove('active');
        document.getElementById('ntab-settings').classList.add('hidden');
        
        // Button styles
        document.getElementById('nbtn-list').classList.remove('active', 'bg-card', 'text-textMain', 'shadow-sm');
        document.getElementById('nbtn-settings').classList.remove('active', 'bg-card', 'text-textMain', 'shadow-sm');
        document.getElementById('nbtn-list').classList.add('bg-bg', 'text-secondary');
        document.getElementById('nbtn-settings').classList.add('bg-bg', 'text-secondary');

        // Show target
        const targetTab = document.getElementById('ntab-' + tabName);
        targetTab.classList.add('active');
        targetTab.classList.remove('hidden');
        
        const targetBtn = document.getElementById('nbtn-' + tabName);
        targetBtn.classList.remove('bg-bg', 'text-secondary');
        targetBtn.classList.add('active', 'bg-card', 'text-textMain', 'shadow-sm');

        // Refresh TinyMCE
        if (tabName === 'settings') {
             setTimeout(() => {
                tinymce.remove('.tinymce');
                tinymce.init(tinymceConfig);
            }, 10);
        }
    }

    // --- MODAL LOGIC ---
    function openEditFish(data) {
        document.getElementById('edit_fish_id').value = data.id;
        document.getElementById('edit_fish_title').value = data.title;
        document.getElementById('edit_fish_sci').value = data.scientific_name;
        document.getElementById('edit_fish_origin').value = data.origin;
        document.getElementById('edit_fish_grade').value = data.grade;
        document.getElementById('edit_fish_cat').value = data.category;
        document.getElementById('editFishModal').style.display = 'flex';
    }

    function openEditNetwork(data) {
        document.getElementById('edit_net_id').value = data.id;
        document.getElementById('edit_net_name').value = data.country_name;
        document.getElementById('editNetModal').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // --- CHART ---
    if(document.getElementById('myChart')) {
        const ctx = document.getElementById('myChart');
        new Chart(ctx, { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], datasets: [{ label: 'Visitors', data: [12, 19, 15, 25, 22, 30], borderColor: '#00C2CB', backgroundColor: 'rgba(67, 24, 255, 0.1)', tension: 0.4, fill: true, pointRadius: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false } } } } });
    }
</script>
</body>
</html>