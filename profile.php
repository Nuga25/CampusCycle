<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /CampusCycle/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university = trim($_POST['university']);
    $name       = trim($_POST['name']);

    if (empty($name)) {
        $error = 'Name cannot be empty.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, university = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $university, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_name'] = $name;
        $_SESSION['university'] = $university;
        $success = 'Profile updated successfully!';

        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<div class="max-w-md mx-auto">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">My profile</h2>
        <p class="text-gray-400 text-sm mt-1">Update your name and university.</p>
    </div>

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

    <form method="POST"
          class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-4">

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Full name</label>
            <input type="text" name="name"
                   value="<?php echo htmlspecialchars($user['name']); ?>"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">University</label>
            <input type="text" name="university"
                   value="<?php echo htmlspecialchars($user['university'] ?? ''); ?>"
                   placeholder="e.g. University of Lagos"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
            <p class="text-xs text-gray-400 mt-1">
                This is used to show you items from your campus first.
            </p>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs text-gray-400 mb-1">Email address</p>
            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <button type="submit"
                class="bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-2.5 rounded-full transition mt-2">
            Save profile
        </button>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>