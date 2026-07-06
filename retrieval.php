<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$mode = $_GET['mode'] ?? 'ABR';
$mode = in_array($mode, ['ABR', 'TBR', 'CBR'], true) ? $mode : 'ABR';
$query_value = trim($_GET['q'] ?? '');
$results = [];

function preview_text($value, $limit = 120) {
    $value = trim((string) $value);
    return strlen($value) > $limit ? substr($value, 0, $limit) . '...' : $value;
}

try {
    $sql = "SELECT s.submission_id, s.matric_no, s.song_title, s.audio_extension, s.lyrics_text,
                   s.submitted_at, s.file_size, s.mime_type, p.name, e.cbr_gender, e.score
            FROM submissions s
            JOIN participants p ON s.matric_no = p.matric_no
            LEFT JOIN evaluations e ON s.matric_no = e.matric_no";

    $params = [];
    $types = '';

    if ($query_value !== '') {
        if ($mode === 'ABR') {
            $sql .= " WHERE s.audio_extension LIKE ? OR s.mime_type LIKE ? OR s.file_size LIKE ?";
            $like = '%' . $query_value . '%';
            $params = [$like, $like, $like];
            $types = 'sss';
        } elseif ($mode === 'TBR') {
            $sql .= " WHERE s.song_title LIKE ? OR s.lyrics_text LIKE ?";
            $like = '%' . $query_value . '%';
            $params = [$like, $like];
            $types = 'ss';
        } else {
            $sql .= " WHERE e.cbr_gender LIKE ?";
            $like = '%' . $query_value . '%';
            $params = [$like];
            $types = 's';
        }
    }

    $sql .= " ORDER BY s.submitted_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }

    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $e) {
    error_log("Retrieval Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retrieval - CCMS</title>
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
        .table-custom th { padding: 15px; font-size: 0.85rem; text-transform: uppercase; }
        .table-custom td { padding: 16px 15px; vertical-align: middle; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="wrapper">
    <?php
        $current_page = 'retrieval.php';
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
            <h3 class="fw-bold m-0 text-dark">ABR, TBR, CBR Retrieval</h3>
        </div>

        <div class="container-fluid px-4 pb-5">
            <div class="panel-card mb-4">
                <form action="retrieval.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="mode" class="form-label text-muted small fw-bold">Retrieval Type</label>
                        <select name="mode" id="mode" class="form-select">
                            <option value="ABR" <?php echo $mode === 'ABR' ? 'selected' : ''; ?>>ABR - Attribute</option>
                            <option value="TBR" <?php echo $mode === 'TBR' ? 'selected' : ''; ?>>TBR - Text</option>
                            <option value="CBR" <?php echo $mode === 'CBR' ? 'selected' : ''; ?>>CBR - Content</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="q" class="form-label text-muted small fw-bold">Search Value</label>
                        <input type="text" name="q" id="q" class="form-control" value="<?php echo htmlspecialchars($query_value); ?>" placeholder="ABR: mp3, audio/mpeg | TBR: keyword/title | CBR: male/female">
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-purple px-4">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                        </button>
                        <a href="retrieval.php" class="btn btn-outline-secondary px-4">Reset</a>
                    </div>
                </form>
            </div>

            <div class="panel-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle m-0">
                        <thead>
                            <tr>
                                <th>Submission</th>
                                <th>Participant</th>
                                <th>ABR Metadata</th>
                                <th>TBR Text</th>
                                <th>CBR</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No retrieval results found.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($row['song_title'] ?? 'Untitled submission'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['submitted_at']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['matric_no']); ?></div>
                                    </td>
                                    <td class="small">
                                        <?php echo htmlspecialchars(strtoupper($row['audio_extension'] ?? 'N/A')); ?><br>
                                        <code><?php echo htmlspecialchars($row['mime_type'] ?? 'N/A'); ?></code><br>
                                        <?php echo htmlspecialchars($row['file_size'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="small text-muted" style="max-width: 300px;">
                                        <?php echo htmlspecialchars(preview_text($row['lyrics_text'] ?? '')); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($row['cbr_gender'] ?? 'UNTESTED'); ?></span>
                                    </td>
                                    <td class="fw-bold text-purple"><?php echo number_format($row['score'] ?? 0, 2); ?></td>
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
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
