<?php
session_start();
include("../backend/config/db.php");

if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

 $token = isset($_GET['token']) ? trim($_GET['token']) : '';
 $error = "";
 $success = "";
 $token_valid = false;
 $reset_email = "";

if (empty($token)) {
    $error = "Invalid or missing reset token.";
} else {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if (!$reset) {
        $error = "This reset link is invalid or expired. <a href='forgot_password.php' class='underline font-semibold'>Request a new one</a>.";
    } else {
        $token_valid = true;
        $reset_email = $reset['email'];
    }
}

if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hashed, $reset_email);
        $upd->execute();

        // Token delete
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param("s", $reset_email);
        $del->execute();

        // ✅ User ka naam nikalo email ke liye
        $nameStmt = $conn->prepare("SELECT name FROM users WHERE email = ?");
        $nameStmt->bind_param("s", $reset_email);
        $nameStmt->execute();
        $nameRes = $nameStmt->get_result();
        $userRow = $nameRes->fetch_assoc();
        $user_name = $userRow ? htmlspecialchars($userRow['name']) : "User";

        // ✅ Confirmation email bhejo
        $site_name = "Commerce";
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $login_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/login.php";

        $subject = "Password Changed Successfully - " . $site_name;

        $email_body = "
        <div style='font-family:Plus Jakarta Sans,Arial,sans-serif; max-width:480px; margin:0 auto; background:#f9fafb; border-radius:16px; overflow:hidden; border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#16b364,#0a9150); padding:32px 24px; text-align:center;'>
                <div style='width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;'>
                    <span style='color:#fff;font-size:22px;'>&#9989;</span>
                </div>
                <h1 style='color:#fff;font-size:20px;font-weight:800;margin:0;'>Password Changed</h1>
            </div>
            <div style='padding:32px 24px;'>
                <p style='color:#374151;font-size:14px;line-height:1.6;margin:0 0 16px;'>
                    Hello <strong>{$user_name}</strong>,
                </p>
                <p style='color:#374151;font-size:14px;line-height:1.6;margin:0 0 24px;'>
                    Your password has been successfully changed. If you did not make this change, please contact us immediately.
                </p>
                <div style='background:#edfcf2;border:1px solid #aaf0c6;border-radius:12px;padding:16px;margin:24px 0;'>
                    <div style='display:flex;align-items:center;gap:10px;'>
                        <div style='width:36px;height:36px;background:#16b364;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;'>
                            <span style='color:#fff;font-size:16px;'>&#128274;</span>
                        </div>
                        <div>
                            <p style='color:#087442;font-size:13px;font-weight:700;margin:0 0 2px;'>Account Secured</p>
                            <p style='color:#0a9150;font-size:12px;margin:0;'>Your account is protected with the new password</p>
                        </div>
                    </div>
                </div>
                <div style='text-align:center;margin:28px 0;'>
                    <a href='{$login_link}' style='display:inline-block;background:linear-gradient(135deg,#16b364,#0a9150);color:#fff;text-decoration:none;padding:14px 32px;border-radius:12px;font-weight:700;font-size:14px;box-shadow:0 8px 24px -6px rgba(22,179,100,0.45);'>
                        Sign In to Your Account
                    </a>
                </div>
                <div style='border-top:1px solid #e5e7eb;padding-top:16px;margin-top:8px;'>
                    <p style='color:#9ca3af;font-size:11px;margin:0 0 4px;line-height:1.5;'>
                        <strong>When did this happen?</strong>
                    </p>
                    <p style='color:#6b7280;font-size:12px;margin:0;line-height:1.5;'>
                        " . date('l, F j, Y') . " at " . date('g:i A') . "
                    </p>
                </div>
                <div style='border-top:1px solid #e5e7eb;padding-top:16px;margin-top:16px;'>
                    <p style='color:#9ca3af;font-size:11px;margin:0;line-height:1.5;'>
                        If you didn't change your password, your account may be compromised. Please contact support immediately.
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

        mail($reset_email, $subject, $email_body, $headers);

        $token_valid = false;
        $success = "Your password has been reset successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
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

  @keyframes checkmark-pop {
    0% { transform: scale(0); opacity:0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity:1; }
  }
  .checkmark-anim { animation: checkmark-pop 0.5s cubic-bezier(.4,0,.2,1) forwards; }

  .pw-strength-bar { transition: width 0.3s ease, background 0.3s ease; }
</style>
</head>

