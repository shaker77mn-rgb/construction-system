<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin', 'Storekeeper']);

$error = '';
$success = '';

/* ============================
   Add New Item
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $item_name = trim($_POST['item_name'] ?? '');
    $quantity  = intval($_POST['quantity'] ?? 0);
    $unit      = trim($_POST['unit'] ?? '');

    if (empty($item_name) || empty($unit)) {
        $error = "Item name and unit are required.";
    } elseif ($quantity < 0) {
        $error = "Quantity cannot be negative.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO inventory_items (item_name, quantity, unit)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("sis", $item_name, $quantity, $unit);

        if ($stmt->execute()) {
            $success = "تمت إضافة العنصر بنجاح!";
        } else {
            $error = "Something went wrong while saving.";
        }

        $stmt->close();
    }
}

/* ============================
   Fetch Inventory Items
============================ */
$items_stmt = $conn->prepare("
    SELECT id, item_name, quantity, unit
    FROM inventory_items
    ORDER BY id DESC
");
$items_stmt->execute();
$items = $items_stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">
            عناصر المخزون
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            إدارة المخزون ومستوياته
        </p>
    </div>

    <!-- Add Item Card -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 max-w-3xl">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            إضافة عنصر جديد
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

            <!-- Item Name -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    اسم العنصر *
                </label>
                <input type="text"
                       name="item_name"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الكمية الأولية
                </label>
                <input type="number"
                       name="quantity"
                       min="0"
                       value="0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Unit -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الوحدة *
                </label>
                <input type="text"
                       name="unit"
                       placeholder="كجم، قطعة، متر..."
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Submit -->
            <div class="md:col-span-3">
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    إضافة عنصر
                </button>
            </div>

        </form>

    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            نظرة عامة على المخزون
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">العنصر</th>
                        <th class="px-4 py-3">الكمية</th>
                        <th class="px-4 py-3">الوحدة</th>
                        <th class="px-4 py-3 text-center">الحالة</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php if ($items->num_rows > 0): ?>
                    <?php while ($row = $items->fetch_assoc()): 

                        $low_stock = $row['quantity'] < 10;
                    ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $row['id'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($row['item_name']) ?>
                            </td>

                            <td class="px-4 py-3 font-semibold <?= $low_stock ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $row['quantity'] ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['unit']) ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($low_stock): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                        مخزون منخفض
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                        متاح
                                    </span>
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على عناصر.
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