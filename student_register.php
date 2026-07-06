<?php
session_start();
require_once 'database.php';

$error_msg = "";
$success_msg = "";

// Keep field values after failed submission so user doesn't retype everything
$form = [
    'name'      => '',
    'matric_no' => '',
    'email'     => '',
];

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $name      = trim($_POST['name'] ?? '');
    $matric_no = strtoupper(trim($_POST['matric_no'] ?? ''));
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirm   = trim($_POST['confirm_password'] ?? '');

    // Persist values back to form
    $form['name']      = $name;
    $form['matric_no'] = $matric_no;
    $form['email']     = $email;

    // ── SERVER-SIDE VALIDATION ──────────────────────────────────────────────

    if (empty($name) || empty($matric_no) || empty($email) || empty($password) || empty($confirm)) {
        $error_msg = "All fields are required.";

    } elseif (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $name)) {
        $error_msg = "Full name must only contain letters, spaces, apostrophes, or hyphens.";

    } elseif (!preg_match('/^[Bb]\d{9}$/', $matric_no)) {
        $error_msg = "Matric number format is invalid. Example: B032310055";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";

    } elseif ($password !== $confirm) {
        $error_msg = "Passwords do not match.";

    } else {
        try {
            // Check for duplicate matric_no or email
            $stmt = mysqli_prepare($conn, "SELECT matric_no FROM student_accounts WHERE matric_no = ? OR email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $matric_no, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_msg = "Matric number or email is already registered.";
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);

                // Insert into participants
                $stmt = mysqli_prepare($conn, "INSERT INTO participants (matric_no, name) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "ss", $matric_no, $name);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Insert into student_accounts (plain text password, no hashing)
                $stmt = mysqli_prepare($conn, "INSERT INTO student_accounts (matric_no, email, password, account_status) VALUES (?, ?, ?, 'ACTIVE')");
                mysqli_stmt_bind_param($stmt, "sss", $matric_no, $email, $password);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Clear form on success
                $form = ['name' => '', 'matric_no' => '', 'email' => ''];
                $success_msg = "Registration successful! You can now sign in.";
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Student Register Error: " . $e->getMessage());
            $error_msg = "A system error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - CCMS</title>
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
        .password-wrapper { position: relative; }
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            background: none;
            border: none;
            padding: 0;
        }
        .hint { font-size: 0.78rem; color: #9ca3af; margin-top: 4px; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="container-fluid h-100">
    <div class="row h-100">

        <!-- Left panel -->
        <div class="col-md-5 left-side d-flex flex-column justify-content-center align-items-center text-center p-5">
            <img src="images/welcome.png" alt="Welcome" class="img-fluid logo-glow mb-4" style="max-width: 280px;">
            <h3 class="fw-light">Submit, track, and improve your competition work.</h3>
        </div>

        <!-- Right form panel -->
        <div class="col-md-7 right-side d-flex flex-column justify-content-center p-5">
            <div class="mx-auto" style="max-width: 420px; width: 100%;">

                <div class="portal-pill">
                    <i class="fa-solid fa-user-graduate"></i> Student Portal
                </div>
                <h2 class="fw-bold text-dark mb-1">Create Account</h2>
                <p class="text-muted mb-4">Register using your student details.</p>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger small"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success small">
                        <?php echo htmlspecialchars($success_msg); ?>
                        <a href="student_login.php" class="alert-link ms-1">Sign in now</a>
                    </div>
                <?php endif; ?>

                <form action="student_register.php" method="POST" id="registerForm" novalidate>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label text-muted small fw-bold">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control form-control-lg fs-6"
                            placeholder="e.g. Ahmad bin Ali"
                            value="<?php echo htmlspecialchars($form['name']); ?>"
                            required
                        >
                        <div class="hint">Letters, spaces, apostrophes and hyphens only.</div>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>

                    <!-- Matric Number -->
                    <div class="mb-3">
                        <label for="matric_no" class="form-label text-muted small fw-bold">Matric Number</label>
                        <input
                            type="text"
                            name="matric_no"
                            id="matric_no"
                            class="form-control form-control-lg fs-6"
                            placeholder="e.g. B012345678"
                            value="<?php echo htmlspecialchars($form['matric_no']); ?>"
                            maxlength="10"
                            required
                        >
                        <div class="hint">Format: B followed by 9 digits (e.g. B012345678).</div>
                        <div class="invalid-feedback">Invalid matric number format.</div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control form-control-lg fs-6"
                            placeholder="nomatric@student.com"
                            value="<?php echo htmlspecialchars($form['email']); ?>"
                            required
                        >
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label text-muted small fw-bold">Password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control form-control-lg fs-6"
                                placeholder="Minimum 6 characters"
                                minlength="6"
                                required
                            >
                            <button type="button" class="toggle-pw" onclick="togglePassword('password', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="hint">At least 6 characters.</div>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label text-muted small fw-bold">Confirm Password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                class="form-control form-control-lg fs-6"
                                placeholder="Re-enter password"
                                required
                            >
                            <button type="button" class="toggle-pw" onclick="togglePassword('confirm_password', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="confirm-feedback">Passwords do not match.</div>
                    </div>

                    <button type="submit" class="btn btn-purple btn-lg w-100 fw-bold fs-6">Register</button>

                    <div class="text-center mt-3">
                        <a href="student_login.php" class="small text-muted">Already have an account? Sign in</a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    // ── CLIENT-SIDE VALIDATION ──────────────────────────────────────────────

    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.getElementById('registerForm').addEventListener('submit', function (e) {
        let valid = true;

        // Full name — letters, spaces, apostrophes, hyphens only
        const name = document.getElementById('name');
        if (!name.value.trim() || !/^[a-zA-Z\s'\-.]+$/.test(name.value.trim())) {
            name.classList.add('is-invalid');
            valid = false;
        } else {
            name.classList.remove('is-invalid');
            name.classList.add('is-valid');
        }

        // Matric number — B followed by 9 digits
        const matric = document.getElementById('matric_no');
        if (!/^[Bb]\d{9}$/.test(matric.value.trim())) {
            matric.classList.add('is-invalid');
            valid = false;
        } else {
            matric.classList.remove('is-invalid');
            matric.classList.add('is-valid');
        }

        // Email — basic format check
        const email = document.getElementById('email');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            email.classList.add('is-invalid');
            valid = false;
        } else {
            email.classList.remove('is-invalid');
            email.classList.add('is-valid');
        }

        // Password — minimum 6 characters
        const password = document.getElementById('password');
        if (password.value.length < 6) {
            password.classList.add('is-invalid');
            valid = false;
        } else {
            password.classList.remove('is-invalid');
            password.classList.add('is-valid');
        }

        // Confirm password — must match password
        const confirm = document.getElementById('confirm_password');
        if (confirm.value !== password.value || confirm.value === '') {
            confirm.classList.add('is-invalid');
            document.getElementById('confirm-feedback').textContent = 'Passwords do not match.';
            valid = false;
        } else {
            confirm.classList.remove('is-invalid');
            confirm.classList.add('is-valid');
        }

        if (!valid) {
            e.preventDefault();
        }
    });

    // Auto-uppercase matric number as user types
    document.getElementById('matric_no').addEventListener('input', function () {
        this.value = this.value.toUpperCase();
    });
</script>
</body>
</html>