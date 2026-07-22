<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
/* ============================
   Fetch Statistics
============================ */

// Total Clients
$clients = $conn->query("SELECT COUNT(*) AS total FROM clients")->fetch_assoc()['total'];

// Total Projects
$projects = $conn->query("SELECT COUNT(*) AS total FROM projects")->fetch_assoc()['total'];

// Total Revenue
$revenue = $conn->query("SELECT IFNULL(SUM(amount),0) AS total FROM revenues")->fetch_assoc()['total'];

// Total Expenses
$expenses = $conn->query("SELECT IFNULL(SUM(amount),0) AS total FROM expenses")->fetch_assoc()['total'];

// Profit
$profit = $revenue - $expenses;

// Low Stock Items Count
$low_stock = $conn->query("SELECT COUNT(*) AS total FROM inventory_items WHERE quantity < 10")->fetch_assoc()['total'];

// Recent Projects
$recent_projects = $conn->query("SELECT name, status, start_date FROM projects ORDER BY id DESC LIMIT 5");

// Low Stock Items List
$low_stock_items = $conn->query("SELECT item_name, quantity FROM inventory_items WHERE quantity < 10 LIMIT 5");
?>

<div class="lg:mr-64 p-6 min-h-screen bg-gray-100">

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-gray-800">الرئيسية</h1>
        <p class="text-gray-500 mt-1">نظرة عامة على أداء النظام</p>
    </div>

    <!-- ===================== STATS CARDS ===================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">

        <!-- Card Template -->
        <?php
        function statCard($title, $value, $icon, $bg, $text) {
            echo "
            <div class='bg-white rounded-2xl shadow-md p-6 flex items-center justify-between hover:shadow-xl transition duration-300'>
                <div>
                    <p class='text-gray-500 text-sm'>$title</p>
                    <h3 class='text-2xl font-bold mt-1 $text'>$value</h3>
                </div>
                <div class='w-12 h-12 flex items-center justify-center rounded-xl $bg $text text-xl'>
                    <i class='fa $icon'></i>
                </div>
            </div>";
        }

        statCard("إجمالي العملاء", $clients, "fa-user-tie", "bg-blue-100", "text-blue-600");
        statCard("إجمالي المشاريع", $projects, "fa-building", "bg-green-100", "text-green-600");
        statCard("إجمالي الإيرادات", "$".number_format($revenue,2), "fa-coins", "bg-emerald-100", "text-emerald-600");
        statCard("إجمالي المصروفات", "$".number_format($expenses,2), "fa-money-bill", "bg-red-100", "text-red-600");
        statCard("صافي الربح", "$".number_format($profit,2), "fa-chart-line", "bg-purple-100", ($profit>=0 ? "text-green-600":"text-red-600"));
        statCard("المخزون المنخفض", $low_stock, "fa-exclamation-triangle", "bg-orange-100", "text-orange-600");
        ?>

    </div>

    <!-- ===================== CHART SECTION ===================== -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-10">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">الإيرادات مقابل المصروفات</h2>
        <canvas id="financeChart" height="100"></canvas>
    </div>

    <!-- ===================== TABLES SECTION ===================== -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <!-- Recent Projects -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">أحدث المشاريع</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">المشروع</th>
                            <th class="px-4 py-3">الحالة</th>
                            <th class="px-4 py-3">تاريخ البدء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php while($row = $recent_projects->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs 
                                    <?= $row['status']=='Completed' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' ?>">
                                    <?= $row['status']=='Completed' ? 'مكتمل' : 'قيد التنفيذ' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['start_date']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">عناصر المخزون المنخفضة</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">العنصر</th>
                            <th class="px-4 py-3">الكمية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php while($item = $low_stock_items->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td class="px-4 py-3 text-red-600 font-semibold">
                                <?= $item['quantity'] ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- ===================== CHART SCRIPT ===================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('financeChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['الإيرادات', 'المصروفات'],
        datasets: [{
            label: 'المبلغ ($)',
            data: [<?= $revenue ?>, <?= $expenses ?>],
            backgroundColor: [
                'rgba(16, 185, 129, 0.7)',
                'rgba(239, 68, 68, 0.7)'
            ],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>