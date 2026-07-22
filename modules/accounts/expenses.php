<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">إدارة المصروفات</h1>
        <p class="text-gray-500 text-sm mt-1">تسجيل وإدارة مصروفات المشاريع</p>
    </div>

    <!-- ===================== ADD EXPENSE FORM ===================== -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8">

        <h2 class="text-lg font-semibold text-gray-700 mb-4">إضافة مصروف</h2>

        <?php if(!empty($error)): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Project -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    المشروع *
                </label>
                <select name="project_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    <option value="">اختر المشروع</option>
                    <?php
                    $projects = $conn->query("SELECT id, name FROM projects ORDER BY name ASC");
                    while($project = $projects->fetch_assoc()):
                    ?>
                        <option value="<?= $project['id'] ?>">
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    المبلغ *
                </label>
                <input type="number" name="amount" step="0.01" min="0" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Date -->
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    التاريخ *
                </label>
                <input type="date" name="expense_date" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">
                    الوصف
                </label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"></textarea>
            </div>

            <!-- Submit -->
            <div class="md:col-span-2">
                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    إضافة مصروف
                </button>
            </div>

        </form>
    </div>

    <!-- ===================== EXPENSE TABLE ===================== -->
    <div class="bg-white rounded-2xl shadow-md p-6">

        <h2 class="text-lg font-semibold text-gray-700 mb-4">سجلات المصروفات</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">المشروع</th>
                        <th class="px-4 py-3">المبلغ</th>
                        <th class="px-4 py-3">التاريخ</th>
                        <th class="px-4 py-3">الوصف</th>
                    </tr>
                </thead>
                <tbody class="divide-y">

<?php
$result = $conn->query("
    SELECT expenses.*, projects.name AS project_name
    FROM expenses
    JOIN projects ON expenses.project_id = projects.id
    ORDER BY expenses.id DESC
");

$total_expenses = 0;

if ($result->num_rows > 0):
    while($row = $result->fetch_assoc()):
        $total_expenses += $row['amount'];
?>

                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3"><?= $row['id'] ?></td>
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['project_name']) ?></td>
                        <td class="px-4 py-3 text-red-600 font-semibold">
                            $<?= number_format($row['amount'],2) ?>
                        </td>
                        <td class="px-4 py-3"><?= $row['expense_date'] ?></td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= htmlspecialchars($row['description']) ?>
                        </td>
                    </tr>

<?php
    endwhile;
else:
?>

                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            لم يتم العثور على سجلات مصروفات.
                        </td>
                    </tr>

<?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- Total -->
        <div class="mt-6 text-left">
            <span class="text-lg font-semibold text-red-600">
                إجمالي المصروفات: $<?= number_format($total_expenses,2) ?>
            </span>
        </div>

    </div>

</div>