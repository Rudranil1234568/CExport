<?php
session_start();
// --- SECURITY: Ensure Admin is Logged In ---
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
require '../includes/db.php';

// --- LOGIC HANDLERS ---
// --- LOGIC HANDLERS ---

/**
 * Helper to redirect back to the exact same page/tab/search parameters
 */
function redirectBack($extraParams = []) {
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_POST['active_tab']) ? $_POST['active_tab'] : '');
    
    $url = "index.php?page=" . urlencode($page);
    if ($tab) $url .= "&tab=" . urlencode($tab);
    
    // Preserve search/filter params for messages
    if (isset($_GET['search'])) $url .= "&search=" . urlencode($_GET['search']);
    if (isset($_GET['start_date'])) $url .= "&start_date=" . urlencode($_GET['start_date']);
    if (isset($_GET['end_date'])) $url .= "&end_date=" . urlencode($_GET['end_date']);

    foreach ($extraParams as $k => $v) {
        $url .= "&$k=$v";
    }
    
    header("Location: $url");
    exit;
}

// 1. Update Text & Hero Video
if (isset($_POST['update_text'])) {
    foreach ($_POST['content'] as $key => $val) {
        $stmt = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?");
        $stmt->execute([$val, $key]);
    }
    
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
                $check = $pdo->query("SELECT count(*) FROM site_content WHERE content_key = 'hero_video'")->fetchColumn();
                if (!$check) {
                    $pdo->prepare("INSERT INTO site_content (content_key, content_value) VALUES ('hero_video', ?)")->execute([$dbPath]);
                } else {
                    $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'hero_video'")->execute([$dbPath]);
                }
            }
        }
    }
    redirectBack(['msg' => 'updated']);
}
$is_featured = isset($_POST['is_featured']) ? 1 : 0;

if (isset($_POST['action']) && $_POST['action'] == 'add_product') {
    // Example for adding a new product
    $stmt = $pdo->prepare("INSERT INTO gallery (title, scientific_name, category, origin, grade, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['title'], 
        $_POST['scientific_name'], 
        $_POST['category'], 
        $_POST['origin'], 
        $_POST['grade'], 
        $is_featured
    ]);
} elseif (isset($_POST['action']) && $_POST['action'] == 'edit_product') {
    // Example for updating an existing product
    $stmt = $pdo->prepare("UPDATE gallery SET title=?, scientific_name=?, category=?, origin=?, grade=?, is_featured=? WHERE id=?");
    $stmt->execute([
        $_POST['title'], 
        $_POST['scientific_name'], 
        $_POST['category'], 
        $_POST['origin'], 
        $_POST['grade'], 
        $is_featured,
        $_POST['id']
    ]);
}
// 2. Upload Fish
if (isset($_POST['upload_fish']) && !empty($_FILES['image']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['image']['tmp_name']);
    $sql = "INSERT INTO gallery (title, scientific_name, origin, category, grade, image_data, status) VALUES (?,?,?,?,?,?,1)";
    $pdo->prepare($sql)->execute([$_POST['title'], $_POST['scientific'], $_POST['origin'], $_POST['category'], $_POST['grade'], $imgData]);
    redirectBack(['msg' => 'added']);
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
    redirectBack(['msg' => 'updated']);
}

// 4. Toggle Fish Status
if (isset($_GET['toggle_fish'])) {
    $id = $_GET['toggle_fish'];
    $current = $pdo->query("SELECT status FROM gallery WHERE id=$id")->fetchColumn();
    $newStatus = $current ? 0 : 1;
    $pdo->prepare("UPDATE gallery SET status=? WHERE id=?")->execute([$newStatus, $id]);
    redirectBack();
}

// 5. Delete Fish
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$_GET['del']]);
    redirectBack(['msg' => 'deleted']);
}

// 6. Update Gallery Settings
if (isset($_POST['update_gallery_settings'])) {
    $keys = ['gallery_title', 'gallery_text'];
    foreach ($keys as $key) {
        if(isset($_POST[$key])) {
            $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?")->execute([$_POST[$key], $key]);
        }
    }
    redirectBack(['msg' => 'settings_updated']);
}

// 7. Upload Network
if (isset($_POST['upload_network']) && !empty($_FILES['flag']['tmp_name'])) {
    $flagData = file_get_contents($_FILES['flag']['tmp_name']);
    $sql = "INSERT INTO network (country_name, flag_data, status) VALUES (?,?,1)";
    $pdo->prepare($sql)->execute([$_POST['country_name'], $flagData]);
    redirectBack(['msg' => 'added']);
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
    redirectBack(['msg' => 'updated']);
}

// 9. Toggle Network Status
if (isset($_GET['toggle_net'])) {
    $id = $_GET['toggle_net'];
    $current = $pdo->query("SELECT status FROM network WHERE id=$id")->fetchColumn();
    $newStatus = $current ? 0 : 1;
    $pdo->prepare("UPDATE network SET status=? WHERE id=?")->execute([$newStatus, $id]);
    redirectBack();
}

// 10. Delete Network
if (isset($_GET['del_net'])) {
    $pdo->prepare("DELETE FROM network WHERE id=?")->execute([$_GET['del_net']]);
    redirectBack(['msg' => 'deleted']);
}

// 11. Update Network Settings
if (isset($_POST['update_network_settings'])) {
    $keys = ['network_title', 'network_subtitle'];
    foreach ($keys as $key) {
        if(isset($_POST[$key])) {
            $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?")->execute([$_POST[$key], $key]);
        }
    }
    redirectBack(['msg' => 'settings_updated']);
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
    redirectBack(['msg' => 'logo_updated']);
}

// 14. Upload Services Image
if (isset($_POST['upload_services_image']) && !empty($_FILES['services_image_upload']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['services_image_upload']['tmp_name']);
    $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'services_img'")->execute([$imgData]);
    redirectBack(['msg' => 'updated']);
}

// 15. Upload About Carousel Slide
if (isset($_POST['upload_about_slide']) && !empty($_FILES['slide_image']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['slide_image']['tmp_name']);
    $pdo->prepare("INSERT INTO about_carousel (image_data) VALUES (?)")->execute([$imgData]);
    redirectBack(['msg' => 'slide_added']);
}

// 16. Delete About Carousel Slide
if (isset($_GET['del_about_slide'])) {
    $pdo->prepare("DELETE FROM about_carousel WHERE id=?")->execute([$_GET['del_about_slide']]);
    redirectBack(['msg' => 'slide_deleted']);
}

// 17. Upload About Badge
if (isset($_POST['upload_about_badge']) && !empty($_FILES['badge_image']['tmp_name'])) {
    $imgData = file_get_contents($_FILES['badge_image']['tmp_name']);
    $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'about_badge_img'")->execute([$imgData]);
    redirectBack(['msg' => 'badge_updated']);
}

// 18. Delete Message
if (isset($_GET['del_msg'])) {
    $pdo->prepare("DELETE FROM messages WHERE id=?")->execute([$_GET['del_msg']]);
    redirectBack(['msg' => 'deleted']);
}

// 19. Toggle Read Status
if (isset($_GET['toggle_read'])) {
    $id = $_GET['toggle_read'];
    $pdo->prepare("UPDATE messages SET is_read = 1 - is_read WHERE id = ?")->execute([$id]);
    redirectBack();
}

// 20. Personal Journey Content
if (isset($_POST['save_journey_content'])) {
    $updates = ['journey_title' => $_POST['journey_title'], 'journey_subtitle' => $_POST['journey_subtitle']];
    foreach ($updates as $key => $val) {
        $stmt = $pdo->prepare("INSERT INTO site_content (content_key, content_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE content_value = ?");
        $stmt->execute([$key, $val, $val]);
    }
    redirectBack(['msg' => 'updated']);
}

// 21. Add Journey Media
if (isset($_POST['add_journey_media'])) {
    if (!isset($_POST['media_type'])) redirectBack(['error' => 'no_type_selected']);
    $type = $_POST['media_type'];
    if ($type === 'image' && !empty($_FILES['media_file']['tmp_name'])) {
        $pdo->prepare("INSERT INTO personal_journey (media_type, file_data) VALUES ('image', ?)")->execute([file_get_contents($_FILES['media_file']['tmp_name'])]);
    } elseif ($type === 'video' && !empty($_FILES['media_file']['tmp_name'])) {
        $targetDir = "../assets/videos/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); 
        $fileName = time() . '_' . basename($_FILES['media_file']['name']);
        if(move_uploaded_file($_FILES['media_file']['tmp_name'], $targetDir . $fileName)) {
            $pdo->prepare("INSERT INTO personal_journey (media_type, video_path) VALUES ('video', ?)")->execute([$fileName]);
        }
    } elseif ($type === 'youtube' && !empty($_POST['youtube_url'])) {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $_POST['youtube_url'], $match);
        $vidId = $match[1] ?? '';
        if ($vidId) $pdo->prepare("INSERT INTO personal_journey (media_type, youtube_id) VALUES ('youtube', ?)")->execute([$vidId]);
    }
    redirectBack(['msg' => 'added']);
}

