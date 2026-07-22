<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Accountant']);

$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$rev_filter = "";
$exp_filter = "";
$params = [];
$types  = "";

/* ============================
   Date Filtering (Secure)
============================ */
if (!empty($start_date) && !empty($end_date)) {
    $rev_filter = "WHERE revenue_date BETWEEN ? AND ?";
    $exp_filter = "WHERE expense_date BETWEEN ? AND ?";
    $params = [$start_date, $end_date, $start_date, $end_date];
    $types  = "ssss";
}

/* ============================
   Total Revenue
============================ */
$rev_stmt = $conn->prepare("
    SELECT IFNULL(SUM(amount),0) AS total
    FROM revenues
    $rev_filter
");

if (!empty($rev_filter)) {
    $rev_stmt->bind_param("ss", $start_date, $end_date);
}
$rev_stmt->execute();
$total_revenue = $rev_stmt->get_result()->fetch_assoc()['total'];
$rev_stmt->close();

/* ============================
   Total Expenses
============================ */
$exp_stmt = $conn->prepare("
    SELECT IFNULL(SUM(amount),0) AS total
    FROM expenses
    $exp_filter
");

if (!empty($exp_filter)) {
    $exp_stmt->bind_param("ss", $start_date, $end_date);
}
$exp_stmt->execute();
$total_expenses = $exp_stmt->get_result()->fetch_assoc()['total'];
$exp_stmt->close();

$net_profit = $total_revenue - $total_expenses;
$profit_margin = $total_revenue > 0
    ? ($net_profit / $total_revenue) * 100
    : 0;

/* ============================
   Project Breakdown (Optimized)
============================ */
$projects = $conn->query("
    SELECT 
        p.id,
        p.name,
        IFNULL(SUM(DISTINCT r.amount),0) AS total_revenue,
        IFNULL(SUM(DISTINCT e.amount),0) AS total_expenses
    FROM projects p
    LEFT JOIN revenues r ON p.id = r.project_id
    LEFT JOIN expenses e ON p.id = e.project_id
    GROUP BY p.id
    ORDER BY p.name ASC
");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100 print:bg-white print:m-0 print:p-0">

    <!-- Print Header -->
    <div class="hidden print:block mb-8 text-center border-b-2 border-gray-800 pb-4">
        <h2 class="text-3xl font-bold text-gray-900">نظام إدارة البناء</h2>
        <h3 class="text-xl font-semibold text-gray-700 mt-2">التقرير المالي</h3>
        <p class="text-gray-500 mt-1">تاريخ الطباعة: <?= date('Y-m-d H:i') ?></p>
        <?php if(!empty($start_date) && !empty($end_date)): ?>
            <p class="text-gray-500 text-sm mt-1">الفترة: <?= $start_date ?> إلى <?= $end_date ?></p>
        <?php endif; ?>
    </div>

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 flex items-center gap-4">
                التقارير:
                <select onchange="window.location.href=this.value" class="text-base border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 font-medium text-gray-700 bg-white shadow-sm px-4 py-2 outline-none cursor-pointer">
                    <option value="/construction_system/modules/reports/financial_report.php" selected>المالية</option>
                    <option value="/construction_system/modules/inventory/stock_report.php">المخزون</option>
                    <option value="/construction_system/modules/reports/project_report.php">المشاريع</option>
                </select>
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                نظرة عامة مالية وتحليل ربحية المشاريع
            </p>
        </div>
        <button onclick="window.print()" class="print:hidden bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-sm transition-all duration-200">
            <i class="fa fa-print"></i>
            <span>طباعة</span>
        </button>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 print:hidden">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    تاريخ البدء
                </label>
                <input type="date"
                       name="start_date"
                       value="<?= htmlspecialchars($start_date) ?>"
                       class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    تاريخ الانتهاء
                </label>
                <input type="date"
                       name="end_date"
                       value="<?= htmlspecialchars($end_date) ?>"
                       class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
            </div>

            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg">
                تطبيق الفلتر
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 print:grid-cols-4 print:gap-4">

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي الإيرادات</p>
            <h3 class="text-2xl font-bold text-green-600 mt-2">
                $<?= number_format($total_revenue,2) ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي المصروفات</p>
            <h3 class="text-2xl font-bold text-red-600 mt-2">
                $<?= number_format($total_expenses,2) ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">صافي الربح</p>
            <h3 class="text-2xl font-bold <?= $net_profit >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-2">
                $<?= number_format($net_profit,2) ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">هامش الربح</p>
            <h3 class="text-2xl font-bold <?= $profit_margin >= 0 ? 'text-blue-600' : 'text-red-600' ?> mt-2">
                <?= round($profit_margin,2) ?>%
            </h3>
        </div>

    </div>

    <!-- Project Breakdown -->
    <div class="bg-white rounded-2xl shadow-md p-6 print:shadow-none print:p-0 print:border print:border-gray-300">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            التحليل المالي للمشاريع
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead class="print:bg-gray-200 print:text-gray-800">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3 border-b print:border-gray-400">المشروع</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">الإيرادات</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">المصروفات</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">الربح</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">الحالة</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php while($row = $projects->fetch_assoc()):
                    $profit = $row['total_revenue'] - $row['total_expenses'];
                    $status = $profit >= 0 ? "مربح" : "خسارة";
                ?>

                    <tr class="hover:bg-gray-50">

                        <td class="px-4 py-3 font-medium text-gray-800 border-b print:border-gray-300">
                            <?= htmlspecialchars($row['name']) ?>
                        </td>

                        <td class="px-4 py-3 text-green-600 border-b print:border-gray-300">
                            $<?= number_format($row['total_revenue'],2) ?>
                        </td>

                        <td class="px-4 py-3 text-red-600 border-b print:border-gray-300">
                            $<?= number_format($row['total_expenses'],2) ?>
                        </td>

                        <td class="px-4 py-3 font-semibold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?> border-b print:border-gray-300">
                            $<?= number_format($profit,2) ?>
                        </td>

                        <td class="px-4 py-3 border-b print:border-gray-300">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold print:border print:border-gray-400 print:text-gray-800 print:bg-transparent
                                <?= $profit >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?>">
                                <?= $status ?>
                            </span>
                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>