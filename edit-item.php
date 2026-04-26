<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /CampusCycle/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id      = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: /CampusCycle/dashboard.php");
    exit();
}

// Fetch item — make sure it belongs to this user
$stmt = $conn->prepare(
    "SELECT items.*, categories.name AS category_name
     FROM items
     JOIN categories ON items.category_id = categories.id
     WHERE items.id = ? AND items.user_id = ?"
);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: /CampusCycle/dashboard.php");
    exit();
}

// Fetch existing images
$img_stmt = $conn->prepare("SELECT * FROM item_images WHERE item_id = ? ORDER BY is_primary DESC");
$img_stmt->bind_param("i", $id);
$img_stmt->execute();
$existing_images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$img_stmt->close();

// Fetch categories
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $condition   = $_POST['condition'] ?? 'good';

    if (empty($title) || empty($description) || empty($category_id)) {
        $error = 'Please fill in all fields.';
    } else {

        // Update item
        $stmt = $conn->prepare(
            "UPDATE items SET title = ?, description = ?, category_id = ?, item_condition = ?
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ssisii", $title, $description, $category_id, $condition, $id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Handle deleted images
        if (!empty($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $img_id) {
                $img_id = intval($img_id);
                $stmt = $conn->prepare(
                    "SELECT filename FROM item_images WHERE id = ? AND item_id = ?"
                );
                $stmt->bind_param("ii", $img_id, $id);
                $stmt->execute();
                $img = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($img) {
                    $path = __DIR__ . '/uploads/' . $img['filename'];
                    if (file_exists($path)) unlink($path);

                    $stmt = $conn->prepare("DELETE FROM item_images WHERE id = ?");
                    $stmt->bind_param("i", $img_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Handle new image uploads
        if (!empty($_FILES['new_images']['name'][0])) {
            $allowed    = ['jpg', 'jpeg', 'png', 'webp'];
            $upload_dir = __DIR__ . '/uploads/';

            // Count existing images
            $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM item_images WHERE item_id = ?");
            $count_stmt->bind_param("i", $id);
            $count_stmt->execute();
            $existing_count = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();

            $uploaded = 0;
            foreach ($_FILES['new_images']['name'] as $index => $filename) {
                if (empty($filename)) continue;
                if ($existing_count + $uploaded >= 5) break;

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) continue;
                if ($_FILES['new_images']['size'][$index] > 2 * 1024 * 1024) continue;

                $new_filename = uniqid('item_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['new_images']['tmp_name'][$index], $upload_dir . $new_filename)) {
                    // If no images exist yet make this primary
                    $is_primary = ($existing_count + $uploaded === 0) ? 1 : 0;
                    $stmt = $conn->prepare(
                        "INSERT INTO item_images (item_id, filename, is_primary) VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param("isi", $id, $new_filename, $is_primary);
                    $stmt->execute();
                    $stmt->close();
                    $uploaded++;
                }
            }
        }

        $success = 'Listing updated successfully!';

        // Refresh existing images
        $img_stmt = $conn->prepare("SELECT * FROM item_images WHERE item_id = ? ORDER BY is_primary DESC");
        $img_stmt->bind_param("i", $id);
        $img_stmt->execute();
        $existing_images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $img_stmt->close();

        // Refresh item data
        $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<div class="max-w-lg mx-auto">

    <div class="mb-6">
        <a href="/CampusCycle/item.php?id=<?php echo $id; ?>"
           class="text-sm text-gray-400 hover:text-gray-600 transition">
            ← Back to listing
        </a>
        <h2 class="text-2xl font-semibold text-gray-800 mt-2">Edit listing</h2>
        <p class="text-gray-400 text-sm mt-1">Update your item details.</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-4 border border-red-200">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 text-sm px-4 py-3 rounded-xl mb-4 border border-green-200">
            <?php echo htmlspecialchars($success); ?>
            <a href="/CampusCycle/item.php?id=<?php echo $id; ?>" class="underline font-medium ml-1">View listing →</a>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data"
          class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-4">

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Item title</label>
            <input type="text" name="title"
                   value="<?php echo htmlspecialchars($item['title']); ?>"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Category</label>
            <select name="category_id"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition bg-white">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"
                        <?php echo $item['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Condition</label>
            <div class="grid grid-cols-4 gap-2">
                <?php foreach (['new' => '✨ New', 'good' => '👍 Good', 'fair' => '👌 Fair', 'poor' => '⚠️ Poor'] as $val => $label): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="condition" value="<?php echo $val; ?>"
                               <?php echo $item['item_condition'] === $val ? 'checked' : ''; ?>
                               class="peer hidden">
                        <div class="text-center text-xs py-2 px-1 rounded-xl border border-gray-200
                                    peer-checked:border-[#2d6a4f] peer-checked:bg-[#d8f3dc] peer-checked:text-[#1b4332]
                                    hover:border-gray-300 transition">
                            <?php echo $label; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Description</label>
            <textarea name="description" rows="4"
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition resize-none"><?php echo htmlspecialchars($item['description']); ?></textarea>
        </div>

        <?php if (!empty($existing_images)): ?>
        <div>
            <label class="text-sm font-medium text-gray-700 block mb-2">Current photos</label>
            <div class="flex gap-2 flex-wrap">
                <?php foreach ($existing_images as $img): ?>
                    <div class="relative">
                        <img src="/CampusCycle/uploads/<?php echo htmlspecialchars($img['filename']); ?>"
                             class="w-20 h-20 object-cover rounded-xl border border-gray-200">
                        <?php if ($img['is_primary']): ?>
                            <span class="absolute top-1 left-1 bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded-full">
                                Cover
                            </span>
                        <?php endif; ?>
                        <label class="absolute top-1 right-1 cursor-pointer">
                            <input type="checkbox" name="delete_images[]"
                                   value="<?php echo $img['id']; ?>" class="peer hidden">
                            <div class="w-5 h-5 rounded-full bg-white/80 border border-gray-300
                                        peer-checked:bg-red-500 peer-checked:border-red-500
                                        flex items-center justify-center text-[10px] text-gray-400
                                        peer-checked:text-white transition">
                                ✕
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-gray-400 mt-1">Click the ✕ on any photo to remove it on save.</p>
        </div>
        <?php endif; ?>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">
                Add more photos
                <span class="text-gray-400 font-normal">(up to <?php echo 5 - count($existing_images); ?> more)</span>
            </label>
            <div id="drop-zone"
                 class="border-2 border-dashed border-gray-200 rounded-xl p-5 text-center cursor-pointer hover:border-[#2d6a4f] transition">
                <p class="text-sm text-gray-400">Click to upload or drag & drop</p>
                <p class="text-xs text-gray-300 mt-1">JPG, PNG, WEBP — max 2MB each</p>
                <input type="file" name="new_images[]" id="image-input" accept="image/*" multiple class="hidden">
            </div>
            <div id="preview" class="flex gap-2 flex-wrap mt-2"></div>
        </div>

        <div class="flex gap-3 mt-2">
            <button type="submit"
                    class="flex-1 bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-2.5 rounded-full transition">
                Save changes
            </button>
            <a href="/CampusCycle/item.php?id=<?php echo $id; ?>"
               class="flex-1 text-center border border-gray-200 text-gray-500 hover:bg-gray-50 text-sm font-medium py-2.5 rounded-full transition">
                Cancel
            </a>
        </div>

    </form>
</div>

<script>
const dropZone   = document.getElementById('drop-zone');
const imageInput = document.getElementById('image-input');
const preview    = document.getElementById('preview');

dropZone.addEventListener('click', () => imageInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-[#2d6a4f]', 'bg-[#f0faf4]');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-[#2d6a4f]', 'bg-[#f0faf4]');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-[#2d6a4f]', 'bg-[#f0faf4]');
    imageInput.files = e.dataTransfer.files;
    showPreviews(e.dataTransfer.files);
});

imageInput.addEventListener('change', () => showPreviews(imageInput.files));

function showPreviews(files) {
    preview.innerHTML = '';
    Array.from(files).slice(0, 5).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'w-20 h-20 rounded-xl overflow-hidden border border-gray-200';
            div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>