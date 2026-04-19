<?php require_once 'includes/header.php'; ?>

<div class="bg-[#1b4332] rounded-2xl px-8 py-6 mb-8 flex justify-between items-center">
    <div>
        <p class="text-green-300 text-sm mb-1">Welcome to</p>
        <h1 class="text-white text-2xl font-semibold">CampusCycle 🌿</h1>
        <p class="text-green-200 text-sm mt-1">Give what you don't need. Take what you do.</p>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/CampusCycle/post-item.php"
           class="bg-[#2d6a4f] hover:bg-[#40916c] text-white text-sm px-5 py-2.5 rounded-full transition">
            + Post an item
        </a>
    <?php else: ?>
        <a href="/CampusCycle/register.php"
           class="bg-[#2d6a4f] hover:bg-[#40916c] text-white text-sm px-5 py-2.5 rounded-full transition">
            Get started
        </a>
    <?php endif; ?>
</div>

<div class="flex gap-2 flex-wrap mb-6">
    <span class="bg-[#2d6a4f] text-white text-xs px-4 py-1.5 rounded-full cursor-pointer">All</span>
    <span class="bg-white border border-gray-200 text-gray-500 text-xs px-4 py-1.5 rounded-full cursor-pointer hover:border-[#2d6a4f] transition">Textbooks</span>
    <span class="bg-white border border-gray-200 text-gray-500 text-xs px-4 py-1.5 rounded-full cursor-pointer hover:border-[#2d6a4f] transition">Kitchen</span>
    <span class="bg-white border border-gray-200 text-gray-500 text-xs px-4 py-1.5 rounded-full cursor-pointer hover:border-[#2d6a4f] transition">Stationery</span>
    <span class="bg-white border border-gray-200 text-gray-500 text-xs px-4 py-1.5 rounded-full cursor-pointer hover:border-[#2d6a4f] transition">Electronics</span>
    <span class="bg-white border border-gray-200 text-gray-500 text-xs px-4 py-1.5 rounded-full cursor-pointer hover:border-[#2d6a4f] transition">Clothing</span>
</div>

<p class="text-gray-400 text-sm text-center py-16">No items yet — be the first to post something! 🌱</p>

<?php require_once 'includes/footer.php'; ?>