<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Find user by email
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Password matches — start session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header("Location: /CampusCycle/index.php");
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}
?>

<div class="max-w-md mx-auto">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Welcome back</h2>
        <p class="text-gray-400 text-sm mt-1">Log in to your CampusCycle account.</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-4 border border-red-200">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-4">

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Email address</label>
            <input type="email" name="email"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   placeholder="you@university.edu"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Password</label>
            <input type="password" name="password"
                   placeholder="Your password"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <button type="submit"
                class="bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-2.5 rounded-full transition mt-2">
            Log in
        </button>

        <p class="text-center text-sm text-gray-400">
            Don't have an account?
            <a href="/CampusCycle/register.php" class="text-[#2d6a4f] font-medium hover:underline">Register</a>
        </p>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>