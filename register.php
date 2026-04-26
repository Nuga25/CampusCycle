<?php
require_once 'includes/header.php';
require_once 'config/db.php';
require_once 'config/universities.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']);
    $university = trim($_POST['university']);
    $email      = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            // Hash password and insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, university, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $university, $email, $hashed);

            if ($stmt->execute()) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>


<div class="max-w-md mx-auto">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Create an account</h2>
        <p class="text-gray-400 text-sm mt-1">Join CampusCycle and start giving.</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-4 border border-red-200">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 text-sm px-4 py-3 rounded-xl mb-4 border border-green-200">
            <?php echo htmlspecialchars($success); ?>
            <a href="/CampusCycle/login.php" class="underline font-medium ml-1">Log in now →</a>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-4">

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Full name</label>
            <input type="text" name="name"
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                   placeholder="Ife Osinuga"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">University</label>
            <select name="university"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition bg-white">
                <option value="">Select your university</option>
                <?php foreach ($nigerian_universities as $uni): ?>
                    <option value="<?php echo htmlspecialchars($uni); ?>"
                        <?php echo (isset($_POST['university']) && $_POST['university'] === $uni) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($uni); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-400 mt-1">Used to show you items from your campus first.</p>
        </div>

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
                   placeholder="At least 6 characters"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">Confirm password</label>
            <input type="password" name="confirm"
                   placeholder="Repeat your password"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#2d6a4f] transition">
        </div>

        <button type="submit"
                class="bg-[#2d6a4f] hover:bg-[#1b4332] text-white text-sm font-medium py-2.5 rounded-full transition mt-2">
            Create account
        </button>

        <p class="text-center text-sm text-gray-400">
            Already have an account?
            <a href="/CampusCycle/login.php" class="text-[#2d6a4f] font-medium hover:underline">Log in</a>
        </p>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>