<?php 


// Include database and functions at the top
require_once 'includes/db.php';


// 1. Helper Function to fetch text content
function getContent($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? $res['content_value'] : ''; // Returns empty string if key missing
}

// 2. Helper Function to fetch gallery items (Fixed column name 'grade')
function getGallery() {
    global $pdo;
    // UPDATED: Added 'WHERE status = 1' to only show active fish
    $stmt = $pdo->query("SELECT * FROM gallery WHERE status = 1 ORDER BY id DESC");
    return $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CExport | Exotic Fish Exporter</title>
    <link rel="icon" type="image/png" href="view_image.php?type=logo">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Oswald:wght@300;500;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            white: '#ffffffff',   
                            light: '#E0F7FA',   
                            blue: '#0096C7',    
                            deep: '#0077B6',    
                            navy: '#023E8A',    
                            accent: '#FFC300',  
                            accentHover: '#FFD60A',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        sans2: ['"Manrope"', 'sans-serif'],
                        serif: ['"Playfair Display"', 'serif'],
                        heading: ['"Oswald"', 'sans-serif'],
                        logo: ['"Kaushan Script"', 'cursive'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'scroll': 'scroll 40s linear infinite',
                        'rise': 'rise 15s infinite linear',
                        'water': 'water 10s ease-in-out infinite',
                        'fadeIn': 'fadeIn 0.5s ease-out forwards',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        scroll: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-50%)' },
                        },
                        rise: {
                            '0%': { bottom: '-10%', transform: 'translateX(0)', opacity: '0' },
                            '50%': { opacity: '0.6' },
                            '100%': { bottom: '110%', transform: 'translateX(-20px)', opacity: '0' },
                        },
                         water: {
                            '0%, 100%': { borderRadius: '60% 40% 30% 70% / 60% 30% 70% 40%' },
                            '50%': { borderRadius: '30% 60% 70% 40% / 50% 60% 30% 60%' },
                        },
                        fadeIn: {
                            'from': { opacity: '0', transform: 'translateY(10px)' },
                            'to': { opacity: '1', transform: 'translateY(0)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #E0F7FA; }
        ::-webkit-scrollbar-thumb { background: #0096C7; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #0077B6; }

        /* Typography Polish */
        .text-shadow-sm { text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }
        
        /* Water Glass Effect */
        .glass-water {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }

        /* Bubbles */
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.1));
            border: 1px solid rgba(255, 255, 255, 0.3);
            pointer-events: none;
            z-index: 0;
        }

        /* Wave Dividers */
        .wave-separator {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            transform: rotate(180deg);
        }
        .wave-separator svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 80px;
        }
        .wave-top {
            position: absolute;
            top: -1px;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }
        .wave-top svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 80px;
        }

        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #023E8A 0%, #0096C7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Swiper Pagination Customization */
        .swiper-pagination-bullet {
            background-color: #0096C7 !important;
            opacity: 0.5;
        }
        .swiper-pagination-bullet-active {
            background-color: #0077B6 !important;
            opacity: 1;
        }
        
        /* About Title Overlay */
        .about-bg-text {
            -webkit-text-stroke: 2px rgba(0, 96, 156, 0.1);
            color: transparent;
        }
    </style>
