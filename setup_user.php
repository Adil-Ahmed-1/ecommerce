<?php
include("config/db.php");

        // ✅ NAYA SMART LOGIN BLOCK
        $is_valid = false;

        // 1. Check hashed password
        if ($user && password_verify($password, $user['password'])) {
            $is_valid = true;
        } 
        // 2. Fallback: Check plain text password (agar DB mein simple text hai)
        elseif ($user && $password === $user['password']) {
            $is_valid = true;
            
            // Plain text ko hash karke DB mein update kar do (security ke liye)
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_pw, $user['id']);
            mysqli_stmt_execute($update_stmt);
        }

        if ($is_valid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }

// Check if user exists
 $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    // Update password
    mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE email = '$email'");
    echo "Password updated! Login with: $email / $plain_password";
} else {
    // Insert new user
    mysqli_query($conn, "INSERT INTO users (name, email, password, image, role) VALUES ('Adil Khoso', '$email', '$hashed_password', 'about.png', 'admin')");
    echo "User created! Login with: $email / $plain_password";
}
?>