<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$total_participants = 0;
$evaluated_count = 0;
$to_evaluate_count = 0;

$passed_abr = 0;
$failed_abr = 0;

$male_cbr = 0;
$female_cbr = 0;

// Expanded to dynamically catch and track your new student class allocations
$group_counts = [
    'GR01' => 0, 'GR02' => 0, 'GR03' => 0,
    'GR04' => 0, 'GR05' => 0, 'GR06' => 0,
    'GR08' => 0, 'G1S2' => 0
];

$top_scorers = [];

try {
    $metrics_query = "SELECT COUNT(*) as total FROM participants";
    $res = mysqli_query($conn, $metrics_query);
    if ($row = mysqli_fetch_assoc($res)) { $total_participants = $row['total']; }

    $eval_query = "SELECT COUNT(*) as total FROM evaluations";
    $res = mysqli_query($conn, $eval_query);
    if ($row = mysqli_fetch_assoc($res)) { $evaluated_count = $row['total']; }

    $to_evaluate_count = $total_participants - $evaluated_count;

    $abr_query = "SELECT abr_status, COUNT(*) as cnt FROM evaluations GROUP BY abr_status";
    $res = mysqli_query($conn, $abr_query);
    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['abr_status'] === 'PASS') $passed_abr = $row['cnt'];
        if ($row['abr_status'] === 'FAIL') $failed_abr = $row['cnt'];
    }

    $cbr_query = "SELECT cbr_gender, COUNT(*) as cnt FROM evaluations GROUP BY cbr_gender";
    $res = mysqli_query($conn, $cbr_query);
    while ($row = mysqli_fetch_assoc($res)) {
        if (strtoupper($row['cbr_gender']) === 'MALE') $male_cbr = $row['cnt'];
        if (strtoupper($row['cbr_gender']) === 'FEMALE') $female_cbr = $row['cnt'];
    }

    $group_query = "SELECT student_group, COUNT(*) as cnt FROM participants GROUP BY student_group";
    $res = mysqli_query($conn, $group_query);
    while ($row = mysqli_fetch_assoc($res)) {
        $g = strtoupper($row['student_group']);
        if (array_key_exists($g, $group_counts)) {
            $group_counts[$g] = $row['cnt'];
        }
    }

    $top_query = "SELECT p.name, e.score FROM participants p 
                  JOIN evaluations e ON p.matric_no = e.matric_no 
                  WHERE e.score > 0.00
                  ORDER BY e.score DESC LIMIT 4";
    $res = mysqli_query($conn, $top_query);
    while ($row = mysqli_fetch_assoc($res)) {
        $top_scorers[] = $row;
    }

} catch (mysqli_sql_exception $e) {
    error_log("Dashboard UI Metrics Error: " . $e->getMessage());
}

$cbr_total = $male_cbr + $female_cbr;
$male_pct = $cbr_total > 0 ? round(($male_cbr / $cbr_total) * 100) : 0;
$female_pct = $cbr_total > 0 ? round(($female_cbr / $cbr_total) * 100) : 0;

$abr_total = $passed_abr + $failed_abr;
$passed_pct = $abr_total > 0 ? round(($passed_abr / $abr_total) * 100) : 0;
$failed_pct = $abr_total > 0 ? round(($failed_abr / $abr_total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCMS Dashboard</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Your custom CSS -->
    <link rel="stylesheet" href="./css/app.css">

    <style>
        body, html {
            height: 100%;
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

        #content {
            flex: 1;
            overflow-y: auto;
        }

        .navbar-custom {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 15px 20px;
        }

        .metric-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            height: 100%;
        }

        .metric-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .viz-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 20px;
            height: 100%;
            text-align: center;
        }

        .viz-title {
            font-weight: bold;
            color: #000;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .star-rating {
            color: #ffca28;
            font-size: 1.2rem;
        }

        .gender-icon {
            font-size: 4.5rem;
        }

        .gender-female {
            color: #ff69b4;
        }

        .gender-male {
            color: #1e90ff;
        }
    </style>
</head>
<body>

