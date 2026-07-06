<?php
$db_server = "localhost";
$db_user = "gr01";
$db_pass = "gr01";
$db_name = "gr01";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
    echo "Connected successfully";
} catch (mysqli_sql_exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
