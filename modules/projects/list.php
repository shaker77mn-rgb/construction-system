<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/* ============================
   Handle Search
============================ */
$search = trim($_GET['search'] ?? '');

/* ============================
   Fetch Projects with Aggregates
   (Optimized - No N+1 Queries)
============================ */
$sql = "
    SELECT 
        p.id,
        p.name,
        p.budget,
        p.status,
        c.name AS client_name,
        IFNULL(SUM(DISTINCT r.amount),0) AS total_revenue,
        IFNULL(SUM(DISTINCT e.amount),0) AS total_expenses
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN revenues r ON p.id = r.project_id
    LEFT JOIN expenses e ON p.id = e.project_id
";

$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " WHERE p.name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$sql .= " GROUP BY p.id ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                إدارة المشاريع
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                إدارة ومتابعة جميع المشاريع
            </p>
        </div>

        <a href="add.php"
           class="inline-flex items-center bg-amber-500 hover:bg-amber-600 text-white font-medium px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition">
            <i class="fa fa-plus ml-2"></i> إضافة مشروع
        </a>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <input type="text"
                   name="search"
                   placeholder="البحث عن مشروع..."
                   value="<?= htmlspecialchars($search) ?>"
                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">

            <button type="submit"
                    class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg transition">
                بحث
            </button>

            <?php if (!empty($search)): ?>
                <a href="list.php"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition">
                    إعادة ضبط
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">المشروع</th>
                        <th class="px-4 py-3">العميل</th>
                        <th class="px-4 py-3">الميزانية</th>
                        <th class="px-4 py-3">الإيرادات</th>
                        <th class="px-4 py-3">المصروفات</th>
                        <th class="px-4 py-3">الربح</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3 text-center">الإجراءات</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 

                        $profit = $row['total_revenue'] - $row['total_expenses'];

                        // Status Badge Colors
                        $status = strtolower(str_replace(' ', '-', $row['status']));
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
                            default        => htmlspecialchars($row['status'])
                        };
                    ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $row['id'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($row['name']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['client_name']) ?>
                            </td>

                            <td class="px-4 py-3">
                                $<?= number_format($row['budget'], 2) ?>
                            </td>

                            <td class="px-4 py-3 text-green-600 font-semibold">
                                $<?= number_format($row['total_revenue'], 2) ?>
                            </td>

                            <td class="px-4 py-3 text-red-600 font-semibold">
                                $<?= number_format($row['total_expenses'], 2) ?>
                            </td>

                            <td class="px-4 py-3 font-semibold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                $<?= number_format($profit, 2) ?>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                                    <?= $status_ar ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center space-x-2 space-x-reverse">

                                <a href="edit.php?id=<?= $row['id'] ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition">
                                    <i class="fa fa-edit"></i>
                                </a>

                                <a href="delete.php?id=<?= $row['id'] ?>"
                                   onclick="return confirm('حذف هذا المشروع؟')"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition">
                                    <i class="fa fa-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على مشاريع.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

<?php
$stmt->close();
include '../../includes/footer.php';
?>