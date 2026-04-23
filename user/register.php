<?php
session_start();
include("../backend/config/db.php"); // DB Connection

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

 $error = "";
 $post_name = "";
 $post_email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_name  = trim($_POST['full_name']);
    $post_email = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $confirm    = trim($_POST['confirm_password']);

    if (empty($post_name) || empty($post_email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $post_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $ins = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $ins->bind_param("ssss", $post_name, $post_email, $hashed, $role);

            if ($ins->execute()) {
                header("Location: login.php?msg=registered");
                exit;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - ShopEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        brand: {
                            500: '#16b364',
                            600: '#0a9150',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Centering the form over background */
        .bg-wrapper {
            background-image: url('https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen relative flex items-center justify-center p-4">

    <!-- FULL BACKGROUND IMAGE -->
    <div class="absolute inset-0 bg-wrapper z-0"></div>

    <!-- DARK OVERLAY (Taake white form pehle aaye) -->
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm z-10"></div>

    <!-- CENTERED COMPACT FORM CARD -->
    <div class="relative z-20 w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden fade-up">
        
        <!-- Header / Logo Area -->
        <div class="bg-white p-8 text-center border-b border-gray-100">
            <div class="w-12 h-12 mx-auto bg-brand-500 text-white rounded-full flex items-center justify-center text-xl shadow-lg mb-3">
                <i class="fa-solid fa-cart-plus"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Create Account</h2>
            <p class="text-sm text-gray-500 mt-1">Join ShopEase today</p>
        </div>

        <!-- Form Body -->
        <div class="p-8">
            <?php if(!empty($error)): ?>
            <div class="bg-red-50 text-red-600 px-4 py-2 rounded-lg text-xs font-bold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($post_name) ?>" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:border-brand-500 focus:bg-white focus:outline-none transition-colors text-sm text-gray-800"
                        placeholder="John Doe">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($post_email) ?>" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:border-brand-500 focus:bg-white focus:outline-none transition-colors text-sm text-gray-800"
                        placeholder="name@example.com">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:border-brand-500 focus:bg-white focus:outline-none transition-colors text-sm text-gray-800"
                        placeholder="••••••••">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Confirm</label>
                    <input type="password" name="confirm_password" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:border-brand-500 focus:bg-white focus:outline-none transition-colors text-sm text-gray-800"
                        placeholder="••••••••">
                </div>

                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-brand-500/30 transition-transform active:scale-95 mt-2">
                    CREATE ACCOUNT
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    Already have an account? 
                    <a href="login.php" class="font-bold text-brand-600 hover:underline">Login</a>
                </p>
            </div>
        </div>

        <!-- Footer / Terms -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-[10px] text-gray-400">
                By signing up, you agree to our <a href="#" class="text-gray-600 hover:underline">Terms</a> and <a href="#" class="text-gray-600 hover:underline">Privacy Policy</a>.
            </p>
        </div>
    </div>

</body>
</html>