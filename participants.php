<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$participants = [];
$search = trim($_GET['search'] ?? '');

try {
    $query = "SELECT p.matric_no, p.name, p.student_group, e.score 
              FROM participants p
              LEFT JOIN evaluations e ON p.matric_no = e.matric_no";

    if ($search !== '') {
        $query .= " WHERE p.name LIKE ? OR p.matric_no LIKE ? OR p.student_group LIKE ?";
    }

    $query .= " ORDER BY p.name ASC";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        throw new Exception("Failed to prepare participants query.");
    }

    if ($search !== '') {
        $search_like = '%' . $search . '%';
        mysqli_stmt_bind_param($stmt, "sss", $search_like, $search_like, $search_like);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $participants[] = $row;
    }

    mysqli_stmt_close($stmt);
} catch (Throwable $e) {
    error_log("Participants UI Fetch Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants - CCMS</title>

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
            padding: 20px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .table-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            padding: 25px;
        }

        .table-custom th {
            background-color: #fff;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            color: #666;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
        }

        .table-custom td {
            padding: 18px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            color: #333;
        }

        .star-rating {
            color: #ffca28;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .action-link {
            color: #6b21a8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .action-link:hover {
            text-decoration: underline;
            color: #581c87;
        }

        .btn-purple {
            background-color: #6b21a8;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            border: none;
        }

        .btn-purple:hover {
            background-color: #581c87;
            color: white;
        }

        .form-control:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 0.2rem rgba(139,92,246,0.15);
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

            .table-container {
                padding: 18px;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php
        $current_page = 'participants.php';
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
            <h3 class="fw-bold m-0 text-dark">Participants</h3>
            <p class="text-muted mb-0">View student profiles, groups and evaluation scores.</p>
        </div>

        <div class="container-fluid px-4 pb-5">
            <div class="table-container">
                <form action="participants.php" method="GET" class="row g-2 align-items-center mb-4">
                    <div class="col-md-8 col-lg-6">
                        <label for="search" class="form-label text-muted small fw-bold mb-1">
                            Search Participant
                        </label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by name, matric no., or group"
                        >
                    </div>

                    <div class="col-md-auto d-flex gap-2 align-self-end">
                        <button type="submit" class="btn btn-purple px-4">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                        </button>

                        <?php if ($search !== ''): ?>
                            <a href="participants.php" class="btn btn-outline-secondary px-4">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-custom align-middle m-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Matric No.</th>
                                <th>Student Group</th>
                                <th>Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        No participants found<?php echo $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($participants as $student): ?>
                                <?php $score = isset($student['score']) ? (float)$student['score'] : 0; ?>

                                <tr>
                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($student['name'] ?? 'N/A'); ?>
                                    </td>

                                    <td class="text-secondary">
                                        <?php echo htmlspecialchars($student['matric_no'] ?? 'N/A'); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student['student_group'] ?? 'N/A'); ?>
                                    </td>

                                    <td>
                                        <span class="star-rating">
                                            <?php
                                                if ($score >= 80) echo '★★★★★';
                                                elseif ($score >= 60) echo '★★★★☆';
                                                elseif ($score >= 40) echo '★★★☆☆';
                                                elseif ($score > 0) echo '★★☆☆☆';
                                                else echo '<span class="text-muted small">Not evaluated</span>';
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a href="participant_profile.php?matric=<?php echo urlencode($student['matric_no']); ?>" class="action-link">
                                            See Profile <i class="fa-solid fa-angle-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

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
