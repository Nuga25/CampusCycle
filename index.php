<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Search and filter
$search      = trim($_GET['search'] ?? '');
$category_id = intval($_GET['category'] ?? 0);
$condition   = $_GET['condition'] ?? '';

// Build query dynamically
$where    = ["1=1"];
$params   = [];
$types    = "";

if ($search) {
    $where[]  = "(items.title LIKE ? OR items.description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}

if ($category_id) {
    $where[]  = "items.category_id = ?";
    $params[] = $category_id;
    $types   .= "i";
}

if ($condition) {
    $where[]  = "items.item_condition = ?";
    $params[] = $condition;
    $types   .= "s";
}

$where_sql = implode(" AND ", $where);

$sql = "SELECT items.*, categories.name AS category_name,
               users.name AS poster_name, users.university
        FROM items
        JOIN categories ON items.category_id = categories.id
        JOIN users ON items.user_id = users.id
        WHERE $where_sql
        ORDER BY items.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch images for each item
foreach ($items as &$item) {
    $img_stmt = $conn->prepare(
        "SELECT filename FROM item_images WHERE item_id = ? ORDER BY is_primary DESC"
    );
    $img_stmt->bind_param("i", $item['id']);
    $img_stmt->execute();
    $item['images'] = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $img_stmt->close();
}
unset($item);

// Fetch categories for filter
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
?>

<div class="bg-[#1b4332] rounded-2xl px-8 py-6 mb-6 flex justify-between items-center">
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

<form method="GET" class="mb-5 flex flex-col gap-3">
    <div class="relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
               placeholder="Search items..."
               class="w-full border border-gray-200 rounded-full pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition bg-white">
    </div>

    <div class="flex gap-2 flex-wrap items-center">
        <a href="/CampusCycle/index.php"
           class="text-xs px-4 py-1.5 rounded-full transition <?php echo !$category_id && !$condition ? 'bg-[#2d6a4f] text-white' : 'bg-white border border-gray-200 text-gray-500 hover:border-[#2d6a4f]'; ?>">
            All
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="?<?php echo http_build_query(['category' => $cat['id'], 'search' => $search, 'condition' => $condition]); ?>"
               class="text-xs px-4 py-1.5 rounded-full transition <?php echo $category_id == $cat['id'] ? 'bg-[#2d6a4f] text-white' : 'bg-white border border-gray-200 text-gray-500 hover:border-[#2d6a4f]'; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>

        <div class="ml-auto">
            <select name="condition" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 rounded-full px-3 py-1.5 focus:outline-none focus:border-[#2d6a4f] bg-white text-gray-500">
                <option value="">Any condition</option>
                <?php foreach (['new' => '✨ New', 'good' => '👍 Good', 'fair' => '👌 Fair', 'poor' => '⚠️ Poor'] as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php echo $condition === $val ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if (empty($items)): ?>
    <div class="text-center py-20">
        <p class="text-gray-400 text-sm">No items found.</p>
        <?php if ($search || $category_id || $condition): ?>
            <a href="/CampusCycle/index.php" class="text-xs text-[#2d6a4f] mt-2 inline-block hover:underline">
                Clear filters →
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="max-w-xl mx-auto flex flex-col gap-5">
        <?php foreach ($items as $item): ?>
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">

                <div class="flex items-center gap-3 px-4 pt-4 pb-3">
                    <div class="w-9 h-9 rounded-full bg-[#d8f3dc] flex items-center justify-center text-sm font-semibold text-[#1b4332]">
                        <?php echo strtoupper(substr($item['poster_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($item['poster_name']); ?></p>
                        <p class="text-xs text-gray-400">
                            <?php echo htmlspecialchars($item['university'] ?? 'Campus'); ?> ·
                            <?php
                                $diff = time() - strtotime($item['created_at']);
                                if ($diff < 3600) echo floor($diff/60) . 'm ago';
                                elseif ($diff < 86400) echo floor($diff/3600) . 'h ago';
                                else echo floor($diff/86400) . 'd ago';
                            ?>
                        </p>
                    </div>
                    <div class="ml-auto">
                        <?php if ($item['status'] === 'available'): ?>
                            <span class="text-xs bg-[#d8f3dc] text-[#1b4332] px-2.5 py-1 rounded-full">Available</span>
                        <?php else: ?>
                            <span class="text-xs bg-gray-100 text-gray-400 px-2.5 py-1 rounded-full">Claimed</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($item['images'])): ?>
                    <div class="relative overflow-hidden" style="height: 280px;">
                        <div class="feed-slider flex h-full transition-transform duration-300"
                             id="slider-<?php echo $item['id']; ?>">
                            <?php foreach ($item['images'] as $img): ?>
                                <img src="/CampusCycle/uploads/<?php echo htmlspecialchars($img['filename']); ?>"
                                     class="w-full h-full object-cover shrink-0"
                                     style="min-width: 100%">
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($item['images']) > 1): ?>
                            <button onclick="slideImg('<?php echo $item['id']; ?>', -1)"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/40 text-white w-7 h-7 rounded-full text-xs hover:bg-black/60 transition">
                                ‹
                            </button>
                            <button onclick="slideImg('<?php echo $item['id']; ?>', 1)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/40 text-white w-7 h-7 rounded-full text-xs hover:bg-black/60 transition">
                                ›
                            </button>
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                                <?php for ($i = 0; $i < count($item['images']); $i++): ?>
                                    <div class="w-1.5 h-1.5 rounded-full bg-white/60 dot-<?php echo $item['id']; ?>"
                                         id="dot-<?php echo $item['id'] . '-' . $i; ?>"></div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-[#d8f3dc] flex items-center justify-center text-5xl" style="height: 200px;">
                        🌿
                    </div>
                <?php endif; ?>

                <div class="px-4 py-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">
                            <?php echo htmlspecialchars($item['category_name']); ?>
                        </span>
                        <?php
                            $condition_map = [
                                'new'  => ['✨ New',    'bg-blue-50 text-blue-600'],
                                'good' => ['👍 Good',   'bg-green-50 text-green-600'],
                                'fair' => ['👌 Fair',   'bg-yellow-50 text-yellow-600'],
                                'poor' => ['⚠️ Poor',  'bg-red-50 text-red-500'],
                            ];
                            $cond = $condition_map[$item['item_condition']] ?? null;
                            if ($cond):
                        ?>
                            <span class="text-xs px-2.5 py-1 rounded-full <?php echo $cond[1]; ?>">
                                <?php echo $cond[0]; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="font-semibold text-gray-800 text-sm mt-1">
                        <?php echo htmlspecialchars($item['title']); ?>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>

                    <a href="/CampusCycle/item.php?id=<?php echo $item['id']; ?>"
                       class="mt-3 block w-full text-center
                              <?php echo $item['status'] === 'available' ? 'bg-[#2d6a4f] hover:bg-[#1b4332] text-white' : 'bg-gray-100 text-gray-400 cursor-default'; ?>
                              text-xs font-medium py-2.5 rounded-full transition">
                        <?php echo $item['status'] === 'available' ? 'View & Claim' : 'Already Claimed'; ?>
                    </a>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
const sliderState = {};

function slideImg(id, dir) {
    const slider = document.getElementById('slider-' + id);
    const total  = slider.children.length;
    if (!sliderState[id]) sliderState[id] = 0;
    sliderState[id] = (sliderState[id] + dir + total) % total;
    slider.style.transform = `translateX(-${sliderState[id] * 100}%)`;
    document.querySelectorAll('.dot-' + id).forEach((dot, i) => {
        dot.style.background = i === sliderState[id] ? 'white' : 'rgba(255,255,255,0.5)';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>