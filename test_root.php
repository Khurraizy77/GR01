<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect("127.0.0.1", "root", "");
    echo "Root connected!\n";
    $res = mysqli_query($conn, "SHOW DATABASES");
    while($row = mysqli_fetch_assoc($res)) {
        echo $row['Database'] . "\n";
    }
} catch (mysqli_sql_exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
