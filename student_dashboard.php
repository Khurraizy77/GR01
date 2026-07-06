<?php
session_start();

if (!isset($_SESSION['is_student']) || $_SESSION['is_student'] !== true) {
    header("Location: student_login.php");
    exit;
}

require_once 'database.php';

$matric_no = $_SESSION['student_matric'];
$student = null;
$submission = null;
$evaluation = null;
$open_competitions = 0;

try {
    $stmt = mysqli_prepare($conn, "SELECT * FROM participants WHERE matric_no = ?");
    mysqli_stmt_bind_param($stmt, "s", $matric_no);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT * FROM submissions WHERE matric_no = ?");
    mysqli_stmt_bind_param($stmt, "s", $matric_no);
    mysqli_stmt_execute($stmt);
    $submission = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT * FROM evaluations WHERE matric_no = ?");
    mysqli_stmt_bind_param($stmt, "s", $matric_no);
    mysqli_stmt_execute($stmt);
    $evaluation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM competitions WHERE status = 'OPEN'");
    if ($row = mysqli_fetch_assoc($result)) {
        $open_competitions = $row['total'];
    }
} catch (mysqli_sql_exception $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - CCMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { height: 100%; background-color: #f6f7fb; font-family: sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; align-items: stretch; height: 100vh; }
        #sidebar { min-width: 260px; max-width: 260px; background-color: #172033; color: #fff; transition: all 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        #sidebar.active { margin-left: -260px; }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-profile { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); }
        #sidebar ul a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 15px; font-size: 1.1rem; padding: 12px 25px; transition: 0.2s; }
        #sidebar ul a:hover, #sidebar ul a.active { color: #fff; background: rgba(255, 255, 255, 0.1); border-radius: 8px; margin: 0 10px; }
        #content { width: 100%; overflow-y: auto; }
        .navbar-custom { background-color: #fff; padding: 15px 20px; }
        .header-banner { padding: 20px 30px; margin-bottom: 10px; }
        .metric-card, .panel-card { background: white; border-radius: 12px; padding: 25px; height: 100%; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="wrapper">
    <?php $current_page = 'student_dashboard.php'; include 'student_sidebar.php'; ?>
    <div id="content">
        <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
            <button type="button" id="sidebarCollapse" class="btn p-0 border-0 fs-4 text-secondary"><i class="fa-solid fa-bars"></i></button>
            <a href="student_logout.php" class="text-secondary text-decoration-none d-flex align-items-center gap-2 fs-5"><i class="fa-solid fa-lock"></i> Logout</a>
        </nav>
        <div class="header-banner">
            <h3 class="fw-bold m-0">Welcome, <?php echo htmlspecialchars($student['name'] ?? $_SESSION['student_name']); ?></h3>
        </div>
        <div class="container-fluid px-4 pb-5">
            <div class="row g-4 mb-4">
                <div class="col-md-4"><div class="metric-card"><div class="text-muted small fw-bold">Open Competitions</div><h1 class="fw-bold mb-0"><?php echo $open_competitions; ?></h1></div></div>
                <div class="col-md-4"><div class="metric-card"><div class="text-muted small fw-bold">Submission Status</div><h3 class="fw-bold mb-0"><?php echo $submission ? 'Submitted' : 'Pending'; ?></h3></div></div>
                <div class="col-md-4"><div class="metric-card"><div class="text-muted small fw-bold">Score</div><h1 class="fw-bold mb-0"><?php echo number_format($evaluation['score'] ?? 0, 2); ?></h1></div></div>
            </div>
            <div class="panel-card">
                <h4 class="fw-bold mb-3">Latest Submission</h4>
                <?php if ($submission): ?>
                    <div class="row">
                        <div class="col-md-4"><span class="text-muted small">Title</span><div class="fw-semibold"><?php echo htmlspecialchars($submission['song_title'] ?? 'Untitled'); ?></div></div>
                        <div class="col-md-4"><span class="text-muted small">Format</span><div class="fw-semibold"><?php echo htmlspecialchars(strtoupper($submission['audio_extension'])); ?></div></div>
                        <div class="col-md-4"><span class="text-muted small">Submitted</span><div class="fw-semibold"><?php echo htmlspecialchars($submission['submitted_at']); ?></div></div>
                    </div>
                <?php else: ?>
                    <div class="text-muted">You have not uploaded a submission yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>document.getElementById('sidebarCollapse').addEventListener('click', function(){document.getElementById('sidebar').classList.toggle('active');});</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
