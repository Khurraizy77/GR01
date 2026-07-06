<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$student = null;
$filename = "No file uploaded";
$filesize_formatted = "0.00 KB";
$mime_type = "N/A";
$last_modified = "N/A";
$audio_file_exists = false;

if (isset($_GET['matric'])) {
    $matric_no = $_GET['matric'];

    try {
        // Updated query to explicitly fetch your new database columns
        $query = "SELECT p.*, s.audio_path, s.file_size, s.mime_type, s.file_modified, e.score 
                  FROM participants p
                  LEFT JOIN submissions s ON p.matric_no = s.matric_no
                  LEFT JOIN evaluations e ON p.matric_no = e.matric_no
                  WHERE p.matric_no = ?";
                  
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $matric_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($student && !empty($student['audio_path'])) {
            $filename = basename($student['audio_path']);
            $audio_file_exists = file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $student['audio_path']));
            
            // Fetch values straight from the database columns
            $filesize_formatted = !empty($student['file_size']) ? $student['file_size'] : "0.00 KB";
            $mime_type = !empty($student['mime_type']) ? $student['mime_type'] : "audio/mpeg";
            $last_modified = !empty($student['file_modified']) ? $student['file_modified'] : "N/A";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Profile Fetch Error: " . $e->getMessage());
    }
}

if (!$student) {
    die("Participant record reference not provided or missing from database storage.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants' Profile - CCMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { height: 100%; background-color: #f3e8ff; font-family: sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; align-items: stretch; height: 100vh; }
        #sidebar { min-width: 260px; max-width: 260px; background-color: #6b21a8; color: #fff; transition: all 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        #sidebar.active { margin-left: -260px; }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-profile { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); }
        #sidebar ul a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 15px; font-size: 1.1rem; padding: 12px 25px; transition: 0.2s; }
        #sidebar ul a:hover, #sidebar ul a.active { color: #fff; background: rgba(255, 255, 255, 0.1); border-radius: 8px; margin: 0 10px; }
        #content { width: 100%; overflow-y: auto; }
        .navbar-custom { background-color: #fff; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .header-banner { background-color: #fff; padding: 20px 30px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .profile-container { background: #fff; border: 1px solid #e1d8f5; border-radius: 4px; padding: 40px; max-width: 1100px; }
        .back-link { color: #888; text-decoration: underline; font-size: 0.9rem; margin-bottom: 25px; display: inline-block; }
        .back-link:hover { color: #6b21a8; }
        .avatar-circle { width: 110px; height: 110px; border-radius: 50%; border: 2px solid #333; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #ccc; background-color: #fff; }
        .field-label { color: #4a148c; font-weight: bold; font-size: 0.85rem; margin-bottom: 6px; display: block; }
        .field-box { background-color: #f6f0ff; border: none; border-radius: 4px; padding: 10px 14px; width: 100%; color: #333; font-size: 0.95rem; margin-bottom: 18px; min-height: 40px; display: flex; align-items: center; }
        audio { width: 100%; filter: sepia(20%) saturate(70%) grayscale(10%) contrast(95%); margin-top: 5px; }
        .meta-label { color: #4a148c; font-weight: bold; font-size: 0.85rem; margin-bottom: 2px; }
        .meta-val { color: #333; font-size: 0.9rem; margin-bottom: 15px; word-break: break-all; }
        .star-rating { color: #ffca28; font-size: 1.3rem; }
    </style>
    <link rel="stylesheet" href="css/app.css">
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
            <h3 class="fw-bold m-0 text-dark">Participants' Profile</h3>
        </div>

        <div class="container-fluid px-4 mb-5">
            <a href="participants.php" class="back-link">< Back</a>

            <div class="profile-container shadow-sm">
                <div class="row">
                    <div class="col-md-2 text-center text-md-start mb-4 mb-md-0">
                        <div class="avatar-circle mx-auto mx-md-0">
                            <i class="fa-regular fa-user"></i>
                        </div>
                    </div>

                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="field-label">Name</span>
                                <div class="field-box"><?php echo htmlspecialchars($student['name']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <span class="field-label">Matric No.</span>
                                <div class="field-box"><?php echo htmlspecialchars($student['matric_no']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <span class="field-label">Phone</span>
                                <div class="field-box"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <span class="field-label">Group</span>
                                <div class="field-box"><?php echo htmlspecialchars($student['student_group']); ?></div>
                            </div>
                            <div class="col-12">
                                <span class="field-label">Life Motto</span>
                                <div class="field-box"><?php echo htmlspecialchars($student['life_motto'] ?? ' '); ?></div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <span class="field-label text-dark fw-bold">Audio Submission</span>
                            <div class="rounded p-2 bg-light border-0 d-flex align-items-center mb-4">
                                <?php if ($audio_file_exists): ?>
                                    <audio controls src="<?php echo htmlspecialchars($student['audio_path']); ?>"></audio>
                                <?php elseif (!empty($student['audio_path'])): ?>
                                    <span class="text-warning small">Audio file is listed in the database but missing from the uploads folder.</span>
                                <?php else: ?>
                                    <span class="text-muted small">No audio submission uploaded.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row text-start mt-2">
                            <div class="col-sm-3">
                                <div class="meta-label">Filename</div>
                                <div class="meta-val"><?php echo htmlspecialchars($filename); ?></div>
                            </div>
                            <div class="col-sm-3">
                                <div class="meta-label">Size</div>
                                <div class="meta-val"><?php echo htmlspecialchars($filesize_formatted); ?></div>
                            </div>
                            <div class="col-sm-3">
                                <div class="meta-label">MIME type</div>
                                <div class="meta-val"><?php echo htmlspecialchars($mime_type); ?></div>
                            </div>
                            <div class="col-sm-3">
                                <div class="meta-label">Last Modified</div>
                                <div class="meta-val"><?php echo htmlspecialchars($last_modified); ?></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="meta-label text-dark fw-bold mb-2">Score</div>
                            <div class="star-rating">
                                <?php 
                                    $score = $student['score'] ?? 0;
                                    if ($score >= 80) echo '★★★★★';
                                    elseif ($score >= 60) echo '★★★★☆';
                                    elseif ($score >= 40) echo '★★★☆☆';
                                    elseif ($score > 0) echo '★★☆☆☆';
                                    else echo '<span class="text-muted small">Not evaluated</span>';
                                ?>
                            </div>
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
