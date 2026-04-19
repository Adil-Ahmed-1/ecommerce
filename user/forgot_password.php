<?php
session_start();
include("../backend/config/db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

date_default_timezone_set("Asia/Karachi");

if (isset($_SESSION['user_id'])) {
    header("Location: ../frontend/index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];

    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {

        // ================= USER CHECK =================
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {

            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // ================= DELETE OLD TOKEN =================
            $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
            mysqli_stmt_bind_param($del, "s", $email);
            mysqli_stmt_execute($del);

            // ================= INSERT NEW TOKEN =================
            $ins = mysqli_prepare($conn, "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, "sss", $email, $token, $expires_at);
            mysqli_stmt_execute($ins);

            // ================= RESET LINK =================
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];

            $reset_link = $base_url . "/ecommerce/user/reset_password.php?token=" . urlencode($token);

            $site_name = "Commerce";
            $user_name = htmlspecialchars($user['name']);

            $subject = "Password Reset - " . $site_name;

            // ================= EMAIL BODY =================
            $email_body = "
<div style='font-family:Arial, sans-serif; max-width:520px; margin:auto; background:#f9fafb; border-radius:16px; overflow:hidden; border:1px solid #e5e7eb;'>

    <div style='background:linear-gradient(135deg,#16b364,#0a9150); padding:30px; text-align:center; color:#fff;'>
        <h1 style='margin:0; font-size:22px;'>Password Reset Request</h1>
        <p style='margin:8px 0 0; font-size:14px; opacity:0.9;'>Secure your account in a few seconds</p>
    </div>

    <div style='padding:30px; color:#374151;'>

        <h2 style='margin-bottom:10px;'>Hello {$user_name},</h2>

        <p style='font-size:14px; line-height:1.6;'>
            We received a request to reset your password for your account.
        </p>

        <p style='font-size:14px; line-height:1.6;'>
            If you made this request, click the button below to create a new password:
        </p>

        <div style='text-align:center; margin:25px 0;'>
            <a href='{$reset_link}' 
               style='background:linear-gradient(135deg,#16b364,#0a9150); 
                      color:#fff; padding:12px 26px; 
                      text-decoration:none; border-radius:10px; 
                      font-weight:bold; display:inline-block;'>
                Reset My Password
            </a>
        </div>

        <div style='background:#edfcf2; padding:12px; border-radius:10px; font-size:12px; color:#065f46;'>
            ⚠️ This link will expire in <b>1 hour</b> for your security.
        </div>

        <p style='font-size:12px; margin-top:20px; color:#6b7280;'>
            If you did not request this, you can safely ignore this email. Your account is safe.
        </p>

    </div>

    <div style='text-align:center; padding:15px; background:#f3f4f6; font-size:11px; color:#9ca3af;'>
        © " . date('Y') . " Commerce. All rights reserved.
    </div>

</div>";

            // ================= PHPMailer =================
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ahmedadilbaloch95@gmail.com';
                $mail->Password   = 'awng jfsh iffe ygxs'; // ⚠️ secure
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // MUST SAME EMAIL
                $mail->setFrom('ahmedadilbaloch95@gmail.com', 'E-Commerce');
                $mail->addAddress($email, $user_name);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $email_body;

                $mail->send();

                $success = "Reset link sent successfully! Check inbox/spam.";

            } catch (Exception $e) {
                $error = "Email failed: " . $mail->ErrorInfo;
            }

        } else {
            // security: always same message
            $success = "If account exists, reset link sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        brand: {
          50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',
          400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',
          800:'#095c37',900:'#084b2e',950:'#032a1a'
        }
      }
    }
  }
}
</script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Plus Jakarta Sans',sans-serif; }
  .form-input { transition:border-color 0.2s ease, box-shadow 0.2s ease; }
  .form-input:focus { border-color:#16b364; box-shadow:0 0 0 3px rgba(22,179,100,0.12); outline:none; }
  .btn-brand {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s ease; position:relative; overflow:hidden;
  }
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 8px 24px -6px rgba(22,179,100,0.45); }
  .btn-brand:active { transform:translateY(0); }
  .btn-brand:disabled { opacity:0.6; cursor:not-allowed; transform:none !important; box-shadow:none !important; }

  @keyframes float {
    0%,100% { transform:translateY(0) rotate(0deg); }
    50% { transform:translateY(-20px) rotate(5deg); }
  }
  .float-1 { animation: float 6s ease-in-out infinite; }
  .float-2 { animation: float 8s ease-in-out infinite 1s; }
  .float-3 { animation: float 7s ease-in-out infinite 2s; }

  @keyframes fadeUp {
    from { opacity:0; transform:translateY(30px); }
    to { opacity:1; transform:translateY(0); }
  }
  .fade-up { animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards; }
  .fade-up-2 { animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) 0.1s forwards; opacity:0; }
  .fade-up-3 { animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) 0.2s forwards; opacity:0; }

  @keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  .spin-slow { animation: spin-slow 1s linear infinite; }
