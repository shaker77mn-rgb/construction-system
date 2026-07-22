<?php
require_once 'config/database.php';
require_once 'config/session.php';

$error = "";

/* ============================
   If Already Logged In
============================ */
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

/* ============================
   Handle Login Submission
============================ */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "يرجى تعبئة جميع الحقول.";
    } else {

        $stmt = $conn->prepare("
            SELECT users.id, users.username, users.password, roles.role_name
            FROM users
            JOIN roles ON users.role_id = roles.id
            WHERE users.username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (md5($password) === $user['password']) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role_name'];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
            }

        } else {
            $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول | نظام إدارة البناء</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        accent: '#F59E0B'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen flex items-center justify-center font-cairo relative">

    <!-- Background Image -->
    <div class="absolute inset-0 bg-cover bg-center"
         style="background-image: url('https://images.unsplash.com/photo-1503387762-592deb58ef4e');">
    </div>

    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-black bg-opacity-70"></div>

    <!-- Login Container -->
    <div class="relative w-full max-w-md px-6 animate-fadeIn">

        <div class="bg-white rounded-2xl shadow-2xl p-8 space-y-6">

            <!-- Logo -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-accent text-white text-2xl mb-3">
                    <i class="fa fa-building"></i>
                </div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    نظام إدارة البناء
                </h2>
                <p class="text-gray-500 text-sm mt-1">
                    تسجيل الدخول إلى حسابك
                </p>
            </div>

            <!-- Error -->
            <?php if(!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 text-sm px-4 py-3 rounded-lg">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-5" id="loginForm">

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        اسم المستخدم
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                            <i class="fa fa-user"></i>
                        </span>
                        <input type="text" name="username" required
                               class="w-full pr-10 pl-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent outline-none transition">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        كلمة المرور
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                            <i class="fa fa-lock"></i>
                        </span>

                        <input type="password" name="password" id="password" required
                               class="w-full px-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent outline-none transition">

                        <!-- Toggle -->
                        <span onclick="togglePassword()"
                              class="absolute inset-y-0 left-3 flex items-center text-gray-400 cursor-pointer">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center space-x-2 space-x-reverse">
                        <input type="checkbox" name="remember"
                               class="accent-accent w-4 h-4">
                        <span class="text-gray-600">تذكرني</span>
                    </label>
                </div>

                <!-- Button -->
                <button type="submit" id="loginBtn"
                        class="w-full bg-accent hover:bg-amber-600 text-white font-medium py-2.5 rounded-lg transition duration-200 shadow-md hover:shadow-lg flex justify-center items-center">

                    <span id="btnText">تسجيل الدخول</span>

                    <!-- Spinner -->
                    <svg id="spinner" class="hidden animate-spin h-5 w-5 mr-2 text-white"
                         xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24">
                        <circle class="opacity-25"
                                cx="12" cy="12" r="10"
                                stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75"
                              fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>

                </button>

            </form>

        </div>

        <p class="text-center text-gray-300 text-xs mt-6">
            © <?php echo date("Y"); ?> نظام إدارة البناء
        </p>

    </div>

    <!-- Scripts -->
    <script>
        // Fade In Animation
        document.querySelector('.animate-fadeIn').style.opacity = 0;
        setTimeout(() => {
            document.querySelector('.animate-fadeIn').style.transition = "opacity 0.8s ease";
            document.querySelector('.animate-fadeIn').style.opacity = 1;
        }, 100);

        // Show/Hide Password
        function togglePassword() {
            const password = document.getElementById("password");
            const icon = document.getElementById("toggleIcon");

            if (password.type === "password") {
                password.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                password.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        // Loading Spinner
        document.getElementById("loginForm").addEventListener("submit", function() {
            document.getElementById("btnText").textContent = "جارِ تسجيل الدخول...";
            document.getElementById("spinner").classList.remove("hidden");
        });
    </script>

</body>
</html>