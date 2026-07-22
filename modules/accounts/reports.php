<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Accountant']);

/* ============================
   Filters
============================ */
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

/* ============================
   Total Revenue
============================ */
if (!empty($start_date) && !empty($end_date)) {
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(amount),0) AS total
        FROM revenues
        WHERE revenue_date BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $total_revenue = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total_revenue = $conn->query("
        SELECT IFNULL(SUM(amount),0) AS total
        FROM revenues
    ")->fetch_assoc()['total'];
}

/* ============================
   Total Expenses
============================ */
if (!empty($start_date) && !empty($end_date)) {
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(amount),0) AS total
        FROM expenses
        WHERE expense_date BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $total_expenses = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total_expenses = $conn->query("
        SELECT IFNULL(SUM(amount),0) AS total
        FROM expenses
    ")->fetch_assoc()['total'];
}

$net_profit = $total_revenue - $total_expenses;

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">التقارير المالية</h1>
        <p class="text-gray-500 text-sm mt-1">تحليل الإيرادات والمصروفات والربحية</p>
    </div>

    <!-- ================= FILTER SECTION ================= -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">تاريخ البدء</label>
                <input type="date" name="start_date"
                       value="<?= htmlspecialchars($start_date) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">تاريخ الانتهاء</label>
                <input type="date" name="end_date"
                       value="<?= htmlspecialchars($end_date) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <div>
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition">
                    تصفية
                </button>
            </div>

            <div>
                <a href="financial_report.php"
                   class="inline-block text-gray-500 hover:text-gray-700 text-sm mt-2">
                    إعادة ضبط
                </a>
            </div>

        </form>
    </div>

    <!-- ================= SUMMARY CARDS ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">

        <!-- Revenue -->
        <div class="bg-white rounded-2xl shadow-md p-6 flex justify-between items-center hover:shadow-xl transition">
            <div>
                <p class="text-gray-500 text-sm">إجمالي الإيرادات</p>
                <h3 class="text-2xl font-bold text-green-600 mt-1">
                    $<?= number_format($total_revenue,2) ?>
                </h3>
            </div>
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa fa-coins"></i>
            </div>
        </div>

        <!-- Expenses -->
        <div class="bg-white rounded-2xl shadow-md p-6 flex justify-between items-center hover:shadow-xl transition">
            <div>
                <p class="text-gray-500 text-sm">إجمالي المصروفات</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1">
                    $<?= number_format($total_expenses,2) ?>
                </h3>
            </div>
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa fa-money-bill"></i>
            </div>
        </div>

        <!-- Profit -->
        <div class="bg-white rounded-2xl shadow-md p-6 flex justify-between items-center hover:shadow-xl transition">
            <div>
                <p class="text-gray-500 text-sm">صافي الربح</p>
                <h3 class="text-2xl font-bold mt-1 <?= $net_profit >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    $<?= number_format($net_profit,2) ?>
                </h3>
            </div>
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa fa-chart-line"></i>
            </div>
        </div>

    </div>

    <!-- ================= CHART ================= -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-10">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">الإيرادات مقابل المصروفات</h2>
        <canvas id="financeChart" height="100"></canvas>
    </div>

    <!-- ================= PROJECT BREAKDOWN ================= -->
    <div class="bg-white rounded-2xl shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">التحليل المالي للمشاريع</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">المشروع</th>
                        <th class="px-4 py-3">الإيرادات</th>
                        <th class="px-4 py-3">المصروفات</th>
                        <th class="px-4 py-3">الربح</th>
                    </tr>
                </thead>
                <tbody class="divide-y">

<?php
$projects = $conn->query("SELECT id, name FROM projects");

while($project = $projects->fetch_assoc()):

    $project_id = $project['id'];

    $rev_stmt = $conn->prepare("
        SELECT IFNULL(SUM(amount),0) AS total 
        FROM revenues 
        WHERE project_id = ?
    ");
    $rev_stmt->bind_param("i", $project_id);
    $rev_stmt->execute();
    $rev = $rev_stmt->get_result()->fetch_assoc()['total'];
    $rev_stmt->close();

    $exp_stmt = $conn->prepare("
        SELECT IFNULL(SUM(amount),0) AS total 
        FROM expenses 
        WHERE project_id = ?
    ");
    $exp_stmt->bind_param("i", $project_id);
    $exp_stmt->execute();
    $exp = $exp_stmt->get_result()->fetch_assoc()['total'];
    $exp_stmt->close();

    $profit = $rev - $exp;
?>

                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-medium">
                            <?= htmlspecialchars($project['name']) ?>
                        </td>
                        <td class="px-4 py-3 text-green-600 font-semibold">
                            $<?= number_format($rev,2) ?>
                        </td>
                        <td class="px-4 py-3 text-red-600 font-semibold">
                            $<?= number_format($exp,2) ?>
                        </td>
                        <td class="px-4 py-3 font-semibold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            $<?= number_format($profit,2) ?>
                        </td>
                    </tr>

<?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ================= CHART SCRIPT ================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('financeChart'), {
    type: 'bar',
    data: {
        labels: ['الإيرادات', 'المصروفات'],
        datasets: [{
            data: [<?= $total_revenue ?>, <?= $total_expenses ?>],
            backgroundColor: [
                'rgba(16,185,129,0.7)',
                'rgba(239,68,68,0.7)'
            ],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>