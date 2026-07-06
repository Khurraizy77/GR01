<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'database.php';

$admin_id = $_SESSION['admin_id'];
$success_msg = "";
$error_msg = "";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (!empty($username) && !empty($email)) {
        try {
            $query = "UPDATE admins SET username = ?, email = ? WHERE admin_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $admin_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_email'] = $email;
                $success_msg = "Profile updated successfully!";
            } else {
                $error_msg = "Failed to update profile. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - CCMS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { height: 100%; background-color: #f3e8ff; font-family: sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; align-items: stretch; height: 100vh; }
        #sidebar { min-width: 260px; max-width: 260px; background-color: #6b21a8; color: #fff; transition: all 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        #sidebar.active { margin-left: -260px; }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-profile { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); }
        #sidebar ul a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 15px; font-size: 1.1rem; padding: 12px 25px; transition: 0.2s; }
        #sidebar ul a:hover, #sidebar ul a.active { color: #fff; background: rgba(255, 255, 255, 0.1); border-radius: 8px; margin: 0 10px; }
        #content { width: 100%; overflow-y: auto; }
        .navbar-custom { background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); padding: 15px 20px; }
        .profile-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px; }
        .btn-purple { background-color: #6b21a8; color: white; border-radius: 8px; font-weight: bold; }
        .btn-purple:hover { background-color: #581c87; color: white; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="wrapper">
    
    <?php 
        $current_page = 'profileadmin.php'; 
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

        <div class="container-fluid p-4">
            <h2 class="fw-bold text-dark mb-4">Admin Profile</h2>

            <div class="row">
                <div class="col-md-6">
                    <div class="card profile-card">
                        <h4 class="fw-bold text-dark mb-4">Account Information</h4>
                        
                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success p-2 small"><?php echo $success_msg; ?></div>
                        <?php endif; ?>

                        <?php if(!empty($error_msg)): ?>
                            <div class="alert alert-danger p-2 small"><?php echo $error_msg; ?></div>
                        <?php endif; ?>

                        <form action="profileadmin.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label text-muted small fw-bold">Admin Username</label>
                                <input type="text" name="username" id="username" class="form-control form-control-lg fs-6" value="<?php echo htmlspecialchars($_SESSION['admin_username']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label text-muted small fw-bold">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control form-control-lg fs-6" value="<?php echo htmlspecialchars($_SESSION['admin_email']); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-purple btn-lg w-100 shadow-sm fs-6">Update Profile Details</button>
                        </form>
                    </div>
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
