<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$selected_student = null;
$success_msg = "";
$error_msg = "";

// 1. Process evaluation entry update form submissions
if (($_SERVER["REQUEST_METHOD"] ?? "GET") == "POST") {
    $matric_no = $_POST['matric_no'];
    $abr_status = $_POST['abr_status'];
    $tbr_status = $_POST['tbr_status'];
    $cbr_gender = $_POST['cbr_gender'];
    $score = floatval($_POST['score']);

    try {
        $check_q = "SELECT matric_no FROM evaluations WHERE matric_no = ?";
        $stmt = mysqli_prepare($conn, $check_q);
        mysqli_stmt_bind_param($stmt, "s", $matric_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $update_q = "UPDATE evaluations SET abr_status = ?, tbr_status = ?, cbr_gender = ?, score = ? WHERE matric_no = ?";
            $stmt = mysqli_prepare($conn, $update_q);
            mysqli_stmt_bind_param($stmt, "sssds", $abr_status, $tbr_status, $cbr_gender, $score, $matric_no);
        } else {
            $insert_q = "INSERT INTO evaluations (matric_no, abr_status, tbr_status, cbr_gender, score) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_q);
            mysqli_stmt_bind_param($stmt, "ssssd", $matric_no, $abr_status, $tbr_status, $cbr_gender, $score);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Evaluation assessment updated successfully!";
        } else {
            $error_msg = "Failed to store criteria scores.";
        }
        mysqli_stmt_close($stmt);
    } catch (mysqli_sql_exception $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

// 2. Fetch selected participant parameters if parameter provided
$target_matric = $_GET['matric'] ?? '';
if (!empty($target_matric)) {
    try {
        $query = "SELECT p.name, p.matric_no, p.student_group, s.audio_path, e.abr_status, e.tbr_status, e.cbr_gender, e.score 
                  FROM participants p
                  LEFT JOIN submissions s ON p.matric_no = s.matric_no
                  LEFT JOIN evaluations e ON p.matric_no = e.matric_no
                  WHERE p.matric_no = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $target_matric);
        mysqli_stmt_execute($stmt);
        $selected_student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    } catch (mysqli_sql_exception $e) {
        error_log("Fetch Target Participant Error: " . $e->getMessage());
    }
}

// 3. Load general evaluation index overview metrics summary
$eval_list = [];
try {
    $list_query = "SELECT p.name, p.matric_no, e.abr_status, e.tbr_status, e.cbr_gender, e.score 
                   FROM participants p
                   LEFT JOIN evaluations e ON p.matric_no = e.matric_no 
                   ORDER BY p.name ASC";
    $res = mysqli_query($conn, $list_query);
    while($r = mysqli_fetch_assoc($res)) { $eval_list[] = $r; }
} catch(mysqli_sql_exception $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluations Manager - CCMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { height: 100%; background-color: #f3e8ff; font-family: sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; align-items: stretch; height: 100vh; }
        
        /* SIDEBAR BEAUTIFICATION LAYOUT STYLES */
        #sidebar { min-width: 260px; max-width: 260px; background-color: #6b21a8; color: #fff; transition: all 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        #sidebar.active { margin-left: -260px; }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-profile { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); }
        #sidebar ul a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 15px; font-size: 1.1rem; padding: 12px 25px; transition: 0.2s; }
        #sidebar ul a:hover, #sidebar ul a.active { color: #fff; background: rgba(255, 255, 255, 0.1); border-radius: 8px; margin: 0 10px; }
        
        /* CONTENT WORKSPACE OVERVIEW STYLES */
        #content { width: 100%; overflow-y: auto; }
        .navbar-custom { background-color: #fff; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .header-banner { background-color: #fff; padding: 20px 30px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .workspace-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; border: none; }
        .btn-purple { background-color: #6b21a8; color: white; border-radius: 8px; font-weight: bold; }
        .btn-purple:hover { background-color: #581c87; color: white; }
        .badge-status { font-size: 0.8rem; padding: 5px 10px; }
        .text-purple { color: #6b21a8; }
        audio { filter: sepia(10%) saturate(80%); }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="wrapper">
    <?php 
        $current_page = 'evaluations.php'; 
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
            <h3 class="fw-bold m-0 text-dark">Evaluations Workspace</h3>
        </div>

        <div class="container-fluid px-4 mb-5">
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success shadow-sm mb-4"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm mb-4"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if($selected_student): ?>
                <div class="col-lg-5">
                    <div class="card workspace-card">
                        <h4 class="fw-bold text-dark mb-3">Grade Student</h4>
                        <p class="text-muted small">Assessing: <strong><?php echo htmlspecialchars($selected_student['name']); ?></strong> (<?php echo htmlspecialchars($selected_student['matric_no']); ?>)</p>
                        
                        <?php
                            $selected_audio_path = $selected_student['audio_path'] ?? '';
                            $selected_audio_exists = !empty($selected_audio_path) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $selected_audio_path));
                        ?>
                        <?php if ($selected_audio_exists): ?>
                            <audio controls class="w-100 mb-4" src="<?php echo htmlspecialchars($selected_student['audio_path']); ?>"></audio>
                        <?php elseif (!empty($selected_audio_path)): ?>
                            <div class="alert alert-warning small mb-4">Audio file is listed in the database but missing from the uploads folder.</div>
                        <?php endif; ?>

                        <form action="evaluations.php?matric=<?php echo urlencode($selected_student['matric_no']); ?>" method="POST">
                            <input type="hidden" name="matric_no" value="<?php echo htmlspecialchars($selected_student['matric_no']); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">ABR Check (Format Validation)</label>
                                <select name="abr_status" class="form-select">
                                    <option value="PASS" <?php echo ($selected_student['abr_status'] === 'PASS') ? 'selected' : ''; ?>>PASS (Valid mp3 layout)</option>
                                    <option value="FAIL" <?php echo ($selected_student['abr_status'] === 'FAIL') ? 'selected' : ''; ?>>FAIL (Invalid layout)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">TBR Check (Lyrics/Keyword Check)</label>
                                <select name="tbr_status" class="form-select">
                                    <option value="PASS" <?php echo ($selected_student['tbr_status'] === 'PASS') ? 'selected' : ''; ?>>PASS (Keywords present)</option>
                                    <option value="FAIL" <?php echo ($selected_student['tbr_status'] === 'FAIL' || empty($selected_student['tbr_status'])) ? 'selected' : ''; ?>>FAIL (Keywords missing)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">CBR Analysis (Acoustic Gender Properties)</label>
                                <select name="cbr_gender" class="form-select">
                                    <option value="POSTPONED" <?php echo (strtoupper($selected_student['cbr_gender'] ?? '') === 'POSTPONED' || empty($selected_student['cbr_gender'])) ? 'selected' : ''; ?>>POSTPONED</option>
                                    <option value="MALE" <?php echo (strtoupper($selected_student['cbr_gender'] ?? '') === 'MALE') ? 'selected' : ''; ?>>MALE Detected</option>
                                    <option value="FEMALE" <?php echo (strtoupper($selected_student['cbr_gender'] ?? '') === 'FEMALE') ? 'selected' : ''; ?>>FEMALE Detected</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">Final Numerical Evaluation Score (0.00 - 100.00)</label>
                                <input type="number" step="0.01" min="0" max="100" name="score" class="form-control" value="<?php echo htmlspecialchars($selected_student['score'] ?? '0.00'); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-purple w-100 py-2">Save Assessment Ratings</button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-lg-5">
                    <div class="card workspace-card text-center py-5">
                        <i class="fa-solid fa-file-signature text-muted fs-1 mb-3"></i>
                        <h5>No student selected</h5>
                        <p class="text-muted small px-4">Click "Select >" on any record in the workspace master roster to open the grading parameters.</p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-lg-7">
                    <div class="card workspace-card">
                        <h4 class="fw-bold text-dark mb-4">Evaluation Master Roster</h4>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small m-0">
                                <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                                    <tr>
                                        <th>Name</th>
                                        <th>ABR</th>
                                        <th>TBR</th>
                                        <th>CBR (Gender)</th>
                                        <th>Score</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eval_list as $e_row): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($e_row['name']); ?></td>
                                        <td>
                                            <span class="badge badge-status bg-<?php echo ($e_row['abr_status'] === 'PASS') ? 'success' : 'danger'; ?>">
                                                <?php echo $e_row['abr_status'] ?? 'PENDING'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-status bg-<?php echo ($e_row['tbr_status'] === 'PASS') ? 'success' : 'danger'; ?>">
                                                <?php echo $e_row['tbr_status'] ?? 'FAIL'; ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-secondary badge-status"><?php echo $e_row['cbr_gender'] ?? 'UNTESTED'; ?></span></td>
                                        <td class="fw-bold text-purple"><?php echo number_format($e_row['score'] ?? 0.00, 2); ?></td>
                                        <td class="text-end">
                                            <a href="evaluations.php?matric=<?php echo urlencode($e_row['matric_no']); ?>" class="text-purple fw-bold text-decoration-none">
                                                Select >
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
