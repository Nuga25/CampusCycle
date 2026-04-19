<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Fetch all items with their category and poster's name
$sql = "SELECT items.*, categories.name AS category_name, users.name AS poster_name
        FROM items
        JOIN categories ON items.category_id = categories.id
        JOIN users ON items.user_id = users.id
        ORDER BY items.created_at DESC";

$result = $conn->query($sql);
$items  = $result->fetch_all(MYSQLI_ASSOC);
?>

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

<?php if (empty($items)): ?>
    <p class="text-gray-400 text-sm text-center py-16">No items yet — be the first to post something! 🌱</p>

<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($items as $item): ?>
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden flex flex-col">

                <?php if ($item['image']): ?>
                    <img src="/CampusCycle/uploads/<?php echo htmlspecialchars($item['image']); ?>"
                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                         class="w-full h-40 object-cover">
                <?php else: ?>
                    <div class="w-full h-40 bg-[#d8f3dc] flex items-center justify-center text-4xl">
                        🌿
                    </div>
                <?php endif; ?>

                <div class="p-4 flex flex-col flex-1">

                    <div class="flex justify-between items-start mb-1">
                        <h3 class="font-medium text-gray-800 text-sm leading-snug">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        <?php if ($item['status'] === 'available'): ?>
                            <span class="text-xs bg-[#d8f3dc] text-[#1b4332] px-2.5 py-1 rounded-full ml-2 shrink-0">
                                Available
                            </span>
                        <?php else: ?>
                            <span class="text-xs bg-gray-100 text-gray-400 px-2.5 py-1 rounded-full ml-2 shrink-0">
                                Claimed
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-xs text-gray-400 mb-2">
                        <?php echo htmlspecialchars($item['category_name']); ?> · by <?php echo htmlspecialchars($item['poster_name']); ?>
                    </p>

                    <p class="text-sm text-gray-500 line-clamp-2 flex-1">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>

                    <a href="/CampusCycle/item.php?id=<?php echo $item['id']; ?>"
                       class="mt-4 text-center bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-xs font-medium py-2 rounded-full transition">
                        View item
                    </a>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>