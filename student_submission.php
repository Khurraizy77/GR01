<?php
session_start();

if (!isset($_SESSION['is_student']) || $_SESSION['is_student'] !== true) {
    header("Location: student_login.php");
    exit;
}

require_once 'database.php';

$matric_no = $_SESSION['student_matric'];
$success_msg = "";
$error_msg = "";

function format_file_size($bytes) {
    return number_format($bytes / 1024, 2) . " KB";
}

try {
    if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
        $song_title = trim($_POST['song_title'] ?? '');
        $lyrics_text = trim($_POST['lyrics_text'] ?? '');
        $audio_path = '';
        $audio_extension = '';
        $file_size = '0.00 KB';
        $mime_type = 'audio/mpeg';
        $file_modified = 'N/A';

        if ($song_title === '' || $lyrics_text === '') {
            $error_msg = "Song title and lyrics/keywords are required.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM submissions WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "s", $matric_no);
            mysqli_stmt_execute($stmt);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['audio_file']['name']);
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                if ($extension !== 'mp3') {
                    $error_msg = "Only MP3 audio files are allowed.";
                } else {
                    $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
                    $target_name = time() . '_' . $matric_no . '_' . $safe_name . '.mp3';
                    $target_relative = 'uploads/audio/' . $target_name;
                    $target_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $target_relative);

                    if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $target_path)) {
                        $audio_path = $target_relative;
                        $audio_extension = 'mp3';
                        $file_size = format_file_size(filesize($target_path));
                        $mime_type = $_FILES['audio_file']['type'] ?: 'audio/mpeg';
                        $file_modified = date('Y-m-d H:i:s', filemtime($target_path));
                    } else {
                        $error_msg = "Unable to save uploaded audio file.";
                    }
                }
            } elseif (!$existing) {
                $error_msg = "Please upload an MP3 file for your first submission.";
            }

            if ($error_msg === '') {
                if ($existing) {
                    if ($audio_path !== '') {
                        $stmt = mysqli_prepare($conn, "UPDATE submissions SET song_title = ?, lyrics_text = ?, audio_path = ?, audio_extension = ?, file_size = ?, mime_type = ?, file_modified = ? WHERE matric_no = ?");
                        mysqli_stmt_bind_param($stmt, "ssssssss", $song_title, $lyrics_text, $audio_path, $audio_extension, $file_size, $mime_type, $file_modified, $matric_no);
                    } else {
                        $stmt = mysqli_prepare($conn, "UPDATE submissions SET song_title = ?, lyrics_text = ? WHERE matric_no = ?");
                        mysqli_stmt_bind_param($stmt, "sss", $song_title, $lyrics_text, $matric_no);
                    }
                } else {
                    $video_path = '';
                    $stmt = mysqli_prepare($conn, "INSERT INTO submissions (matric_no, audio_path, audio_extension, lyrics_text, video_path, song_title, file_size, mime_type, file_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "sssssssss", $matric_no, $audio_path, $audio_extension, $lyrics_text, $video_path, $song_title, $file_size, $mime_type, $file_modified);
                }

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if ($audio_path !== '') {
                    $stmt = mysqli_prepare($conn, "SELECT matric_no FROM evaluations WHERE matric_no = ?");
                    mysqli_stmt_bind_param($stmt, "s", $matric_no);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    $evaluation_exists = mysqli_stmt_num_rows($stmt) > 0;
                    mysqli_stmt_close($stmt);

                    if ($evaluation_exists) {
                        $stmt = mysqli_prepare($conn, "UPDATE evaluations SET cbr_gender = 'POSTPONED' WHERE matric_no = ?");
                        mysqli_stmt_bind_param($stmt, "s", $matric_no);
                    } else {
                        $abr_status = 'PASS';
                        $tbr_status = 'FAIL';
                        $cbr_gender = 'POSTPONED';
                        $score = 0.00;
                        $stmt = mysqli_prepare($conn, "INSERT INTO evaluations (matric_no, abr_status, tbr_status, cbr_gender, score) VALUES (?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, "ssssd", $matric_no, $abr_status, $tbr_status, $cbr_gender, $score);
                    }

                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                $success_msg = "Submission saved successfully.";
            }
        }
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM submissions WHERE matric_no = ?");
    mysqli_stmt_bind_param($stmt, "s", $matric_no);
    mysqli_stmt_execute($stmt);
    $submission = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $e) {
    error_log("Student Submission Error: " . $e->getMessage());
    $error_msg = "Unable to save submission.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submission - CCMS</title>
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
        .panel-card { background: white; border-radius: 12px; padding: 25px; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="wrapper">
    <?php $current_page = 'student_submission.php'; include 'student_sidebar.php'; ?>
    <div id="content">
        <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
            <button type="button" id="sidebarCollapse" class="btn p-0 border-0 fs-4 text-secondary"><i class="fa-solid fa-bars"></i></button>
            <a href="student_logout.php" class="text-secondary text-decoration-none d-flex align-items-center gap-2 fs-5"><i class="fa-solid fa-lock"></i> Logout</a>
        </nav>
        <div class="header-banner"><h3 class="fw-bold m-0">My Submission</h3></div>
        <div class="container-fluid px-4 pb-5">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="panel-card">
                        <?php if ($success_msg): ?><div class="alert alert-success small"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                        <?php if ($error_msg): ?><div class="alert alert-danger small"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
                        <form action="student_submission.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="song_title" class="form-label text-muted small fw-bold">Song / Poem Title</label>
                                <input type="text" name="song_title" id="song_title" class="form-control" value="<?php echo htmlspecialchars($submission['song_title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="lyrics_text" class="form-label text-muted small fw-bold">Lyrics, Poem Text, Keywords, or Description</label>
                                <textarea name="lyrics_text" id="lyrics_text" class="form-control" rows="7" required><?php echo htmlspecialchars($submission['lyrics_text'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="audio_file" class="form-label text-muted small fw-bold">MP3 Audio File</label>
                                <input type="file" name="audio_file" id="audio_file" class="form-control" accept=".mp3,audio/mpeg">
                                <div class="form-text"><?php echo $submission ? 'Upload a new MP3 only if you want to replace the current file.' : 'Required for first submission.'; ?></div>
                            </div>
                            <button type="submit" class="btn btn-purple px-4">Save Submission</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="panel-card">
                        <h4 class="fw-bold mb-3">Current File</h4>
                        <?php if ($submission): ?>
                            <?php
                                $audio_path = $submission['audio_path'] ?? '';
                                $audio_exists = $audio_path && file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $audio_path));
                            ?>
                            <div class="small text-muted mb-2">Title</div>
                            <div class="fw-semibold mb-3"><?php echo htmlspecialchars($submission['song_title'] ?? 'Untitled'); ?></div>
                            <div class="small text-muted mb-2">File</div>
                            <?php if ($audio_exists): ?>
                                <audio controls class="w-100 mb-3" src="<?php echo htmlspecialchars($audio_path); ?>"></audio>
                            <?php else: ?>
                                <div class="text-warning small mb-3">Audio file is listed but missing from uploads.</div>
                            <?php endif; ?>
                            <div class="small text-muted">Size: <?php echo htmlspecialchars($submission['file_size'] ?? 'N/A'); ?></div>
                            <div class="small text-muted">Submitted: <?php echo htmlspecialchars($submission['submitted_at']); ?></div>
                        <?php else: ?>
                            <div class="text-muted">No submission uploaded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>document.getElementById('sidebarCollapse').addEventListener('click', function(){document.getElementById('sidebar').classList.toggle('active');});</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
