<aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="brand">
          <div class="logo-icon">CC</div>
          <div>
            <div class="title">CovidCare</div>
            <div class="small muted">Hospital Dashboard</div>
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
          <a href="../Hospital/dashboard.php"class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        </nav>
      </div>

      <div class="nav-section">
        <div class="nav-title">Main</div>
        <nav class="nav" id="mainNav">
          <a href="../Hospital/patients.php" class="<?php echo ($current_page == 'patients.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> Patients</a>
          <a href="../Hospital/bookings.php" class="<?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i>
           Bookings</a>
          <a href="../Hospital/update_reports.php"class="<?php echo ($current_page == 'update_reports.php') ? 'active' : ''; ?>"><i class="fa fa-file-medical"></i> Update Reports</a>
          <a href="../Hospital/appointments.php" class="<?php echo ($current_page == 'appointments.php') ? 'active' : ''; ?>">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                    <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/>
                    <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/>
                    <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/>
         </svg> Apponitments</a> 
        </nav>
      </div>
      <div class="nav-section">
        <div class="nav-title">Manage</div>
        <nav class="nav">
          <a href="../Hospital/profile.php"class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> Profile Settings</a>
          <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
      </div>
    </aside>