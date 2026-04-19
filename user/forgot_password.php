<?php
session_start();
include("../backend/config/db.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ../frontend/index.php");
    exit;
}

 $error = "";
 $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Purane token delete karo
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            // Naya token insert karo
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token, $expires_at);
            $ins->execute();

            // Reset link banao
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
            $reset_link = $base_url . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

            $site_name = "Commerce";
            $user_name = htmlspecialchars($user['name']);

            $subject = "Password Reset - " . $site_name;

            $email_body = "
            <div style='font-family:Plus Jakarta Sans,Arial,sans-serif; max-width:480px; margin:0 auto; background:#f9fafb; border-radius:16px; overflow:hidden; border:1px solid #e5e7eb;'>
                <div style='background:linear-gradient(135deg,#16b364,#0a9150); padding:32px 24px; text-align:center;'>
                    <div style='width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;'>
                        <span style='color:#fff;font-size:22px;'>&#128273;</span>
                    </div>
                    <h1 style='color:#fff;font-size:20px;font-weight:800;margin:0;'>Password Reset</h1>
                </div>
                <div style='padding:32px 24px;'>
                    <p style='color:#374151;font-size:14px;line-height:1.6;margin:0 0 16px;'>
                        Hello <strong>{$user_name}</strong>,
                    </p>
                    <p style='color:#374151;font-size:14px;line-height:1.6;margin:0 0 24px;'>
                        We received a request to reset your password. Click the button below to set a new password. This link will expire in <strong>1 hour</strong>.
                    </p>
                    <div style='text-align:center;margin:32px 0;'>
                        <a href='{$reset_link}' style='display:inline-block;background:linear-gradient(135deg,#16b364,#0a9150);color:#fff;text-decoration:none;padding:14px 32px;border-radius:12px;font-weight:700;font-size:14px;box-shadow:0 8px 24px -6px rgba(22,179,100,0.45);'>
                            Reset My Password
                        </a>
                    </div>
                    <p style='color:#9ca3af;font-size:12px;line-height:1.5;margin:0 0 8px;'>
                        If the button doesn't work, copy and paste this link into your browser:
                    </p>
                    <p style='color:#16b364;font-size:11px;word-break:break-all;margin:0 0 24px;background:#edfcf2;padding:10px 14px;border-radius:8px;border:1px solid #aaf0c6;'>
                        {$reset_link}
                    </p>
                    <div style='border-top:1px solid #e5e7eb;padding-top:16px;margin-top:8px;'>
                        <p style='color:#9ca3af;font-size:11px;margin:0;line-height:1.5;'>
                            If you didn't request this, you can safely ignore this email. Your password won't be changed.
                        </p>
                    </div>
                </div>
                <div style='background:#f3f4f6;padding:16px 24px;text-align:center;'>
                    <p style='color:#9ca3af;font-size:11px;margin:0;'>&copy; " . date('Y') . " {$site_name}. All rights reserved.</p>
                </div>
            </div>";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";

            mail($email, $subject, $email_body, $headers);
        }

        $success = "If an account exists with this email, a reset link has been sent. Check your inbox (and spam folder).";
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