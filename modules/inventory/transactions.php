<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Storekeeper']);

$error = '';
$success = '';

/* ============================
   Handle Transaction
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $item_id    = intval($_POST['item_id'] ?? 0);
    $project_id = !empty($_POST['project_id']) ? intval($_POST['project_id']) : null;
    $quantity   = intval($_POST['quantity'] ?? 0);
    $type       = $_POST['type'] ?? '';
    $date       = $_POST['transaction_date'] ?? '';

    if ($item_id <= 0 || $quantity <= 0 || empty($type) || empty($date)) {
        $error = "يجب ملء جميع الحقول المطلوبة.";
    } elseif ($type === 'OUT' && empty($project_id)) {
        $error = "المشروع مطلوب لمعاملات الصرف.";
    } else {

        $conn->begin_transaction();

        try {
            // Lock stock row
            $stock_stmt = $conn->prepare("
                SELECT quantity FROM inventory_items 
                WHERE id = ? FOR UPDATE
            ");
            $stock_stmt->bind_param("i", $item_id);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();

            if ($stock_result->num_rows !== 1) {
                throw new Exception("العنصر غير موجود.");
            }

            $current_stock = $stock_result->fetch_assoc()['quantity'];
            $stock_stmt->close();

            if ($type === "OUT" && $quantity > $current_stock) {
                throw new Exception("المخزون المتوفر غير كافٍ!");
            }

            // Insert transaction
            $insert_stmt = $conn->prepare("
                INSERT INTO inventory_transactions
                (item_id, project_id, quantity, type, transaction_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $insert_stmt->bind_param(
                "iiissi",
                $item_id,
                $project_id,
                $quantity,
                $type,
                $date,
                $_SESSION['user_id']
            );

            if (!$insert_stmt->execute()) {
                throw new Exception("فشلت المعاملة.");
            }

            $insert_stmt->close();

            // Update stock
            $new_quantity = ($type === "IN")
                ? $current_stock + $quantity
                : $current_stock - $quantity;

            $update_stmt = $conn->prepare("
                UPDATE inventory_items
                SET quantity = ?
                WHERE id = ?
            ");

            $update_stmt->bind_param("ii", $new_quantity, $item_id);

            if (!$update_stmt->execute()) {
                throw new Exception("فشل تحديث المخزون.");
            }

            $update_stmt->close();

            $conn->commit();
            $success = "تمت المعاملة بنجاح!";

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

/* ============================
   Fetch Items & Projects
============================ */
$items = $conn->query("SELECT id, item_name FROM inventory_items ORDER BY item_name ASC");
$projects = $conn->query("SELECT id, name FROM projects ORDER BY name ASC");

/* ============================
   Fetch Transaction History
============================ */
$history = $conn->query("
    SELECT t.*, i.item_name, p.name AS project_name
    FROM inventory_transactions t
    JOIN inventory_items i ON t.item_id = i.id
    LEFT JOIN projects p ON t.project_id = p.id
    ORDER BY t.id DESC
");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">
            حركة المخزون
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            إدارة حركة المخزون (وارد / منصرف)
        </p>
    </div>

    <!-- Transaction Form -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 max-w-4xl">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            معاملة جديدة
        </h3>

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

        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">العنصر *</label>
                <select name="item_id" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
                    <option value="">اختر العنصر</option>
                    <?php while($item = $items->fetch_assoc()): ?>
                        <option value="<?= $item['id'] ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">نوع المعاملة *</label>
                <select name="type" required
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
                    <option value="IN">وارد (إضافة مخزون)</option>
                    <option value="OUT">منصرف (صرف للمشروع)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    المشروع (مطلوب للمنصرف)
                </label>
                <select name="project_id"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
                    <option value="">اختر المشروع</option>
                    <?php while($project = $projects->fetch_assoc()): ?>
                        <option value="<?= $project['id'] ?>">
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">الكمية *</label>
                <input type="number" name="quantity" min="1" required
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">التاريخ *</label>
                <input type="date" name="transaction_date" required
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500">
            </div>

            <div class="md:col-span-3">
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg shadow transition">
                    تنفيذ المعاملة
                </button>
            </div>

        </form>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            سجل الحركات
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
                <?php while($row = $history->fetch_assoc()): ?>
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
                </tbody>

            </table>
        </div>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>