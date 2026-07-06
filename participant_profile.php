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
$audio_src = "";

if (isset($_GET['matric']) && !empty($_GET['matric'])) {
    $matric_no = trim($_GET['matric']);

    try {
        $query = "SELECT 
                    p.*, 
                    s.audio_path, 
                    s.file_size, 
                    s.mime_type, 
                    s.file_modified, 
                    e.score
                  FROM participants p
                  LEFT JOIN submissions s ON p.matric_no = s.matric_no
                  LEFT JOIN evaluations e ON p.matric_no = e.matric_no
                  WHERE p.matric_no = ?";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            throw new Exception("Failed to prepare participant query.");
        }

        mysqli_stmt_bind_param($stmt, "s", $matric_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($student && !empty($student['audio_path'])) {
            $audio_src = $student['audio_path'];
            $filename = basename($student['audio_path']);

            $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $student['audio_path']);
            $absolute_path = __DIR__ . DIRECTORY_SEPARATOR . ltrim($normalized_path, DIRECTORY_SEPARATOR);

            $audio_file_exists = file_exists($absolute_path);

            $filesize_formatted = !empty($student['file_size']) ? $student['file_size'] : "0.00 KB";
            $mime_type = !empty($student['mime_type']) ? $student['mime_type'] : "audio/mpeg";
            $last_modified = !empty($student['file_modified']) ? $student['file_modified'] : "N/A";
        }
    } catch (Throwable $e) {
        error_log("Profile Fetch Error: " . $e->getMessage());
    }
}

if (!$student) {
    die("Participant record reference not provided or missing from database storage.");
}

$score = isset($student['score']) ? (float)$student['score'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Profile - CCMS</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
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

        .profile-container {
            background: #fff;
            border: 1px solid #e9d5ff;
            border-radius: 18px;
            padding: 35px;
            max-width: 1100px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
        }

        .back-link {
            color: #6b21a8;
            text-decoration: none;
            font-size: 0.95rem;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .back-link:hover {
            color: #581c87;
        }

        .avatar-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solid #e9d5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: #a78bfa;
            background-color: #faf5ff;
        }

        .field-label {
            color: #6b21a8;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 6px;
            display: block;
        }

        .field-box {
            background-color: #f8f5ff;
            border: 1px solid #ede9fe;
            border-radius: 10px;
            padding: 12px 14px;
            width: 100%;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 18px;
            min-height: 46px;
            display: flex;
            align-items: center;
        }

        .audio-box {
            background-color: #f8f5ff;
            border: 1px solid #ede9fe;
            border-radius: 12px;
            padding: 12px;
        }

        audio {
            width: 100%;
            margin-top: 5px;
        }

        .meta-label {
            color: #6b21a8;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .meta-val {
            color: #333;
            font-size: 0.92rem;
            margin-bottom: 15px;
            word-break: break-word;
        }

        .star-rating {
            color: #ffca28;
            font-size: 1.4rem;
            letter-spacing: 2px;
        }

        .score-box {
            background: #faf5ff;
            border: 1px solid #ede9fe;
            border-radius: 12px;
            padding: 14px 16px;
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

            .profile-container {
                padding: 24px 18px;
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
            <h3 class="fw-bold m-0 text-dark">Participant Profile</h3>
        </div>

        <div class="container-fluid px-4 mb-5">
            <a href="participants.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Participants
            </a>

            <div class="profile-container">
                <div class="row g-4">
                    <div class="col-md-2 text-center text-md-start">
                        <div class="avatar-circle mx-auto mx-md-0">
                            <i class="fa-regular fa-user"></i>
                        </div>
                    </div>

                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="field-label">Name</span>
                                <div class="field-box">
                                    <?php echo htmlspecialchars($student['name'] ?? 'N/A'); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <span class="field-label">Matric No.</span>
                                <div class="field-box">
                                    <?php echo htmlspecialchars($student['matric_no'] ?? 'N/A'); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <span class="field-label">Phone</span>
                                <div class="field-box">
                                    <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <span class="field-label">Group</span>
                                <div class="field-box">
                                    <?php echo htmlspecialchars($student['student_group'] ?? 'N/A'); ?>
                                </div>
                            </div>

                            <div class="col-12">
                                <span class="field-label">Life Motto</span>
                                <div class="field-box">
                                    <?php echo htmlspecialchars($student['life_motto'] ?? ''); ?>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <span class="field-label text-dark">Audio Submission</span>
                            <div class="audio-box mb-4">
                                <?php if ($audio_file_exists): ?>
                                    <audio controls>
                                        <source src="<?php echo htmlspecialchars($audio_src); ?>" type="<?php echo htmlspecialchars($mime_type); ?>">
                                        Your browser does not support the audio element.
                                    </audio>
                                <?php elseif (!empty($audio_src)): ?>
                                    <span class="text-warning small">
                                        Audio file is listed in the database but missing from the uploads folder.
                                    </span>
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
                                <div class="meta-label">MIME Type</div>
                                <div class="meta-val"><?php echo htmlspecialchars($mime_type); ?></div>
                            </div>

                            <div class="col-sm-3">
                                <div class="meta-label">Last Modified</div>
                                <div class="meta-val"><?php echo htmlspecialchars($last_modified); ?></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="meta-label text-dark mb-2">Score</div>
                            <div class="score-box">
                                <div class="star-rating">
                                    <?php
                                        if ($score >= 80) echo '★★★★★';
                                        elseif ($score >= 60) echo '★★★★☆';
                                        elseif ($score >= 40) echo '★★★☆☆';
                                        elseif ($score > 0) echo '★★☆☆☆';
                                        else echo '<span class="text-muted small">Not evaluated</span>';
                                    ?>
                                </div>
                                <?php if ($score > 0): ?>
                                    <div class="text-muted small mt-2">
                                        Score: <?php echo htmlspecialchars(number_format($score, 2)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
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