// 22. Delete Journey Media
if (isset($_GET['delete_journey'])) {
    $pdo->prepare("DELETE FROM personal_journey WHERE id = ?")->execute([$_GET['delete_journey']]);
    redirectBack();
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

    <!-- FIX: ADD ALPINE.JS FOR INTERACTIVITY -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #E0F9FA;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .tox-tinymce {
            border-radius: 1rem !important;
            border: 1px solid #E5E7EB;
            !important;
            margin-top: 0.5rem;
        }

        .tab-btn.active,
        .gal-tab-btn.active,
        .net-tab-btn.active {
            background-color: #00C2CB;
            color: white;
            box-shadow: 0 4px 12px rgba(24, 209, 255, 0.3);
        }

        .tab-content,
        .gal-tab-content,
        .net-tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active,
        .gal-tab-content.active,
        .net-tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toggle-checkbox:checked {
            right: 0;
            border-color: #00C2CB;
        }

        .toggle-checkbox:checked+.toggle-label {
            background-color: #00C2CB;
        }

        /* Hide scrollbar for gallery thumbnails but keep functionality */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-bg text-textMain h-screen flex overflow-hidden font-sans">

    <aside class="w-72 bg-card flex flex-col border-r border-gray-100 hidden md:flex">
        <div class="p-8 flex items-center gap-3">
            <div class="flex items-center gap-2">
                <img id="nav-logo" src="../view_image.php?type=logo" alt="CExport Logo"
                    class="h-12 w-auto object-contain drop-shadow-md transition-all duration-300 "><span
                    class="font-extrabold text-secondary ml-5">CMS</span>
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-2 mt-4 m">
            <a href="?page=dashboard"
                class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='dashboard' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="layout-grid" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Dashboard</span>
            </a>
            <a href="?page=content"
                class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='content' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="file-text" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Site Content</span>
            </a>
            <a href="?page=Product"
                class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='Product' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="image" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Product Manager</span>
            </a>
            <a href="?page=network"
                class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='network' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="globe" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Global Network</span>
            </a>
            <a href="?page=messages"
                class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='messages' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="mail" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Inquiries</span>

                <?php $msgCount = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn(); ?>
                <?php if($msgCount > 0): ?>
                <span
                    class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-md shadow-red-500/30 animate-pulse">
                    <?= $msgCount ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="?page=gallery"
                class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group <?= $page=='Gallery' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-secondary hover:bg-gray-50' ?>">
                <i data-lucide="compass" class="w-6 h-6"></i>
                <span class="font-medium text-lg">Gallery</span>
            </a>

        </nav>

        <div class="p-6">
            <div
                class="bg-gradient-to-br from-[#868CFF] to-[#00C2CB] rounded-3xl p-6 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white/20 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div
                        class="w-12 h-12 bg-white rounded-full mx-auto mb-3 flex items-center justify-center text-primary font-bold">
                        A</div>
                    <h4 class="font-bold text-lg">Admin User</h4>
                    <a href="logout.php"
                        class="inline-block mt-4 bg-white/20 hover:bg-white/30 backdrop-blur-md py-2 px-6 rounded-xl text-sm font-medium transition-colors">Log
                        Out</a>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full relative overflow-y-auto">
        <header
            class="h-24 px-8 pt-5 pb-4 flex items-center justify-between bg-bg/50 backdrop-blur-md sticky top-0 z-20">
            <div>
                <p class="text-secondary text-sm font-medium">Pages /
                    <?= ucfirst($page) ?>
                </p>
                <h1 class="text-3xl font-bold text-textMain">
                    <?= ucfirst($page) ?>
                </h1>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="bg-white px-4 py-2 rounded-full shadow-sm text-sm text-secondary flex items-center gap-2"><i
                        data-lucide="calendar" class="w-4 h-4"></i>
                    <?= date("F j, Y") ?>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold shadow-lg shadow-primary/20">
                    AD</div>
            </div>
            
        </header>

        <div class="p-8 pb-20">
            <?php if(isset($_GET['msg'])): ?>
            <div
                class="mb-6 p-4 rounded-2xl bg-green-100 text-green-700 flex items-center gap-3 border border-green-200 shadow-sm">
                <i data-lucide="check-circle"></i> Action Successful</div>
            <?php endif; ?>

            <?php if ($page == 'dashboard'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div
                        class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-primary"><i
                                data-lucide="fish" class="w-7 h-7"></i></div>
                        <div>
                            <p class="text-secondary text-sm font-medium">Total Species</p>
                            <h3 class="text-2xl font-bold text-textMain">
                                <?= $fishCount ?>
                            </h3>
                        </div>
                    </div>
                    <div
                        class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-primary"><i
                                data-lucide="layers" class="w-7 h-7"></i></div>
                        <div>
                            <p class="text-secondary text-sm font-medium">Content Blocks</p>
                            <h3 class="text-2xl font-bold text-textMain">
                                <?= $textCount ?>
                            </h3>
                        </div>
                    </div>
                    <div
                        class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-orange-500"><i
                                data-lucide="globe" class="w-7 h-7"></i></div>
                        <div>
                            <p class="text-secondary text-sm font-medium">Active Countries</p>
                            <h3 class="text-2xl font-bold text-textMain">
                                <?= $netCount ?>
                            </h3>
                        </div>
                    </div>
                    <div
                        class="bg-card p-5 rounded-[20px] shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="w-14 h-14 rounded-full bg-bg flex items-center justify-center text-green-500"><i
                                data-lucide="trending-up" class="w-7 h-7"></i></div>
                        <div>
                            <p class="text-secondary text-sm font-medium">Weekly Visits</p>
                            <h3 class="text-2xl font-bold text-textMain">3.2K</h3>
                        </div>
                    </div>
                </div>
                <div class="bg-card p-6 rounded-[20px] shadow-sm h-96 relative mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-textMain">Visitor Analytics</h3>
                    </div>
                    <canvas id="myChart" class="w-full h-full"></canvas>
                </div>
            <?php endif; ?>

            <?php if ($page == 'content'): ?>
            <?php 
                // 0. Get Current Tab from URL (Default to 'hero')
                $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'hero';

                // 1. Group Data Logic
                $groups = ['hero' => [], 'about' => [], 'services' => [], 'social' => [], 'stats' => [], 'contact' => []];
                
                $stmt = $pdo->query("SELECT * FROM site_content");
                while ($row = $stmt->fetch()) {
                    if (strpos($row['content_key'], 'gallery_') === 0 || strpos($row['content_key'], 'network_') === 0 || strpos($row['content_key'], 'journey_') === 0) { continue; }
                    
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
                    <button onclick="switchTab('<?= $g ?>')"
                        class="tab-btn <?= $g==$currentTab ?'active':'text-secondary' ?> px-5 py-2 rounded-xl text-sm font-bold transition-all"
                        id="btn-<?= $g ?>">
                        <?= ucfirst($g) ?>
                    </button>
                    <?php endforeach; ?>
                    <button onclick="switchTab('logo')"
                        class="tab-btn <?= 'logo'==$currentTab ?'active':'text-secondary' ?> px-5 py-2 rounded-xl text-sm font-bold transition-all"
                        id="btn-logo">Logo & Identity</button>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="active_tab" id="active_tab_input" value="<?= $currentTab ?>">

                    <?php foreach($groups as $groupKey => $fields): ?>
                    <div id="tab-<?= $groupKey ?>" class="tab-content <?= $groupKey === $currentTab ? 'active' : '' ?>">

                        <?php 
                                    $findKey = function($k) use ($fields) {
                                        foreach($fields as $f) { if($f['content_key'] == $k) return $f['content_value']; }
                                        return '';
                                    };
                                ?>

                        <?php if($groupKey === 'hero'): ?>
                        <div class="space-y-8">
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="heading"
                                            class="w-5 h-5"></i></div>
                                    <h4 class="font-bold text-gray-700">1. Hero Section Title</h4>
                                </div>
                                <textarea name="content[hero_title]"
                                    class="tinymce w-full"><?= $findKey('hero_title') ?></textarea>
                                <p class="text-xs text-gray-400 mt-2">Main headline of the website.</p>
                            </div>
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="align-left"
                                            class="w-5 h-5"></i></div>
                                    <h4 class="font-bold text-gray-700">2. Hero Subtitle</h4>
                                </div>
                                <textarea name="content[hero_subtitle]"
                                    class="tinymce w-full"><?= $findKey('hero_subtitle') ?></textarea>
                                <p class="text-xs text-gray-400 mt-2">Text that appears below the main headline.</p>
                            </div>
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="p-2 bg-purple-100 text-purple-600 rounded-lg"><i data-lucide="star"
                                            class="w-5 h-5"></i></div>
                                    <h4 class="font-bold text-gray-700">3. Hero Feature Title</h4>
                                </div>
                                <textarea name="content[hero_feature_title]"
                                    class="tinymce w-full"><?= $findKey('hero_feature_title') ?></textarea>
                                <p class="text-xs text-gray-400 mt-2">Header for the highlighted feature section.</p>
                            </div>
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="p-2 bg-purple-100 text-purple-600 rounded-lg"><i data-lucide="file-text"
                                            class="w-5 h-5"></i></div>
                                    <h4 class="font-bold text-gray-700">4. Hero Feature Subtitle</h4>
                                </div>
                                <textarea name="content[hero_feature_subtitle]"
                                    class="tinymce w-full"><?= $findKey('hero_feature_subtitle') ?></textarea>
                                <p class="text-xs text-gray-400 mt-2">Description for the highlighted feature.</p>
                            </div>
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-pink-100 text-pink-600 rounded-lg"><i data-lucide="video"
                                            class="w-5 h-5"></i></div>
                                    <h4 class="font-bold text-gray-700">Hero Background Video</h4>
                                </div>
                                <label
                                    class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors group relative overflow-hidden">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6 relative z-10">
                                        <div
                                            class="w-12 h-12 mb-3 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 group-hover:text-primary transition-colors">
                                            <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                                        </div>
                                        <p class="mb-1 text-sm text-gray-500"><span class="font-semibold">Click to
                                                upload video</span> or drag and drop</p>
                                        <p class="text-xs text-gray-400">MP4 or WEBM (Max 10MB)</p>
                                    </div>
                                    <input type="file" name="hero_video_upload" class="hidden"
                                        accept="video/mp4,video/webm"
                                        onchange="document.getElementById('video-file-name').textContent = this.files[0].name">
                                    <?php if(!empty($findKey('hero_video'))): ?>
                                    <div
                                        class="absolute bottom-3 right-3 bg-green-100 text-green-700 text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-bold shadow-sm">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Video Active
                                    </div>
                                    <?php endif; ?>
                                </label>
                                <p id="video-file-name"
                                    class="text-sm text-center text-primary mt-3 font-medium min-h-[1.25rem]"></p>
                            </div>
                        </div>

                        <?php elseif($groupKey === 'social'): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-2 bg-blue-50 rounded-lg text-blue-600"><i data-lucide="share-2"
                                        class="w-6 h-6"></i></div>
                                <h2 class="text-xl font-bold text-gray-800">Social Media Links</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Facebook URL</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3.5 text-gray-400"><i data-lucide="facebook"
                                                class="w-5 h-5"></i></span>
                                        <input type="text" name="content[social_facebook]"
                                            value="<?= htmlspecialchars($findKey('social_facebook')) ?>"
                                            class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Instagram URL</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3.5 text-gray-400"><i data-lucide="instagram"
                                                class="w-5 h-5"></i></span>
                                        <input type="text" name="content[social_instagram]"
                                            value="<?= htmlspecialchars($findKey('social_instagram')) ?>"
                                            class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                                    </div>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">WhatsApp Link (Full
                                        URL)</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3.5 text-green-500"><i data-lucide="phone"
                                                class="w-5 h-5"></i></span>
                                        <input type="text" name="content[social_whatsapp]"
                                            value="<?= htmlspecialchars($findKey('social_whatsapp')) ?>"
                                            placeholder="https://wa.me/919876543210"
                                            class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all">
                                        <p class="text-xs text-gray-400 mt-1 ml-2">Format: https://wa.me/YOUR_NUMBER
                                            (include country code, no +)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php elseif($groupKey === 'contact'): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <div class="space-y-6">
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                    <label class="block text-xs font-bold text-secondary mb-2 uppercase">Section
                                        Title</label>
                                    <textarea name="content[contact_title]"
                                        class="tinymce w-full"><?= $findKey('contact_title') ?></textarea>
                                </div>
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                    <label class="block text-xs font-bold text-secondary mb-2 uppercase">Description
                                        Text</label>
                                    <textarea name="content[contact_desc]"
                                        class="tinymce w-full"><?= $findKey('contact_desc') ?></textarea>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm h-full">
                                    <h4
                                        class="font-bold text-gray-700 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                        <i data-lucide="phone" class="w-5 h-5 text-blue-500"></i> Contact Details
                                    </h4>
                                    <div class="space-y-6">
                                        <div><label class="block text-xs font-bold text-secondary mb-2">Phone
                                                Number</label><textarea name="content[contact_phone]"
                                                class="tinymce w-full"><?= $findKey('contact_phone') ?></textarea></div>
                                        <div><label class="block text-xs font-bold text-secondary mb-2">Email
                                                Address</label><textarea name="content[contact_email]"
                                                class="tinymce w-full"><?= $findKey('contact_email') ?></textarea></div>
                                        <div><label class="block text-xs font-bold text-secondary mb-2">Office
                                                Address</label><textarea name="content[contact_address]"
                                                class="tinymce w-full"><?= $findKey('contact_address') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php elseif($groupKey === 'services'): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                            <div class="lg:col-span-2 space-y-6">
                                <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 mb-4">
                                    <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                                        <i data-lucide="layout-list" class="w-5 h-5"></i> Page Content
                                    </h3>
                                </div>
                                <?php 
                                            $priorityKeys = ['services_title', 'services_subtitle', 'services_desc', 'service_1_title', 'service_1_desc', 'service_2_title', 'service_2_desc', 'service_3_title', 'service_3_desc'];
                                            $findVal = function($k) use ($fields) { foreach($fields as $f) { if($f['content_key'] == $k) return $f['content_value']; } return ''; };
                                            foreach($priorityKeys as $key):
                                                $label = ucwords(str_replace(['services_', 'service_', '_'], ['Services ', 'Service ', ' '], $key));
                                            ?>
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                    <label class="block text-xs font-bold text-secondary mb-2 uppercase tracking-wide">
                                        <?= $label ?>
                                    </label>
                                    <textarea name="content[<?= $key ?>]"
                                        class="tinymce w-full"><?= $findVal($key) ?></textarea>
                                </div>
                                <?php endforeach; ?>
                                <?php foreach($fields as $row): 
                                                if($row['content_key'] == 'services_img') continue;
                                                if(in_array($row['content_key'], $priorityKeys)) continue;
                                                $label = !empty($row['label']) ? $row['label'] : ucwords(str_replace(['services_', '_'], ['',' '], $row['content_key'])); 
                                            ?>
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                    <label class="block text-xs font-bold text-secondary mb-2 uppercase tracking-wide">
                                        <?= $label ?>
                                    </label>
                                    <textarea name="content[<?= $row['content_key'] ?>]"
                                        class="tinymce w-full"><?= $row['content_value'] ?></textarea>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="lg:col-span-1">
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm sticky top-6">
                                    <div class="border-b border-gray-100 pb-4 mb-4">
                                        <h4 class="text-gray-800 font-bold flex items-center gap-2"><i
                                                data-lucide="image" class="w-5 h-5 text-primary"></i> Feature Image</h4>
                                        <p class="text-xs text-gray-400 mt-1">Main illustration for the services
                                            section.</p>
                                    </div>
                                    <div
                                        class="rounded-xl overflow-hidden bg-gray-50 border border-gray-200 mb-5 group relative shadow-inner">
                                        <img src="../view_image.php?type=content&key=services_img&t=<?= time() ?>"
                                            class="w-full h-56 object-cover transition-transform duration-700 group-hover:scale-105">
                                    </div>
                                    <div class="space-y-3">
                                        <label class="block text-xs font-bold text-secondary uppercase">Upload New
                                            File</label>
                                        <div class="relative"><input type="file" name="services_image_upload"
                                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-all cursor-pointer">
                                        </div>
                                        <button type="submit" name="upload_services_image"
                                            formenctype="multipart/form-data"
                                            class="w-full bg-primary hover:bg-brand-dark text-white py-3 rounded-xl font-bold shadow-lg shadow-primary/20 transition-all transform active:scale-95 flex items-center justify-center gap-2 mt-4">
                                            <i data-lucide="upload-cloud" class="w-4 h-4"></i> Update Image
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php else: ?>

                        <?php if($groupKey === 'about'): ?>
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 mb-6">
                            <h4 class="text-primary font-bold mb-4">Image Carousel</h4>
                            <div class="flex items-end gap-4 mb-6 border-b border-gray-100 pb-6">
                                <div class="flex-1">
                                    <label class="block text-xs font-bold text-secondary mb-2">ADD NEW SLIDE</label>
                                    <input type="file" name="slide_image"
                                        class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-primary/10 file:text-primary">
                                </div>
                                <button type="submit" name="upload_about_slide" formenctype="multipart/form-data"
                                    class="bg-primary text-white px-6 py-2 rounded-lg font-bold">Upload</button>
                            </div>
                            <div class="grid grid-cols-4 gap-4">
                                <?php foreach($pdo->query("SELECT id FROM about_carousel") as $slide): ?>
                                <div
                                    class="relative group rounded-xl overflow-hidden aspect-video bg-gray-100 object-cover">
                                    <img src="../view_image.php?type=about_slide&id=<?= $slide['id'] ?>"
                                        class="w-full h-full object-cover">
                                    <a href="?del_about_slide=<?= $slide['id'] ?>"
                                        onclick="return confirm('Delete slide?')"
                                        class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-white object-cover">
                                        <i data-lucide="trash-2"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 mb-6 flex items-center gap-6">
                            <img src="../view_image.php?type=content&key=about_badge_img&t=<?= time() ?>"
                                class="w-20 h-20 object-contain bg-gray-50 rounded-full border">
                            <div class="flex-1">
                                <h4 class="text-primary font-bold">Floating Badge Icon</h4>
                                <p class="text-xs text-secondary mb-2">The circular icon at the bottom left (e.g., Koi
                                    Fish).</p>
                                <div class="flex gap-2">
                                    <input type="file" name="badge_image" class="text-sm">
                                    <button type="submit" name="upload_about_badge" formenctype="multipart/form-data"
                                        class="bg-secondary text-white px-4 py-1 rounded text-xs font-bold">Update
                                        Icon</button>
                                </div>
                            </div>
                        </div>
                        <div class="bg-bg p-6 rounded-2xl border border-blue-100 mb-6">
                            <h4 class="text-primary font-bold mb-4">Info Tabs (Vision, Mission, etc)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php for($i=1; $i<=4; $i++): 
                                                    $findTab = function($k) use ($fields) { foreach($fields as $f) if($f['content_key'] == $k) return $f['content_value']; return ''; };
                                                ?>
                                <div class="bg-white p-4 rounded-xl shadow-sm">
                                    <span class="text-xs font-bold text-gray-400 uppercase">Tab
                                        <?= $i ?>
                                    </span>
                                    <input type="text" name="content[tab_<?= $i ?>_label]"
                                        value="<?= $findTab('tab_'.$i.'_label') ?>"
                                        class="w-full font-bold border-b border-gray-200 mb-2 focus:outline-none"
                                        placeholder="Label">
                                    <input type="text" name="content[tab_<?= $i ?>_title]"
                                        value="<?= $findTab('tab_'.$i.'_title') ?>"
                                        class="w-full text-sm font-bold text-primary mb-2 focus:outline-none"
                                        placeholder="Heading">
                                    <textarea name="content[tab_<?= $i ?>_text]"
                                        class="tinymce w-full"><?= $findTab('tab_'.$i.'_text') ?></textarea>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 gap-6">
                            <?php foreach($fields as $row): 
                                            if(in_array($row['content_key'], ['hero_title', 'hero_subtitle', 'hero_feature_title', 'hero_feature_subtitle'])) continue;
                                            if($row['content_key'] == 'services_img' || $row['content_key'] == 'about_badge_img') continue;
                                            if(strpos($row['content_key'], 'tab_') === 0) continue; 
                                            if(strpos($row['content_key'], 'social_') === 0) continue; 
                                            if(strpos($row['content_key'], 'contact_') === 0) continue;
                                            $label = !empty($row['label']) ? $row['label'] : ucwords(str_replace('_', ' ', $row['content_key'])); 
                                        ?>
                            <div>
                                <label class='block text-xs font-bold text-secondary mb-2 uppercase tracking-wide'>
                                    <?= $label ?>
                                </label>
                                <textarea name='content[<?= $row[' content_key']
                                    ?>]' class='tinymce w-full shadow-sm border-gray-100'><?= $row['content_value'] ?></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php endif; ?>

                        <div class="pt-8 mt-4">
                            <button type="submit" name="update_text"
                                class="bg-primary hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg transition-all transform hover:-translate-y-1 w-full md:w-auto">
                                Save Changes
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </form>

                <div id="tab-logo" class="tab-content <?= $currentTab === 'logo' ? 'active' : '' ?>">
                    <div class="bg-bg p-8 rounded-2xl border border-gray-200 text-center max-w-2xl mx-auto">
                        <h3 class="text-textMain font-bold mb-6 text-lg">Site Identity</h3>
                        <form method="post" enctype="multipart/form-data">
                            <div class="bg-white p-6 inline-block rounded-xl shadow-sm border mb-6">
                                <img src="../view_image.php?type=logo&t=<?= time() ?>"
                                    class="h-24 object-contain mx-auto" alt="Current Logo">
                                <p class="text-xs text-secondary mt-2 font-medium">Current Logo / Favicon</p>
                            </div>
                            <div class="max-w-md mx-auto space-y-5">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-secondary mb-2 uppercase tracking-wide">Upload
                                        New Image (PNG/WEBP)</label>
                                    <div
                                        class="bg-white p-2 rounded-xl border-2 border-dashed border-gray-300 hover:border-primary transition-colors cursor-pointer group">
                                        <input type="file" name="logo" required
                                            class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                                            accept="image/png, image/jpeg, image/webp">
                                    </div>
                                </div>
                                <button type="submit" name="upload_logo"
                                    class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg transition-all transform hover:-translate-y-1">
                                    Update Identity
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <?php endif; ?>

            <?php if ($page == 'Product'): 
    // --- PHP LOGIC HANDLERS ---
    
    /**
     * Helper to redirect safely even if HTML output has already started
     */
    $safeRedirect = function($url) {
        if (!headers_sent()) {
            header("Location: $url");
        } else {
            echo '<script>window.location.href="' . $url . '";</script>';
        }
        exit;
    };

    // 1. Toggle Home Display (is_featured)
    if (isset($_GET['toggle_featured'])) {
    $id = intval($_GET['toggle_featured']);
    
    // Check current state of the specific fish
    $currentState = $pdo->prepare("SELECT is_featured FROM gallery WHERE id = ?");
    $currentState->execute([$id]);
    $isCurrentlyFeatured = $currentState->fetchColumn();

    // If we are trying to turn it ON, check the limit
    if (!$isCurrentlyFeatured) {
        $count = $pdo->query("SELECT COUNT(*) FROM gallery WHERE is_featured = 1")->fetchColumn();
        if ($count >= 8) {
            echo "<script>alert('Limit Reached: You can only have a maximum of 8 Featured Slots. Please unfeature another item first.'); window.location.href='index.php?page=Product';</script>";
            exit;
        }
    }
    
    $pdo->prepare("UPDATE gallery SET is_featured = NOT is_featured WHERE id = ?")->execute([$id]);
    $safeRedirect("index.php?page=Product");
}

    // 2. Toggle Catalog Status (Visibility)
    if (isset($_GET['toggle_status'])) {
        $id = intval($_GET['toggle_status']);
        $pdo->prepare("UPDATE gallery SET status = NOT status WHERE id = ?")->execute([$id]);
        $safeRedirect("index.php?page=Product");
    }

    // 3. Delete Fish
    if (isset($_GET['del_fish'])) {
        $id = intval($_GET['del_fish']);
        $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
        $safeRedirect("index.php?page=Product");
    }

    // 4. Save or Update Fish Record
    if (isset($_POST['save_fish'])) {
    $action = $_POST['action'];
    $title = $_POST['title'];
    $scientific = $_POST['scientific'];
    $origin = $_POST['origin'];
    $grade = $_POST['grade'];
    $category = $_POST['category'];
    $is_featured_requested = isset($_POST['is_featured']) ? 1 : 0;
    $id = isset($_POST['fish_id']) ? intval($_POST['fish_id']) : 0;

    // RESTRICTION CHECK: If trying to feature this item
    if ($is_featured_requested == 1) {
        // Count others that are currently featured (excluding this ID if updating)
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE is_featured = 1 AND id != ?");
        $stmtCount->execute([$id]);
        $currentFeaturedCount = $stmtCount->fetchColumn();

        if ($currentFeaturedCount >= 8) {
            echo "<script>alert('Limit Reached: You can only have a maximum of 8 Featured Slots. Please unfeature another item first.'); window.history.back();</script>";
            exit;
        }
    }

    if ($action == 'upload_fish') {
        $imgData = null;
        if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
            $imgData = file_get_contents($_FILES['image']['tmp_name']);
        }
        $stmt = $pdo->prepare("INSERT INTO gallery (title, scientific_name, origin, grade, category, is_featured, image_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $scientific, $origin, $grade, $category, $is_featured_requested, $imgData]);
    } elseif ($action == 'update_fish') {
        if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
            $imgData = file_get_contents($_FILES['image']['tmp_name']);
            $stmt = $pdo->prepare("UPDATE gallery SET title=?, scientific_name=?, origin=?, grade=?, category=?, is_featured=?, image_data=? WHERE id=?");
            $stmt->execute([$title, $scientific, $origin, $grade, $category, $is_featured_requested, $imgData, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE gallery SET title=?, scientific_name=?, origin=?, grade=?, category=?, is_featured=? WHERE id=?");
            $stmt->execute([$title, $scientific, $origin, $grade, $category, $is_featured_requested, $id]);
        }
    }
    $safeRedirect("index.php?page=Product");
}

    // 5. Update Gallery Settings
    if (isset($_POST['update_gallery_settings'])) {
        $title = $_POST['gallery_title'];
        $text = $_POST['gallery_text'];
        $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'gallery_title'")->execute([$title]);
        $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'gallery_text'")->execute([$text]);
        $safeRedirect("index.php?page=Product&tab=settings");
    }

    // --- FETCH DATA FOR UI ---
    $featuredCount = $pdo->query("SELECT COUNT(*) FROM gallery WHERE is_featured = 1")->fetchColumn();
    $fishCount = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
    
    $galTitle = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'gallery_title'")->fetchColumn() ?: '';
    $galText = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'gallery_text'")->fetchColumn() ?: '';
?>
    <!-- Tab Navigation -->
    <div class="flex gap-4 mb-6">
        <button onclick="switchGalleryTab('inventory')"
            class="gal-tab-btn active px-6 py-3 rounded-xl bg-card text-textMain font-bold shadow-sm border border-transparent flex items-center gap-2 hover:border-primary transition-all"
            id="gbtn-inventory">
            <i data-lucide="grid"></i> Fish Inventory
        </button>
        <button onclick="switchGalleryTab('settings')"
            class="gal-tab-btn px-6 py-3 rounded-xl bg-bg text-secondary font-bold border border-transparent flex items-center gap-2 hover:bg-card hover:text-textMain transition-all"
            id="gbtn-settings">
            <i data-lucide="settings"></i> Page Settings
        </button>
    </div>

    <!-- Inventory Tab Content -->
    <div id="gtab-inventory" class="gal-tab-content active">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Sidebar: Add Fish Form -->
            <div class="lg:col-span-1">
                <div class="bg-card p-6 rounded-[20px] shadow-sm sticky top-28 border border-gray-100">
                    <h3 class="text-lg font-bold text-textMain mb-4 flex items-center gap-2">
                        <i data-lucide="plus-circle" class="text-primary"></i> 
                        <span id="form-mode-title">Add New Fish</span>
                    </h3>
                    
                    <form method="post" enctype="multipart/form-data" class="space-y-4">
                        <!-- Action Hidden Field -->
                        <input type="hidden" name="action" id="fish_action" value="upload_fish">
                        <input type="hidden" name="fish_id" id="fish_id" value="">

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-secondary uppercase ml-1">Fish Title</label>
                            <input type="text" name="title" id="form_title" placeholder="e.g. Blue Diamond Discus"
                                class="w-full bg-bg p-3 rounded-xl outline-none border border-transparent focus:border-primary transition-all" required>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-secondary uppercase ml-1">Scientific Name</label>
                            <input type="text" name="scientific" id="form_scientific" placeholder="Symphysodon aequifasciatus"
                                class="w-full bg-bg p-3 rounded-xl outline-none border border-transparent focus:border-primary transition-all">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-secondary uppercase ml-1">Origin</label>
                                <input type="text" name="origin" id="form_origin" placeholder="Amazon Basin"
                                    class="w-full bg-bg p-3 rounded-xl outline-none border border-transparent focus:border-primary transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-secondary uppercase ml-1">Grade</label>
                                <input type="text" name="grade" id="form_grade" placeholder="Premium / A+"
                                    class="w-full bg-bg p-3 rounded-xl outline-none border border-transparent focus:border-primary transition-all">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-secondary uppercase ml-1">Category</label>
                            <select name="category" id="form_category" class="w-full bg-bg p-3 rounded-xl outline-none border border-transparent focus:border-primary transition-all">
                                <option value="freshwater">Freshwater</option>
                                <option value="marine">Marine</option>
                            </select>
                        </div>

                        <!-- Featured Toggle -->
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                            <label class="flex items-center justify-between cursor-pointer">
                                <span class="text-sm font-bold text-amber-800">Top 8 (Home Page)</span>
                                <div class="relative inline-block w-10 align-middle select-none">
                                    <input type="checkbox" name="is_featured" id="form_is_featured" value="1"
                                        class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300 checked:right-0 checked:border-amber-500 transition-all duration-200" />
                                    <label class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </label>
                            <p class="text-[10px] text-amber-600 mt-1">Check to feature this fish in the main gallery (Max 8 recommended).</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-secondary uppercase ml-1">Product Image</label>
                            <div class="bg-bg rounded-xl p-4 border-2 border-dashed border-gray-200 text-center cursor-pointer group hover:border-primary transition-all">
                                <input type="file" name="image" id="form_image"
                                    class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            </div>
                            <p id="img-note" class="text-[10px] text-secondary mt-1 ml-1">Leave empty to keep existing image when editing.</p>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" name="save_fish"
                                class="flex-grow bg-primary text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-primary/20">
                                Save Product
                            </button>
                            <button type="button" id="cancel-edit" onclick="resetFishForm()"
                                class="hidden px-4 bg-gray-100 text-secondary font-bold rounded-xl hover:bg-gray-200 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Table: Inventory List -->
            <div class="lg:col-span-2">
                <div class="bg-card rounded-[20px] shadow-sm overflow-hidden min-h-[600px] border border-gray-100">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                        <div>
                            <h3 class="text-xl font-bold text-textMain">Inventory Management</h3>
                            <p class="text-xs text-secondary">Manage products and homepage features</p>
                        </div>
                        <div class="flex gap-2">
                            <div class="text-right">
                                <span class="block text-xs font-bold text-secondary uppercase">Featured Slots</span>
                                <span class="text-sm font-extrabold <?= $featuredCount >= 8 ? 'text-amber-500' : 'text-primary' ?>">
                                    <?= $featuredCount ?> / 8 Active
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 text-secondary text-[10px] uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="p-4 pl-6">Fish Details</th>
                                    <th class="p-4 text-center">Home Display</th>
                                    <th class="p-4 text-center">Catalog Status</th>
                                    <th class="p-4 text-right pr-6">Management</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php 
                                $stmt = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
                                while($fish = $stmt->fetch()): 
                                    // Prepare data for JS
                                    $fishJS = $fish;
                                    unset($fishJS['image_data']);
                                    $safeJson = htmlspecialchars(json_encode($fishJS), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="p-4 pl-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 rounded-xl overflow-hidden bg-bg border border-gray-100 shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                                <img src="../view_image.php?id=<?= $fish['id'] ?>" class="w-full h-full object-cover" onerror="this.src='../assets/img/placeholder.png'">
                                            </div>
                                            <div>
                                                <p class="font-bold text-textMain text-sm mb-0.5"><?= htmlspecialchars($fish['title']) ?></p>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-bg text-secondary border border-gray-100"><?= strtoupper($fish['category']) ?></span>
                                                    <span class="text-[10px] text-secondary font-medium italic"><?= htmlspecialchars($fish['scientific_name']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- TOP 8 TOGGLE COLUMN -->
                                    <td class="p-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <a href="?page=Product&toggle_featured=<?= $fish['id'] ?>" 
                                               class="relative inline-block w-10 h-5 align-middle select-none transition duration-200 ease-in">
                                                <div class="w-10 h-5 rounded-full shadow-inner transition-colors <?= $fish['is_featured'] ? 'bg-amber-400' : 'bg-gray-200' ?>"></div>
                                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform <?= $fish['is_featured'] ? 'translate-x-5' : '' ?>"></div>
                                            </a>
                                            <span class="text-[9px] font-bold uppercase <?= $fish['is_featured'] ? 'text-amber-600' : 'text-gray-400' ?>">
                                                <?= $fish['is_featured'] ? 'Featured' : 'Off' ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- VISIBILITY TOGGLE COLUMN -->
                                    <td class="p-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <a href="?page=Product&toggle_status=<?= $fish['id'] ?>" 
                                               class="relative inline-block w-10 h-5 align-middle select-none transition duration-200 ease-in">
                                                <div class="w-10 h-5 rounded-full shadow-inner transition-colors <?= $fish['status'] ? 'bg-primary' : 'bg-gray-200' ?>"></div>
                                                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform <?= $fish['status'] ? 'translate-x-5' : '' ?>"></div>
                                            </a>
                                            <span class="text-[9px] font-bold uppercase <?= $fish['status'] ? 'text-primary' : 'text-gray-400' ?>">
                                                <?= $fish['status'] ? 'Visible' : 'Hidden' ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="p-4 text-right pr-6">
                                        <div class="flex justify-end gap-2">
                                            <button onclick='populateEditForm(<?= $safeJson ?>)'
                                                class="w-9 h-9 flex items-center justify-center text-secondary hover:text-primary bg-gray-50 hover:bg-primary/10 rounded-xl transition-all border border-gray-100">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <a href="?page=Product&del_fish=<?= $fish['id'] ?>" onclick="return confirm('Delete this product permanently?')"
                                                class="w-9 h-9 flex items-center justify-center text-secondary hover:text-red-500 bg-gray-50 hover:bg-red-50 rounded-xl transition-all border border-gray-100">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if($fishCount == 0): ?>
                        <div class="py-20 text-center">
                            <i data-lucide="package-search" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
                            <p class="text-secondary font-medium">Your inventory is empty.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Settings Tab Content -->
    <div id="gtab-settings" class="gal-tab-content hidden">
        <div class="bg-card p-8 rounded-[20px] shadow-sm max-w-3xl border border-gray-100">
            <h3 class="text-lg font-bold text-textMain mb-6">Home Gallery Header</h3>
            <form method="post">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-secondary uppercase tracking-widest">Section Title</label>
                        <textarea name="gallery_title" class="tinymce w-full"><?= $galTitle ?></textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-secondary uppercase tracking-widest">Section Description</label>
                        <textarea name="gallery_text" class="tinymce w-full"><?= $galText ?></textarea>
                    </div>
                </div>
                <div class="pt-6 mt-6 border-t border-gray-100">
                    <button type="submit" name="update_gallery_settings"
                        class="bg-primary hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-primary/20 transition-all">
                        Update Gallery Content
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript logic specific to the Inventory Form -->
    <script>
        function populateEditForm(fish) {
            // Change Form Appearance
            document.getElementById('form-mode-title').innerText = "Edit Fish Details";
            document.getElementById('fish_action').value = "update_fish";
            document.getElementById('fish_id').value = fish.id;
            
            // Fill Inputs
            document.getElementById('form_title').value = fish.title;
            document.getElementById('form_scientific').value = fish.scientific_name;
            document.getElementById('form_origin').value = fish.origin;
            document.getElementById('form_grade').value = fish.grade;
            document.getElementById('form_category').value = fish.category;
            
            // Handle Featured Toggle
            document.getElementById('form_is_featured').checked = (parseInt(fish.is_featured) === 1);
            
            // UI Tweaks
            document.getElementById('form_image').required = false;
            document.getElementById('img-note').classList.remove('hidden');
            document.getElementById('cancel-edit').classList.remove('hidden');
            
            // Scroll to form smoothly
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetFishForm() {
            document.getElementById('form-mode-title').innerText = "Add New Fish";
            document.getElementById('fish_action').value = "upload_fish";
            document.getElementById('fish_id').value = "";
            
            document.getElementById('form_title').value = "";
            document.getElementById('form_scientific').value = "";
            document.getElementById('form_origin').value = "";
            document.getElementById('form_grade').value = "";
            document.getElementById('form_category').value = "freshwater";
            document.getElementById('form_is_featured').checked = false;
            
            document.getElementById('form_image').required = true;
            document.getElementById('img-note').classList.add('hidden');
            document.getElementById('cancel-edit').classList.add('hidden');
        }

        // Handle tab switching based on URL parameters if needed
        window.addEventListener('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('tab') === 'settings') {
                if(typeof switchGalleryTab === 'function') switchGalleryTab('settings');
            }
        });
    </script>

<?php endif; ?>

            <?php if ($page == 'network'): 
                $netTitle = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'network_title'")->fetchColumn() ?: '';
                $netSub = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'network_subtitle'")->fetchColumn() ?: '';
            ?>


            <div class="flex gap-4 mb-6">
                <button onclick="switchNetworkTab('list')"
                    class="net-tab-btn active px-6 py-3 rounded-xl bg-card text-textMain font-bold shadow-sm border border-transparent flex items-center gap-2 hover:border-primary transition-all"
                    id="nbtn-list"><i data-lucide="globe"></i> Manage Countries</button>
                <button onclick="switchNetworkTab('settings')"
                    class="net-tab-btn px-6 py-3 rounded-xl bg-bg text-secondary font-bold border border-transparent flex items-center gap-2 hover:bg-card hover:text-textMain transition-all"
                    id="nbtn-settings"><i data-lucide="settings"></i> Page Settings</button>
            </div>

            <div id="ntab-list" class="net-tab-content active">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1">
                        <div class="bg-card p-6 rounded-[20px] shadow-sm sticky top-28">
                            <h3 class="text-lg font-bold text-textMain mb-4">Add Country</h3>
                            <form method="post" enctype="multipart/form-data" class="space-y-4">
                                <input type="text" name="country_name" class="w-full bg-bg p-3 rounded-xl outline-none"
                                    required placeholder="e.g. USA">
                                <div
                                    class="bg-bg rounded-xl p-4 border-2 border-dashed border-gray-200 text-center cursor-pointer">
                                    <input type="file" name="flag" required
                                        class="w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                </div>
                                <button type="submit" name="upload_network"
                                    class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-colors shadow-lg">Add
                                    to Network</button>
                            </form>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="bg-card rounded-[20px] shadow-sm overflow-hidden">

                            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="text-xl font-bold text-textMain">Active Network</h3><span
                                    class="text-xs font-bold bg-green-100 text-green-600 px-3 py-1 rounded-full">
                                    <?= $netCount ?> Countries
                                </span>
                            </div>

                            <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach($pdo->query("SELECT * FROM network ORDER BY id DESC") as $country): 
                                        // --- FIX: Prepare Safe Data for JavaScript ---
                                        $countryJS = $country;
                                        unset($countryJS['flag_data']); // Remove the heavy BLOB data
                                        $safeJson = htmlspecialchars(json_encode($countryJS), ENT_QUOTES, 'UTF-8');
                                    ?>
                                <div
                                    class="bg-bg p-4 rounded-xl flex flex-col items-center text-center relative group border <?= $country['status'] ? 'border-transparent' : 'border-red-200 bg-red-50' ?>">

                                    <div
                                        class="w-16 h-16 rounded-full overflow-hidden border-4 border-white shadow-sm mb-3">
                                        <img src="../view_image.php?type=network&id=<?= $country['id'] ?>"
                                            class="w-full h-full object-cover">
                                    </div>

                                    <h4 class="font-bold text-textMain">
                                        <?= htmlspecialchars($country['country_name']) ?>
                                    </h4>

                                    <div class="flex items-center gap-2 mt-2">
                                        <a href="?page=network&toggle_net=<?= $country['id'] ?>"
                                            class="text-xs font-bold px-2 py-1 rounded <?= $country['status'] ? 'bg-green-100 text-green-600' : 'bg-red-200 text-red-600' ?>">
                                            <?= $country['status'] ? 'Active' : 'Inactive' ?>
                                        </a>

                                        <button onclick='openEditNetwork(<?= $safeJson ?>)'
                                            class="text-secondary hover:text-primary">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>

                                        <a href="?del_net=<?= $country['id'] ?>" onclick="return confirm('Remove?')"
                                            class="text-secondary hover:text-red-500">
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
                            <div><label
                                    class="block text-xs font-bold text-secondary mb-2 uppercase">Title</label><textarea
                                    name="network_title" class="tinymce w-full"><?= $netTitle ?></textarea></div>
                            <div><label
                                    class="block text-xs font-bold text-secondary mb-2 uppercase">Subtitle</label><textarea
                                    name="network_subtitle" class="tinymce w-full"><?= $netSub ?></textarea></div>
                        </div>
                        <div class="pt-6 mt-6 border-t border-gray-100"><button type="submit"
                                name="update_network_settings"
                                class="bg-primary hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg">Update
                                Network Text</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($page == 'messages'): ?>
            <?php 
                
               // 1. PHP 8.2 COMPATIBLE DATE HELPER (UPDATED)
                if (!function_exists('time_elapsed_string')) {
                    function time_elapsed_string($datetime, $full = false) {
                        // FIX: Set Timezone to India/Kolkata to sync with your DB
                        date_default_timezone_set('Asia/Kolkata'); 

                        $now = new DateTime;
                        $ago = new DateTime($datetime);
                        $diff = $now->diff($ago);

                        $weeks = floor($diff->d / 7);
                        $days = $diff->d - ($weeks * 7);

                        $string = ['y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'min','s' => 'sec'];
                        $values = ['y' => $diff->y, 'm' => $diff->m, 'w' => $weeks, 'd' => $days, 'h' => $diff->h, 'i' => $diff->i, 's' => $diff->s];

                        foreach ($string as $k => &$v) {
                            if ($values[$k]) {
                                $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
                            } else {
                                unset($string[$k]);
                            }
                        }

                        if (!$full) $string = array_slice($string, 0, 1);
                        return $string ? implode(', ', $string) . ' ago' : 'just now';
                    }
                }
                // 2. Avatar Colors
                if (!function_exists('getAvatarColor')) {
                    function getAvatarColor($char) {
                        $colors = ['bg-teal-100 text-teal-700', 'bg-indigo-100 text-indigo-700', 'bg-pink-100 text-pink-700', 'bg-orange-100 text-orange-700', 'bg-blue-100 text-blue-700'];
                        return $colors[ord(strtoupper($char)) % count($colors)];
                    }
                }

                // 3. SEARCH LOGIC (UPDATED: Name & Email Only)
                // 3. ADVANCED SEARCH & FILTER LOGIC
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
                $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

                // Base Query
                $sql = "SELECT * FROM messages WHERE 1=1";
                $params = [];

                // Name/Email Filter
                if (!empty($search)) {
                    $sql .= " AND (name LIKE ? OR email LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                // Date Range Filter
                if (!empty($startDate)) {
                    $sql .= " AND DATE(created_at) >= ?";
                    $params[] = $startDate;
                }
                if (!empty($endDate)) {
                    $sql .= " AND DATE(created_at) <= ?";
                    $params[] = $endDate;
                }

                // Finalize Query
                $sql .= " ORDER BY created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $messages = $stmt->fetchAll();
                ?>

            <div class="flex h-[calc(100vh-140px)] gap-6">


                <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">

                    <div
                        class="h-auto py-4 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between px-6 bg-white sticky top-0 z-10 gap-4">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <?= (!empty($search) || !empty($startDate)) ? 'Filtered Results' : 'All Inquiries' ?>
                        </h3>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <form method="get" class="flex flex-col md:flex-row gap-2 w-full md:items-center">
                                <input type="hidden" name="page" value="messages">

                                <div
                                    class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1">
                                    <span class="text-xs font-bold text-gray-400 uppercase">From</span>
                                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                                        class="bg-transparent text-sm text-gray-600 focus:outline-none p-1">
                                    <span class="text-gray-300">|</span>
                                    <span class="text-xs font-bold text-gray-400 uppercase">To</span>
                                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                                        class="bg-transparent text-sm text-gray-600 focus:outline-none p-1">
                                </div>

                                <div class="relative flex-1 md:flex-none">
                                    <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-gray-400"></i>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                        placeholder="Search name..."
                                        class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary w-full md:w-48 bg-gray-50 focus:bg-white transition-all">
                                </div>

                                <button type="submit"
                                    class="bg-primary hover:bg-brand-dark text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-colors">
                                    Filter
                                </button>

                                <?php if(!empty($search) || !empty($startDate) || !empty($endDate)): ?>
                                <a href="?page=messages" class="text-gray-400 hover:text-red-500 transition-colors"
                                    title="Clear Filters">
                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-gray-50">
                        <?php if(count($messages) > 0): ?>
                        <div class="divide-y divide-gray-200 border-b border-gray-200">
                            <?php foreach($messages as $msg): 
                                        $initial = !empty($msg['name']) ? strtoupper(substr($msg['name'], 0, 1)) : '?';
                                        $colorClass = getAvatarColor($initial);
                                        $exactDate = date("M j, Y • h:i A", strtotime($msg['created_at']));
                                        
                                        // LOGIC: Check if read
                                        $isRead = $msg['is_read'] == 1;
                                    ?>
                            <details
                                class="group hover:bg-slate-50 transition-colors open:bg-blue-50/30 border-b border-gray-100 <?= $isRead ? 'bg-white' : 'bg-blue-50/40' ?>">
                                <summary
                                    class="flex items-center gap-4 px-6 py-4 cursor-pointer list-none select-none relative">

                                    <?php if(!$isRead): ?>
                                    <span
                                        class="absolute left-2 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-primary shadow-sm"></span>
                                    <?php endif; ?>

                                    <div
                                        class="w-10 h-10 rounded-full <?= $colorClass ?> flex items-center justify-center font-bold text-sm shrink-0 border border-black/5">
                                        <?= $initial ?>
                                    </div>

                                    <div class="flex-1 min-w-0 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                        <div class="md:col-span-3">
                                            <h4
                                                class="text-sm <?= $isRead ? 'font-semibold text-gray-700' : 'font-bold text-black' ?> truncate">
                                                <?= htmlspecialchars($msg['name']) ?>
                                            </h4>
                                            <p class="text-xs text-gray-500 truncate">
                                                <?= htmlspecialchars($msg['email']) ?>
                                            </p>
                                        </div>

                                        <div class="md:col-span-6">
                                            <p
                                                class="text-sm text-gray-600 truncate group-open:text-primary group-open:font-medium">
                                                <?php if (!empty($msg['company'])): ?>
                                                <span class="font-bold text-gray-800">Co:</span>
                                                <?= htmlspecialchars($msg['company']) ?>
                                                <span class="text-gray-300 mx-2">|</span>
                                                <?php endif; ?>

                                                <?php if (!empty($msg['phone'])): ?>
                                                <i data-lucide="phone" class="w-3 h-3 inline-block mb-0.5"></i>
                                                <?= htmlspecialchars($msg['phone']) ?>
                                                <span class="text-gray-300 mx-2">|</span>
                                                <?php endif; ?>

                                                <span class="italic text-gray-500">
                                                    <?= htmlspecialchars(substr($msg['message'], 0, 30)) . (strlen($msg['message']) > 30 ? '...' : '') ?>
                                                </span>
                                            </p>
                                        </div>

                                        <div class="md:col-span-3 text-right">
                                            <span class="text-xs font-mono text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                <?= $exactDate ?>
                                            </span>
                                        </div>
                                    </div>
                                </summary>

                                <div class="px-6 pb-6 pt-2 pl-[88px] border-t border-gray-100/50 cursor-default">
                                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <h5 class="font-bold text-gray-800">Inquiry Details</h5>
                                                <div class="text-xs text-gray-400 mt-1 mb-2">Received:
                                                    <?= $exactDate ?> (
                                                    <?= time_elapsed_string($msg['created_at']) ?>)
                                                </div>
                                                <?php if(!empty($msg['phone'])): ?>
                                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                                    <i data-lucide="phone" class="w-3 h-3"></i>
                                                    <?= htmlspecialchars($msg['phone']) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="flex gap-2">
                                                <a href="?page=messages&toggle_read=<?= $msg['id'] ?>"
                                                    class="flex items-center gap-2 px-3 py-1.5 border <?= $isRead ? 'border-gray-200 text-gray-600 bg-gray-50' : 'border-blue-200 text-blue-600 bg-blue-50' ?> text-xs font-bold rounded-lg hover:shadow-md transition-all">
                                                    <?php if($isRead): ?>
                                                    <i data-lucide="mail" class="w-3 h-3"></i> Mark Unread
                                                    <?php else: ?>
                                                    <i data-lucide="mail-open" class="w-3 h-3"></i> Mark Read
                                                    <?php endif; ?>
                                                </a>

                                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"
                                                    class="flex items-center gap-2 px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-brand-dark transition-colors">
                                                    <i data-lucide="reply" class="w-3 h-3"></i> Reply
                                                </a>

                                                <a href="?del_msg=<?= $msg['id'] ?>" onclick="return confirm('Delete?')"
                                                    class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50 transition-colors">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                        <div
                                            class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-gray-50 p-4 rounded-lg border border-gray-100">
                                            <?= htmlspecialchars($msg['message']) ?>
                                        </div>
                                    </div>
                                </div>
                            </details>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="h-full flex flex-col items-center justify-center text-center">
                            <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i data-lucide="check-circle" class="w-12 h-12 text-gray-300"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">
                                <?= !empty($search) ? 'No results found' : 'No Inquiries' ?>
                            </h3>
                            <p class="text-gray-500 text-sm mt-2">
                                <?= !empty($search) ? 'Try adjusting your search terms.' : 'You are all caught up!' ?>
                            </p>
                            <?php if(!empty($search)): ?>
                            <a href="?page=messages" class="mt-4 text-primary text-sm font-bold hover:underline">Clear
                                Search</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($page == 'gallery'): 
    // 1. Fetch Text Content
    $jTitle = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'journey_title'")->fetchColumn() ?: '';
    $jSub = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'journey_subtitle'")->fetchColumn() ?: '';

    // 2. Fetch Media
    $journey_media = $pdo->query("SELECT * FROM personal_journey ORDER BY id DESC")->fetchAll();
    
    // 3. Prepare Data for AlpineJS Gallery
    $js_media = [];
    foreach($journey_media as $m) {
        if($m['media_type'] == 'image') {
            $src = "../view_image.php?id={$m['id']}&type=journey";
            $thumb = $src;
        } elseif($m['media_type'] == 'video') {
            $src = "../assets/videos/{$m['video_path']}";
            // Thumb for local video is the video itself (handled by Alpine logic)
            $thumb = $src; 
        } else {
            // Youtube
            $src = "https://www.youtube.com/embed/{$m['youtube_id']}?autoplay=1&mute=1&rel=0";
            $thumb = "https://img.youtube.com/vi/{$m['youtube_id']}/hqdefault.jpg";
        }
        $js_media[] = [
            'id' => $m['id'], 
            'type' => $m['media_type'], 
            'src' => $src, 
            'thumb' => $thumb
        ];
    }
?>

<style>
    .aspect-cinematic { aspect-ratio: 16 / 9; }
    .journey-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .journey-scrollbar::-webkit-scrollbar-thumb { background: #00C2CB; border-radius: 10px; }
    @keyframes fadeInViewer { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
    .animate-viewer { animation: fadeInViewer 0.4s ease-out forwards; }
</style>

<!-- Alpine.js Data Context for Gallery -->
<div class="space-y-6" x-data="{ 
    mediaItems: <?= htmlspecialchars(json_encode($js_media), ENT_QUOTES, 'UTF-8') ?>,
    activeItem: null,
    currentIndex: 0,
    setActive(index) { 
        this.currentIndex = index;
        this.activeItem = this.mediaItems[index]; 
    },
    next() {
        this.currentIndex = (this.currentIndex + 1) % this.mediaItems.length;
        this.setActive(this.currentIndex);
    },
    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.mediaItems.length) % this.mediaItems.length;
        this.setActive(this.currentIndex);
    }
}" x-init="if(mediaItems.length > 0) { setActive(0); }">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Forms stay at the top for easy editing -->
        <div class="bg-card p-6 rounded-[20px] shadow-sm border border-gray-100">
            <h3 class="font-bold text-textMain mb-4 flex items-center gap-2">
                <i data-lucide="type" class="w-5 h-5 text-primary"></i> Section Text
            </h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-secondary uppercase mb-2">Main Title</label>
                    <input type="text" name="journey_title" value="<?= htmlspecialchars($jTitle) ?>" class="w-full bg-bg border-none p-3 rounded-xl focus:ring-2 focus:ring-primary outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-secondary uppercase mb-2">Sub Title</label>
                    <textarea name="journey_subtitle" class="w-full bg-bg border-none p-3 rounded-xl focus:ring-2 focus:ring-primary outline-none transition-all h-24 resize-none"><?= htmlspecialchars($jSub) ?></textarea>
                </div>
                <button type="submit" name="save_journey_content" class="bg-secondary text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-800 transition-colors w-full md:w-auto">Update Text</button>
            </form>
        </div>

        <div class="bg-card p-6 rounded-[20px] shadow-sm border border-gray-100" x-data="{ type: 'image' }">
            <h3 class="font-bold text-textMain mb-4 flex items-center gap-2">
                <i data-lucide="upload" class="w-5 h-5 text-primary"></i> Add Media
            </h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <div class="flex bg-bg p-1 rounded-xl w-fit">
                    <label class="px-4 py-2 rounded-lg text-sm font-bold cursor-pointer transition-all" :class="type==='image' ? 'bg-white text-primary shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <input type="radio" name="media_type" value="image" x-model="type" class="hidden"> Image
                    </label>
                    <label class="px-4 py-2 rounded-lg text-sm font-bold cursor-pointer transition-all" :class="type==='video' ? 'bg-white text-primary shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <input type="radio" name="media_type" value="video" x-model="type" class="hidden"> Video
                    </label>
                    <label class="px-4 py-2 rounded-lg text-sm font-bold cursor-pointer transition-all" :class="type==='youtube' ? 'bg-white text-primary shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                        <input type="radio" name="media_type" value="youtube" x-model="type" class="hidden"> YouTube
                    </label>
                </div>
                <div x-show="type !== 'youtube'"><div class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:border-primary transition-colors bg-bg/50 cursor-pointer relative group"><input type="file" name="media_file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"><i data-lucide="cloud-upload" class="w-8 h-8 text-gray-300 mx-auto mb-2 group-hover:text-primary transition-colors"></i><p class="text-sm text-gray-500 font-medium">Click to upload file</p></div></div>
                <div x-show="type === 'youtube'" style="display: none;"><input type="text" name="youtube_url" placeholder="Paste YouTube Link" class="w-full bg-bg border-none p-3 rounded-xl focus:ring-2 focus:ring-primary outline-none"></div>
                <button type="submit" name="add_journey_media" class="w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-brand-dark shadow-lg shadow-primary/20">Add to Gallery</button>
            </form>
        </div>
    </div>

    <!-- ENHANCED CINEMATIC PREVIEW SECTION -->
    <div class="bg-card p-6 rounded-[32px] shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-textMain flex items-center gap-2">
                <i data-lucide="monitor" class="w-5 h-5 text-primary"></i> Cinematic Preview
            </h3>
            <div class="text-[10px] font-bold text-[#00C2CB] bg-cyan-50 px-3 py-1 rounded-full uppercase tracking-widest" x-text="mediaItems.length > 0 ? (currentIndex + 1) + ' / ' + mediaItems.length : 'No Media'"></div>
        </div>

        <div class="grid grid-cols-12 gap-8 h-auto lg:h-[550px]">
            <!-- THUMBNAILS PANEL -->
            <div class="col-span-12 lg:col-span-3 xl:col-span-2 flex flex-col gap-4 overflow-y-auto pr-2 journey-scrollbar max-h-[550px]">
                <template x-for="(item, index) in mediaItems" :key="item.id">
                    <div class="relative group">
                        <div @click="setActive(index)" 
                             class="relative cursor-pointer rounded-2xl overflow-hidden border-2 transition-all aspect-[4/3] shrink-0 bg-slate-100"
                             :class="currentIndex === index ? 'border-primary ring-4 ring-primary/10 opacity-100' : 'border-transparent opacity-60 hover:opacity-100'">
                            
                            <!-- Thumbnail rendering logic -->
                            <template x-if="item.type !== 'video'">
                                <img :src="item.thumb" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" @error="$el.src='https://placehold.co/400x300?text=Image+Missing'">
                            </template>

                            <template x-if="item.type === 'video'">
                                <video :src="item.src" class="w-full h-full object-cover pointer-events-none" muted preload="metadata" playsinline></video>
                            </template>

                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span x-show="item.type === 'video'" class="bg-black/40 backdrop-blur-md text-white rounded-full p-2 scale-75"><i data-lucide="play" class="w-4 h-4"></i></span>
                                <span x-show="item.type === 'youtube'" class="bg-red-600 text-white rounded-full p-2 scale-75"><i data-lucide="youtube" class="w-4 h-4"></i></span>
                            </div>
                        </div>

                        <!-- Admin Actions -->
                        <div class="absolute top-2 right-2 flex gap-1 z-30">
                            <a :href="'?page=journey&delete_journey=' + item.id" onclick="return confirm('Permanently delete this media?')" class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </template>
                
                <div x-show="mediaItems.length === 0" class="text-center py-20 text-gray-400 text-xs italic bg-bg rounded-2xl border border-dashed border-gray-200">
                    Your gallery is empty
                </div>
            </div>

            <!-- CINEMATIC VIEWER -->
            <div class="col-span-12 lg:col-span-9 xl:col-span-10 bg-[#001f2bff] rounded-[32px] overflow-hidden flex items-center justify-center relative shadow-2xl group/viewer">
                <template x-if="activeItem">
                    <div class="w-full h-full flex items-center justify-center animate-viewer">
                        <img x-show="activeItem.type === 'image'" :src="activeItem.src" class="max-w-full max-h-full object-contain select-none" @error="$el.src='https://placehold.co/1200x800?text=Image+Unavailable'">
                        <video x-show="activeItem.type === 'video'" :src="activeItem.src" controls autoplay muted loop class="max-w-full max-h-full"></video>
                        <iframe x-show="activeItem.type === 'youtube'" :src="activeItem.src" class="w-full h-full" frameborder="0" allowfullscreen></iframe>

                        <!-- Overlay Nav -->
                        <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md text-white flex items-center justify-center transition-all opacity-0 group-hover/viewer:opacity-100 -translate-x-2 group-hover/viewer:translate-x-0 z-20">
                            <i data-lucide="chevron-left" class="w-6 h-6"></i>
                        </button>
                        <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md text-white flex items-center justify-center transition-all opacity-0 group-hover/viewer:opacity-100 translate-x-2 group-hover/viewer:translate-x-0 z-20">
                            <i data-lucide="chevron-right" class="w-6 h-6"></i>
                        </button>

                        <!-- Badge -->
                        <div class="absolute bottom-6 left-6 flex items-center gap-3 bg-black/40 backdrop-blur-xl px-4 py-2 rounded-xl border border-white/10 pointer-events-none">
                            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                            <span class="text-white text-[10px] font-black uppercase tracking-widest" x-text="activeItem.type"></span>
                        </div>
                    </div>
                </template>
                
                <template x-if="!activeItem">
                    <div class="text-white/30 flex flex-col items-center">
                        <i data-lucide="clapperboard" class="w-16 h-16 mb-4 opacity-20"></i>
                        <p class="text-sm font-bold tracking-widest uppercase">Admin Preview Ready</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
        </div>
    </main>

    <div id="editFishModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl transform scale-95 transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-textMain">Edit Fish</h3><button onclick="closeModal('editFishModal')"
                    class="text-secondary hover:text-primary"><i data-lucide="x"></i></button>
            </div>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" id="edit_fish_id">
                <input type="text" name="title" id="edit_fish_title" class="w-full bg-bg p-3 rounded-xl outline-none"
                    required placeholder="Title">
                <input type="text" name="scientific" id="edit_fish_sci" class="w-full bg-bg p-3 rounded-xl outline-none"
                    placeholder="Scientific Name">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="origin" id="edit_fish_origin"
                        class="w-full bg-bg p-3 rounded-xl outline-none" placeholder="Origin">
                    <input type="text" name="grade" id="edit_fish_grade"
                        class="w-full bg-bg p-3 rounded-xl outline-none" placeholder="Grade">
                </div>
                <select name="category" id="edit_fish_cat" class="w-full bg-bg p-3 rounded-xl outline-none">
                    <option value="freshwater">Freshwater</option>
                    <option value="marine">Marine</option>
                </select>
                <div><label class="text-xs font-bold text-secondary block mb-1">New Image (Optional)</label><input
                        type="file" name="image" class="w-full text-sm"></div>
                <div class="pt-4"><button type="submit" name="edit_fish"
                        class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editNetModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-textMain">Edit Country</h3><button
                    onclick="closeModal('editNetModal')" class="text-secondary hover:text-primary"><i
                        data-lucide="x"></i></button>
            </div>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" id="edit_net_id">
                <input type="text" name="country_name" id="edit_net_name"
                    class="w-full bg-bg p-3 rounded-xl outline-none" required>
                <div><label class="text-xs font-bold text-secondary block mb-1">New Flag (Optional)</label><input
                        type="file" name="flag" class="w-full text-sm"></div>
                <div class="pt-4"><button type="submit" name="edit_network"
                        class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg">Save Changes</button>
                </div>
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
            if (activeBtn) {
                activeBtn.classList.add('active');
                activeBtn.classList.remove('text-secondary');
            }

            // NEW: Update Hidden Input
            const hiddenInput = document.getElementById('active_tab_input');
            if (hiddenInput) {
                hiddenInput.value = tabId;
            }
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
        if (document.getElementById('myChart')) {
            const ctx = document.getElementById('myChart');
            new Chart(ctx, { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], datasets: [{ label: 'Visitors', data: [12, 19, 15, 25, 22, 30], borderColor: '#00C2CB', backgroundColor: 'rgba(67, 24, 255, 0.1)', tension: 0.4, fill: true, pointRadius: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false } } } } });
        }
    </script>
</body>

</html>