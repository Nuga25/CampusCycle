<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /CampusCycle/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's posted items
$stmt = $conn->prepare(
    "SELECT items.*, categories.name AS category_name
     FROM items
     JOIN categories ON items.category_id = categories.id
     WHERE items.user_id = ?
     ORDER BY items.created_at DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch items the user has claimed
$stmt = $conn->prepare(
    "SELECT items.*, categories.name AS category_name,
            users.name AS poster_name, users.email AS poster_email
     FROM claims
     JOIN items ON claims.item_id = items.id
     JOIN categories ON items.category_id = categories.id
     JOIN users ON items.user_id = users.id
     WHERE claims.claimant_id = ?
     ORDER BY claims.claimed_at DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_given     = count($my_items);
$total_claimed   = count($my_claims);
$still_available = count(array_filter($my_items, fn($i) => $i['status'] === 'available'));
?>

<div class="max-w-3xl mx-auto">

    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-gray-800">
            Hey, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋
        </h2>
        <p class="text-gray-400 text-sm mt-1">Here's everything you've given and claimed.</p>
    </div>

    <!-- STATS_SECTION -->
    <div class="grid grid-cols-3 gap-4 mb-10">
        <div class="bg-white border border-gray-200 rounded-2xl p-5 text-center">
            <p class="text-3xl font-semibold text-[#2d6a4f]"><?php echo $total_given; ?></p>
            <p class="text-xs text-gray-400 mt-1">Items posted</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 text-center">
            <p class="text-3xl font-semibold text-[#2d6a4f]"><?php echo $still_available; ?></p>
            <p class="text-xs text-gray-400 mt-1">Still available</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 text-center">
            <p class="text-3xl font-semibold text-[#2d6a4f]"><?php echo $total_claimed; ?></p>
            <p class="text-xs text-gray-400 mt-1">Items claimed</p>
        </div>
    </div>

    <!-- MY_ITEMS_SECTION -->
    <div class="mb-10">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-semibold text-gray-700">My listings</h3>
            <a href="/CampusCycle/post-item.php"
               class="text-xs bg-[#2d6a4f] hover:bg-[#1b4332] text-white px-4 py-1.5 rounded-full transition">
                + Post new
            </a>
        </div>

        <?php if (empty($my_items)): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center">
                <p class="text-gray-400 text-sm">You haven't posted anything yet.</p>
                <a href="/CampusCycle/post-item.php"
                   class="inline-block mt-3 text-xs text-[#2d6a4f] font-medium hover:underline">
                    Post your first item →
                </a>
            </div>
        <?php else: ?>
            <div class="flex flex-col gap-3">
                <?php foreach ($my_items as $item): ?>
                    <div class="bg-white border border-gray-200 rounded-2xl p-4 flex gap-4 items-center">

                        <?php if ($item['image']): ?>
                            <img src="/CampusCycle/uploads/<?php echo htmlspecialchars($item['image']); ?>"
                                 class="w-14 h-14 rounded-xl object-cover shrink-0">
                        <?php else: ?>
                            <div class="w-14 h-14 rounded-xl bg-[#d8f3dc] flex items-center justify-center text-2xl shrink-0">
                                🌿
                            </div>
                        <?php endif; ?>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?php echo htmlspecialchars($item['category_name']); ?> ·
                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                            </p>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <?php if ($item['status'] === 'available'): ?>
                                <span class="text-xs bg-[#d8f3dc] text-[#1b4332] px-3 py-1 rounded-full">
                                    Available
                                </span>
                            <?php else: ?>
                                <span class="text-xs bg-gray-100 text-gray-400 px-3 py-1 rounded-full">
                                    Claimed
                                </span>
                            <?php endif; ?>
                            <a href="/CampusCycle/item.php?id=<?php echo $item['id']; ?>"
                               class="text-xs text-[#2d6a4f] font-medium hover:underline">
                                View →
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MY_CLAIMS_SECTION -->
    <div>
        <h3 class="text-base font-semibold text-gray-700 mb-4">Items I've claimed</h3>

        <?php if (empty($my_claims)): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center">
                <p class="text-gray-400 text-sm">You haven't claimed anything yet.</p>
                <a href="/CampusCycle/index.php"
                   class="inline-block mt-3 text-xs text-[#2d6a4f] font-medium hover:underline">
                    Browse available items →
                </a>
            </div>
        <?php else: ?>
            <div class="flex flex-col gap-3">
                <?php foreach ($my_claims as $claim): ?>
                    <div class="bg-white border border-gray-200 rounded-2xl p-4 flex gap-4 items-center">

                        <?php if ($claim['image']): ?>
                            <img src="/CampusCycle/uploads/<?php echo htmlspecialchars($claim['image']); ?>"
                                 class="w-14 h-14 rounded-xl object-cover shrink-0">
                        <?php else: ?>
                            <div class="w-14 h-14 rounded-xl bg-[#d8f3dc] flex items-center justify-center text-2xl shrink-0">
                                🌿
                            </div>
                        <?php endif; ?>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?php echo htmlspecialchars($claim['title']); ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                From <?php echo htmlspecialchars($claim['poster_name']); ?> ·
                                <?php echo htmlspecialchars($claim['category_name']); ?>
                            </p>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <a href="mailto:<?php echo htmlspecialchars($claim['poster_email']); ?>"
                               class="text-xs bg-[#d8f3dc] text-[#1b4332] px-3 py-1.5 rounded-full hover:bg-[#b7e4c7] transition">
                                Contact giver
                            </a>
                            <a href="/CampusCycle/item.php?id=<?php echo $claim['id']; ?>"
                               class="text-xs text-[#2d6a4f] font-medium hover:underline">
                                View →
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>