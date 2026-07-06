<nav id="sidebar">
    <div>
        <div class="sidebar-header">
            <h3 class="fw-bold m-0">CCMS Student</h3>
        </div>
        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fa-solid fa-user-graduate text-white"></i>
            </div>
            <div>
                <span class="fw-bold d-block">Student</span>
                <span class="small text-white-50"><?php echo htmlspecialchars($_SESSION['student_matric'] ?? ''); ?></span>
            </div>
        </div>
        <ul class="list-unstyled mt-3">
            <li>
                <a href="student_dashboard.php" class="<?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-table-cells-large"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="student_profile.php" class="<?php echo ($current_page == 'student_profile.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-address-card"></i> Profile
                </a>
            </li>
            <li>
                <a href="student_submission.php" class="<?php echo ($current_page == 'student_submission.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-arrow-up"></i> Submission
                </a>
            </li>
        </ul>
    </div>
</nav>
