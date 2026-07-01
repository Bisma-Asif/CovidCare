    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="brand">
          <div class="logo-icon">CC</div>
          <div>
            <div class="title">CovidCare</div>
            <div class="small muted">Admin Dashboard</div>
          </div>
        </div>
        <button class="sidebar-close" id="sidebarClose">
          <i class="fas fa-times"></i>
        </button>
      </div>

        <?php
    $current_page = basename($_SERVER['PHP_SELF']); ?>

      <div class="nav-section">
        <div class="nav-title">Overview</div>
        <nav class="nav">
          <a href="../Admin/dashboard.php"class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        </nav>
      </div>

      <div class="nav-section">
        <div class="nav-title">Main</div>
        <nav class="nav" id="mainNav">
          <a href="../Admin/patients.php" class="<?php echo ($current_page == 'patients.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> Patients</a>
          <a href="../Admin/hospitals.php" class="<?php echo ($current_page == 'hospitals.php') ? 'active' : ''; ?>"><i class="fa-solid fa-hospital"></i> Hospitals</a>
          <a href="../Admin/login_approval.php" class="<?php echo ($current_page == 'login_approval.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-shield"></i><span>Login Approval</span></a>
          <a href="../Admin/test_req.php" class="<?php echo ($current_page == 'test_req.php') ? 'active' : ''; ?>"><i class="fa-regular fa-clock"></i> Test Approval</a>
          <a href="../Admin/reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-medical"></i> Test Result</a>
          <a href="../Admin/vaccines.php" class="<?php echo ($current_page == 'vaccines.php') ? 'active' : ''; ?>"><i class="fa-solid fa-syringe"></i> Manage Vaccines</a>
           
        </nav>
      </div>
      <div class="nav-section">
        <div class="nav-title">Manage</div>
        <nav class="nav">
          <a href="../Admin/profile.php"class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> My Profile</a>
          <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
      </div>
    </aside>