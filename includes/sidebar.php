<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-40 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed top-0 right-0 h-full w-64 bg-slate-900 text-gray-300 shadow-xl transform translate-x-full lg:translate-x-0 transition-transform duration-300 z-40">

    <!-- Logo -->
    <div class="flex items-center justify-center h-16 border-b border-slate-700">
        <h2 class="text-xl font-semibold text-white tracking-wide">
            <i class="fa fa-building text-amber-400 ml-2"></i> لوحة التحكم
        </h2>
    </div>

    <!-- Menu -->
    <nav class="p-4 space-y-2 overflow-y-auto h-[calc(100%-4rem)]">

        <!-- Dashboard -->
        <a href="/construction_system/dashboard.php"
           class="flex items-center px-4 py-2 rounded-lg transition-all duration-200
           <?= ($current_page == 'dashboard.php' || $current_page == 'dashboard.php.php') ? 'bg-amber-500 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa fa-home ml-3"></i>
            <span>الرئيسية</span>
        </a>

        <!-- ================= ADMIN ================= -->
        <?php if($role == 'Admin'): ?>

            <?php sidebarLink("users/list.php","fa-users","إدارة المستخدمين",$current_page); ?>
            <?php sidebarLink("reports/financial_report.php","fa-chart-line","التقارير الشاملة",$current_page); ?>
            <?php sidebarLink("clients/list.php","fa-user-tie","العملاء",$current_page); ?>
            <?php sidebarLink("projects/list.php","fa-building","المشاريع",$current_page); ?>
            <?php sidebarLink("accounts/revenues.php","fa-coins","الحسابات",$current_page); ?>
            <?php sidebarLink("inventory/items.php","fa-boxes","المخزون",$current_page); ?>

        <?php endif; ?>

        <!-- ================= ACCOUNTANT ================= -->
        <?php if($role == 'Accountant'): ?>

            <?php sidebarLink("accounts/revenues.php","fa-coins","الإيرادات",$current_page); ?>
            <?php sidebarLink("accounts/expenses.php","fa-money-bill","المصروفات",$current_page); ?>
            <?php sidebarLink("reports/financial_report.php","fa-chart-line","التقارير المالية",$current_page); ?>

        <?php endif; ?>

        <!-- ================= ENGINEER ================= -->
        <?php if($role == 'Engineer'): ?>

            <?php sidebarLink("projects/list.php","fa-building","متابعة المشاريع",$current_page); ?>
            <?php sidebarLink("reports/project_report.php","fa-file-alt","تقارير الإنجاز",$current_page); ?>

        <?php endif; ?>

        <!-- ================= STOREKEEPER ================= -->
        <?php if($role == 'Storekeeper'): ?>

            <?php sidebarLink("inventory/items.php","fa-boxes","عناصر المخزون",$current_page); ?>
            <?php sidebarLink("inventory/transactions.php","fa-exchange-alt","حركة المخزون",$current_page); ?>
            <?php sidebarLink("inventory/stock_report.php","fa-clipboard-list","تقرير المخزون",$current_page); ?>

        <?php endif; ?>

        <!-- ================= NORMAL USER ================= -->
        <?php if($role == 'User'): ?>

            <?php sidebarLink("projects/list.php","fa-building","عرض المشاريع",$current_page); ?>

        <?php endif; ?>

        <!-- Logout -->
        <a href="/construction_system/logout.php"
           class="flex items-center px-4 py-2 mt-4 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
            <i class="fa fa-sign-out-alt ml-3"></i>
            <span>تسجيل الخروج</span>
        </a>

    </nav>
</aside>

<!-- Mobile Toggle Button -->
<button onclick="toggleSidebar()" 
    class="lg:hidden fixed top-4 right-4 z-50 bg-amber-500 text-white p-2 rounded-md shadow-lg">
    <i class="fa fa-bars"></i>
</button>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    sidebar.classList.toggle("translate-x-full");
    overlay.classList.toggle("hidden");
}
</script>

<?php
function sidebarLink($path, $icon, $label, $current_page){

    // Get current full URL path
    $current_url = $_SERVER['REQUEST_URI'];

    // Build full link URL
    $full_link = "/construction_system/modules/".$path;

    // Check if current URL matches this link
    $active = strpos($current_url, $full_link) !== false;

    $classes = $active
        ? "bg-amber-500 text-white shadow-md"
        : "hover:bg-slate-800 hover:text-white";

    echo '
    <a href="'.$full_link.'"
       class="flex items-center px-4 py-2 rounded-lg transition-all duration-200 '.$classes.'">
        <i class="fa '.$icon.' ml-3"></i>
        <span>'.$label.'</span>
    </a>';
}
?>