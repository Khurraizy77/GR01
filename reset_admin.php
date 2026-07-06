<?php
require_once 'database.php';

$hash = password_hash('admin123', PASSWORD_DEFAULT);
$query = "UPDATE admins SET password=? WHERE email='admin@example.com'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $hash);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin password has been reset to: <b>admin123</b><br>";
    echo "<a href='login.php'>Click here to login</a>";
} else {
    echo "Failed to reset password.";
}
?>
