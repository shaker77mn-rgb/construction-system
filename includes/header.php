<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام إدارة المقاولات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        primary: '#1E293B',
                        secondary: '#F59E0B',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 font-cairo">

<!-- ================= NAVBAR ================= -->
<nav class="bg-primary shadow-lg lg:mr-64">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-end items-center h-16">

            <!-- Right Side Only -->
            <div class="relative">
                <button onclick="toggleDropdown()" 
                        class="flex items-center space-x-2 space-x-reverse text-white hover:text-secondary transition duration-200 focus:outline-none">

                    <i class="fa-solid fa-user-circle text-xl"></i>
                    <span class="hidden sm:block font-medium">
                        <?php echo $_SESSION['username'] ?? 'ضيف'; ?>
                    </span>
                    <i class="fa-solid fa-chevron-down text-sm"></i>
                </button>

                <!-- Dropdown -->
                <div id="userDropdown" 
                     class="hidden absolute left-0 mt-3 w-48 bg-white rounded-lg shadow-xl py-2 z-50 transition-all duration-200">

                    <a href="#" 
                       class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 transition">
                        <i class="fa fa-user ml-2 text-gray-500"></i> الملف الشخصي
                    </a>

                    <a href="/construction_system/logout.php" 
                       class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 transition">
                        <i class="fa fa-sign-out-alt ml-2"></i> تسجيل الخروج
                    </a>
                </div>
            </div>

        </div>
    </div>
</nav>

<!-- Dropdown Script -->
<script>
    function toggleDropdown() {
        const dropdown = document.getElementById("userDropdown");
        dropdown.classList.toggle("hidden");
    }

    window.addEventListener("click", function(e) {
        const button = document.querySelector("button");
        const dropdown = document.getElementById("userDropdown");

        if (!button.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add("hidden");
        }
    });
</script>

</body>
</html>