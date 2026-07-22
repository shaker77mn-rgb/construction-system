<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';
$search  = trim($_GET['search'] ?? '');

/* ============================
   Fetch Clients (Optimized)
============================ */
$sql = "
    SELECT c.id, c.name, c.phone, c.email,
           COUNT(p.id) AS project_count
    FROM clients c
    LEFT JOIN projects p ON c.id = p.client_id
";

$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " WHERE c.name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

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

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            تم حذف العميل بنجاح.
        </div>
    <?php endif; ?>

    <?php if ($error === 'has_projects'): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            لا يمكن حذف العميل لوجود مشاريع مرتبطة به.
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                إدارة العملاء
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                إدارة ومتابعة جميع العملاء
            </p>
        </div>

        <a href="add.php"
           class="inline-flex items-center bg-amber-500 hover:bg-amber-600 text-white font-medium px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition">
            <i class="fa fa-plus ml-2"></i> إضافة عميل
        </a>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <input type="text"
                   name="search"
                   placeholder="البحث عن عميل..."
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

    <!-- Clients Table -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">الاسم</th>
                        <th class="px-4 py-3">الهاتف</th>
                        <th class="px-4 py-3">البريد الإلكتروني</th>
                        <th class="px-4 py-3 text-center">المشاريع</th>
                        <th class="px-4 py-3 text-center">الإجراءات</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $row['id'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($row['name']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['phone']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['email']) ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                    <?= $row['project_count'] ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center space-x-2 space-x-reverse">

                                <a href="edit.php?id=<?= $row['id'] ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition">
                                    <i class="fa fa-edit"></i>
                                </a>

                                <a href="delete.php?id=<?= $row['id'] ?>"
                                   onclick="return confirm('هل أنت متأكد من حذف هذا العميل؟')"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition">
                                    <i class="fa fa-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على عملاء.
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