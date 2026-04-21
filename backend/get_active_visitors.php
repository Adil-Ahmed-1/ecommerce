<?php
include('config/db.php');
header('Content-Type: application/json');

mysqli_query($conn, "DELETE FROM active_visitors WHERE last_activity < NOW() - INTERVAL 1 Hour");

 $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM active_visitors");
 $row = mysqli_fetch_assoc($r);

echo json_encode(['active' => (int)$row['total']]);