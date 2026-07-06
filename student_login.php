<?php
session_start();
require_once 'database.php';

$error_msg = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $stmt = mysqli_prepare($conn, "SELECT p.matric_no, p.name, sa.email, sa.password, sa.account_status
                                       FROM participants p
                                       JOIN student_accounts sa ON p.matric_no = sa.matric_no
                                       WHERE sa.email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($student && $student['account_status'] === 'ACTIVE' && hash_equals((string) $student['password'], $password)) {
            $stmt = mysqli_prepare($conn, "UPDATE student_accounts SET last_login = CURRENT_TIMESTAMP WHERE matric_no = ?");
            mysqli_stmt_bind_param($stmt, "s", $student['matric_no']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION['is_student'] = true;
            $_SESSION['student_matric'] = $student['matric_no'];
            $_SESSION['student_name'] = $student['name'];
            header("Location: student_dashboard.php");
            exit;
        }

        $error_msg = "Invalid email or password.";
    } catch (mysqli_sql_exception $e) {
        error_log("Student Login Error: " . $e->getMessage());
        $error_msg = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - CCMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body, html { height: 100%; margin: 0; font-family: sans-serif; }
        .left-side { background: url('images/leftbg.png') no-repeat center center; background-size: cover; color: white; }
        .right-side { background-color: #f6f7fb; }
        .portal-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e0f2fe;
            color: #075985;
            border-radius: 999px;
            padding: 7px 12px;
            font-weight: 700;
            font-size: 0.82rem;
            margin-bottom: 14px;
        }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="container-fluid h-100">
    <div class="row h-100">
        <div class="col-md-5 left-side d-flex flex-column justify-content-center align-items-center text-center p-5">
            <img src="images/welcome.png" alt="Welcome" class="img-fluid logo-glow mb-4" style="max-width: 280px;">
            <h3 class="fw-light">Submit, track, and improve your competition work.</h3>
        </div>
        <div class="col-md-7 right-side d-flex flex-column justify-content-center p-5">
            <div class="mx-auto" style="max-width: 420px; width: 100%;">
                <div class="portal-pill">
                    <i class="fa-solid fa-user-graduate"></i> Student Portal
                </div>
                <h2 class="fw-bold text-dark mb-1">Student Sign In</h2>
                <p class="text-muted mb-4">Use your student email and password.</p>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger small"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <form action="student_login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold">Email</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg fs-6" placeholder="nomatric@student.com" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label text-muted small fw-bold">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg fs-6" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-purple btn-lg w-100 fw-bold fs-6">Sign In as Student</button>
                    <div class="text-center mt-3">
                        <a href="login.php" class="small text-muted">I am an admin</a>
                    </div>
                    <div class="text-center mt-2">
                        <a href="student_register.php" class="small text-muted">Don't have an account? Register</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>