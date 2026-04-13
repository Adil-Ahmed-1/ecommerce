<?php
$conn = mysqli_connect("localhost", "root", "", "ecommerce");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>