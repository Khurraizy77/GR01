<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$submissions = [];

try {
    $query = "SELECT s.*, p.name, e.cbr_gender
              FROM submissions s
              JOIN participants p ON s.matric_no = p.matric_no
              LEFT JOIN evaluations e ON s.matric_no = e.matric_no
              ORDER BY s.submission_id DESC";
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $submissions[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Submissions View Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Archive - CCMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { height: 100%; background-color: #f3e8ff; font-family: sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; align-items: stretch; height: 100vh; }
        
        /* SIDEBAR FIXED LAYOUT STYLES */
        #sidebar { min-width: 260px; max-width: 260px; background-color: #6b21a8; color: #fff; transition: all 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        #sidebar.active { margin-left: -260px; }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-profile { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); }
        #sidebar ul a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 15px; font-size: 1.1rem; padding: 12px 25px; transition: 0.2s; }
        #sidebar ul a:hover, #sidebar ul a.active { color: #fff; background: rgba(255, 255, 255, 0.1); border-radius: 8px; margin: 0 10px; }
        
        /* CONTENT & WORKSPACE PANELS */
        #content { width: 100%; overflow-y: auto; }
        .navbar-custom { background-color: #fff; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .header-banner { background-color: #fff; padding: 20px 30px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .panel-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; }
        .table-custom th { background-color: #fff; border-bottom: 2px solid #dee2e6; padding: 15px; color: #666; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .table-custom td { padding: 18px 15px; border-bottom: 1px solid #eee; vertical-align: middle; color: #333; }
        .btn-evaluate { background-color: #6b21a8; color: white; border-radius: 6px; font-weight: 500; font-size: 0.85rem; padding: 6px 12px; }
        .btn-evaluate:hover { background-color: #581c87; color: white; }
        .btn-ai { border-radius: 6px; font-weight: 500; font-size: 0.85rem; padding: 6px 12px; }
        audio { max-width: 220px; height: 32px; filter: sepia(10%) saturate(80%); }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="wrapper">
    <?php 
        $current_page = 'submissions.php'; 
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
            <h3 class="fw-bold m-0 text-dark">Submissions Archive</h3>
        </div>

        <div class="container-fluid px-4">
            <div class="panel-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle m-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Matric No.</th>
                                <th>Audio Asset File</th>
                                <th>Size</th>
                                <th>MIME Type</th>
                                <th>AI Voice Gender</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $row): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="text-secondary small"><?php echo htmlspecialchars($row['matric_no']); ?></td>
                                <td>
                                    <?php
                                        $audio_path = $row['audio_path'] ?? '';
                                        $audio_exists = !empty($audio_path) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $audio_path));
                                    ?>
                                    <?php if($audio_exists): ?>
                                        <audio controls src="<?php echo htmlspecialchars($row['audio_path']); ?>" data-audio="<?php echo htmlspecialchars($row['audio_path']); ?>"></audio>
                                    <?php elseif(!empty($audio_path)): ?>
                                        <span class="text-warning small">File missing: <?php echo htmlspecialchars(basename($audio_path)); ?></span>
                                    <?php else: ?>
                                        <span class="text-danger small">Missing asset pointer</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($row['file_size'] ?? '0.00 KB'); ?></td>
                                <td class="small text-muted"><code><?php echo htmlspecialchars($row['mime_type'] ?? 'audio/mpeg'); ?></code></td>
                                <td>
                                    <span class="badge bg-secondary voice-gender-badge" id="gender-<?php echo htmlspecialchars($row['matric_no']); ?>">
                                        <?php echo htmlspecialchars($row['cbr_gender'] ?? 'NOT DETECTED YET'); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if($audio_exists): ?>
                                        <button
                                            type="button"
                                            class="btn btn-outline-dark btn-ai shadow-sm me-2"
                                            data-matric="<?php echo htmlspecialchars($row['matric_no']); ?>"
                                            data-audio="<?php echo htmlspecialchars($row['audio_path']); ?>"
                                        >
                                            <i class="fa-solid fa-wave-square me-1"></i> AI Detect
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary btn-ai shadow-sm me-2" disabled title="Audio file must exist before AI detection can run.">
                                            <i class="fa-solid fa-wave-square me-1"></i> AI Detect
                                        </button>
                                    <?php endif; ?>
                                    <a href="evaluations.php?matric=<?php echo urlencode($row['matric_no']); ?>" class="btn btn-evaluate shadow-sm">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Evaluate
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
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });

    function estimatePitch(buffer, sampleRate) {
        const data = buffer.getChannelData(0);
        let windowSize = Math.floor(sampleRate * 0.5); // 0.5 second per window
        
        // Fix: If audio is too short, limit windowSize to data length
        if (data.length < windowSize) {
            windowSize = data.length;
        }

        const numWindows = 6;
        // Fix: Ensure step is never negative
        const step = Math.max(0, Math.floor((data.length - windowSize) / Math.max(1, numWindows - 1)));
        const minLag = Math.floor(sampleRate / 300);     // max detectable: 300 Hz
        const maxLag = Math.floor(sampleRate / 75);      // min detectable: 75 Hz

        let pitches = [];

        for (let w = 0; w < numWindows; w++) {
            const start = step * w;
            
            // Fix: ensure start + windowSize does not exceed data length
            if (start + windowSize > data.length) break; 
            
            const samples = data.slice(start, start + windowSize);
            
            // Fix: ensure we have enough samples for the max lag
            if (samples.length <= maxLag) continue;

            let bestLag = -1;
            let bestCorr = 0;

            for (let lag = minLag; lag <= maxLag; lag++) {
                let corr = 0;
                for (let i = 0; i < samples.length - lag; i++) {
                    corr += samples[i] * samples[i + lag];
                }
                if (corr > bestCorr) {
                    bestCorr = corr;
                    bestLag = lag;
                }
            }

            // Only accept windows with a clear enough correlation signal
            if (bestLag > 0 && bestCorr > 0) {
                pitches.push(sampleRate / bestLag);
            }
        }

        if (pitches.length === 0) return 0;

        // Use median instead of a single-window estimate to reduce outlier impact
        pitches.sort(function(a, b) { return a - b; });
        return pitches[Math.floor(pitches.length / 2)];
    }

    async function saveVoiceGender(matricNo, gender) {
        const response = await fetch('save_voice_gender.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ matric_no: matricNo, gender: gender })
        });

        if (!response.ok) {
            throw new Error('Unable to save detected gender.');
        }

        return response.json();
    }

    async function detectVoiceGender(button) {
        const matricNo = button.dataset.matric;
        const audioUrl = button.dataset.audio;
        const badge = document.getElementById('gender-' + matricNo);
        const originalText = button.innerHTML;

        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Analyzing';
        badge.textContent = 'ANALYZING';

        try {
            const response = await fetch(audioUrl);
            const audioBytes = await response.arrayBuffer();
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            const audioContext = new AudioContextClass();
            const decoded = await audioContext.decodeAudioData(audioBytes);
            const pitch = estimatePitch(decoded, decoded.sampleRate);
            await audioContext.close();

            if (!pitch) {
                throw new Error('Could not estimate pitch from this audio.');
            }

            // FIX: Raised threshold from 190 Hz to 255 Hz
            // Male singing range: ~85–180 Hz | Female singing range: ~165–300 Hz
            // 255 Hz sits safely above the male upper range to avoid misclassifying females
            const gender = pitch < 255 ? 'MALE' : 'FEMALE';

            await saveVoiceGender(matricNo, gender);

            badge.textContent = gender;
            badge.classList.remove('bg-secondary', 'bg-danger');
            badge.classList.add(gender === 'MALE' ? 'bg-primary' : 'bg-success');
            button.innerHTML = '<i class="fa-solid fa-check me-1"></i> Saved';
        } catch (error) {
            badge.textContent = 'POSTPONED';
            badge.classList.remove('bg-success', 'bg-primary');
            badge.classList.add('bg-secondary');
            button.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Retry';
            console.error(error);
        } finally {
            button.disabled = false;
            setTimeout(function () {
                if (!button.disabled && button.innerHTML.includes('Saved')) {
                    button.innerHTML = originalText;
                }
            }, 1800);
        }
    }

    document.querySelectorAll('.btn-ai').forEach(function (button) {
        button.addEventListener('click', function () {
            detectVoiceGender(button);
        });
    });
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>