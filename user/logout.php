<?php
session_start();

/* SESSION destroy */
session_unset();
session_destroy();

/* Redirect to login page */
header("Location: login.php");
exit;
?>