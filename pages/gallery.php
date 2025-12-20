<?php 


// Include database and functions at the top
require_once '../includes/db.php';


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

if (isset($_POST['send_message'])) {
    // 1. Capture Data
    $company = htmlspecialchars($_POST['company_name']);
    $person  = htmlspecialchars($_POST['contact_person']);
    $email   = htmlspecialchars($_POST['email']);
    $phone   = htmlspecialchars($_POST['phone']); // <--- Added Phone
    $req     = htmlspecialchars($_POST['requirements']);

    // 2. Format Data for Database
    $dbName = !empty($person) ? $person : 'Unknown'; 

    // 3. Insert into Database
    // Make sure your table has 'phone' and 'company' columns
    $stmt = $pdo->prepare("INSERT INTO messages (name, email, phone, company, message) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$dbName, $email, $phone, $company, $req])) {
        echo "<script>alert('Request sent successfully! We will contact you shortly.'); window.location.href = '../index.php';</script>";
    } else {
        echo "<script>alert('Failed to send message. Please try again.');</script>";
    }
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
                            accentHover: '#b49600ff',
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
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-20px)' } },
                        scroll: { '0%': { transform: 'translateX(0)' }, '100%': { transform: 'translateX(-50%)' } },
                        rise: { '0%': { bottom: '-10%', transform: 'translateX(0)', opacity: '0' }, '50%': { opacity: '0.6' }, '100%': { bottom: '110%', transform: 'translateX(-20px)', opacity: '0' } },
                        water: { '0%, 100%': { borderRadius: '60% 40% 30% 70% / 60% 30% 70% 40%' }, '50%': { borderRadius: '30% 60% 70% 40% / 50% 60% 30% 60%' } },
                        fadeIn: { 'from': { opacity: '0', transform: 'translateY(10px)' }, 'to': { opacity: '1', transform: 'translateY(0)' } }
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

        /* Navigation Link Styling */
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        /* Animated underline effect */
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #FFC300; /* brand-accent */
            transition: width 0.3s ease;
        }

        /* Active State - Transparent Nav (Top) */
        .nav-link.active {
            color: #FFC300 !important;
        }
        .nav-link.active::after {
            width: 100%;
        }

        /* Active State - Scrolled Nav (White bg) */
        .scrolled-nav .nav-link.active {
            color: #023E8A !important; /* brand-navy */
        }
        .scrolled-nav .nav-link.active::after {
            background-color: #023E8A;
        }

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
    </style>
</head>

