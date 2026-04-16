<?php
session_start();
session_destroy();

 $type = isset($_GET['type']) ? $_GET['type'] : 'normal';
if ($type === 'delete') {
    $msg = "Account deleted successfully.";
} else {
    $msg = "Logged out successfully.";
}
?>
<!DOCTYPE html>
<html>
<head><title>Logging Out...</title></head>
<body class="min-h-screen flex items-center justify-center bg-[#0a0f0d]">
  <div class="text-center px-6">
    <?php if ($type === 'delete') { ?>
      <div style="margin-bottom:20px">
        <div class="w-20 h-20 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center mx-auto flex items-center justify-center">
          <i class="fa-solid fa-user-xmark text-red-500 text-3xl"></i>
        </div>
      </div>
    <?php } else { ?>
      <div style="margin-bottom:20px">
        <div class="w-20 h-20 rounded-full bg-brand-100 dark:bg-brand-900 flex items-center justify-center mx-auto">
          <i class="fa-solid fa-check text-brand-500 text-3xl"></i>
        </div>
      </div>
    <?php } ?>
    <h2 class="text-2xl font-extrabold text-white"><?= $msg ?></h2>
    <p class="text-gray-500 mt-2">Redirecting to login...</p>
  </div>
</body>
</html>