</style>
</head>

<body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-50/50 flex items-center justify-center p-4 relative overflow-hidden">

  <div class="absolute top-20 left-10 w-72 h-72 bg-brand-200/30 rounded-full blur-3xl float-1"></div>
  <div class="absolute bottom-20 right-10 w-96 h-96 bg-brand-100/40 rounded-full blur-3xl float-2"></div>
  <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-brand-300/20 rounded-full blur-3xl float-3"></div>

  <div class="w-full max-w-md relative z-10">
    
    <div class="text-center mb-8 fade-up">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-orange-500/30 mb-4">
        <i class="fa-solid fa-key text-white text-2xl"></i>
      </div>
      <h1 class="text-2xl font-extrabold text-gray-900">Forgot Password?</h1>
      <p class="text-sm text-gray-500 mt-1">No worries, we'll send you a reset link</p>
    </div>

    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-white/60 shadow-xl shadow-brand-900/5 p-8 fade-up-2">
      
      <?php if (!empty($success)) { ?>
      <div class="text-center py-4">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-brand-50 border-2 border-brand-200 mb-4">
          <i class="fa-solid fa-envelope-circle-check text-brand-500 text-2xl"></i>
        </div>
        <p class="text-sm font-medium text-gray-700 leading-relaxed"><?= $success ?></p>
        <div class="mt-6 pt-6 border-t border-gray-100">
          <a href="login.php" class="btn-brand inline-flex items-center gap-2 text-white font-bold text-sm py-3 px-6 rounded-xl">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Back to Login
          </a>
        </div>
      </div>
      <?php } else { ?>

      <?php if (!empty($error)) { ?>
      <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-6">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <p class="text-sm font-medium"><?= $error ?></p>
      </div>
      <?php } ?>

      <form method="POST" action="" id="forgotForm" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fa-solid fa-envelope text-sm"></i></span>
            <input type="email" name="email" id="fpEmail" required
              class="form-input w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 pl-11 pr-4 text-sm text-gray-800 placeholder:text-gray-400"
              placeholder="Enter your registered email">
          </div>
        </div>

        <button type="submit" id="fpBtn" class="btn-brand w-full text-white font-bold text-sm py-3.5 rounded-xl flex items-center justify-center gap-2">
          <span id="fpBtnText">
            <i class="fa-solid fa-paper-plane text-xs mr-1"></i>
            Send Reset Link
          </span>
          <span id="fpBtnLoading" class="hidden">
            <i class="fa-solid fa-spinner spin-slow text-xs mr-1"></i>
            Sending...
          </span>
        </button>
      </form>

      <div class="mt-6 pt-6 border-t border-gray-100 text-center">
        <a href="login.php" class="text-sm text-gray-500 hover:text-brand-600 transition font-medium">
          <i class="fa-solid fa-arrow-left text-[10px] mr-1"></i>
          Back to Login
        </a>
      </div>
      <?php } ?>
    </div>

    <div class="text-center mt-6 fade-up-3">
      <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> Commerce. All rights reserved.</p>
    </div>
  </div>

</body>
</html>
<script>
document.getElementById('forgotForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('fpBtn');
    var btnText = document.getElementById('fpBtnText');
    var btnLoading = document.getElementById('fpBtnLoading');
    btn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
});
</script>