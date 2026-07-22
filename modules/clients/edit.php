<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$error = '';
$success = '';

/* ============================
   Validate ID
============================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$client_id = intval($_GET['id']);

/* ============================
   Fetch Client Data
============================ */
$client_stmt = $conn->prepare("
    SELECT id, name, phone, email, address
    FROM clients
    WHERE id = ?
");
$client_stmt->bind_param("i", $client_id);
$client_stmt->execute();
$client_result = $client_stmt->get_result();

if ($client_result->num_rows !== 1) {
    header("Location: list.php");
    exit();
}

$client = $client_result->fetch_assoc();
$client_stmt->close();

/* ============================
   Handle Update
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $error = "Client name is required.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {

        $update_stmt = $conn->prepare("
            UPDATE clients
            SET name=?, phone=?, email=?, address=?
            WHERE id=?
        ");

        $update_stmt->bind_param(
            "ssssi",
            $name,
            $phone,
            $email,
            $address,
            $client_id
        );

        if ($update_stmt->execute()) {
            $success = "تم تحديث بيانات العميل بنجاح!";

            // Refresh local data
            $client['name']    = $name;
            $client['phone']   = $phone;
            $client['email']   = $email;
            $client['address'] = $address;

        } else {
            $error = "Update failed.";
        }

        $update_stmt->close();
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                تعديل بيانات العميل
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                تحديث معلومات العميل
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

            <!-- Client Name -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    اسم العميل *
                </label>
                <input type="text"
                       name="name"
                       value="<?= htmlspecialchars($client['name']) ?>"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Phone -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الهاتف
                </label>
                <input type="text"
                       name="phone"
                       value="<?= htmlspecialchars($client['phone']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    البريد الإلكتروني
                </label>
                <input type="email"
                       name="email"
                       value="<?= htmlspecialchars($client['email']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Address -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    العنوان
                </label>
                <textarea name="address"
                          rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition resize-none"><?= 
                          htmlspecialchars($client['address']) ?></textarea>
            </div>

            <!-- Submit -->
            <div class="md:col-span-2">
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    تحديث العميل
                </button>
            </div>

        </form>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>