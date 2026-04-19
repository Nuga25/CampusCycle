<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Only logged in users can post
if (!isset($_SESSION['user_id'])) {
    header("Location: /CampusCycle/login.php");
    exit();
}

$error   = '';
$success = '';

// Fetch categories for the dropdown
$categories = $conn->query("SELECT * FROM categories");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $user_id     = $_SESSION['user_id'];
    $image       = null;

    if (empty($title) || empty($description) || empty($category_id)) {
        $error = 'Please fill in all fields.';

    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $allowed     = ['jpg', 'jpeg', 'png', 'webp'];
            $filename    = $_FILES['image']['name'];
            $ext         = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $upload_dir  = __DIR__ . '/uploads/';

            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, and WEBP images are allowed.';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = 'Image must be under 2MB.';
            } else {
                // Give the file a unique name so nothing gets overwritten
                $new_filename = uniqid('item_', true) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename);
                $image = $new_filename;
            }
        }

        if (!$error) {
            $stmt = $conn->prepare(
                "INSERT INTO items (user_id, category_id, title, description, image)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iisss", $user_id, $category_id, $title, $description, $image);

            if ($stmt->execute()) {
                $success = 'Item posted successfully!';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $stmt->close();
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
            <a href="/CampusCycle/index.php" class="underline font-medium ml-1">View all items →</a>
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
            <label class="text-sm font-medium text-gray-700 block mb-1">Description</label>
            <textarea name="description" rows="4"
                      placeholder="Describe the condition, any wear and tear, why you're giving it away..."
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition resize-none"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Photo <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="file" name="image" accept="image/*"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition bg-white">
            <p class="text-xs text-gray-400 mt-1">JPG, PNG or WEBP — max 2MB</p>
        </div>

        <button type="submit"
                class="bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-2.5 rounded-full transition mt-2">
            Post item
        </button>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>