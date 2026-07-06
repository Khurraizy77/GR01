<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$competitions = [];

try {
    $query = "SELECT competition_id, competition_name, category, deadline, status, description
              FROM competitions
              ORDER BY deadline ASC";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $competitions[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Competitions Fetch Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competitions - CCMS</title>
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
        .competition-card { height: 100%; }
        .status-badge { letter-spacing: 0; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="wrapper">
    <?php
        $current_page = 'competitions.php';
        include 'sidebar.php';
    ?>

    <div id="content">
        <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
            <button type="button" id="sidebarCollapse" class="btn p-0 border-0 fs-4 text-secondary">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="logout.php" class="text-secondary text-decoration-none d-flex align-items-center gap-2 fs-5">
                <i class="fa-solid fa-lock"></i> Logout
            </a>
        </nav>

        <div class="header-banner">
            <h3 class="fw-bold m-0 text-dark">Competition Management</h3>
        </div>

        <div class="container-fluid px-4 pb-5">
            <div class="row g-4">
                <?php if (empty($competitions)): ?>
                    <div class="col-12">
                        <div class="panel-card text-center text-muted py-5">
                            No competitions have been created yet.
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($competitions as $competition): ?>
                    <?php
                        $status = strtoupper($competition['status']);
                        $badge = $status === 'OPEN' ? 'success' : ($status === 'UPCOMING' ? 'secondary' : 'danger');
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card competition-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3 mb-3">
                                    <div>
                                        <h4 class="h5 fw-bold mb-1"><?php echo htmlspecialchars($competition['competition_name']); ?></h4>
                                        <div class="text-muted small"><?php echo htmlspecialchars($competition['category']); ?></div>
                                    </div>
                                    <span class="badge bg-<?php echo $badge; ?> status-badge align-self-start"><?php echo htmlspecialchars($status); ?></span>
                                </div>
                                <p class="text-muted small mb-4"><?php echo htmlspecialchars($competition['description'] ?? 'No description provided.'); ?></p>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fa-solid fa-calendar-days me-2"></i>
                                    Deadline: <?php echo htmlspecialchars(date('d M Y', strtotime($competition['deadline']))); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
