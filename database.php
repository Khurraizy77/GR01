<?php
$db_server = "localhost";
$db_user = "gr01";
$db_pass = "gr01";
$db_name = "gr01";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

} catch (mysqli_sql_exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    
    die("<div style='text-align:center; margin-top:50px;'>
            <h3>Oops! We are experiencing some technical difficulties.</h3>
            <p>Please try refreshing the page in a few moments.</p>
         </div>");
}
?>
