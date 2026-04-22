<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /CampusCycle/login.php");
    exit();
}

$error   = '';
$success = '';
$categories = $conn->query("SELECT * FROM categories");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $condition   = $_POST['condition'] ?? 'good';
    $user_id     = $_SESSION['user_id'];

    if (empty($title) || empty($description) || empty($category_id)) {
        $error = 'Please fill in all fields.';
    } else {
        // Insert item first
        $stmt = $conn->prepare(
            "INSERT INTO items (user_id, category_id, title, description, item_condition)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $user_id, $category_id, $title, $description, $condition);

        if ($stmt->execute()) {
            $item_id = $conn->insert_id;
            $stmt->close();

            // Handle multiple image uploads
            $allowed     = ['jpg', 'jpeg', 'png', 'webp'];
            $upload_dir  = __DIR__ . '/uploads/';
            $images      = $_FILES['images'];
            $uploaded    = 0;

            foreach ($images['name'] as $index => $filename) {
                if (empty($filename)) continue;

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) continue;
                if ($images['size'][$index] > 2 * 1024 * 1024) continue;

                $new_filename = uniqid('item_', true) . '.' . $ext;
                if (move_uploaded_file($images['tmp_name'][$index], $upload_dir . $new_filename)) {
                    $is_primary = ($uploaded === 0) ? 1 : 0;
                    $stmt = $conn->prepare(
                        "INSERT INTO item_images (item_id, filename, is_primary) VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param("isi", $item_id, $new_filename, $is_primary);
                    $stmt->execute();
                    $stmt->close();
                    $uploaded++;
                }

                if ($uploaded >= 5) break; // Max 5 images
            }

            $success = 'Item posted successfully!';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>

<div class="max-w-lg mx-auto">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Post an item</h2>
        <p class="text-gray-400 text-sm mt-1">Give something you no longer need.</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-4 border border-red-200">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 text-sm px-4 py-3 rounded-xl mb-4 border border-green-200">
            <?php echo htmlspecialchars($success); ?>
            <a href="/CampusCycle/index.php" class="underline font-medium ml-1">View feed →</a>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data"
          class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-4">

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Item title</label>
            <input type="text" name="title"
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                   placeholder="e.g. Calculus Textbook, Non-stick Pan"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Category</label>
            <select name="category_id"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition bg-white">
                <option value="">Select a category</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>"
                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Condition</label>
            <div class="grid grid-cols-4 gap-2">
                <?php foreach (['new' => '✨ New', 'good' => '👍 Good', 'fair' => '👌 Fair', 'poor' => '⚠️ Poor'] as $val => $label): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="condition" value="<?php echo $val; ?>"
                               <?php echo (!isset($_POST['condition']) && $val === 'good') || (isset($_POST['condition']) && $_POST['condition'] === $val) ? 'checked' : ''; ?>
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
                      placeholder="Describe the item, its condition, why you're giving it away..."
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition resize-none"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">
                Photos <span class="text-gray-400 font-normal">(up to 5)</span>
            </label>
            <div id="drop-zone"
                 class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-[#2d6a4f] transition">
                <p class="text-sm text-gray-400">Click to upload or drag & drop</p>
                <p class="text-xs text-gray-300 mt-1">JPG, PNG, WEBP — max 2MB each</p>
                <input type="file" name="images[]" id="image-input"
                       accept="image/*" multiple class="hidden">
            </div>
            <div id="preview" class="flex gap-2 flex-wrap mt-2"></div>
        </div>

        <button type="submit"
                class="bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-2.5 rounded-full transition mt-2">
            Post item
        </button>

    </form>
</div>

<script>
const dropZone    = document.getElementById('drop-zone');
const imageInput  = document.getElementById('image-input');
const preview     = document.getElementById('preview');

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
            div.className = 'relative w-20 h-20';
            div.innerHTML = `
                <img src="${e.target.result}"
                     class="w-20 h-20 object-cover rounded-xl border border-gray-200">
                <span class="absolute top-1 left-1 bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded-full">
                    ${preview.children.length === 0 ? 'Cover' : ''}
                </span>`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>