<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$error = '';

/* ============================
   Validate Project ID
============================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$project_id = intval($_GET['id']);

/* ============================
   Fetch Project Info
============================ */
$project_stmt = $conn->prepare("
    SELECT p.id, p.name, p.budget, p.start_date, p.end_date, p.status,
           c.name AS client_name
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    WHERE p.id = ?
");
$project_stmt->bind_param("i", $project_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();

if ($project_result->num_rows !== 1) {
    header("Location: list.php");
    exit();
}

$project = $project_result->fetch_assoc();
$project_stmt->close();

/* ============================
   Financial Summary (Secure)
============================ */
$rev_stmt = $conn->prepare("
    SELECT IFNULL(SUM(amount),0) AS total
    FROM revenues
    WHERE project_id = ?
");
$rev_stmt->bind_param("i", $project_id);
$rev_stmt->execute();
$revenue = $rev_stmt->get_result()->fetch_assoc()['total'];
$rev_stmt->close();

$exp_stmt = $conn->prepare("
    SELECT IFNULL(SUM(amount),0) AS total
    FROM expenses
    WHERE project_id = ?
");
$exp_stmt->bind_param("i", $project_id);
$exp_stmt->execute();
$expenses = $exp_stmt->get_result()->fetch_assoc()['total'];
$exp_stmt->close();

$profit = $revenue - $expenses;

/* ============================
   Budget Progress
============================ */
$progress = 0;
if ($project['budget'] > 0) {
    $progress = min(($expenses / $project['budget']) * 100, 100);
}

/* Status Styling */
$status = strtolower(str_replace(' ', '-', $project['status']));
$statusClass = match($status) {
    'pending'      => 'bg-yellow-100 text-yellow-600',
    'in-progress'  => 'bg-blue-100 text-blue-600',
    'completed'    => 'bg-green-100 text-green-600',
    default        => 'bg-gray-100 text-gray-600'
};

$status_ar = match($status) {
    'pending'      => 'قيد الانتظار',
    'in-progress'  => 'قيد التنفيذ',
    'completed'    => 'مكتمل',
    default        => htmlspecialchars($project['status'])
};

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                تفاصيل المشروع
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                عرض نظرة عامة على المشروع والملخص المالي
            </p>
        </div>

        <a href="list.php"
           class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-5 py-2.5 rounded-lg transition">
            رجوع &rarr;
        </a>
    </div>

    <!-- Project Info Card -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8">

        <h2 class="text-xl font-semibold text-gray-800 mb-4">
            <?= htmlspecialchars($project['name']) ?>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">

            <div>
                <span class="font-medium">العميل:</span>
                <?= htmlspecialchars($project['client_name']) ?>
            </div>

            <div>
                <span class="font-medium">الحالة:</span>
                <span class="mr-2 px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                    <?= $status_ar ?>
                </span>
            </div>

            <div>
                <span class="font-medium">تاريخ البدء:</span>
                <?= htmlspecialchars($project['start_date']) ?>
            </div>

            <div>
                <span class="font-medium">تاريخ الانتهاء:</span>
                <?= htmlspecialchars($project['end_date']) ?: '—' ?>
            </div>

        </div>

    </div>

    <!-- Financial Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">

        <!-- Budget -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">الميزانية</p>
            <h3 class="text-xl font-bold text-blue-600 mt-1">
                $<?= number_format($project['budget'], 2) ?>
            </h3>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي الإيرادات</p>
            <h3 class="text-xl font-bold text-green-600 mt-1">
                $<?= number_format($revenue, 2) ?>
            </h3>
        </div>

        <!-- Expenses -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي المصروفات</p>
            <h3 class="text-xl font-bold text-red-600 mt-1">
                $<?= number_format($expenses, 2) ?>
            </h3>
        </div>

        <!-- Profit -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">صافي الربح</p>
            <h3 class="text-xl font-bold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?> mt-1">
                $<?= number_format($profit, 2) ?>
            </h3>
        </div>

    </div>

    <!-- Budget Usage -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            استهلاك الميزانية
        </h3>

        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div class="h-4 rounded-full 
                <?= $progress < 70 ? 'bg-green-500' : ($progress < 90 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                style="width: <?= $progress ?>%;">
            </div>
        </div>

        <p class="mt-3 text-sm text-gray-600">
            تم استهلاك <?= round($progress, 1) ?>% من الميزانية
        </p>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>