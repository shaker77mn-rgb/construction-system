<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

checkRole(['Admin']);

/* ============================
   Fetch Users
============================ */
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.full_name, u.created_at,
           r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id DESC
");
$stmt->execute();
$users = $stmt->get_result();
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar.php'; ?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">إدارة المستخدمين</h1>
            <p class="text-gray-500 text-sm mt-1">
                إدارة مستخدمي النظام وصلاحياتهم
            </p>
        </div>

        <a href="add.php"
           class="inline-flex items-center bg-amber-500 hover:bg-amber-600 text-white font-medium px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition">
            <i class="fa fa-plus ml-2"></i> إضافة مستخدم
        </a>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">اسم المستخدم</th>
                        <th class="px-4 py-3">الاسم الكامل</th>
                        <th class="px-4 py-3">الصلاحية</th>
                        <th class="px-4 py-3">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-center">الإجراءات</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                <?php if ($users->num_rows > 0): ?>
                    <?php while ($row = $users->fetch_assoc()): ?>

                        <?php
                        // Role badge colors
                        $role = strtolower($row['role_name']);
                        $badgeClass = match($role) {
                            'admin'       => 'bg-red-100 text-red-600',
                            'accountant'  => 'bg-blue-100 text-blue-600',
                            'engineer'    => 'bg-green-100 text-green-600',
                            'storekeeper' => 'bg-purple-100 text-purple-600',
                            default       => 'bg-gray-100 text-gray-600'
                        };
                        $role_ar = match($role) {
                            'admin'       => 'مدير النظام',
                            'accountant'  => 'محاسب',
                            'engineer'    => 'مهندس',
                            'storekeeper' => 'أمين مخزن',
                            default       => htmlspecialchars($row['role_name'])
                        };
                        ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3">
                                <?= $row['id'] ?>
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($row['username']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($row['full_name']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>">
                                    <?= $role_ar ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                <?= date("Y-m-d", strtotime($row['created_at'])) ?>
                            </td>

                            <td class="px-4 py-3 text-center space-x-2 space-x-reverse">

                                <!-- Edit -->
                                <a href="edit.php?id=<?= $row['id'] ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition">
                                    <i class="fa fa-edit"></i>
                                </a>

                                <!-- Delete (Prevent self delete) -->
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <a href="delete.php?id=<?= $row['id'] ?>"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')"
                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على مستخدمين.
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