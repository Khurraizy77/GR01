<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'database.php';

$payload = json_decode(file_get_contents('php://input'), true);
$matric_no = trim($payload['matric_no'] ?? '');
$gender = strtoupper(trim($payload['gender'] ?? ''));

if ($matric_no === '' || !in_array($gender, ['MALE', 'FEMALE', 'POSTPONED'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid voice gender payload']);
    exit;
}

try {
    $stmt = mysqli_prepare($conn, "SELECT matric_no FROM evaluations WHERE matric_no = ?");
    mysqli_stmt_bind_param($stmt, "s", $matric_no);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        $stmt = mysqli_prepare($conn, "UPDATE evaluations SET cbr_gender = ? WHERE matric_no = ?");
        mysqli_stmt_bind_param($stmt, "ss", $gender, $matric_no);
    } else {
        $abr_status = 'PASS';
        $tbr_status = 'FAIL';
        $score = 0.00;
        $stmt = mysqli_prepare($conn, "INSERT INTO evaluations (matric_no, abr_status, tbr_status, cbr_gender, score) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssd", $matric_no, $abr_status, $tbr_status, $gender, $score);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['success' => true, 'gender' => $gender]);
} catch (mysqli_sql_exception $e) {
    error_log("Save Voice Gender Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
