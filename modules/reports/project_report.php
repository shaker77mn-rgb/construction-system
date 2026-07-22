<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Engineer']);

$project_id = isset($_GET['project_id']) && is_numeric($_GET['project_id'])
    ? intval($_GET['project_id'])
    : null;

$project = null;
$revenue = 0;
$expenses = 0;
$profit = 0;
$profit_margin = 0;
$budget_usage = 0;

/* ============================
   Fetch Projects for Dropdown
============================ */
$projects_list = $conn->query("
    SELECT id, name 
    FROM projects 
    ORDER BY name ASC
");

/* ============================
   If Project Selected
============================ */
if ($project_id) {

    // Fetch project + client
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS client_name
        FROM projects p
        JOIN clients c ON p.client_id = c.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $project = $result->fetch_assoc();

        // Fetch totals in ONE query
        $totals_stmt = $conn->prepare("
            SELECT
                (SELECT IFNULL(SUM(amount),0) FROM revenues WHERE project_id = ?) AS total_revenue,
                (SELECT IFNULL(SUM(amount),0) FROM expenses WHERE project_id = ?) AS total_expenses
        ");
        $totals_stmt->bind_param("ii", $project_id, $project_id);
        $totals_stmt->execute();
        $totals = $totals_stmt->get_result()->fetch_assoc();
        $totals_stmt->close();

        $revenue = $totals['total_revenue'];
        $expenses = $totals['total_expenses'];

        $profit = $revenue - $expenses;

        $profit_margin = $revenue > 0
            ? ($profit / $revenue) * 100
            : 0;

        $budget_usage = $project['budget'] > 0
            ? ($expenses / $project['budget']) * 100
            : 0;
    }

    $stmt->close();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100 print:bg-white print:m-0 print:p-0">

    <!-- Print Header -->
    <div class="hidden print:block mb-8 text-center border-b-2 border-gray-800 pb-4">
        <h2 class="text-3xl font-bold text-gray-900">نظام إدارة البناء</h2>
        <h3 class="text-xl font-semibold text-gray-700 mt-2">تقرير المشروع</h3>
        <p class="text-gray-500 mt-1">تاريخ الطباعة: <?= date('Y-m-d H:i') ?></p>
        <?php if($project): ?>
            <p class="text-gray-500 text-sm mt-1">المشروع: <?= htmlspecialchars($project['name']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 flex items-center gap-4">
                التقارير:
                <select onchange="window.location.href=this.value" class="text-base border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 font-medium text-gray-700 bg-white shadow-sm px-4 py-2 outline-none cursor-pointer">
                    <option value="/construction_system/modules/reports/financial_report.php">المالية</option>
                    <option value="/construction_system/modules/inventory/stock_report.php">المخزون</option>
                    <option value="/construction_system/modules/reports/project_report.php" selected>المشاريع</option>
                </select>
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                تحليل مالي مفصل لكل مشروع
            </p>
        </div>
        <button onclick="window.print()" class="print:hidden bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-sm transition-all duration-200">
            <i class="fa fa-print"></i>
            <span>طباعة</span>
        </button>
    </div>

    <!-- Project Selector -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 max-w-xl print:hidden">

        <form method="GET">
            <label class="block text-sm font-medium text-gray-600 mb-2">
                اختر المشروع
            </label>

            <select name="project_id"
                    onchange="this.form.submit()"
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">

                <option value="">اختر مشروعاً</option>

                <?php while($p = $projects_list->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= ($p['id'] == $project_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endwhile; ?>

            </select>
        </form>
    </div>

<?php if ($project): ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 print:grid-cols-4 print:gap-4">

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">ميزانية المشروع</p>
            <h3 class="text-2xl font-bold text-blue-600 mt-2">
                $<?= number_format($project['budget'],2) ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي الإيرادات</p>
            <h3 class="text-2xl font-bold text-green-600 mt-2">
                $<?= number_format($revenue,2) ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي المصروفات</p>
            <h3 class="text-2xl font-bold text-red-600 mt-2">
                $<?= number_format($expenses,2) ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">صافي الربح</p>
            <h3 class="text-2xl font-bold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-2">
                $<?= number_format($profit,2) ?>
            </h3>
        </div>

    </div>

    <!-- Analysis Section -->
    <div class="bg-white rounded-2xl shadow-md p-6 print:shadow-none print:p-0 print:border print:border-gray-300">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            تحليل المشروع
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <div>
                <p class="text-gray-500 text-sm">العميل</p>
                <p class="font-semibold"><?= htmlspecialchars($project['client_name']) ?></p>
            </div>

            <div>
                <p class="text-gray-500 text-sm">الحالة</p>
                <?php
                $status_ar = match(strtolower(str_replace(' ', '-', $project['status']))) {
                    'pending'      => 'قيد الانتظار',
                    'in-progress'  => 'قيد التنفيذ',
                    'completed'    => 'مكتمل',
                    default        => htmlspecialchars($project['status'])
                };
                ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold print:border print:border-gray-400 print:text-gray-800 print:bg-transparent
                    <?= $profit >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                    <?= $status_ar ?>
                </span>
            </div>

            <div>
                <p class="text-gray-500 text-sm">هامش الربح</p>
                <p class="font-semibold <?= $profit_margin >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    <?= round($profit_margin,2) ?>%
                </p>
            </div>

        </div>

        <!-- Budget Usage -->
        <div>
            <p class="text-gray-500 text-sm mb-2">استهلاك الميزانية</p>

            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="<?= $budget_usage > 100 ? 'bg-red-600' : 'bg-amber-500' ?> h-4 rounded-full"
                     style="width: <?= min($budget_usage,100) ?>%;">
                </div>
            </div>

            <p class="mt-2 text-sm">
                تم استهلاك <?= round($budget_usage,2) ?>% من الميزانية
            </p>
        </div>

    </div>

<?php endif; ?>

</div>

<?php include '../../includes/footer.php'; ?>