<body>

    <div id="bubble-container" class="fixed inset-0 pointer-events-none z-0 overflow-hidden h-full w-full"></div>

    <nav class="fixed w-full z-50 transition-all duration-300 border-b border-white/0" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-2">
                    <img id="nav-logo" src="view_image.php?type=logo" alt="CExport Logo"
                        class="h-12 w-auto object-contain drop-shadow-md transition-all duration-300 brightness-0 ">
                </div>

                <div class="hidden md:block">
                    <div class="ml-10 flex items-center space-x-8">
                        <a href="../index.php" class="nav-link text-brand-navy hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors">Home</a>
                        <a href="about.php" class="nav-link text-brand-navy hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors ">About</a>
                        <a href="#personal-journey" class="nav-link text-brand-navy hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors active">Gallery</a>
                        <a href="product.php" class="nav-link text-brand-navy hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors">Product</a>
                        <a href="service.php" class="nav-link text-brand-navy hover:text-brand-accent font-bold text-sm uppercase tracking-wide transition-colors">Services</a>
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
                <a href="#home" class="block px-3 py-3 rounded-md text-brand-navy font-bold hover:bg-white/10 transition-colors">Home</a>
                <a href="#about" class="block px-3 py-3 rounded-md text-brand-navy font-bold hover:bg-white/10 transition-colors">About</a>
                <a href="#personal-journey" class="block px-3 py-3 rounded-md text-brand-navy font-bold hover:bg-white/10 transition-colors active">Gallery</a>
                <a href="#gallery" class="block px-3 py-3 rounded-md text-brand-navy font-bold hover:bg-white/10 transition-colors">Product</a>
                <a href="#services" class="block px-3 py-3 rounded-md text-brand-navy font-bold hover:bg-white/10 transition-colors">Services</a>
                <a href="#contact" class="block text-center mt-4 px-3 py-3 rounded-md bg-brand-accent text-brand-navy font-bold hover:bg-brand-accentHover transition-colors">Contact Us</a>
            </div>
        </div>
    </nav>

    

       <?php
    // 1. Fetch Text Content
    $jTitle = getContent('journey_title'); 
    $jSub   = getContent('journey_subtitle');

    // 2. Fetch Media Items from Database (UPDATED SORTING)
    global $pdo; 
    $stmt = $pdo->query("SELECT * FROM personal_journey ORDER BY priority ASC, id DESC");
    $journey_media = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Prepare Data for Alpine.js (JSON)
    $js_media = [];
    foreach($journey_media as $m) {
        if($m['media_type'] == 'image') {
            $src = "view_image.php?id={$m['id']}&type=journey";
            $thumb = $src;
        } elseif($m['media_type'] == 'video') {
            $src = "../assets/videos/{$m['video_path']}";
            $thumb = null; 
        } else {
            $src = "https://www.youtube.com/embed/{$m['youtube_id']}?rel=0&enablejsapi=1";
            $thumb = "https://img.youtube.com/vi/{$m['youtube_id']}/hqdefault.jpg";
        }
        $js_media[] = [
            'id' => $m['id'], 
            'type' => $m['media_type'], 
            'src' => $src, 
            'thumb' => $thumb
        ];
    }
    $jsonOutput = htmlspecialchars(json_encode($js_media), ENT_QUOTES, 'UTF-8');
    ?>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js" defer></script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 10px;
        }

        .progress-bar {
            transition: width 0.1s linear;
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>

    <section class="py-24 bg-[#F8FAFC]" id="personal-journey">
        <div class="max-w-7xl mx-auto px-6" x-data="{ 
            mediaItems: <?= $jsonOutput ?>,
            activeItem: null,
            activeIndex: 0,
            isPlaying: true,
            timer: null,
            progress: 0,
            interval: 5000,

            init() {
                // REMOVED RANDOM SORT to respect Admin Priority
                if(this.mediaItems.length > 0) {
                    this.setActive(0);
                    this.startAutoplay();
                }
            },

           setActive(index) {
            this.activeIndex = index;
            this.activeItem = this.mediaItems[index];
            this.progress = 0;
            
            this.$nextTick(() => {
                const container = this.$refs.thumbContainer;
                const activeThumb = container.children[index];
                
                if (container && activeThumb) {
                    // Manual calculation of scroll position inside the container
                    // This prevents the global window from moving
                    const scrollLeft = activeThumb.offsetLeft - (container.offsetWidth / 2) + (activeThumb.offsetWidth / 2);
                    
                    container.scrollTo({
                        left: scrollLeft,
                        behavior: 'smooth'
                    });
                }
                
                if(window.lucide) lucide.createIcons();
            });
        },

            next() {
                this.activeIndex = (this.activeIndex + 1) % this.mediaItems.length;
                this.setActive(this.activeIndex);
            },

            startAutoplay() {
                this.isPlaying = true;
                if(this.timer) clearInterval(this.timer);
                this.timer = setInterval(() => {
                    if (this.progress >= 100) {
                        this.next();
                    } else {
                        this.progress += (100 / (this.interval / 100));
                    }
                }, 100);
            },

            stopAutoplay() {
                this.isPlaying = false;
                if(this.timer) clearInterval(this.timer);
                this.progress = 0;
            },

            togglePlay() {
                this.isPlaying ? this.stopAutoplay() : this.startAutoplay();
            }
         }">

            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                <div class="max-w-2xl">
                    <h2
                        class="font-heading text-5xl md:text-6xl font-bold text-brand-navy mb-4 [&>p]:m-0 [&>h1]:m-0 [&>h2]:m-0 leading-tight">
                        <?= htmlspecialchars($jTitle ?: 'Our Journey') ?>
                    </h2>
                    <p class="text-gray-600 max-w-lg text-lg [&>p]:mb-2">
                        <?= nl2br(htmlspecialchars($jSub ?: 'Experience the shuffle of our favorite moments.')) ?>
                    </p>
                </div>

                <div class="hidden md:flex items-center gap-2 text-slate-400">
                    <span class="w-2 h-2 rounded-full"
                        :class="isPlaying ? 'bg-green-500 animate-pulse' : 'bg-slate-300'"></span>
                    <span class="text-xs font-bold uppercase tracking-widest"
                        x-text="isPlaying ? 'Autoplay Active' : 'Autoplay Paused'"></span>
                </div>
            </div>

            <div
                class="relative bg-black rounded-[2.5rem] overflow-hidden shadow-2xl aspect-video border-[10px] border-white z-10">

                <div class="absolute top-6 left-6 z-40">
                    <button @click="togglePlay()"
                        class="flex items-center justify-center transition-all duration-300 active:scale-90 shadow-2xl rounded-full group"
                        :class="isPlaying ? 'w-12 h-12 bg-black/70 border border-white/40 backdrop-blur-md' : 'w-12 h-12 bg-white/95 border border-slate-200 shadow-xl'">

                        <template x-if="isPlaying">
                            <div class="flex gap-1">
                                <div class="w-1.5 h-5 bg-white rounded-full"></div>
                                <div class="w-1.5 h-5 bg-white rounded-full"></div>
                            </div>
                        </template>

                        <template x-if="!isPlaying">
                            <div
                                class="ml-1 w-0 h-0 border-t-[10px] border-t-transparent border-l-[16px] border-l-slate-800 border-b-[10px] border-b-transparent">
                            </div>
                        </template>
                    </button>
                </div>

                <template x-if="activeItem">
                    <div class="w-full h-full">
                        <template x-if="activeItem.type === 'image'">
                            <img :src="activeItem.src" class="w-full h-full object-cover animate-fadeIn"
                                :key="activeItem.id">
                        </template>

                        <template x-if="activeItem.type === 'video'">
                            <video :src="activeItem.src" controls autoplay muted class="w-full h-full object-contain"
                                :key="activeItem.id"></video>
                        </template>

                        <template x-if="activeItem.type === 'youtube'">
                            <iframe :src="activeItem.src + (isPlaying ? '&autoplay=1&mute=1' : '')"
                                class="w-full h-full" frameborder="0" allow="autoplay; encrypted-media"
                                :key="activeItem.id"></iframe>
                        </template>
                    </div>
                </template>

                <div class="absolute bottom-0 left-0 h-1.5 bg-white/10 w-full z-30">
                    <div class="h-full bg-blue-500 progress-bar" :style="`width: ${progress}%`" x-show="isPlaying">
                    </div>
                </div>
            </div>

            <div class="mt-8 relative">
                <div x-ref="thumbContainer"
                    class="flex justify-center gap-4 overflow-x-auto pb-4 custom-scrollbar snap-x">
                    <template x-for="(item, index) in mediaItems" :key="item.id">
                        <button @click="stopAutoplay(); setActive(index)"
                            class="relative flex-none w-44 aspect-video rounded-2xl overflow-hidden border-4 transition-all snap-center"
                            :class="activeIndex === index ? 'border-blue-500 scale-105 shadow-xl z-10' : 'border-transparent opacity-60 hover:opacity-100'">
                            <template x-if="item.type === 'image' || item.type === 'youtube'">
                                <img :src="item.thumb" class="w-full h-full object-cover">
                            </template>
                            <template x-if="item.type === 'video'">
                                <video :src="item.src" class="w-full h-full object-cover" preload="metadata"
                                    muted></video>
                            </template>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                                <div x-show="item.type !== 'image'"
                                    class="p-2 bg-white/20 backdrop-blur-md rounded-full border border-white/30">
                                    <i :data-lucide="item.type === 'video' ? 'play' : 'youtube'"
                                        class="w-5 h-5 text-white"></i>
                                </div>
                            </div>
                        </button>
                    </template>
                    
                </div>
            </div>
        </div>
       
    </section>

    <script>
        document.addEventListener('alpine:initialized', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>

     <section id="contact" class="py-24 bg-brand-light relative">
        <div class="max-w-5xl mx-auto px-6 relative z-10">
            <div class="glass-water rounded-[3rem] shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

                <div class="bg-brand-blue p-12 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-brand-blue to-brand-deep"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 bg-brand-accent/20 rounded-full blur-3xl"></div>

                    <div class="relative z-10 h-full flex flex-col justify-between">
                        <div>
                            <h2 class="font-heading text-4xl font-bold mb-6">
                                <?= getContent('contact_title') ?>
                            </h2>
                            <p class="text-brand-light mb-8">
                                <?= getContent('contact_Desc') ?>
                            </p>

                            <div class="space-y-4 mt-8">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-brand-accent backdrop-blur-sm shadow-inner">
                                        <i data-lucide="phone" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-bold tracking-widest">
                                        <?= getContent('contact_phone') ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-brand-accent backdrop-blur-sm shadow-inner">
                                        <i data-lucide="mail" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-bold tracking-widest">
                                        <?= getContent('contact_email') ?>
                                    </span>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-brand-accent shrink-0 backdrop-blur-sm shadow-inner">
                                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                                    </div>
                                    <span class="font-bold tracking-widest text-sm leading-relaxed">
                                        <?= getContent('contact_address') ?>
                                    </span>
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
                                <a href="<?= htmlspecialchars($fb) ?>" target="_blank"
                                    class="hover:text-brand-accent transition-colors">
                                    <img width="32" height="32"
                                        src="https://img.icons8.com/windows/32/FFFFFF/facebook-new.png"
                                        alt="facebook-new" />
                                </a>
                                <?php endif; ?>

                                <?php if(!empty($insta) && $insta != '#'): ?>
                                <a href="<?= htmlspecialchars($insta) ?>" target="_blank"
                                    class="hover:text-brand-accent transition-colors">
                                    <img width="32" height="32"
                                        src="https://img.icons8.com/windows/32/FFFFFF/instagram-new.png"
                                        alt="instagram-new" />
                                </a>
                                <?php endif; ?>

                                <?php if(!empty($wa) && $wa != '#'): ?>
                                <a href="<?= htmlspecialchars($wa) ?>" target="_blank"
                                    class="hover:text-brand-accent transition-colors">
                                    <img width="32" height="32"
                                        src="https://img.icons8.com/windows/32/FFFFFF/whatsapp--v1.png"
                                        alt="whatsapp--v1" />
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-12 bg-white/80">
                    <form class="space-y-6" method="post">

                        <div>
                            <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Company Name</label>
                            <input type="text" name="company_name" required
                                class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm"
                                placeholder="e.g. Ocean Blue Ltd">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Contact
                                    Person</label>
                                <input type="text" name="contact_person" required
                                    class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm"
                                    placeholder="John Doe">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Phone Number <span
                                        class="text-red-500">*</span></label>
                                <input type="tel" name="phone" required
                                    class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm  invalid:text-red-600"
                                    placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="md:col-span-2"> <label
                                    class="block text-xs font-bold uppercase text-brand-navy mb-2">Email</label>
                                <input type="email" name="email" required
                                    class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm"
                                    placeholder="john@company.com">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-brand-navy mb-2">Requirements</label>
                            <textarea name="requirements" rows="3" required
                                class="w-full bg-white border border-brand-light rounded-xl p-4 focus:outline-none focus:border-brand-blue focus:ring-4 focus:ring-brand-light transition-all shadow-sm"
                                placeholder="Tell us about your order needs..."></textarea>
                        </div>

                        <button type="submit" name="send_message"
                            class="w-full py-4 bg-brand-accent hover:bg-brand-accentHover text-white font-bold uppercase tracking-widest shadow-lg transform hover:-translate-y-1 transition-all flex justify-center gap-2 rounded-xl">
                            Request Price List <i data-lucide="send" class="w-4 h-4"></i>
                        </button>

                    </form>
                </div>

            </div>
        </div>
    </section>

    <footer class="relative bg-gradient-to-b from-brand-white to-gray-50 border-t border-gray-200/70">
        <!-- soft glow -->
        <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.06),transparent_60%)]"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-14">
            <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-10">

                <!-- Logo, Brand & Social -->
                <div class="flex flex-col items-center md:items-start gap-5 max-w-sm">
                    <img 
                        id="nav-logo"
                        src="view_image.php?type=logo"
                        alt="CExport Logo"
                        class="h-12 w-auto object-contain drop-shadow-lg transition-transform duration-300 hover:scale-105"
                    >

                    <p class="text-sm text-gray-500 text-center md:text-left">
                        Powering global exports with precision, trust, and modern technology.
                    </p>

                    <!-- Social Connect -->
                    <div class="mt-4">
                        <p class="text-xs uppercase tracking-widest text-gray-400 mb-3 text-center md:text-left">
                            Social Connect
                        </p>

                        <div class="flex gap-4 justify-center md:justify-start">
                            <?php
                            $fb    = getContent('social_facebook');
                            $insta = getContent('social_instagram');
                            $wa    = getContent('social_whatsapp');
                            ?>

                            <?php if(!empty($fb) && $fb != '#'): ?>
                            <a href="<?= htmlspecialchars($fb) ?>" target="_blank"
                            class="group">
                                <img width="28" height="28"
                                    class="opacity-60 group-hover:opacity-100 transition"
                                    src="https://img.icons8.com/windows/32/000000/facebook-new.png"
                                    alt="facebook" />
                            </a>
                            <?php endif; ?>

                            <?php if(!empty($insta) && $insta != '#'): ?>
                            <a href="<?= htmlspecialchars($insta) ?>" target="_blank"
                            class="group">
                                <img width="28" height="28"
                                    class="opacity-60 group-hover:opacity-100 transition"
                                    src="https://img.icons8.com/windows/32/000000/instagram-new.png"
                                    alt="instagram" />
                            </a>
                            <?php endif; ?>

                            <?php if(!empty($wa) && $wa != '#'): ?>
                            <a href="<?= htmlspecialchars($wa) ?>" target="_blank"
                            class="group">
                                <img width="28" height="28"
                                    class="opacity-60 group-hover:opacity-100 transition"
                                    src="https://img.icons8.com/windows/32/000000/whatsapp--v1.png"
                                    alt="whatsapp" />
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Links -->
                <div class="flex flex-col sm:flex-row gap-10 text-center sm:text-left">
                    <div>
                        <h4 class="text-sm font-semibold text-brand-navy mb-3 tracking-wide uppercase">
                            Company
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-500">
                            <li><a href="about.php" class="hover:text-brand-blue transition">About</a></li>
                            <li><a href="#" class="hover:text-brand-blue transition">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-brand-blue transition">Terms & Conditions</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-brand-navy mb-3 tracking-wide uppercase">
                            Support
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-500">
                            <li><a href="#contact" class="hover:text-brand-blue transition">Contact</a></li>
                            <li><a href="#" class="hover:text-brand-blue transition">Documentation</a></li>
                            <li><a href="#" class="hover:text-brand-blue transition">Help Center</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-10 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>

            <!-- Bottom Row -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-400">
                <p>
                    © <?= date('Y') ?> <a href="#" rel="noopener noreferrer"
                                        class="text-gray-600 font-medium hover:text-brand-blue transition">
                                            CExport
                                    </a>. All rights reserved.
                </p>
                <p>
                    Crafted with care by 
                    <a href="https://quesystems.in/" target="_blank" rel="noopener noreferrer"
                        class="text-gray-600 font-semibold hover:text-brand-blue transition">
                            Que Systems
                    </a>
                </p>
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
                if (b.textContent.toLowerCase() === type || (type === 'all' && b.textContent === 'All')) {
                    b.classList.add('bg-brand-blue', 'text-white', 'shadow-lg');
                    b.classList.remove('text-gray-500');
                }
            });

            // Filter
            items.forEach(item => {
                if (type === 'all' || item.classList.contains(type)) {
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
                if (navLogo) {
                    navLogo.classList.remove('brightness-0', 'invert');
                }

                // 2. Change Menu Links to Dark Blue
                const links = navbar.querySelectorAll('a, button, span.font-logo');
                links.forEach(link => {
                    if (link.id !== 'mobile-menu-btn' && !link.classList.contains('crimson-btn')) {
                        link.classList.remove('text-white');
                        link.classList.add('text-brand-navy');
                    }
                    else if (link.id === 'mobile-menu-btn') {
                        link.classList.remove('text-white');
                        link.classList.add('text-brand-navy');
                    }
                });

            } else {
                // Top State (Transparent Background)
                navbar.classList.remove('shadow-md', 'bg-white/95', 'backdrop-blur-sm');
                navbar.classList.add('border-b', 'border-white/0');

                // 1. Make Logo White again using CSS Filter
                // if (navLogo) {
                //     navLogo.classList.add('brightness-0', 'invert');
                // }

                // 2. Revert Menu Links to White
                // const links = navbar.querySelectorAll('a, button, span.font-logo');
                // links.forEach(link => {
                //     if (link.id !== 'mobile-menu-btn' && !link.classList.contains('crimson-btn')) {
                //         link.classList.add('text-white');
                //         link.classList.remove('text-brand-navy');
                //     }
                //     else if (link.id === 'mobile-menu-btn') {
                //         link.classList.add('text-white');
                //         link.classList.remove('text-brand-navy');
                //     }
                // });
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

            if (video && audioBtn) {
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