</head>
<body class="bg-brand-light text-brand-navy font-sans antialiased overflow-x-hidden">
    

    <div id="bubble-container" class="fixed inset-0 pointer-events-none z-0 overflow-hidden h-full w-full"></div>

    <nav class="fixed w-full z-50 transition-all duration-300 border-b border-white/0" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-2">
                    <img id="nav-logo" src="view_image.php?type=logo" alt="CExport Logo" class="h-12 w-auto object-contain drop-shadow-md transition-all duration-300 brightness-0 invert">
                </div>
                                                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-center space-x-8">
                        <a href="#home" class="text-white hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors drop-shadow-sm">Home</a>
                        <a href="#about" class="text-white hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors drop-shadow-sm">About</a>
                        <a href="#gallery" class="text-white hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors drop-shadow-sm">Gallery</a>
                        <a href="#services" class="text-white hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors drop-shadow-sm">Services</a>
                        <a href="#contact" class="crimson-btn px-6 py-2 rounded-full text-sm font-bold tracking-wide shadow-lg border border-white/20 bg-brand-accent text-brand-navy hover:bg-brand-accentHover transition-colors">Contact Us</a>
                    </div>
                </div>

                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-white hover:text-brand-accent transition-colors">
                        <i data-lucide="menu" class="h-8 w-8"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="md:hidden hidden bg-brand-navy/95 backdrop-blur-xl absolute w-full border-b border-white/20 shadow-xl" id="mobile-menu">
            <div class="px-4 pt-4 pb-6 space-y-2">
                <a href="#home" class="block px-3 py-3 rounded-md text-white font-bold hover:bg-white/10 transition-colors">Home</a>
                <a href="#about" class="block px-3 py-3 rounded-md text-white font-bold hover:bg-white/10 transition-colors">About</a>
                <a href="#gallery" class="block px-3 py-3 rounded-md text-white font-bold hover:bg-white/10 transition-colors">Gallery</a>
                <a href="#services" class="block px-3 py-3 rounded-md text-white font-bold hover:bg-white/10 transition-colors">Services</a>
                <a href="#contact" class="block text-center mt-4 px-3 py-3 rounded-md bg-brand-accent text-brand-navy font-bold hover:bg-brand-accentHover transition-colors">Contact Us</a>
            </div>
        </div>
    </nav>
    
    <header id="home" class="relative h-screen flex items-center justify-center overflow-hidden">
        
        <div class="absolute inset-0 z-0">
            <?php 
                // Fetch the video path from DB
                $heroVideo = getContent('hero_video'); 
                
                // Logic to determine if video should show
                $showVideo = false;
                if (!empty($heroVideo)) {
                    if (file_exists($heroVideo)) {
                        $showVideo = true;
                    } 
                }
            ?>

            <?php if($showVideo): ?>
                <!-- Added id="heroVideo" and removed 'muted' to attempt default audio -->
                <video id="heroVideo" autoplay loop playsinline preload="auto" class="w-full h-full object-cover">
                    <!-- Use htmlspecialchars for security -->
                    <source src="<?= htmlspecialchars($heroVideo) ?>" type="video/mp4">
                    <!-- Fallback text/image if browser doesn't support video -->
                </video>
            <?php else: ?>
                <!-- Fallback Gradient if no video or file missing -->
                <div class="w-full h-full bg-gradient-to-br from-slate-900 to-slate-800"></div>
            <?php endif; ?>
            
            <div class="absolute inset-0 bg-black/40"></div> <!-- Overlay -->
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center h-full">
            <div class="pt-20 md:pt-0" data-aos="fade-right" data-aos-duration="1000">
                
                <h1 class="font-heading text-6xl md:text-8xl font-bold leading-[0.9] text-white mb-8 drop-shadow-lg">
                    <?= getContent('hero_title') ?>
                </h1>
                
                <div class="font-serif italic text-xl md:text-2xl text-white/90 mb-10 max-w-lg border-l-4 border-brand-accent pl-6 leading-relaxed shadow-sm">
                    <?= getContent('hero_subtitle') ?>
                </div>
                
                <div class="flex flex-wrap gap-4">
                    <a href="#collection" class="px-8 py-4 bg-brand-accent text-brand-navy font-bold uppercase tracking-wider hover:bg-white hover:text-brand-blue transition-all shadow-lg shadow-brand-accent/20 flex items-center gap-2 group rounded-full transform hover:-translate-y-1">
                        View Catalog <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="#contact" class="px-8 py-4 border-2 border-white/30 text-white font-bold uppercase tracking-wider hover:bg-white hover:text-brand-navy hover:border-white transition-all rounded-full backdrop-blur-sm">
                        Start Order
                    </a>
                </div>
            </div>
            
             <div class="hidden md:block relative h-full pointer-events-none">
                <div class="absolute top-1/3 right-10 glass-water p-3 rounded-2xl shadow-2xl animate-float z-20 border-t border-white/50" style="animation-delay: 1s;">
                    <div class="flex items-center gap-4">
                        <div class="bg-brand-light p-2 rounded-full text-brand-blue shadow-inner">
                            <i data-lucide="check-circle" class="w-8 h-8"></i>
                        </div>
                        <div>
                            <p class="font-bold text-brand-navy text-lg">
                                <?= getContent('hero_feature_title') ?>
                            </p>
                            
                            <p class="text-sm text-brand-deep font-medium">
                                <?= getContent('hero_feature_subtitle') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- AUDIO TOGGLE BUTTON START -->
        <?php if($showVideo): ?>
        <div class="absolute bottom-10 right-10 z-30 animate-fadeIn">
            <button id="audioToggle" class="p-4 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-white hover:bg-white/20 hover:scale-110 transition-all duration-300 shadow-xl group">
                <i data-lucide="volume-2" class="w-6 h-6"></i>
            </button>
        </div>
        <?php endif; ?>
        <!-- AUDIO TOGGLE BUTTON END -->

        <div class="wave-separator">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-brand-blue"></path>
            </svg>
        </div>
    </header>

    <section class="bg-brand-blue py-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="absolute top-0 left-10 w-20 h-20 bg-white/10 rounded-full blur-xl animate-float"></div>
        <div class="absolute bottom-10 right-10 w-32 h-32 bg-white/10 rounded-full blur-xl animate-float" style="animation-delay: 2s"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x divide-white/20">
                
                <div data-aos="fade-up" data-aos-delay="0">
                    <div class="flex justify-center items-center gap-1 mb-2">
                        <span class="text-5xl md:text-6xl font-heading font-bold text-white drop-shadow-md">
                            <?= getContent('stats_species') ?>
                        </span>
                        <span class="text-brand-accent text-4xl md:text-5xl font-bold">+</span>
                    </div>
                    <span class="text-brand-light font-bold text-xs uppercase tracking-widest">Species Available</span>
                </div>

                <div data-aos="fade-up" data-aos-delay="100">
                    <div class="flex justify-center items-center gap-1 mb-2">
                        <span class="text-5xl md:text-6xl font-heading font-bold text-white drop-shadow-md">
                            <?= getContent('stats_countries') ?>
                        </span>
                        <span class="text-brand-accent text-4xl md:text-5xl font-bold">+</span>
                    </div>
                    <span class="text-brand-light font-bold text-xs uppercase tracking-widest">Countries Served</span>
                </div>

                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="flex justify-center items-center gap-1 mb-2">
                        <span class="text-5xl md:text-6xl font-heading font-bold text-white drop-shadow-md">
                            <?= getContent('stats_customers') ?>
                        </span>
                        <span class="text-brand-accent text-4xl md:text-5xl font-bold">+</span>
                    </div>
                    <span class="text-brand-light font-bold text-xs uppercase tracking-widest">Happy Customers</span>
                </div>

                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="flex justify-center items-center gap-1 mb-2">
                        <span class="text-5xl md:text-6xl font-heading font-bold text-white drop-shadow-md">
                            24<span class="text-brand-accent">/</span>7
                        </span>
                    </div>
                    <span class="text-brand-light font-bold text-xs uppercase tracking-widest">Support</span>
                </div>

            </div>
        </div>
    </section>

    <section class="py-20 bg-white relative">
        <div class="wave-top">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-brand-blue"></path>
            </svg>
        </div>

        <div class="text-center mt-8 mb-12">
            <div class="text-brand-blue/60 font-bold uppercase tracking-[0.3em] text-xs mb-2 [&>p]:m-0">
                <?= getContent('network_title') ?>
            </div>
            <div class="text-gray-400 text-sm [&>p]:m-0">
                <?= getContent('network_subtitle') ?>
            </div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-6 group">
            <div class="absolute top-0 left-0 w-32 h-full bg-gradient-to-r from-white to-transparent z-10 pointer-events-none"></div>
            <div class="absolute top-0 right-0 w-32 h-full bg-gradient-to-l from-white to-transparent z-10 pointer-events-none"></div>

            <div class="clients-slider swiper">
                <div class="swiper-wrapper align-items-center">
                    <?php
                    // Fetch Dynamic Network
                    $stmt = $pdo->query("SELECT * FROM network WHERE status = 1 ORDER BY id DESC");
                    while ($country = $stmt->fetch()):
                    ?>
                    <div class="swiper-slide text-center flex flex-col items-center">
                        <img src="view_image.php?type=network&id=<?= $country['id'] ?>" class="w-20 h-20 rounded-full object-cover border-4 border-gray-100 shadow-lg mx-auto mb-4" alt="<?= htmlspecialchars($country['country_name']) ?>">
                        <h4 class="text-lg font-heading font-bold text-gray-400 uppercase tracking-widest"><?= htmlspecialchars($country['country_name']) ?></h4>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section id="about" class="py-24 bg-[#F0F2F5] overflow-hidden relative font-sans">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                <!-- Left Column: Carousel & Image -->
                <div class="relative" data-aos="fade-right">
                    
                    <!-- Swiper Carousel Container - Fixed Aspect Ratio (4:5 Portrait) -->
                    <!-- Removed h-[500px], added aspect-[4/5] -->
                    <div class="swiper aboutSwiper   overflow-hidden shadow-2xl relative z-10 h-[750px] w-full max-w-md mx-auto aspect-[4/5] bg-white">
                        <div class="swiper-wrapper h-full">
                            <?php 
                            $slides = $pdo->query("SELECT id FROM about_carousel ORDER BY id ASC")->fetchAll();
                            if(empty($slides)): ?>
                                <div class="swiper-slide h-full w-full">
                                    <img src="https://via.placeholder.com/600x900?text=Upload+Images+in+Admin" class="w-full h-full ">
                                </div>
                            <?php else: foreach($slides as $slide): ?>
                                <div class="swiper-slide h-full w-full">
                                    <img src="view_image.php?type=about_slide&id=<?= $slide['id'] ?>" class="w-full h-full object-cover">
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>

                    <!-- Floating Badge -->
                    <div class="absolute -bottom-16 -left-12 z-20 w-48 h-48 drop-shadow-2xl animate-float hidden md:block">
                        <img src="view_image.php?type=content&key=about_badge_img" class="w-full h-full object-contain hover:scale-110 transition-transform duration-500">
                    </div>
                </div>

                <!-- Right Column: Content -->
                <div class="relative z-10 pt-10" data-aos="fade-left">
                    
                    <!-- Standard ABOUT US Heading as requested -->
                    <h2 class="font-heading text-4xl md:text-5xl font-bold text-brand-navy mb-4 leading-tight">
                        <?= getContent('about_large_title') ?>
                    </h2>
                    
                    <h4 class="text-[#008CCF] font-bold tracking-widest uppercase  mt-8  text-xl">
                        <?= getContent('about_subtitle') ?>
                    </h4>
                    
                    <p class="text-gray-500 leading-relaxed mb-2 text-lg">
                        <?= getContent('about_desc') ?>
                    </p>

                    <!-- Tabs / Toggle Buttons (Ref Img 2 Style) -->
                    <div class="flex flex-wrap gap-4 mb-8 mt-8">
                        <?php for($i=1; $i<=4; $i++): ?>
                            <!-- Use dynamic button classes for pill shape and state -->
                            <button onclick="openAboutTab(<?= $i ?>)" id="tab-btn-<?= $i ?>" 
                                class="px-8 py-3 rounded-full border-2 border-[#00609C] font-bold transition-all duration-300 shadow-sm
                                <?= $i===1 ? 'bg-[#00609C] text-white shadow-md' : 'bg-white text-[#00609C] hover:bg-gray-50' ?>">
                                <?= getContent('tab_'.$i.'_label') ?>
                            </button>
                        <?php endfor; ?>
                    </div>

                    <!-- Tab Content Area -->
                    <div class="bg-[#E2EBF1] p-10 rounded-2xl border border-blue-100 min-h-[200px] relative">
                        <?php for($i=1; $i<=4; $i++): ?>
                            <div id="tab-content-<?= $i ?>" class="<?= $i!==1 ? 'hidden' : '' ?> animate-fadeIn">
                                <h3 class="text-[#FFC107] font-extrabold text-2xl uppercase mb-4 tracking-wider">
                                    <?= getContent('tab_'.$i.'_title') ?>
                                </h3>
                                <p class="text-gray-600 font-medium leading-relaxed">
                                    <?= getContent('tab_'.$i.'_text') ?>
                                </p>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="text-center mt-12">
                <a href="#" class="inline-block border-b-2 border-brand-accent pb-1 text-brand-navy font-bold uppercase tracking-widest hover:text-brand-blue hover:border-brand-blue transition-colors">
                    <?= getContent('about_btn_text') ?>
                </a>
            </div>
                </div>
            </div>
        </div>
    </section>



    <section id="gallery" class="py-24 bg-gradient-to-b from-brand-light to-white relative">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16">
                        <div data-aos="fade-right">
                            <div class="font-heading text-5xl md:text-6xl font-bold text-brand-navy mb-4 [&>p]:m-0 [&>h1]:m-0 [&>h2]:m-0 leading-tight">
                                <?= getContent('gallery_title') ?>
                            </div>
                            
                            <div class="text-gray-600 max-w-lg text-lg [&>p]:mb-2">
                                <?= getContent('gallery_text') ?>
                            </div>
                        </div>
                                                
                <div class="flex gap-2 bg-white/50 backdrop-blur p-1.5 rounded-full border border-white shadow-lg mt-6 md:mt-0" data-aos="fade-left">
                    <button onclick="filterGallery('all')" class="gallery-btn active px-6 py-2 rounded-full text-sm font-bold text-white bg-brand-blue shadow-lg transition-all">All</button>
                    <button onclick="filterGallery('freshwater')" class="gallery-btn px-6 py-2 rounded-full text-sm font-bold text-gray-500 hover:text-brand-blue hover:bg-white transition-all">Freshwater</button>
                    <button onclick="filterGallery('marine')" class="gallery-btn px-6 py-2 rounded-full text-sm font-bold text-gray-500 hover:text-brand-blue hover:bg-white transition-all">Marine</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php 
                // Fetch items from DB
                $gallery = getGallery();
                foreach($gallery as $fish): 
                ?>
                <div class="gallery-item <?= htmlspecialchars($fish['category']) ?> group bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl hover:shadow-brand-blue/20 transition-all duration-500 transform hover:-translate-y-2 border border-brand-light" data-aos="fade-up">
                    <div class="relative h-80 overflow-hidden">
                        <img src="view_image.php?id=<?= $fish['id'] ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="<?= htmlspecialchars($fish['title']) ?>">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-blue/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-6">
                            <button class="bg-brand-accent text-brand-navy font-bold px-4 py-2 rounded-full text-sm uppercase tracking-wider w-full hover:bg-white transition-colors shadow-lg">View Details</button>
                        </div>
                        <div class="absolute top-4 left-4 bg-white/80 backdrop-blur-sm text-brand-navy font-bold text-xs px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                            <?= htmlspecialchars($fish['category']) ?>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="font-serif font-bold text-2xl text-brand-navy"><?= htmlspecialchars($fish['title']) ?></h3>
                        <p class="text-brand-blue font-medium mb-2"><?= htmlspecialchars($fish['scientific_name']) ?></p>
                        <div class="flex justify-between items-center text-sm text-gray-500 border-t border-gray-100 pt-3 mt-3">
                            <span><?= htmlspecialchars($fish['origin']) ?></span>
                            <span class="font-bold text-brand-navy"><?= htmlspecialchars($fish['grade']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="#" class="inline-block border-b-2 border-brand-accent pb-1 text-brand-navy font-bold uppercase tracking-widest hover:text-brand-blue hover:border-brand-blue transition-colors">View Full Catalog</a>
            </div>
        </div>
        
        <div class="wave-separator">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-white"></path>
            </svg>
        </div>
    </section>

    <section id="services" class="py-24 bg-white overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                
                <div class="relative" data-aos="fade-right">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-brand-accent rounded-full opacity-20 blur-2xl"></div>
                    <div class="relative z-10 rounded-[4rem] rounded-tl-none overflow-hidden shadow-2xl border-8 border-brand-light animate-water bg-white">
                        <img src="view_image.php?type=content&key=services_img" class="w-full h-full object-cover scale-110 hover:scale-100 transition-transform duration-1000">
                    </div>
                    
                    <div class="absolute -bottom-6 -right-6 bg-brand-blue text-white p-8 rounded-full shadow-xl shadow-brand-blue/30 z-20 w-32 h-32 flex flex-col items-center justify-center border-4 border-white">
                        <p class="font-heading font-bold text-3xl mb-0"><?= getContent('services_badge_val') ?></p>
                        <p class="text-[0.6rem] uppercase tracking-widest text-brand-light"><?= getContent('services_badge_label') ?></p>
                    </div>
                </div>

                <div data-aos="fade-left">
                    <h4 class="text-brand-blue font-bold tracking-[0.2em] uppercase text-sm mb-4 flex items-center gap-2">
                        <span class="w-8 h-0.5 bg-brand-blue"></span> <?= getContent('services_subtitle') ?>
                    </h4>
                    <h2 class="font-heading text-5xl md:text-6xl font-bold text-brand-navy mb-6 leading-tight">
                        <?= getContent('services_title') ?>
                    </h2>
                    <div class="text-gray-500 text-lg mb-8 leading-relaxed">
                        <?= getContent('services_desc') ?>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4 p-6 rounded-2xl border border-brand-light hover:bg-brand-light/50 transition-colors group shadow-sm hover:shadow-md">
                            <div class="w-12 h-12 bg-white border border-brand-light shadow-md rounded-full flex items-center justify-center text-brand-accent group-hover:bg-brand-blue group-hover:text-white transition-colors shrink-0">
                                <i data-lucide="briefcase" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-navy text-xl"><?= getContent('service_1_title') ?></h3>
                                <div class="text-sm text-gray-500 mt-2"><?= getContent('service_1_desc') ?></div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-6 rounded-2xl border border-brand-light hover:bg-brand-light/50 transition-colors group shadow-sm hover:shadow-md">
                            <div class="w-12 h-12 bg-white border border-brand-light shadow-md rounded-full flex items-center justify-center text-brand-accent group-hover:bg-brand-blue group-hover:text-white transition-colors shrink-0">
                                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-navy text-xl"><?= getContent('service_2_title') ?></h3>
                                <div class="text-sm text-gray-500 mt-2"><?= getContent('service_2_desc') ?></div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-6 rounded-2xl border border-brand-light hover:bg-brand-light/50 transition-colors group shadow-sm hover:shadow-md">
                            <div class="w-12 h-12 bg-white border border-brand-light shadow-md rounded-full flex items-center justify-center text-brand-accent group-hover:bg-brand-blue group-hover:text-white transition-colors shrink-0">
                                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-navy text-xl"><?= getContent('service_3_title') ?></h3>
                                <div class="text-sm text-gray-500 mt-2"><?= getContent('service_3_desc') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-24 bg-brand-light relative">
        <div class="max-w-5xl mx-auto px-6 relative z-10" >
            <div class="glass-water rounded-[3rem] shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">
                
                <div class="bg-brand-blue p-12 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-brand-blue to-brand-deep"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 bg-brand-accent/20 rounded-full blur-3xl"></div>

                    <div class="relative z-10 h-full flex flex-col justify-between">
                        <div>
                            <h2 class="font-heading text-4xl font-bold mb-6"><?= getContent('contact_title') ?></h2>
                            <p class="text-brand-light mb-8"><?= getContent('contact_Desc') ?></p>
                            
                            <div class="space-y-4 mt-8">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-brand-accent backdrop-blur-sm shadow-inner">
                                        <i data-lucide="phone" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-bold tracking-widest"><?= getContent('contact_phone') ?></span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-brand-accent backdrop-blur-sm shadow-inner">
                                        <i data-lucide="mail" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-bold tracking-widest"><?= getContent('contact_email') ?></span>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-brand-accent shrink-0 backdrop-blur-sm shadow-inner">
                                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-bold tracking-widest text-sm leading-relaxed"><?= getContent('contact_address') ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12">
                            <p class="text-xs uppercase text-brand-light mb-2">Social Connect</p>
                            <div class="flex gap-4">
                                <?php 
                                // Fetch variables from database (using functions.php helper)
                                $fb = getContent('social_facebook');
                                $insta = getContent('social_instagram');
                                $wa = getContent('social_whatsapp');
                                ?>

                                <?php if(!empty($fb) && $fb != '#'): ?>
                                    <a href="<?= htmlspecialchars($fb) ?>" target="_blank" class="hover:text-brand-accent transition-colors">
                                        <img width="32" height="32" src="https://img.icons8.com/windows/32/FFFFFF/facebook-new.png" alt="facebook-new"/>
                                    </a>
                                <?php endif; ?>

                                <?php if(!empty($insta) && $insta != '#'): ?>
                                    <a href="<?= htmlspecialchars($insta) ?>" target="_blank" class="hover:text-brand-accent transition-colors">
                                        <img width="32" height="32" src="https://img.icons8.com/windows/32/FFFFFF/instagram-new.png" alt="instagram-new"/>
                                    </a>
                                <?php endif; ?>

                                <?php if(!empty($wa) && $wa != '#'): ?>
                                    <a href="<?= htmlspecialchars($wa) ?>" target="_blank" class="hover:text-brand-accent transition-colors">
                                        <img width="32" height="32" src="https://img.icons8.com/windows/32/FFFFFF/whatsapp--v1.png" alt="whatsapp--v1"/> 
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-12 bg-white/80">
                    <form class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Company Name</label>
                            <input type="text" class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm" placeholder="e.g. Ocean Blue Ltd">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Contact Person</label>
                                <input type="text" class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm" placeholder="John Doe">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Email</label>
                                <input type="email" class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm" placeholder="john@company.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Requirements</label>
                            <textarea rows="3" class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm" placeholder="Tell us about your order needs..."></textarea>
                        </div>
                        <button class="w-full py-4 bg-brand-accent hover:bg-brand-accentHover text-white font-bold uppercase tracking-widest shadow-lg transform hover:-translate-y-1 transition-all flex justify-center gap-2 rounded-xl">
                            Request Price List <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <footer class="bg-brand-white border-t border-gray-100 py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <img id="nav-logo" src="view_image.php?type=logo" alt="CExport Logo" class="h-12 w-auto object-contain drop-shadow-md transition-all ">
            </div>
            <p class="text-gray-400 text-sm">© 2024 CExport. All rights reserved.</p>
            <p class="text-gray-400 text-sm">Design By Que Systems</p>
            <div class="flex gap-6 text-sm font-bold text-brand-navy">
                <a href="#" class="hover:text-brand-blue">Privacy</a>
                <a href="#" class="hover:text-brand-blue">Terms</a>
                <a href="#" class="hover:text-brand-blue">Sitemap</a>
            </div>
        </div>
    </footer>

     <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Init Icons
        lucide.createIcons();
        
        // Init AOS
        AOS.init({
            once: true,
            offset: 100,
            duration: 800,
            easing: 'ease-out-cubic',
        });

        // Bubble Generator
        function createBubbles() {
            const container = document.getElementById('bubble-container');
            const bubbleCount = 30;

            for (let i = 0; i < bubbleCount; i++) {
                const bubble = document.createElement('div');
                bubble.classList.add('bubble');
                const size = Math.random() * 20 + 5;
                bubble.style.width = `${size}px`;
                bubble.style.height = `${size}px`;
                bubble.style.left = `${Math.random() * 100}%`;
                // Start below viewport
                bubble.style.bottom = `-${Math.random() * 20 + 10}%`; 
                const duration = Math.random() * 15 + 10;
                const delay = Math.random() * 20;
                
                bubble.style.animation = `rise ${duration}s infinite linear`;
                bubble.style.animationDelay = `-${delay}s`; // Start immediately at random positions
                bubble.style.opacity = Math.random() * 0.4 + 0.1;
                container.appendChild(bubble);
            }
        }
        createBubbles();

        // Mobile Menu
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });

        // Gallery Filter
        function filterGallery(type) {
            const items = document.querySelectorAll('.gallery-item');
            const buttons = document.querySelectorAll('.gallery-btn');
            
            // Update Buttons
            buttons.forEach(b => {
                b.classList.remove('bg-brand-blue', 'text-white', 'shadow-lg');
                b.classList.add('text-gray-500');
                if(b.textContent.toLowerCase() === type || (type === 'all' && b.textContent === 'All')) {
                    b.classList.add('bg-brand-blue', 'text-white', 'shadow-lg');
                    b.classList.remove('text-gray-500');
                }
            });

            // Filter
            items.forEach(item => {
                if(type === 'all' || item.classList.contains(type)) {
                    item.style.display = 'block';
                    // Re-trigger AOS if hidden previously
                    item.classList.add('aos-animate'); 
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Navbar Scroll Logic
        const navbar = document.getElementById('navbar');
        const navLogo = document.getElementById('nav-logo'); // Get the logo image
    
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                // Scrolled State (White Background)
                navbar.classList.add('shadow-md', 'bg-white/95', 'backdrop-blur-sm');
                navbar.classList.remove('border-b', 'border-white/10');
                
                // 1. Make Logo Original Color (Black) by removing the filter
                if(navLogo) {
                    navLogo.classList.remove('brightness-0', 'invert');
                }

                // 2. Change Menu Links to Dark Blue
                const links = navbar.querySelectorAll('a, button, span.font-logo');
                links.forEach(link => {
                    if(link.id !== 'mobile-menu-btn' && !link.classList.contains('crimson-btn')) {
                        link.classList.remove('text-white');
                        link.classList.add('text-brand-navy');
                    } 
                    else if(link.id === 'mobile-menu-btn') {
                        link.classList.remove('text-white');
                        link.classList.add('text-brand-navy');
                    }
                });

            } else {
                // Top State (Transparent Background)
                navbar.classList.remove('shadow-md', 'bg-white/95', 'backdrop-blur-sm');
                navbar.classList.add('border-b', 'border-white/0');

                 // 1. Make Logo White again using CSS Filter
                 if(navLogo) {
                    navLogo.classList.add('brightness-0', 'invert');
                }

                 // 2. Revert Menu Links to White
                 const links = navbar.querySelectorAll('a, button, span.font-logo');
                 links.forEach(link => {
                    if(link.id !== 'mobile-menu-btn' && !link.classList.contains('crimson-btn')) {
                        link.classList.add('text-white');
                        link.classList.remove('text-brand-navy');
                    } 
                    else if(link.id === 'mobile-menu-btn') {
                        link.classList.add('text-white');
                        link.classList.remove('text-brand-navy');
                    }
                });
            }
        });
            

        // Initialize Swiper (RENAMED to avoid conflict)
        const clientsSwiper = new Swiper('.clients-slider', {
            speed: 400,
            loop: true,
            autoplay: {
                delay: 2000,
                disableOnInteraction: false
            },
            slidesPerView: 'auto',
            pagination: {
                el: '.swiper-pagination',
                type: 'bullets',
                clickable: true
            },
            breakpoints: {
                320: {
                    slidesPerView: 2,
                    spaceBetween: 40
                },
                480: {
                    slidesPerView: 3,
                    spaceBetween: 60
                },
                640: {
                    slidesPerView: 4,
                    spaceBetween: 80
                },
                992: {
                    slidesPerView: 5,
                    spaceBetween: 120
                }
            }
        });

        // About Slider Initialization (RENAMED to avoid conflict)
        var aboutSlider = new Swiper(".aboutSwiper", {
            loop: true,
            autoplay: { delay: 3000, disableOnInteraction: false },
            effect: "fade",
            pagination: { el: ".swiper-pagination", clickable: true },
        });

        // 2. Tab Logic
        function openAboutTab(id) {
            // Hide all contents
            document.querySelectorAll('[id^="tab-content-"]').forEach(el => el.classList.add('hidden'));
            // Reset all buttons
            document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
                btn.classList.remove('bg-[#00609C]', 'text-white', 'shadow-md');
                btn.classList.add('text-[#00609C]', 'bg-white');
            });

            // Show active
            document.getElementById('tab-content-' + id).classList.remove('hidden');
            const activeBtn = document.getElementById('tab-btn-' + id);
            activeBtn.classList.remove('text-[#00609C]', 'bg-white');
            activeBtn.classList.add('bg-[#00609C]', 'text-white', 'shadow-md');
        }

        // --- NEW AUDIO LOGIC ---
        // Handles "Default Audio On" request while respecting browser policies
        document.addEventListener('DOMContentLoaded', () => {
            const video = document.getElementById('heroVideo');
            const audioBtn = document.getElementById('audioToggle');

            if(video && audioBtn) {
                // Define SVG icons as strings to allow easy swapping without external library re-runs
                const iconMuted = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-volume-x"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" x2="17" y1="9" y2="15"/><line x1="17" x2="23" y1="9" y2="15"/></svg>`;
                const iconUnmuted = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-volume-2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>`;

                // Function to update icon state
                const updateIcon = (isMuted) => {
                    audioBtn.innerHTML = isMuted ? iconMuted : iconUnmuted;
                };

                // 1. Try to play unmuted by default
                video.muted = false;
                // Initially assume it worked (unmuted icon)
                updateIcon(false);

                var promise = video.play();

                if (promise !== undefined) {
                    promise.then(_ => {
                        // Autoplay with sound started successfully!
                        console.log("Autoplay with sound successful.");
                    }).catch(error => {
                        // Autoplay was prevented by browser policy
                        // Fallback: Mute and play to ensure video at least starts
                        console.log("Autoplay with sound blocked. Muting to play.");
                        video.muted = true;
                        video.play();
                        // Update icon to reflect the actual muted state
                        updateIcon(true);
                    });
                }

                // 2. Button Click Handler
                audioBtn.addEventListener('click', () => {
                    video.muted = !video.muted;
                    updateIcon(video.muted);
                });
            }
        });
    </script>
</body>
</html>