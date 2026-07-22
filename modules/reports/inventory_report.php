<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Storekeeper']);

$item_id = isset($_GET['item_id']) && is_numeric($_GET['item_id'])
    ? intval($_GET['item_id'])
    : null;

/* ============================
   Fetch Items for Dropdown
============================ */
$items = $conn->query("
    SELECT id, item_name 
    FROM inventory_items 
    ORDER BY item_name ASC
");

/* ============================
   Totals (Optimized SQL)
============================ */
if ($item_id) {

    $totals_stmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END) AS total_in,
            SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END) AS total_out
        FROM inventory_transactions
        WHERE item_id = ?
    ");
    $totals_stmt->bind_param("i", $item_id);

} else {

    $totals_stmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END) AS total_in,
            SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END) AS total_out
        FROM inventory_transactions
    ");
}

$totals_stmt->execute();
$totals = $totals_stmt->get_result()->fetch_assoc();
$totals_stmt->close();

$total_in = $totals['total_in'] ?? 0;
$total_out = $totals['total_out'] ?? 0;
$current_stock = $total_in - $total_out;

/* ============================
   Fetch Transactions
============================ */
if ($item_id) {

    $transactions_stmt = $conn->prepare("
        SELECT t.*, i.item_name, p.name AS project_name
        FROM inventory_transactions t
        JOIN inventory_items i ON t.item_id = i.id
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.item_id = ?
        ORDER BY t.transaction_date DESC
    ");
    $transactions_stmt->bind_param("i", $item_id);

} else {

    $transactions_stmt = $conn->prepare("
        SELECT t.*, i.item_name, p.name AS project_name
        FROM inventory_transactions t
        JOIN inventory_items i ON t.item_id = i.id
        LEFT JOIN projects p ON t.project_id = p.id
        ORDER BY t.transaction_date DESC
    ");
}

$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                تقرير المخزون
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                تحليل حركة المخزون
            </p>
        </div>
        <button onclick="window.print()" class="print:hidden bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 shadow-sm transition-all duration-200">
            <i class="fa fa-print"></i>
            <span>طباعة</span>
        </button>
    </div>

    <!-- Item Selector -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 max-w-xl">

        <form method="GET">
            <label class="block text-sm font-medium text-gray-600 mb-2">
                اختر العنصر
            </label>

            <select name="item_id"
                    onchange="this.form.submit()"
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">

                <option value="">جميع العناصر</option>

                <?php while($item = $items->fetch_assoc()): ?>
                    <option value="<?= $item['id'] ?>"
                        <?= ($item['id'] == $item_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($item['item_name']) ?>
                    </option>
                <?php endwhile; ?>

            </select>
        </form>

    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي الوارد</p>
            <h3 class="text-2xl font-bold text-green-600 mt-2">
                <?= $total_in ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">إجمالي المنصرف</p>
            <h3 class="text-2xl font-bold text-red-600 mt-2">
                <?= $total_out ?>
            </h3>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6">
            <p class="text-gray-500 text-sm">المخزون الحالي (محسوب)</p>
            <h3 class="text-2xl font-bold text-blue-600 mt-2">
                <?= $current_stock ?>
            </h3>
        </div>

    </div>

    <!-- Transaction Table -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            تفاصيل الحركات
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">العنصر</th>
                        <th class="px-4 py-3">النوع</th>
                        <th class="px-4 py-3">الكمية</th>
                        <th class="px-4 py-3">المشروع</th>
                        <th class="px-4 py-3">التاريخ</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php if ($transactions->num_rows > 0): ?>
                    <?php while($row = $transactions->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['item_name']) ?>
                            </td>

                            <td class="px-4 py-3 font-semibold 
                                <?= $row['type'] === 'IN' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $row['type'] === 'IN' ? 'وارد' : 'منصرف' ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $row['quantity'] ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['project_name'] ?? '-') ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $row['transaction_date'] ?>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على حركات.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

<?php
$transactions_stmt->close();
include '../../includes/footer.php';
?>