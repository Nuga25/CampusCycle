<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Get item ID from URL
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: /CampusCycle/index.php");
    exit();
}

// Fetch item with category and poster info
$stmt = $conn->prepare(
    "SELECT items.*, categories.name AS category_name,
            users.name AS poster_name, users.email AS poster_email
     FROM items
     JOIN categories ON items.category_id = categories.id
     JOIN users ON items.user_id = users.id
     WHERE items.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch images for this item
$img_stmt = $conn->prepare(
    "SELECT filename FROM item_images WHERE item_id = ? ORDER BY is_primary DESC"
);
$img_stmt->bind_param("i", $id);
$img_stmt->execute();
$item['images'] = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$img_stmt->close();

if (!$item) {
    header("Location: /CampusCycle/index.php");
    exit();
}

// Check if current user already claimed this item
$already_claimed = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id FROM claims WHERE item_id = ? AND claimant_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    $already_claimed = $stmt->num_rows > 0;
    $stmt->close();
}

// Fetch claimer email if item is claimed (only show to poster)
$claimer = null;
if ($item['status'] === 'claimed' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']) {
    $stmt = $conn->prepare(
        "SELECT users.name, users.email FROM claims
         JOIN users ON claims.claimant_id = users.id
         WHERE claims.item_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $claimer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$error   = '';
$success = '';

// Handle claim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /CampusCycle/login.php");
        exit();
    }

    if ($_SESSION['user_id'] == $item['user_id']) {
        $error = "You can't claim your own item.";

    } elseif ($item['status'] === 'claimed') {
        $error = 'This item has already been claimed.';

    } else {
        $claimant_id = $_SESSION['user_id'];

        // Insert claim
        $stmt = $conn->prepare("INSERT INTO claims (item_id, claimant_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $id, $claimant_id);
        $stmt->execute();
        $stmt->close();

        // Update item status
        $stmt = $conn->prepare("UPDATE items SET status = 'claimed' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $item['status'] = 'claimed';
        $already_claimed = true;
        $success = 'Item claimed! Contact the giver to arrange pickup.';
    }
}
?>

<div class="max-w-2xl mx-auto">

    <a href="/CampusCycle/index.php"
       class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition mb-6">
        ← Back to listings
    </a>

    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">

        <?php if (!empty($item['images'])): ?>
    <div class="relative overflow-hidden" style="height: 480px;">
        <div class="flex h-full transition-transform duration-300"
             id="slider-main">
            <?php foreach ($item['images'] as $img): ?>
                <img src="/CampusCycle/uploads/<?php echo htmlspecialchars($img['filename']); ?>"
                     class="w-full h-full object-cover shrink-0"
                     style="min-width: 100%">
            <?php endforeach; ?>
        </div>
        <?php if (count($item['images']) > 1): ?>
            <button onclick="slide(-1)"
                    class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white w-8 h-8 rounded-full text-sm transition">
                ‹
            </button>
            <button onclick="slide(1)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white w-8 h-8 rounded-full text-sm transition">
                ›
            </button>
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                <?php for ($i = 0; $i < count($item['images']); $i++): ?>
                    <div class="w-1.5 h-1.5 rounded-full bg-white/50" id="dot-<?php echo $i; ?>"></div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="w-full bg-[#d8f3dc] flex items-center justify-center text-6xl" style="height: 240px;">
        🌿
    </div>
<?php endif; ?>

        <div class="p-6">

            <div class="flex justify-between items-start mb-3">
                <h1 class="text-xl font-semibold text-gray-800">
                    <?php echo htmlspecialchars($item['title']); ?>
                </h1>
                <?php if ($item['status'] === 'available'): ?>
                    <span class="text-xs bg-[#d8f3dc] text-[#1b4332] px-3 py-1.5 rounded-full shrink-0 ml-3">
                        Available
                    </span>
                <?php else: ?>
                    <span class="text-xs bg-gray-100 text-gray-400 px-3 py-1.5 rounded-full shrink-0 ml-3">
                        Claimed
                    </span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3 mb-5">
                <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full">
                    <?php echo htmlspecialchars($item['category_name']); ?>
                </span>
                <span class="text-xs text-gray-400">
                    Posted by <?php echo htmlspecialchars($item['poster_name']); ?>
                </span>
                <span class="text-xs text-gray-300">·</span>
                <span class="text-xs text-gray-400">
                    <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                </span>
            </div>

            <p class="text-sm text-gray-600 leading-relaxed mb-6">
                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
            </p>

            <div class="border-t border-gray-100 pt-5">

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-4 border border-red-200">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-700 text-sm px-4 py-3 rounded-xl mb-4 border border-green-200">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($item['status'] === 'available' && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $item['user_id'] && !$already_claimed): ?>
                    <form method="POST">
                        <button type="submit" name="claim"
                                class="w-full bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-3 rounded-full transition">
                            Claim this item
                        </button>
                    </form>

                <?php elseif ($already_claimed && $item['status'] === 'claimed'): ?>
                    <div class="bg-[#d8f3dc] rounded-2xl p-5">
                        <p class="text-[#1b4332] font-medium text-sm mb-1">🎉 You claimed this item!</p>
                        <p class="text-[#2d6a4f] text-sm mb-3">Contact the giver to arrange pickup:</p>
                        <div class="bg-white rounded-xl px-4 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Giver</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($item['poster_name']); ?></p>
                            </div>
                            <a href="mailto:<?php echo htmlspecialchars($item['poster_email']); ?>"
                               class="text-sm bg-[#2d6a4f] text-white px-4 py-2 rounded-full hover:bg-[#1b4332] transition">
                                <?php echo htmlspecialchars($item['poster_email']); ?>
                            </a>
                        </div>
                    </div>

                <?php elseif ($item['status'] === 'claimed' && $_SESSION['user_id'] == $item['user_id'] && $claimer): ?>
                    <div class="bg-[#d8f3dc] rounded-2xl p-5">
                        <p class="text-[#1b4332] font-medium text-sm mb-1">✅ Your item has been claimed!</p>
                        <p class="text-[#2d6a4f] text-sm mb-3">Here's who claimed it — reach out to arrange pickup:</p>
                        <div class="bg-white rounded-xl px-4 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Claimer</p>
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($claimer['name']); ?></p>
                            </div>
                            <a href="mailto:<?php echo htmlspecialchars($claimer['email']); ?>"
                               class="text-sm bg-[#2d6a4f] text-white px-4 py-2 rounded-full hover:bg-[#1b4332] transition">
                                <?php echo htmlspecialchars($claimer['email']); ?>
                            </a>
                        </div>
                    </div>

                <?php elseif ($item['status'] === 'claimed'): ?>
                    <div class="bg-gray-50 rounded-2xl p-5 text-center">
                        <p class="text-gray-400 text-sm">This item has already been claimed.</p>
                    </div>

                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="/CampusCycle/login.php"
                       class="block w-full text-center bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-3 rounded-full transition">
                        Log in to claim this item
                    </a>

                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']): ?>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <p class="text-gray-500 text-sm font-medium mb-3">This is your listing</p>
                        <div class="flex gap-2">
                            <a href="/CampusCycle/edit-item.php?id=<?php echo $item['id']; ?>"
                            class="flex-1 text-center text-sm border border-[#2d6a4f] text-[#2d6a4f] hover:bg-[#d8f3dc] py-2.5 rounded-full transition">
                                Edit listing
                            </a>
                            <form method="POST" action="/CampusCycle/delete-item.php"
                                onsubmit="return confirm('Are you sure you want to delete this listing?')">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit"
                                        class="text-sm border border-red-200 text-red-400 hover:bg-red-50 px-5 py-2.5 rounded-full transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
let current = 0;
const slider = document.getElementById('slider-main');
const dots   = document.querySelectorAll('[id^="dot-"]');

function slide(dir) {
    if (!slider) return;
    const total = slider.children.length;
    current = (current + dir + total) % total;
    slider.style.transform = `translateX(-${current * 100}%)`;
    dots.forEach((d, i) => {
        d.style.background = i === current ? 'white' : 'rgba(255,255,255,0.5)';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>