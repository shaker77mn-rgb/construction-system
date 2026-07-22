<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Storekeeper']);

/* ============================
   Fetch Summary Data (Optimized)
============================ */
$summary_stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_items,
        IFNULL(SUM(quantity),0) AS total_quantity,
        SUM(CASE WHEN quantity < 10 THEN 1 ELSE 0 END) AS low_stock_count
    FROM inventory_items
");
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

/* ============================
   Fetch Inventory Items
============================ */
$items_stmt = $conn->prepare("
    SELECT id, item_name, quantity, unit
    FROM inventory_items
    ORDER BY item_name ASC
");
$items_stmt->execute();
$items = $items_stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100 print:bg-white print:m-0 print:p-0">

    <!-- Print Header -->
    <div class="hidden print:block mb-8 text-center border-b-2 border-gray-800 pb-4">
        <h2 class="text-3xl font-bold text-gray-900">نظام إدارة البناء</h2>
        <h3 class="text-xl font-semibold text-gray-700 mt-2">تقرير المخزون</h3>
        <p class="text-gray-500 mt-1">تاريخ الطباعة: <?= date('Y-m-d H:i') ?></p>
    </div>

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 flex items-center gap-4">
                التقارير:
                <select onchange="window.location.href=this.value" class="text-base border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 font-medium text-gray-700 bg-white shadow-sm px-4 py-2 outline-none cursor-pointer">
                    <option value="/construction_system/modules/reports/financial_report.php">المالية</option>
                    <option value="/construction_system/modules/inventory/stock_report.php" selected>المخزون</option>
                    <option value="/construction_system/modules/reports/project_report.php">المشاريع</option>
                </select>
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                نظرة عامة على المخزون والتحليلات
            </p>
        </div>
        <button onclick="window.print()" class="print:hidden bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-sm transition-all duration-200">
            <i class="fa fa-print"></i>
            <span>طباعة</span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-8 print:grid-cols-3 print:gap-4">

        <!-- Total Item Types -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي أنواع العناصر</p>
            <h3 class="text-2xl font-bold text-blue-600 mt-2">
                <?= $summary['total_items'] ?>
            </h3>
        </div>

        <!-- Total Units -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي الوحدات في المخزون</p>
            <h3 class="text-2xl font-bold text-green-600 mt-2">
                <?= $summary['total_quantity'] ?>
            </h3>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">عناصر المخزون المنخفضة</p>
            <h3 class="text-2xl font-bold text-red-600 mt-2">
                <?= $summary['low_stock_count'] ?>
            </h3>
        </div>

    </div>

    <!-- Detailed Stock Table -->
    <div class="bg-white rounded-2xl shadow-md p-6 print:shadow-none print:p-0 print:border print:border-gray-300">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            نظرة عامة تفصيلية على المخزون
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead class="print:bg-gray-200 print:text-gray-800">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3 border-b print:border-gray-400">#</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">العنصر</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">الكمية</th>
                        <th class="px-4 py-3 border-b print:border-gray-400">الوحدة</th>
                        <th class="px-4 py-3 text-center border-b print:border-gray-400">الحالة</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php if ($items->num_rows > 0): ?>
                    <?php while ($row = $items->fetch_assoc()): 
                        $is_low = $row['quantity'] < 10;
                    ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3 border-b print:border-gray-300"><?= $row['id'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800 border-b print:border-gray-300">
                                <?= htmlspecialchars($row['item_name']) ?>
                            </td>

                            <td class="px-4 py-3 font-semibold <?= $is_low ? 'text-red-600' : 'text-green-600' ?> border-b print:border-gray-300">
                                <?= $row['quantity'] ?>
                            </td>

                            <td class="px-4 py-3 border-b print:border-gray-300">
                                <?= htmlspecialchars($row['unit']) ?>
                            </td>

                            <td class="px-4 py-3 text-center border-b print:border-gray-300">
                                <?php if ($is_low): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold print:border print:border-gray-400 print:text-gray-800 print:bg-transparent bg-red-100 text-red-600">
                                        مخزون منخفض
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold print:border print:border-gray-400 print:text-gray-800 print:bg-transparent bg-green-100 text-green-600">
                                        متاح
                                    </span>
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على عناصر مخزون.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

<?php
$items_stmt->close();
include '../../includes/footer.php';
?>