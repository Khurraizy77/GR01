<?php
session_start();

if (!isset($_SESSION['is_student']) || $_SESSION['is_student'] !== true) {
    header("Location: student_login.php");
    exit;
}

require_once 'database.php';

$matric_no = $_SESSION['student_matric'] ?? '';
$success_msg = "";
$error_msg = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $student_group = trim($_POST['student_group'] ?? '');
    $life_motto = trim($_POST['life_motto'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }

        $stmt = mysqli_prepare($conn, "UPDATE participants SET phone = ?, life_motto = ?, student_group = ? WHERE matric_no = ?");
        mysqli_stmt_bind_param($stmt, "ssss", $phone, $life_motto, $student_group, $matric_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($password !== '') {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = mysqli_prepare($conn, "UPDATE student_accounts SET email = ?, password = ? WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "sss", $email, $hashed_password, $matric_no);
        } else {
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
            padding: 22px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .panel-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
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
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php
        $current_page = 'student_profile.php';
        include 'student_sidebar.php';
    ?>

    <div id="content">
        <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
            <button type="button" id="sidebarCollapse" class="btn p-0 border-0 fs-4 text-secondary">
                <i class="fa-solid fa-bars"></i>
            </button>

            <a href="student_logout.php" class="text-secondary text-decoration-none d-flex align-items-center gap-2 fs-5">
                <i class="fa-solid fa-lock"></i> Logout
            </a>
        </nav>

        <div class="header-banner">
            <h3 class="fw-bold m-0 text-dark">My Profile</h3>
            <p class="text-muted mb-0">Update your student account details.</p>
        </div>

        <div class="container-fluid px-4 pb-5">
            <div class="panel-card" style="max-width: 760px;">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success small">
                        <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger small">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <form action="student_profile.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Name</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Matric No.</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($student['matric_no'] ?? ''); ?>" disabled>
                        </div>

                        <div class="col-md-6">
                            <label for="student_group" class="form-label text-muted small fw-bold">Group</label>
                            <input type="text" name="student_group" id="student_group" class="form-control" value="<?php echo htmlspecialchars($student['student_group'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label text-muted small fw-bold">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label text-muted small fw-bold">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                        </div>

                        <div class="col-12">
                            <label for="life_motto" class="form-label text-muted small fw-bold">Life Motto</label>
                            <textarea name="life_motto" id="life_motto" class="form-control" rows="4"><?php echo htmlspecialchars($student['life_motto'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label text-muted small fw-bold">New Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-purple mt-4 px-4">
                        Update Profile
                    </button>
                </form>
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
