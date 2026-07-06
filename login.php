<?php
session_start();

require_once 'database.php'; 

$error_msg = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $query = "SELECT * FROM admins WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_email'] = $row['email'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error_msg = "Invalid email or password. Please try again.";
            }
        } else {
            $error_msg = "Invalid email or password. Please try again.";
        }
        
        mysqli_stmt_close($stmt);

    } catch (mysqli_sql_exception $e) {
        error_log("Login Error: " . $e->getMessage());
        $error_msg = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Creative Competition</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body, html { height: 100%; margin: 0; font-family: sans-serif; }
        
        .left-side { 
            background: url('images/leftbg.png') no-repeat center center; 
            background-size: cover; 
            color: white; 
        }
        
        .right-side { background-color: #f3e8ff; }
        .btn-purple { background-color: #6b21a8; color: white; }
        .btn-purple:hover { background-color: #581c87; color: white; }
        
        .logo-glow {
            filter: drop-shadow(0px 0px 15px rgba(168, 85, 247, 0.4));
            transition: transform 0.3s ease;
        }
        .logo-glow:hover {
            transform: scale(1.03);
        }
        .portal-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ede9fe;
            color: #432c8a;
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
            <div class="mb-4">
                <img src="images/welcome.png" alt="Welcome Back" class="img-fluid logo-glow" style="max-width: 280px; height: auto;">
            </div>
            <h3 class="fw-light px-3 py-1 rounded" style="text-shadow: 1px 1px 4px rgba(0,0,0,0.15);">
                Your future writing skills starts here!
            </h3>
        </div>

        <div class="col-md-7 right-side d-flex flex-column justify-content-center p-5">
            <div class="mx-auto" style="max-width: 400px; width: 100%;">
                <div class="portal-pill">
                    <i class="fa-solid fa-user-shield"></i> Admin Portal
                </div>
                <h2 class="fw-bold text-dark mb-1">Admin Sign In</h2>
                <p class="text-muted mb-4">For administrators and competition managers only.</p>

                <?php if(!empty($error_msg)): ?>
                    <div class="alert alert-danger p-2 small"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold mb-1">Email</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg rounded-3 fs-6" placeholder="Email" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label text-muted small fw-bold mb-1">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg rounded-3 fs-6" placeholder="Password" required>
                    </div>

                    <button type="submit" class="btn btn-purple btn-lg w-100 rounded-pill fw-bold fs-6 shadow-sm">Sign In as Admin</button>
                </form>
                <div class="text-center mt-3">
                    <a href="student_login.php" class="small text-muted">I am a student</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
