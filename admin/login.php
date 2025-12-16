<?php
session_start();
require '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CExport Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#00C2CB',
                            dark: '#009fa6',
                            light: '#E0F9FA'
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Modern Grid Background */
        .bg-grid {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0.04) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
            mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center relative overflow-hidden text-slate-800">

    <div class="absolute inset-0 bg-grid z-0"></div>
    
    <div class="absolute top-[-10%] right-[-5%] w-[500px] h-[500px] bg-brand/10 rounded-full blur-[100px] animate-float"></div>
    <div class="absolute bottom-[-10%] left-[-5%] w-[500px] h-[500px] bg-purple-100 rounded-full blur-[100px] animate-float" style="animation-delay: 2s"></div>

    <div class="relative z-10 w-full max-w-[400px] px-4">
        
        <div class="text-center mb-8 animate-float" style="animation-duration: 4s">
             <div class="inline-flex items-center justify-center h-16 w-16 bg-white rounded-2xl shadow-lg shadow-brand/10 mb-4 border border-brand/10 p-2">
                 <img src="../view_image.php?type=logo" alt="Logo" class="h-full w-full object-contain">
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Welcome Back</h1>
            <p class="text-slate-500 text-sm mt-1">Sign in to manage your dashboard</p>
        </div>

        <div class="bg-white/80 backdrop-blur-xl border border-white/50 rounded-3xl shadow-[0_20px_40px_-15px_rgba(0,0,0,0.05)] p-8 ring-1 ring-gray-100">
            
            <?php if(isset($error)): ?>
                <div class="flex items-center gap-3 bg-red-50 border border-red-100 text-red-600 text-sm p-3 rounded-xl mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-medium"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 ml-1">User ID</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-focus-within:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <input type="text" name="username" required 
                            class="block w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-slate-800 text-sm placeholder-gray-400 focus:outline-none focus:bg-white focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all duration-200 font-medium"
                            placeholder="Enter ID">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5 ml-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Password</label>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-focus-within:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <input type="password" name="password" required 
                            class="block w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-slate-800 text-sm placeholder-gray-400 focus:outline-none focus:bg-white focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all duration-200 font-medium"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="w-full relative group overflow-hidden bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-xl shadow-[0_10px_20px_-10px_rgba(0,194,203,0.5)] transition-all duration-300 transform hover:-translate-y-1">
                    <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_1s_infinite]"></div>
                    <span class="relative flex items-center justify-center gap-2 text-sm tracking-wide">
                        SIGN IN
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </span>
                </button>

            </form>
        </div>
        
        <p class="text-center text-xs text-slate-400 mt-8 font-medium">
            &copy; <?= date('Y') ?> CExport CMS. Secured System.
        </p>

    </div>

</body>
</html>