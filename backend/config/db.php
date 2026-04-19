<?php
$conn = mysqli_connect("localhost", "root", "", "ecommerce_v2");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>
