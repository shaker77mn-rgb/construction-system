<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Accountant']);

$error = '';
$success = '';

/* ============================
   Handle Add Revenue
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $project_id = intval($_POST['project_id'] ?? 0);
    $amount     = floatval($_POST['amount'] ?? 0);
    $date       = $_POST['revenue_date'] ?? '';
    $desc       = trim($_POST['description'] ?? '');

    if ($project_id <= 0 || $amount <= 0 || empty($date)) {
        $error = "Project, amount and date are required.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO revenues 
            (project_id, amount, description, revenue_date, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "idssi",
            $project_id,
            $amount,
            $desc,
            $date,
            $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $success = "Revenue recorded successfully!";
        } else {
            $error = "Something went wrong while saving.";
        }

        $stmt->close();
    }
}

/* ============================
   Fetch Revenue Records
============================ */
$records_stmt = $conn->prepare("
    SELECT r.id, r.amount, r.description, r.revenue_date,
           p.name AS project_name
    FROM revenues r
    JOIN projects p ON r.project_id = p.id
    ORDER BY r.id DESC
");
$records_stmt->execute();
$records = $records_stmt->get_result();

$total_revenue = 0;

/* ============================
   Fetch Projects
============================ */
$projects_stmt = $conn->prepare("
    SELECT id, name FROM projects ORDER BY name ASC
");
$projects_stmt->execute();
$projects = $projects_stmt->get_result();
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar.php'; ?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">إدارة الإيرادات</h1>
        <p class="text-gray-500 text-sm mt-1">
            تسجيل وإدارة إيرادات المشاريع
        </p>
    </div>

    <!-- ===================== ADD REVENUE FORM ===================== -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8">

        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            إضافة إيراد
        </h2>

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

            <!-- Project -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    المشروع *
                </label>
                <select name="project_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                    <option value="">اختر المشروع</option>
                    <?php while($project = $projects->fetch_assoc()): ?>
                        <option value="<?= $project['id'] ?>">
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    المبلغ *
                </label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="0"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>

            <!-- Date -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    التاريخ *
                </label>
                <input type="date"
                       name="revenue_date"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الوصف
                </label>
                <textarea name="description"
                          rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"></textarea>
            </div>

            <!-- Submit -->
            <div class="md:col-span-2">
                <button type="submit"
                        class="bg-emerald-500 hover:bg-emerald-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    إضافة إيراد
                </button>
            </div>

        </form>
    </div>

    <!-- ===================== REVENUE TABLE ===================== -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            سجلات الإيرادات
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">المشروع</th>
                        <th class="px-4 py-3">المبلغ</th>
                        <th class="px-4 py-3">التاريخ</th>
                        <th class="px-4 py-3">الوصف</th>
                    </tr>
                </thead>
                <tbody class="divide-y">

                    <?php if ($records->num_rows > 0): ?>
                        <?php while($row = $records->fetch_assoc()):
                            $total_revenue += $row['amount'];
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3"><?= $row['id'] ?></td>
                                <td class="px-4 py-3 font-medium">
                                    <?= htmlspecialchars($row['project_name']) ?>
                                </td>
                                <td class="px-4 py-3 text-green-600 font-semibold">
                                    $<?= number_format($row['amount'], 2) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?= htmlspecialchars($row['revenue_date']) ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?= htmlspecialchars($row['description']) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                لم يتم العثور على سجلات إيرادات.
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- Total Revenue -->
        <div class="mt-6 text-left">
            <span class="text-lg font-semibold text-emerald-600">
                إجمالي الإيرادات: $<?= number_format($total_revenue, 2) ?>
            </span>
        </div>

    </div>

</div>

<?php
$records_stmt->close();
$projects_stmt->close();
include '../../includes/footer.php';
?>