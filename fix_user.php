<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect("127.0.0.1", "root", "");
    echo "Root connected!\n";
    // Try to recreate or alter the gr01 user
    mysqli_query($conn, "CREATE USER IF NOT EXISTS 'gr01'@'localhost' IDENTIFIED BY 'gr01'");
    mysqli_query($conn, "ALTER USER 'gr01'@'localhost' IDENTIFIED BY 'gr01'");
    mysqli_query($conn, "GRANT ALL PRIVILEGES ON *.* TO 'gr01'@'localhost'");
    
    mysqli_query($conn, "CREATE USER IF NOT EXISTS 'gr01'@'127.0.0.1' IDENTIFIED BY 'gr01'");
    mysqli_query($conn, "ALTER USER 'gr01'@'127.0.0.1' IDENTIFIED BY 'gr01'");
    mysqli_query($conn, "GRANT ALL PRIVILEGES ON *.* TO 'gr01'@'127.0.0.1'");
    
    mysqli_query($conn, "CREATE USER IF NOT EXISTS 'gr01'@'%' IDENTIFIED BY 'gr01'");
    mysqli_query($conn, "ALTER USER 'gr01'@'%' IDENTIFIED BY 'gr01'");
    mysqli_query($conn, "GRANT ALL PRIVILEGES ON *.* TO 'gr01'@'%'");
    mysqli_query($conn, "FLUSH PRIVILEGES");
    echo "User altered successfully.\n";
} catch (mysqli_sql_exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
