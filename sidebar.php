<nav id="sidebar">
    <div>
        <div class="sidebar-header">
            <h3 class="fw-bold m-0 tracking-wide">CCMS</h3>
        </div>
        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fa-solid fa-user text-white"></i>
            </div>
            <span class="fw-bold fs-5">Admin</span>
        </div>
        <ul class="list-unstyled mt-3">
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-table-cells-large"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="profileadmin.php" class="<?php echo ($current_page == 'profileadmin.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate"></i> Profile
                </a>
            </li>
            <li>
                <a href="participants.php" class="<?php echo ($current_page == 'participants.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i> Participants
                </a>
            </li>
            <li>
                <a href="submissions.php" class="<?php echo ($current_page == 'submissions.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-arrow-up"></i> Submissions
                </a>
            </li>
            <li>
                <a href="competitions.php" class="<?php echo ($current_page == 'competitions.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-trophy"></i> Competitions
                </a>
            </li>
            <li>
                <a href="evaluations.php" class="<?php echo ($current_page == 'evaluations.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-square-poll-vertical"></i> Evaluations
                </a>
            </li>
            <li>
                <a href="retrieval.php" class="<?php echo ($current_page == 'retrieval.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-magnifying-glass-chart"></i> Retrieval
                </a>
            </li>
        </ul>
    </div>
</nav>
