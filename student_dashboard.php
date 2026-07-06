<?php
session_start();

if (!isset($_SESSION['is_student']) || $_SESSION['is_student'] !== true) {
    header("Location: student_login.php");
    exit;
}

require_once 'database.php';

$matric_no = $_SESSION['student_matric'] ?? '';
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
} catch (Throwable $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
}

$student_name = $student['name'] ?? ($_SESSION['student_name'] ?? 'Student');
$score = isset($evaluation['score']) ? (float)$evaluation['score'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - CCMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/app.css">

    <style>
        body, html {
            min-height: 100%;
            background-color: #f3e8ff;
            font-family: Arial, sans-serif;
            overflow-x: hidden;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background-color: #6b21a8;
            color: #fff;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        #sidebar.active {
            margin-left: -260px;
        }

        .sidebar-header {
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-profile {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
        }

        #sidebar ul a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.05rem;
            padding: 12px 25px;
            transition: 0.2s;
        }

        #sidebar ul a:hover,
        #sidebar ul a.active {
            color: #fff;
            background: rgba(255,255,255,0.12);
            border-radius: 8px;
            margin: 0 10px;
        }

        #content {
            flex: 1;
            overflow-y: auto;
        }

        .navbar-custom {
            background-color: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .header-banner {
            background-color: #fff;
            padding: 22px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .metric-card,
        .panel-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ede9fe;
            color: #6b21a8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 12px;
        }

        .status-submitted {
            color: #198754;
        }

        .status-pending {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -260px;
                position: fixed;
                min-height: 100vh;
                z-index: 999;
            }

            #sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php
        $current_page = 'student_dashboard.php';
        include 'student_sidebar.php';
    ?>

    <div id="content">
        <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
            <button type="button" id="sidebarCollapse" class="btn p-0 border-0 fs-4 text-secondary">
                <i class="fa-solid fa-bars"></i>
            </button>

            <a href="student_logout.php" class="text-secondary text-decoration-none d-flex align-items-center gap-2 fs-5">
                <i class="fa-solid fa-lock"></i> Logout
            </a>
        </nav>

        <div class="header-banner">
            <h3 class="fw-bold m-0 text-dark">
                Welcome, <?php echo htmlspecialchars($student_name); ?>
            </h3>
            <p class="text-muted mb-0">Track your competition submission and evaluation status.</p>
        </div>

        <div class="container-fluid px-4 pb-5">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <div class="text-muted small fw-bold">Open Competitions</div>
                        <h1 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($open_competitions); ?></h1>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="text-muted small fw-bold">Submission Status</div>
                        <?php if ($submission): ?>
                            <h3 class="fw-bold mb-0 status-submitted">Submitted</h3>
                        <?php else: ?>
                            <h3 class="fw-bold mb-0 status-pending">Pending</h3>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div class="text-muted small fw-bold">Score</div>
                        <h1 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars(number_format($score, 2)); ?></h1>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <h4 class="fw-bold mb-3 text-dark">Latest Submission</h4>

                <?php if ($submission): ?>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <span class="text-muted small fw-bold">Title</span>
                            <div class="fw-semibold">
                                <?php echo htmlspecialchars($submission['song_title'] ?? 'Untitled'); ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <span class="text-muted small fw-bold">Format</span>
                            <div class="fw-semibold">
                                <?php echo htmlspecialchars(strtoupper($submission['audio_extension'] ?? 'N/A')); ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <span class="text-muted small fw-bold">Submitted</span>
                            <div class="fw-semibold">
                                <?php echo htmlspecialchars($submission['submitted_at'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-muted">You have not uploaded a submission yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const sidebarBtn = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');

    if (sidebarBtn && sidebar) {
        sidebarBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
