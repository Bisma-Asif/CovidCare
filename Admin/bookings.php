<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Bookings</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <style>
    /* style.css - shared theme */
    :root {
      --bg: #f8fafc;
      --card: #ffffff;
      --muted: #64748b;
      --text: #0f172a;
      --accent: #0d9488;   /* teal */
      --accent-2: #14b8a6;
      --border: #e2e8f0;
      --radius: 12px;
      --glass: rgba(255,255,255,0.6);
      --primary-600: #0d9488;
      --primary-700: #0f766e;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
    }
    .dark {
      --bg: #0e1117;
      --card: #0f1724;
      --muted: #94a3b8;
      --text: #e6eef6;
      --accent: #3b82f6;
      --accent-2: #06b6d4;
      --border: rgba(255,255,255,0.06);
      --glass: rgba(255,255,255,0.03);
      --primary-600: #14b8a6;
      --primary-700: #0d9488;
      --success: #34d399;
      --danger: #f87171;
      --warning: #fbbf24;
    }

    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0; 
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: var(--bg); 
      color: var(--text); 
      line-height: 1.45;
      -webkit-font-smoothing: antialiased;
      scroll-behavior: smooth;
    }

    /* layout */
    .layout {
      display: grid; 
      grid-template-columns: 256px 1fr; 
      min-height: 100vh;
    }
    .sidebar {
      background: var(--card); 
      border-right: 1px solid var(--border);
      padding: 20px; 
      position: sticky; 
      top: 0; 
      height: 100vh;
      overflow-y: auto;
      transition: all 0.3s ease;
    }
    .brand {
      display: flex; 
      align-items: center; 
      gap: 12px; 
      margin-bottom: 18px;
    }
    .logo-icon {
      width: 40px; 
      height: 40px; 
      border-radius: 10px;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      display: flex; 
      align-items: center; 
      justify-content: center; 
      color: #fff; 
      font-weight: 700;
      box-shadow: 0 6px 18px rgba(2,6,23,0.06);
    }
    .brand .title {
      font-weight: 700; 
      color: var(--text); 
      font-size: 18px;
    }
    .nav-section {
      margin-top: 14px;
    }
    .nav-title {
      font-size: 12px; 
      color: var(--muted); 
      text-transform: uppercase; 
      margin-bottom: 10px; 
      letter-spacing: .08em;
    }
    .nav {
      display: flex; 
      flex-direction: column; 
      gap: 10px;
    }
    .nav a {
      display: flex; 
      gap: 12px; 
      align-items: center; 
      padding: 10px 12px; 
      border-radius: 10px; 
      text-decoration: none;
      color: var(--muted); 
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .nav a i {
      width: 20px; 
      text-align: center;
    }
    .nav a:hover {
      background: linear-gradient(90deg, rgba(13,148,136,0.12), rgba(15,118,110,0.08));
      color: var(--primary-700);
      transform: translateX(4px);
    }
    .nav a.active {
            background: #f0fdfa;
            color: #0d9488;
            border-right: 2px solid #14b8a6;
    }

    /* main & header */
    .main {
      display: flex; 
      flex-direction: column; 
      min-height: 100vh; 
      background: transparent;
    }
    .header {
      display: flex; 
      align-items: center; 
      justify-content: space-between;
      gap: 16px; 
      padding: 14px 22px; 
      background: var(--card); 
      border-bottom: 1px solid var(--border);
      position: sticky; 
      top: 0; 
      z-index: 6;
    }
    .header-left {
      display: flex; 
      align-items: center; 
      gap: 12px;
    }
    .menu-toggle {
      display: none; 
      background: none; 
      border: none; 
      padding: 8px; 
      border-radius: 8px; 
      cursor: pointer; 
      color: var(--muted);
      transition: all 0.3s ease;
    }
    .menu-toggle:hover {
      background: var(--primary-600);
      color: white;
    }

    .header-right {
      display: flex; 
      align-items: center; 
      gap: 12px;
    }
    .icon-btn {
      background: transparent; 
      border: 0; 
      padding: 8px; 
      border-radius: 8px; 
      cursor: pointer; 
      color: var(--muted);
      transition: all 0.3s ease;
    }
    .icon-btn:hover {
      background: var(--primary-600);
      color: white;
      transform: scale(1.05);
    }
    .profile {
      position: relative; 
      display: flex; 
      align-items: center; 
      gap: 10px; 
      cursor: pointer;
      padding: 6px;
      border-radius: 10px;
      transition: all 0.3s ease;
    }
    .profile:hover {
      background: var(--glass);
    }
    .avatar {
      width: 38px; 
      height: 38px; 
      border-radius: 10px; 
      background: linear-gradient(135deg, var(--accent-2), var(--accent)); 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      color: white; 
      font-weight: 700;
      transition: all 0.3s ease;
    }
    .profile:hover .avatar {
      transform: scale(1.1);
    }
    .header-profile-name {
      display: flex; 
      flex-direction: column; 
      align-items: flex-start;
      font-weight: 700;
      font-size: 16px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
    }
    .dropdown {
      position: absolute; 
      right: 0; 
      top: 56px; 
      background: var(--card); 
      border: 1px solid var(--border);
      box-shadow: 0 10px 30px rgba(2,6,23,0.06); 
      border-radius: 10px; 
      min-width: 180px; 
      overflow: hidden; 
      display: none;
      z-index: 10;
    }
    .dropdown a {
      display: block; 
      padding: 10px 12px; 
      text-decoration: none; 
      color: var(--text); 
      border-bottom: 1px dashed var(--border);
      transition: all 0.3s ease;
    }
    .dropdown a:last-child {
      border-bottom: none;
    }
    .dropdown a:hover {
      background: var(--primary-600);
      color: white;
      padding-left: 16px;
    }

    /* Bookings Page Styles */
    .content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .page-title {
      font-size: 24px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      padding-left: 16px;
    }

    .page-title::before {
      content: "";
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      height: 24px;
      width: 4px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      border-radius: 2px;
    }

    .filter-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .filter-buttons {
      display: flex;
      background: var(--glass);
      border-radius: 8px;
      padding: 4px;
      border: 1px solid var(--border);
    }

    .filter-btn {
      padding: 8px 16px;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      color: var(--muted);
    }

    .filter-btn.active {
      background: var(--primary-600);
      color: white;
    }

    .bookings-table-container {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      border: 1px solid var(--border);
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    .table-title {
      font-size: 20px;
      font-weight: 700;
    }

    .table-search {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: 8px;
      background: var(--glass);
      border: 1px solid var(--border);
      width: 300px;
    }

    .table-search input {
      border: 0;
      outline: 0;
      background: transparent;
      width: 100%;
      color: var(--text);
    }
    
    .table-search:hover{
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      border-color: var(--primary-600);
    }
    
    .table-search:focus-within {
      border-color: var(--primary-600);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      text-align: left;
      padding: 16px 20px;
      background-color: var(--glass);
      color: var(--muted);
      font-weight: 600;
      border-bottom: 1px solid var(--border);
      position: relative;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    th:hover {
      background: linear-gradient(135deg, rgba(13,148,136,0.1), rgba(15,118,110,0.05));
      color: var(--primary-600);
    }

    th:hover::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
    }

    td {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr {
      transition: all 0.3s ease;
    }

    tr:hover {
      background: var(--glass);
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 700;
      display: inline-block;
    }
    
    .status-approved {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }
    
    .status-rejected {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }
    
    .status-pending {
      background-color: rgba(245, 158, 11, 0.1);
      color: var(--warning);
    }

    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .view-btn {
      padding: 8px 12px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .view-btn:hover {
      background: linear-gradient(135deg, rgba(13,148,136,0.1), rgba(15,118,110,0.05));
      color: var(--primary-600);
    }
    
    .view-btn-lg {
      padding: 9px 8px;
      font-size: 13px;
    }

    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .modal.active {
      opacity: 1;
      visibility: visible;
    }

    .modal-content {
      background: var(--card);
      border-radius: var(--radius);
      width: 600px;
      max-width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      transform: translateY(-20px);
      transition: all 0.3s ease;
    }

    .modal.active .modal-content {
      transform: translateY(0);
    }

    .modal-header {
      padding: 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-title {
      font-size: 20px;
      font-weight: 700;
    }

    .close-btn {
      background: transparent;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--muted);
      transition: all 0.3s ease;
    }

    .close-btn:hover {
      color: var(--primary-600);
      transform: rotate(90deg);
    }

    .modal-body {
      padding: 20px;
    }

    .booking-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .detail-group {
      margin-bottom: 16px;
    }

    .detail-label {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
    }

    .detail-value {
      font-size: 16px;
      font-weight: 600;
    }

    .modal-footer {
      padding: 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
    }

    /* responsive */
    @media (max-width: 1024px){
      .layout{grid-template-columns: 1fr}
      .sidebar{position:fixed; left:-100%; top:0; transition:left .25s; z-index:9; width:260px}
      .sidebar.open{left:0}
      .menu-toggle{display:inline-block}
      .table-search{width: 200px;}
    }
    @media (max-width:768px){
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }
      .booking-details {
        grid-template-columns: 1fr;
      }
      .filter-container {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
      }
    }
    @media (max-width:600px){
      .content{padding:12px}
      .table-search{width: 100%; margin-top: 12px;}
      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }
      th, td {
        padding: 12px 14px;
      }
      .action-buttons {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div class="logo-icon">CC</div>
        <div>
          <div class="title">CovidCare</div>
        </div>
      </div>

      <div class="nav-section">
        <div class="nav-title">Overview</div>
        <nav class="nav">
          <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        </nav>
      </div>

      <div class="nav-section">
        <div class="nav-title">Main</div>
        <nav class="nav" id="mainNav">
          <a href="../Admin/patients.php"><i class="fa-regular fa-user"></i> Patients</a>
          <a href="../Admin/hospitals.php"><i class="fa-solid fa-hospital"></i> Hospitals</a>
          <a href="../Admin/approval.php"><i class="fa-solid fa-user-shield"></i><span>Login Approval</span></a>
          <a href="../Admin/reports.php"><i class="fa-solid fa-file-medical"></i> COVID-19 Reports</a>
          <a href="../Admin/vaccines.php"><i class="fa-solid fa-syringe"></i> Manage Vaccines</a>
          <a href="../Admin/bookings.php" class="active"><i class="fa-solid fa-calendar-check"></i> Bookings</a>
          <a href="../Admin/profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
          <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
      </div>
    </aside>

    <div class="main">
      <header class="header">
        <div class="header-left">
          <button class="menu-toggle icon-btn" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
          <div class="left">

          </div>
        </div>

        <div class="header-right">
          <button class="icon-btn" id="themeToggle" title="Toggle theme"><i class="fa-solid fa-moon" id="themeIcon"></i></button>
          <button class="icon-btn" title="Notifications"><i class="fa-regular fa-bell"></i></button>

          <div class="profile" id="profileBtn">
            <div class="avatar">A</div>
            <div class="profile-name"><span>Admin</span><small class="muted">Administrator</small></div>
            <div class="dropdown" id="profileDropdown">
              <a href="../Admin/profile.php"><i class="fa-solid fa-user"></i> Profile</a>
              <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
          </div>
        </div>
      </header>

      <div class="content">
        <div class="page-header">
          <h1 class="page-title">Vaccine Bookings</h1>
        </div>

        <div class="bookings-table-container">
          <div class="table-header">
            <div class="table-search">
              <i class="fa-solid fa-search muted"></i>
              <input type="text" placeholder="Search by patient, hospital, or vaccine..." id="searchInput" />
            </div>
                      <div class="filter-container">
            <div class="filter-buttons">
              <button class="filter-btn active" data-filter="all">All</button>
              <button class="filter-btn" data-filter="approved">Approved</button>
              <button class="filter-btn" data-filter="rejected">Rejected</button>
              <button class="filter-btn" data-filter="pending">Pending</button>
            </div>
          </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Patient Name</th>
                <th>Hospital Name</th>
                <th>Vaccine Name</th>
                <th>Test Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="bookingsTableBody">
              <tr data-status="approved">
                <td>BK-1001</td>
                <td>Rajesh Kumar</td>
                <td>Apollo Hospital</td>
                <td>Pfizer-BioNTech</td>
                <td>2023-05-15</td>
                <td><span class="status-badge status-approved">Approved</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="view-btn view-btn-lg" data-id="1"><i class="fa-regular fa-eye"></i> View</button>
                  </div>
                </td>
              </tr>
              <tr data-status="rejected">
                <td>BK-1002</td>
                <td>Priya Sharma</td>
                <td>Max Healthcare</td>
                <td>Moderna</td>
                <td>2023-05-16</td>
                <td><span class="status-badge status-rejected">Rejected</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="view-btn view-btn-lg" data-id="2"><i class="fa-regular fa-eye"></i> View</button>
                  </div>
                </td>
              </tr>
              <tr data-status="pending">
                <td>BK-1003</td>
                <td>Vikram Singh</td>
                <td>Fortis Hospital</td>
                <td>AstraZeneca</td>
                <td>2023-05-18</td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="view-btn view-btn-lg" data-id="3"><i class="fa-regular fa-eye"></i> View</button>
                  </div>
                </td>
              </tr>
              <tr data-status="approved">
                <td>BK-1004</td>
                <td>Anjali Patel</td>
                <td>Medanta Hospital</td>
                <td>Pfizer-BioNTech</td>
                <td>2023-05-20</td>
                <td><span class="status-badge status-approved">Approved</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="view-btn view-btn-lg" data-id="4"><i class="fa-regular fa-eye"></i> View</button>
                  </div>
                </td>
              </tr>
              <tr data-status="pending">
                <td>BK-1005</td>
                <td>Sanjay Gupta</td>
                <td>AIIMS Delhi</td>
                <td>Covishield</td>
                <td>2023-05-22</td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="view-btn view-btn-lg" data-id="5"><i class="fa-regular fa-eye"></i> View</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Details Modal -->
  <div class="modal" id="bookingModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Booking Details</h2>
        <button class="close-btn" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="booking-details">
          <div class="detail-group">
            <div class="detail-label">Booking ID</div>
            <div class="detail-value" id="modal-booking-id">BK-1001</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Patient Name</div>
            <div class="detail-value" id="modal-patient-name">Rajesh Kumar</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Patient Age</div>
            <div class="detail-value" id="modal-patient-age">42</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Patient Contact</div>
            <div class="detail-value" id="modal-patient-contact">+91 9876543210</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Hospital Name</div>
            <div class="detail-value" id="modal-hospital-name">Apollo Hospital</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Vaccine Name</div>
            <div class="detail-value" id="modal-vaccine-name">Pfizer-BioNTech</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Booking Date</div>
            <div class="detail-value" id="modal-booking-date">2023-05-10</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Test Date</div>
            <div class="detail-value" id="modal-test-date">2023-05-15</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Time Slot</div>
            <div class="detail-value" id="modal-time-slot">10:00 AM - 11:00 AM</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Status</div>
            <div class="detail-value" id="modal-status"><span class="status-badge status-approved">Approved</span></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Approved/Rejected By</div>
            <div class="detail-value" id="modal-approved-by">Dr. Sharma</div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Notes</div>
            <div class="detail-value" id="modal-notes">Patient has completed all required pre-vaccination checks.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="view-btn" id="closeModalBtn">Close</button>
      </div>
    </div>
  </div>

  <script>
    // Toggle sidebar on mobile
    document.getElementById('menuToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('open');
    });

// Function to apply theme based on saved preference
function applyTheme() {
  const savedTheme = localStorage.getItem('theme');
  const icon = document.getElementById('themeIcon');

  if (savedTheme === 'dark') {
    document.body.classList.add('dark');
    icon.classList.remove('fa-moon');
    icon.classList.add('fa-sun');
  } else {
    document.body.classList.remove('dark');
    icon.classList.remove('fa-sun');
    icon.classList.add('fa-moon');
  }
}

// Call this on page load
applyTheme();

// Toggle theme on button click
document.getElementById('themeToggle').addEventListener('click', function() {
  document.body.classList.toggle('dark');
  const icon = document.getElementById('themeIcon');

  if (document.body.classList.contains('dark')) {
    icon.classList.remove('fa-moon');
    icon.classList.add('fa-sun');
    localStorage.setItem('theme', 'dark');
  } else {
    icon.classList.remove('fa-sun');
    icon.classList.add('fa-moon');
    localStorage.setItem('theme', 'light');
  }
});


    // Toggle profile dropdown
    document.getElementById('profileBtn').addEventListener('click', function() {
      document.getElementById('profileDropdown').style.display = 
        document.getElementById('profileDropdown').style.display === 'block' ? 'none' : 'block';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.closest('#profileBtn')) {
        document.getElementById('profileDropdown').style.display = 'none';
      }
    });

    // Booking data
    const bookings = [
      {
        id: 1,
        bookingId: "BK-1001",
        patientName: "Rajesh Kumar",
        patientAge: 42,
        patientContact: "+91 9876543210",
        hospitalName: "Apollo Hospital",
        vaccineName: "Pfizer-BioNTech",
        bookingDate: "2023-05-10",
        testDate: "2023-05-15",
        timeSlot: "10:00 AM - 11:00 AM",
        status: "Approved",
        approvedBy: "Dr. Sharma",
        notes: "Patient has completed all required pre-vaccination checks."
      },
      {
        id: 2,
        bookingId: "BK-1002",
        patientName: "Priya Sharma",
        patientAge: 35,
        patientContact: "+91 8765432109",
        hospitalName: "Max Healthcare",
        vaccineName: "Moderna",
        bookingDate: "2023-05-11",
        testDate: "2023-05-16",
        timeSlot: "11:30 AM - 12:30 PM",
        status: "Rejected",
        approvedBy: "Dr. Verma",
        notes: "Patient has not completed the required pre-screening questionnaire."
      },
      {
        id: 3,
        bookingId: "BK-1003",
        patientName: "Vikram Singh",
        patientAge: 50,
        patientContact: "+91 7654321098",
        hospitalName: "Fortis Hospital",
        vaccineName: "AstraZeneca",
        bookingDate: "2023-05-12",
        testDate: "2023-05-18",
        timeSlot: "2:00 PM - 3:00 PM",
        status: "Pending",
        approvedBy: "",
        notes: "Awaiting review from hospital staff."
      },
      {
        id: 4,
        bookingId: "BK-1004",
        patientName: "Anjali Patel",
        patientAge: 28,
        patientContact: "+91 6543210987",
        hospitalName: "Medanta Hospital",
        vaccineName: "Pfizer-BioNTech",
        bookingDate: "2023-05-13",
        testDate: "2023-05-20",
        timeSlot: "9:00 AM - 10:00 AM",
        status: "Approved",
        approvedBy: "Dr. Kapoor",
        notes: "All documents verified and approved."
      },
      {
        id: 5,
        bookingId: "BK-1005",
        patientName: "Sanjay Gupta",
        patientAge: 45,
        patientContact: "+91 5432109876",
        hospitalName: "AIIMS Delhi",
        vaccineName: "Covishield",
        bookingDate: "2023-05-14",
        testDate: "2023-05-22",
        timeSlot: "3:30 PM - 4:30 PM",
        status: "Pending",
        approvedBy: "",
        notes: "Vaccine availability confirmation pending."
      }
    ];

    // Modal functionality
    const modal = document.getElementById('bookingModal');
    const viewButtons = document.querySelectorAll('.view-btn');
    const closeModalBtn = document.getElementById('closeModal');
    const closeModalBtn2 = document.getElementById('closeModalBtn');

    viewButtons.forEach(button => {
      button.addEventListener('click', function() {
        const bookingId = this.getAttribute('data-id');
        const booking = bookings.find(b => b.id == bookingId);
        
        if (booking) {
          document.getElementById('modal-booking-id').textContent = booking.bookingId;
          document.getElementById('modal-patient-name').textContent = booking.patientName;
          document.getElementById('modal-patient-age').textContent = booking.patientAge;
          document.getElementById('modal-patient-contact').textContent = booking.patientContact;
          document.getElementById('modal-hospital-name').textContent = booking.hospitalName;
          document.getElementById('modal-vaccine-name').textContent = booking.vaccineName;
          document.getElementById('modal-booking-date').textContent = booking.bookingDate;
          document.getElementById('modal-test-date').textContent = booking.testDate;
          document.getElementById('modal-time-slot').textContent = booking.timeSlot;
          
          const statusElement = document.getElementById('modal-status');
          if (booking.status === "Approved") {
            statusElement.innerHTML = '<span class="status-badge status-approved">Approved</span>';
          } else if (booking.status === "Rejected") {
            statusElement.innerHTML = '<span class="status-badge status-rejected">Rejected</span>';
          } else {
            statusElement.innerHTML = '<span class="status-badge status-pending">Pending</span>';
          }
          
          document.getElementById('modal-approved-by').textContent = booking.approvedBy || "Not reviewed yet";
          document.getElementById('modal-notes').textContent = booking.notes;
          
          modal.classList.add('active');
        }
      });
    });

    closeModalBtn.addEventListener('click', function() {
      modal.classList.remove('active');
    });

    closeModalBtn2.addEventListener('click', function() {
      modal.classList.remove('active');
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === modal) {
        modal.classList.remove('active');
      }
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#bookingsTableBody tr');

    searchInput.addEventListener('input', function() {
      const searchText = this.value.toLowerCase();
      
      tableRows.forEach(row => {
        const patientName = row.cells[1].textContent.toLowerCase();
        const hospitalName = row.cells[2].textContent.toLowerCase();
        const vaccineName = row.cells[3].textContent.toLowerCase();
        
        if (patientName.includes(searchText) || hospitalName.includes(searchText) || vaccineName.includes(searchText)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });

    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Remove active class from all buttons
        filterButtons.forEach(btn => btn.classList.remove('active'));
        // Add active class to clicked button
        this.classList.add('active');
        
        const filter = this.getAttribute('data-filter');
        
        tableRows.forEach(row => {
          if (filter === 'all') {
            row.style.display = '';
          } else {
            const status = row.getAttribute('data-status');
            if (status === filter) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          }
        });
      });
    });
  </script>
</body>
</html>