<body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-50/50 flex items-center justify-center p-4 relative overflow-hidden">

  <div class="absolute top-20 left-10 w-72 h-72 bg-brand-200/30 rounded-full blur-3xl float-1"></div>
  <div class="absolute bottom-20 right-10 w-96 h-96 bg-brand-100/40 rounded-full blur-3xl float-2"></div>
  <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-brand-300/20 rounded-full blur-3xl float-3"></div>

  <div class="w-full max-w-md relative z-10">
    
    <div class="text-center mb-8 fade-up">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 shadow-lg shadow-brand-500/30 mb-4">
        <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
      </div>
      <h1 class="text-2xl font-extrabold text-gray-900">Reset Password</h1>
      <p class="text-sm text-gray-500 mt-1">Enter your new password below</p>
    </div>

    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-white/60 shadow-xl shadow-brand-900/5 p-8 fade-up-2">

      <!-- ✅ SUCCESS -->
      <?php if (!empty($success)) { ?>
      <div class="text-center py-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-brand-50 border-2 border-brand-200 mb-4 checkmark-anim">
          <i class="fa-solid fa-check text-brand-500 text-3xl"></i>
        </div>
        <p class="text-lg font-bold text-gray-900 mb-1">Password Updated!</p>
        <p class="text-sm text-gray-500 mb-2"><?= $success ?></p>
        
        <!-- ✅ Email confirmation note -->
        <div class="flex items-center justify-center gap-2 bg-brand-50 border border-brand-100 rounded-xl px-4 py-3 mb-6">
          <i class="fa-solid fa-envelope text-brand-500 text-sm"></i>
          <p class="text-xs text-brand-700 font-medium">Confirmation email sent to your inbox</p>
        </div>

        <a href="login.php?msg=password_changed" class="btn-brand inline-flex items-center gap-2 text-white font-bold text-sm py-3 px-8 rounded-xl">
          <i class="fa-solid fa-right-to-bracket text-xs"></i>
          Sign In Now
        </a>
      </div>

      <!-- ❌ INVALID / EXPIRED TOKEN -->
      <?php } elseif (!$token_valid && !empty($error)) { ?>
      <div class="text-center py-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50 border-2 border-red-200 mb-4">
          <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl"></i>
        </div>
        <p class="text-sm text-red-600 leading-relaxed mb-6"><?= $error ?></p>
        <a href="forgot_password.php" class="btn-brand inline-flex items-center gap-2 text-white font-bold text-sm py-3 px-6 rounded-xl">
          <i class="fa-solid fa-rotate-right text-xs"></i>
          Request New Link
        </a>
      </div>

      <!-- 🔐 RESET FORM -->
      <?php } else { ?>

      <?php if (!empty($error)) { ?>
      <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-6">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <p class="text-sm font-medium"><?= $error ?></p>
      </div>
      <?php } ?>

      <form method="POST" action="" id="resetForm" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">New Password</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fa-solid fa-lock text-sm"></i></span>
            <input type="password" name="new_password" id="newPw" required minlength="6"
              class="form-input w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 pl-11 pr-11 text-sm text-gray-800 placeholder:text-gray-400"
              placeholder="Minimum 6 characters" oninput="checkStrength(this.value)">
            <button type="button" onclick="togglePw('newPw', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-500 transition">
              <i class="fa-solid fa-eye text-sm"></i>
            </button>
          </div>
          <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div id="pwStrengthBar" class="pw-strength-bar h-full rounded-full" style="width:0%; background:#e5e7eb;"></div>
          </div>
          <p id="pwStrengthText" class="text-[11px] mt-1 text-gray-400"></p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Confirm New Password</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fa-solid fa-lock text-sm"></i></span>
            <input type="password" name="confirm_password" id="confirmPw" required minlength="6"
              class="form-input w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 pl-11 pr-11 text-sm text-gray-800 placeholder:text-gray-400"
              placeholder="Re-enter your password">
            <button type="button" onclick="togglePw('confirmPw', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-500 transition">
              <i class="fa-solid fa-eye text-sm"></i>
            </button>
          </div>
          <p id="matchText" class="text-[11px] mt-1 text-gray-400"></p>
        </div>

        <button type="submit" id="resetBtn" class="btn-brand w-full text-white font-bold text-sm py-3.5 rounded-xl flex items-center justify-center gap-2">
          <span id="resetBtnText">
            <i class="fa-solid fa-check-circle text-xs mr-1"></i>
            Update Password
          </span>
          <span id="resetBtnLoading" class="hidden">
            <i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i>
            Updating...
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
function togglePw(id, btn) {
  var input = document.getElementById(id);
  var icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

function checkStrength(pw) {
  var bar = document.getElementById('pwStrengthBar');
  var text = document.getElementById('pwStrengthText');
  var score = 0;

  if (pw.length >= 6) score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  var levels = [
    { width:'0%', bg:'#e5e7eb', label:'', color:'#9ca3af' },
    { width:'20%', bg:'#ef4444', label:'Very Weak', color:'#ef4444' },
    { width:'40%', bg:'#f97316', label:'Weak', color:'#f97316' },
    { width:'60%', bg:'#eab308', label:'Fair', color:'#eab308' },
    { width:'80%', bg:'#22c55e', label:'Strong', color:'#22c55e' },
    { width:'100%', bg:'#16b364', label:'Very Strong', color:'#16b364' }
  ];

  if (pw.length === 0) score = 0;
  bar.style.width = levels[score].width;
  bar.style.background = levels[score].bg;
  text.textContent = levels[score].label;
  text.style.color = levels[score].color;
}

var confirmInput = document.getElementById('confirmPw');
if (confirmInput) {
  confirmInput.addEventListener('input', function() {
    var newPw = document.getElementById('newPw').value;
    var matchText = document.getElementById('matchText');
    if (this.value.length === 0) {
      matchText.textContent = '';
    } else if (this.value === newPw) {
      matchText.textContent = '✓ Passwords match';
      matchText.style.color = '#16b364';
    } else {
      matchText.textContent = '✗ Passwords do not match';
      matchText.style.color = '#ef4444';
    }
  });
}

document.getElementById('resetForm')?.addEventListener('submit', function() {
  var btn = document.getElementById('resetBtn');
  document.getElementById('resetBtnText').classList.add('hidden');
  document.getElementById('resetBtnLoading').classList.remove('hidden');
  btn.disabled = true;
});
</script>