<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin']);

$error = '';
$success = '';

/* ============================
   Handle Form Submission
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $role_id   = intval($_POST['role_id'] ?? 0);

    if (empty($username) || empty($full_name) || empty($password) || $role_id <= 0) {
        $error = "جميع الحقول مطلوبة.";
    } else {

        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "اسم المستخدم موجود بالفعل.";
        } else {

            // MD5 hash (keeping compatibility with your login system)
            $hashed_password = md5($password);

            $stmt = $conn->prepare("
                INSERT INTO users (username, password, full_name, role_id)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param("sssi", $username, $hashed_password, $full_name, $role_id);

            if ($stmt->execute()) {
                $success = "تم إنشاء المستخدم بنجاح!";
            } else {
                $error = "Something went wrong while saving.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

/* ============================
   Fetch Roles
============================ */
$roles_stmt = $conn->prepare("SELECT id, role_name FROM roles ORDER BY role_name ASC");
$roles_stmt->execute();
$roles = $roles_stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                إضافة مستخدم جديد
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                إنشاء مستخدمي النظام وتعيين الصلاحيات
            </p>
        </div>

        <a href="list.php"
           class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-5 py-2.5 rounded-lg transition">
            رجوع &rarr;
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-md p-6 max-w-3xl">

        <?php if (!empty($error)): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    اسم المستخدم *
                </label>
                <input type="text"
                       name="username"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Full Name -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الاسم الكامل *
                </label>
                <input type="text"
                       name="full_name"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    كلمة المرور *
                </label>
                <input type="password"
                       name="password"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الصلاحية *
                </label>
                <select name="role_id"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    <option value="">اختر الصلاحية</option>
                    <?php while ($role = $roles->fetch_assoc()): 
                        $role_ar = match(strtolower($role['role_name'])) {
                            'admin'       => 'مدير النظام',
                            'accountant'  => 'محاسب',
                            'engineer'    => 'مهندس',
                            'storekeeper' => 'أمين مخزن',
                            default       => htmlspecialchars($role['role_name'])
                        };
                    ?>
                        <option value="<?= $role['id'] ?>">
                            <?= $role_ar ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Submit -->
            <div class="md:col-span-2">
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    إنشاء مستخدم
                </button>
            </div>

        </form>

    </div>

</div>

<?php
$roles_stmt->close();
include '../../includes/footer.php';
?>