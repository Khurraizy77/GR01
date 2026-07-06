<?php
// 1. Include your database connection file
// This makes the $conn variable available on this page
require_once 'database.php'; 

$competitions = [];

try {
    $query = "SELECT competition_name, category, deadline, status, description
              FROM competitions
              ORDER BY FIELD(status, 'OPEN', 'UPCOMING', 'CLOSED'), deadline ASC
              LIMIT 4";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $competitions[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Public Competitions Fetch Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the Competition Arena</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f3e8ff;
            color: #1f1230;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(107, 33, 168, 0.08);
        }

        .brand {
            color: #6b21a8;
            font-weight: 800;
            letter-spacing: 0;
        }

        .nav-link {
            color: #4b5563;
            font-weight: 600;
        }

        .nav-link:hover {
            color: #6b21a8;
        }

        .hero {
            min-height: 520px;
            background:
                linear-gradient(90deg, rgba(31, 18, 48, 0.88), rgba(107, 33, 168, 0.58)),
                url('images/leftbg.png') center / cover no-repeat;
            color: white;
            display: flex;
            align-items: center;
        }

        .hero h1 {
            max-width: 780px;
            font-size: clamp(2.25rem, 5vw, 4.5rem);
            font-weight: 800;
            line-height: 1.05;
        }

        .hero p {
            max-width: 560px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 1.1rem;
        }

        .btn-purple {
            background: #6b21a8;
            color: #fff;
            border: 0;
            font-weight: 700;
            padding: 0.8rem 1.5rem;
        }

        .btn-purple:hover {
            background: #581c87;
            color: #fff;
        }

        .section-title {
            color: #2d1745;
            font-weight: 800;
        }

        .competition-card {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(88, 28, 135, 0.08);
            height: 100%;
        }

        .competition-card .card-body {
            padding: 1.5rem;
        }

        footer {
            color: #6b7280;
        }

        .portal-actions .btn {
            font-weight: 700;
            padding: 0.7rem 1.15rem;
        }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container py-2">
            <a class="navbar-brand brand" href="index.php">CompeteDBMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <div class="navbar-nav ms-auto gap-lg-3">
                    <a class="nav-link" href="index.php">Home</a>
                    <a class="nav-link" href="#competitions">Competitions</a>
                    <div class="d-lg-flex gap-2 ms-lg-2 portal-actions">
                        <a class="btn btn-purple btn-sm" href="student_login.php">
                            <i class="fa-solid fa-user-graduate me-1"></i> Student Portal
                        </a>
                        <a class="btn btn-outline-dark btn-sm" href="login.php">
                            <i class="fa-solid fa-user-shield me-1"></i> Admin Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="container py-5">
                <h1>Welcome to the Ultimate Competition Platform</h1>
                <p class="mt-3 mb-4">Join ongoing challenges, showcase your skills, and climb the leaderboard.</p>
                <div class="d-flex flex-wrap gap-3 portal-actions">
                    <a href="student_login.php" class="btn btn-purple rounded-pill">
                        <i class="fa-solid fa-user-graduate me-1"></i> Student Portal
                    </a>
                    <a href="login.php" class="btn btn-light rounded-pill">
                        <i class="fa-solid fa-user-shield me-1"></i> Admin Portal
                    </a>
                </div>
            </div>
        </section>

        <section class="py-5" id="competitions">
            <div class="container">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="section-title m-0">Active Competitions</h2>
                </div>

                <div class="row g-4">
                    <?php if (empty($competitions)): ?>
                        <div class="col-12">
                            <div class="card competition-card">
                                <div class="card-body text-muted">No competitions are available yet.</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($competitions as $competition): ?>
                        <div class="col-md-6">
                            <div class="card competition-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-3 mb-2">
                                        <h3 class="h4 fw-bold mb-0"><?php echo htmlspecialchars($competition['competition_name']); ?></h3>
                                        <span class="badge bg-secondary align-self-start"><?php echo htmlspecialchars($competition['status']); ?></span>
                                    </div>
                                    <div class="text-muted small mb-3"><?php echo htmlspecialchars($competition['category']); ?> &middot; Deadline <?php echo htmlspecialchars(date('d M Y', strtotime($competition['deadline']))); ?></div>
                                    <p class="text-muted mb-4"><?php echo htmlspecialchars($competition['description'] ?? ''); ?></p>
                                    <a href="login.php" class="btn btn-outline-dark rounded-pill">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="py-4 bg-white">
        <div class="container small">
            &copy; <?php echo date("Y"); ?> Competition Portal. All rights reserved.
        </div>
    </footer>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
