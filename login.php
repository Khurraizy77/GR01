<?php
session_start();

require_once 'database.php';

$error_msg = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $query = "SELECT * FROM admins WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            throw new Exception("Failed to prepare login query.");
        }

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

    } catch (Throwable $e) {
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

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <!-- Optional custom CSS -->
    <link rel="stylesheet" href="./css/app.css">

    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f5ff;
        }

        .left-side {
            background: url('./images/leftbg.png') no-repeat center center;
            background-size: cover;
            color: white;
            position: relative;
            min-height: 100vh;
        }

        .left-side::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(88, 28, 135, 0.55),
                rgba(107, 33, 168, 0.35)
            );
        }

        .left-content {
            position: relative;
            z-index: 2;
        }

        .right-side {
            background-color: #f3e8ff;
            min-height: 100vh;
        }

        .btn-purple {
            background-color: #6b21a8;
            color: white;
            border: none;
        }

        .btn-purple:hover {
            background-color: #581c87;
            color: white;
        }

        .logo-glow {
            filter: drop-shadow(0px 0px 15px rgba(168, 85, 247, 0.35));
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

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .form-control {
            border: 1px solid #ddd6fe;
            box-shadow: none;
        }

        .form-control:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.15);
        }

        .text-soft {
            color: #6b7280;
        }

        @media (max-width: 767.98px) {
            .left-side {
                min-height: 280px;
            }

            .right-side {
                min-height: auto;
                padding: 2rem 1.25rem !important;
            }

            .login-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid h-100">
    <div class="row min-vh-100">

        <!-- LEFT PANEL -->
        <div class="col-md-5 left-side d-flex flex-column justify-content-center align-items-center text-center p-5">
            <div class="left-content">
                <div class="mb-4">
                    <img src="./images/welcome.png" alt="Welcome Back" class="img-fluid logo-glow" style="max-width: 280px; height: auto;">
                </div>
                <h3 class="fw-light px-3 py-1" style="text-shadow: 1px 1px 4px rgba(0,0,0,0.2);">
                    Your future writing skills starts here!
                </h3>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-7 right-side d-flex flex-column justify-content-center align-items-center p-4 p-md-5">
            <div class="login-card">
                <div class="portal-pill">
                    <i class="fa-solid fa-user-shield"></i> Admin Portal
                </div>

                <h2 class="fw-bold text-dark mb-1">Admin Sign In</h2>
                <p class="text-muted mb-4">For administrators and competition managers only.</p>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger p-2 small">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold mb-1">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control form-control-lg rounded-3 fs-6"
                            placeholder="Email"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label text-muted small fw-bold mb-1">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control form-control-lg rounded-3 fs-6"
                            placeholder="Password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-purple btn-lg w-100 rounded-pill fw-bold fs-6 shadow-sm">
                        Sign In as Admin
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="student_login.php" class="small text-soft text-decoration-none">
                        I am a student
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
