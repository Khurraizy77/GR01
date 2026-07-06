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

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $student_group = trim($_POST['student_group'] ?? ''); // 1. Retrieve the group value from the form
    $life_motto    = trim($_POST['life_motto'] ?? '');
    $password      = trim($_POST['password'] ?? '');

    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }

        if ($password !== '') {
            // Securely hash the password if the user wants to change it
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // 2. Updated to include student_group in the update statement
            $stmt = mysqli_prepare($conn, "UPDATE participants SET phone = ?, life_motto = ?, student_group = ? WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "ssss", $phone, $life_motto, $student_group, $matric_no);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE student_accounts SET email = ?, password = ? WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "sss", $email, $hashed_password, $matric_no);
        } else {
            // 2. Updated to include student_group here as well
            $stmt = mysqli_prepare($conn, "UPDATE participants SET phone = ?, life_motto = ?, student_group = ? WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "ssss", $phone, $life_motto, $student_group, $matric_no);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE student_accounts SET email = ? WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "ss", $email, $matric_no);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success_msg = "Profile updated successfully.";
    } catch (Throwable $e) {
        error_log("Student Profile Update Error: " . $e->getMessage());
        $error_msg = "Unable to update profile.";
    }
}

$stmt = mysqli_prepare($conn, "SELECT p.*, sa.email
                               FROM participants p
                               JOIN student_accounts sa ON p.matric_no = sa.matric_no
                               WHERE p.matric_no = ?");
mysqli_stmt_bind_param($stmt, "s", $matric_no);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - CCMS</title>
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
    <?php $current_page = 'student_profile.php'; include 'student_sidebar.php'; ?>
    <div id="content">
        <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
            <button type="button" id="sidebarCollapse" class="btn p-0 border-0 fs-4 text-secondary"><i class="fa-solid fa-bars"></i></button>
            <a href="student_logout.php" class="text-secondary text-decoration-none d-flex align-items-center gap-2 fs-5"><i class="fa-solid fa-lock"></i> Logout</a>
        </nav>
        <div class="header-banner"><h3 class="fw-bold m-0">My Profile</h3></div>
        <div class="container-fluid px-4 pb-5">
            <div class="panel-card" style="max-width: 760px;">
                <?php if ($success_msg): ?><div class="alert alert-success small"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
                <?php if ($error_msg): ?><div class="alert alert-danger small"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
                <form action="student_profile.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label text-muted small fw-bold">Name</label><input class="form-control" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label text-muted small fw-bold">Matric No.</label><input class="form-control" value="<?php echo htmlspecialchars($student['matric_no'] ?? ''); ?>" disabled></div>
                        
                        <div class="col-md-6"><label id="student_group" class="form-label text-muted small fw-bold">Group</label><input type="text" name="student_group" id="student_group" class="form-control" value="<?php echo htmlspecialchars($student['student_group'] ?? ''); ?>"></div>
                        
                        <div class="col-md-6"><label for="email" class="form-label text-muted small fw-bold">Email</label><input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required></div>
                        <div class="col-md-6"><label for="phone" class="form-label text-muted small fw-bold">Phone</label><input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"></div>
                        <div class="col-12"><label for="life_motto" class="form-label text-muted small fw-bold">Life Motto</label><textarea name="life_motto" id="life_motto" class="form-control" rows="4"><?php echo htmlspecialchars($student['life_motto'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label for="password" class="form-label text-muted small fw-bold">New Password</label><input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current password"></div>
                    </div>
                    <button type="submit" class="btn btn-purple mt-4 px-4">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>document.getElementById('sidebarCollapse').addEventListener('click', function(){document.getElementById('sidebar').classList.toggle('active');});</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>