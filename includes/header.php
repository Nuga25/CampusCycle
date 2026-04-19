<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusCycle 🌱</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        green: {
                            dark: '#1b4332',
                            mid: '#2d6a4f',
                            light: '#d8f3dc',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

<nav class="bg-[#2d6a4f] px-6 py-4 flex justify-between items-center shadow-sm">
    <a href="/CampusCycle/index.php" class="text-white font-semibold text-lg tracking-tight">
        🌱 CampusCycle
    </a>
    <div class="flex items-center gap-4">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/CampusCycle/post-item.php"
               class="text-white text-sm opacity-85 hover:opacity-100 transition">
                + Post Item
            </a>
            <a href="/CampusCycle/dashboard.php"
               class="text-white text-sm opacity-85 hover:opacity-100 transition">
                Dashboard
            </a>
            <a href="/CampusCycle/logout.php"
               class="text-sm bg-white/20 hover:bg-white/30 text-white px-4 py-1.5 rounded-full transition">
                Logout
            </a>
        <?php else: ?>
            <a href="/CampusCycle/login.php"
               class="text-white text-sm opacity-85 hover:opacity-100 transition">
                Login
            </a>
            <a href="/CampusCycle/register.php"
               class="text-sm bg-white/20 hover:bg-white/30 text-white px-4 py-1.5 rounded-full transition">
                Register
            </a>
        <?php endif; ?>
    </div>
</nav>

<main class="flex-1 max-w-5xl mx-auto w-full px-4 py-8">