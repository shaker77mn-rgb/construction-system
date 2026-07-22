<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$error = '';
$success = '';

/* ============================
   Handle Form Submission
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name       = trim($_POST['name'] ?? '');
    $client_id  = intval($_POST['client_id'] ?? 0);
    $budget     = floatval($_POST['budget'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $status     = $_POST['status'] ?? 'Pending';

    if (empty($name) || $client_id <= 0 || empty($start_date)) {
        $error = "Project name, client, and start date are required.";
    } elseif (!empty($end_date) && $end_date < $start_date) {
        $error = "End date cannot be before start date.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO projects 
            (name, client_id, budget, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sidsss",
            $name,
            $client_id,
            $budget,
            $start_date,
            $end_date,
            $status
        );

        if ($stmt->execute()) {
            $success = "تم إنشاء المشروع بنجاح!";
        } else {
            $error = "Something went wrong while saving.";
        }

        $stmt->close();
    }
}

/* ============================
   Fetch Clients
============================ */
$clients_stmt = $conn->prepare("
    SELECT id, name FROM clients ORDER BY name ASC
");
$clients_stmt->execute();
$clients = $clients_stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                إضافة مشروع جديد
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                إنشاء وإدارة مشاريع البناء
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

            <!-- Project Name -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    اسم المشروع *
                </label>
                <input type="text"
                       name="name"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Client -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    العميل *
                </label>
                <select name="client_id"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    <option value="">اختر العميل</option>
                    <?php while($client = $clients->fetch_assoc()): ?>
                        <option value="<?= $client['id'] ?>">
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Budget -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الميزانية
                </label>
                <input type="number"
                       name="budget"
                       step="0.01"
                       min="0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    تاريخ البدء *
                </label>
                <input type="date"
                       name="start_date"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    تاريخ الانتهاء
                </label>
                <input type="date"
                       name="end_date"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Status -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الحالة
                </label>
                <select name="status"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    <option value="Pending">قيد الانتظار</option>
                    <option value="In Progress">قيد التنفيذ</option>
                    <option value="Completed">مكتمل</option>
                </select>
            </div>

            <!-- Submit -->
            <div class="md:col-span-2">
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    إنشاء مشروع
                </button>
            </div>

        </form>

    </div>

</div>

<?php
$clients_stmt->close();
include '../../includes/footer.php';
?>