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
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 15px 20px;
        }

        .header-banner {
            padding: 24px 30px 10px;
        }

        .competition-card {
            height: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .status-badge {
            letter-spacing: 0;
            border-radius: 999px;
            padding: 7px 10px;
        }

        .panel-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
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
            <p class="text-muted mb-0">View and manage all creative competition activities.</p>
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
                        $status = strtoupper($competition['status'] ?? '');
                        $badge = $status === 'OPEN' ? 'success' : ($status === 'UPCOMING' ? 'secondary' : 'danger');

                        $deadline_text = 'No deadline';
                        if (!empty($competition['deadline'])) {
                            $deadline_text = date('d M Y', strtotime($competition['deadline']));
                        }
                    ?>

                    <div class="col-md-6 col-xl-4">
                        <div class="card competition-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between gap-3 mb-3">
                                    <div>
                                        <h4 class="h5 fw-bold mb-1">
                                            <?php echo htmlspecialchars($competition['competition_name']); ?>
                                        </h4>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($competition['category']); ?>
                                        </div>
                                    </div>

                                    <span class="badge bg-<?php echo $badge; ?> status-badge align-self-start">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </div>

                                <p class="text-muted small mb-4">
                                    <?php echo htmlspecialchars($competition['description'] ?? 'No description provided.'); ?>
                                </p>

                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fa-solid fa-calendar-days me-2"></i>
                                    Deadline: <?php echo htmlspecialchars($deadline_text); ?>
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