<div class="wrapper">
    
    <!-- FETCH EXTERNAL SIDEBAR WORKSPACE LAYER -->
    <?php 
        $current_page = 'dashboard.php'; 
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

        <div class="container-fluid p-4">
            <h2 class="fw-bold text-dark mb-4">Dashboard</h2>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card metric-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="metric-icon-box bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-user-group"></i>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small fw-bold d-block">Total participants</span>
                                <h1 class="fw-bold m-0 text-dark"><?php echo $total_participants; ?></h1>
                                <a href="participants.php" class="text-muted small text-decoration-none">See all ></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card metric-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="metric-icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small fw-bold d-block">Evaluated</span>
                                <h1 class="fw-bold m-0 text-dark"><?php echo $evaluated_count; ?></h1>
                                <a href="#" class="text-muted small text-decoration-none">See all ></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card metric-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="metric-icon-box bg-danger bg-opacity-10 text-danger">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small fw-bold d-block">To Evaluate:</span>
                                <h1 class="fw-bold m-0 text-dark"><?php echo $to_evaluate_count; ?></h1>
                                <a href="#" class="text-muted small text-decoration-none">See all ></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card viz-card text-start">
                        <div class="viz-title text-center">Current Top Score</div>
                        <div class="px-4 py-2">
                            <?php if (empty($top_scorers)): ?>
                                <div class="py-4 text-center text-muted">
                                    No scored evaluations yet.
                                </div>
                            <?php elseif (false): ?>
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                                    <span class="fs-5 fw-semibold text-secondary">Anieys</span>
                                    <span class="star-rating">★★★★★</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                                    <span class="fs-5 fw-semibold text-secondary">Nur</span>
                                    <span class="star-rating">★★★★</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                                    <span class="fs-5 fw-semibold text-secondary">Ahmad</span>
                                    <span class="star-rating">★★★★</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between py-2">
                                    <span class="fs-5 fw-semibold text-secondary">Ali</span>
                                    <span class="star-rating">★★★</span>
                                </div>
                            <?php else: ?>
                                <?php foreach($top_scorers as $index => $row): ?>
                                    <div class="d-flex align-items-center justify-content-between py-2 <?php echo $index < count($top_scorers)-1 ? 'border-bottom border-light' : ''; ?>">
                                        <span class="fs-5 fw-semibold text-secondary"><?php echo htmlspecialchars($row['name']); ?></span>
                                        <span class="star-rating">
                                            <?php 
                                                if($row['score'] >= 80) echo '★★★★★';
                                                elseif($row['score'] >= 60) echo '★★★★';
                                                elseif($row['score'] >= 40) echo '★★★';
                                                else echo '★★';
                                            ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card viz-card">
                        <div class="viz-title">Gender Audio</div>
                        <div class="d-flex justify-content-center align-items-center gap-5 my-3">
                            <div>
                                <i class="fa-solid fa-venus gender-icon gender-female"></i>
                                <h3 class="fw-bold mt-2 text-dark"><?php echo $female_pct; ?>%</h3>
                            </div>
                            <div>
                                <i class="fa-solid fa-mars gender-icon gender-male"></i>
                                <h3 class="fw-bold mt-2 text-dark"><?php echo $male_pct; ?>%</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card viz-card">
                        <div class="viz-title">Group Performance</div>
                        <div style="height: 220px; display: flex; align-items: center; justify-content: center;">
                            <canvas id="groupPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card viz-card">
                        <div class="viz-title">ABR MP3 Follow</div>
                        <div style="height: 220px; display: flex; align-items: center; justify-content: center;">
                            <canvas id="abrFollowChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });

    const ctxGroup = document.getElementById('groupPerformanceChart').getContext('2d');
    new Chart(ctxGroup, {
        type: 'bar',
        data: {
            // Updated dynamically to also chart and render your custom laboratory group indexes
            labels: ['GR01', 'GR02', 'GR03', 'GR04', 'GR05', 'GR06', 'GR08', 'G1S2'],
            datasets: [{
                data: [
                    <?php echo $group_counts['GR01']; ?>,
                    <?php echo $group_counts['GR02']; ?>,
                    <?php echo $group_counts['GR03']; ?>,
                    <?php echo $group_counts['GR04']; ?>,
                    <?php echo $group_counts['GR05']; ?>,
                    <?php echo $group_counts['GR06']; ?>,
                    <?php echo $group_counts['GR08']; ?>,
                    <?php echo $group_counts['G1S2']; ?>
                ],
                backgroundColor: '#d1d5db',
                borderWidth: 0,
                barThickness: 14
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: true }, ticks: { stepSize: 5 } },
                y: { grid: { display: false }, border: { display: true } }
            }
        }
    });

    const ctxAbr = document.getElementById('abrFollowChart').getContext('2d');
    new Chart(ctxAbr, {
        type: 'pie',
        data: {
            labels: ['Followed', 'Unfollowed'],
            datasets: [{
                data: [<?php echo $passed_pct; ?>, <?php echo $failed_pct; ?>],
                backgroundColor: ['#4da3ff', '#ffffff'],
                borderColor: '#4da3ff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
