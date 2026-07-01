<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Approval</title>
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
      background: linear-gradient(90deg, rgba(20,184,166,0.12), rgba(13,148,136,0.06)); 
      color: var(--accent);
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
    .profile-name {
      display: flex; 
      flex-direction: column; 
      align-items: flex-start;
    }
    .profile-name span {
      font-size: 13px; 
      font-weight: 700; 
      color: var(--text);
    }
    .profile-name small {
      font-size: 11px; 
      color: var(--muted); 
      margin-top: 2px;
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

    /* Approval Page Styles */
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
      flex-wrap: wrap;
      background: var(--glass);
      border-radius: 8px;
      padding: 4px;
      border: 1px solid var(--border);
      gap: 4px;
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
      font-size: 14px;
    }

    .filter-btn.active {
      background: var(--primary-600);
      color: white;
    }

    .approval-table-container {
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
    
    .status-pending {
      background-color: rgba(245, 158, 11, 0.1);
      color: var(--warning);
    }
    
    .status-approved {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }
    
    .status-rejected {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }

    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .approve-btn {
      background: linear-gradient(135deg, var(--success), #0ca678);
      color: white;
    }

    .approve-btn:hover {
      background: linear-gradient(135deg, #0ca678, var(--success));
      transform: translateY(-2px);
    }

    .reject-btn {
      background: linear-gradient(135deg, var(--danger), #c92a2a);
      color: white;
    }

    .reject-btn:hover {
      background: linear-gradient(135deg, #c92a2a, var(--danger));
      transform: translateY(-2px);
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
      .filter-container {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
      }
      .table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
      .filter-buttons {
        width: 100%;
        justify-content: center;
      }
      .action-buttons {
        flex-direction: column;
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
          <a href="../Hospital/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        </nav>
      </div>

      <div class="nav-section">
        <div class="nav-title">Main</div>
        <nav class="nav" id="mainNav">
          <a href="../Hospital/approval.php" class="active"><i class="fa-solid fa-user-check"></i> Approval</a>
          <a href="../Hospital/patients.php"><i class="fa-regular fa-user"></i> Patients</a>
          <a href="../Hospital/covid_test_update.php"><i class="fa fa-file-medical"></i> COVID-19 Test</a>
          <a href="../Hospital/vaccine_update.php"><i class="fa-solid fa-file-medical"></i> Vaccination</a>
          <a href="../Hospital/profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
          <a href="../Hospital/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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
              <a href="../Hospital/profile.php"><i class="fa-solid fa-user"></i> Profile</a>
              <a href="../Hospital/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
          </div>
        </div>
      </header>

      <div class="content">
        <div class="page-header">
          <h1 class="page-title">COVID Test Approval Requests</h1>
        </div>

        <div class="approval-table-container">
          <div class="table-header">
            <div class="filter-container">
              <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="approved">Approved</button>
                <button class="filter-btn" data-filter="rejected">Rejected</button>
              </div>
            </div>
            <div class="table-search">
              <i class="fa-solid fa-search muted"></i>
              <input type="text" placeholder="Search by patient name..." id="searchInput" />
            </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Patient Name</th>
                <th>Age</th>
                <th>Phone Number</th>
                <th>Test Type</th>
                <th>Request Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="approvalTableBody">
              <tr data-status="pending">
                <td>REQ-2001</td>
                <td>John Doe</td>
                <td>35</td>
                <td>+1 234-567-8901</td>
                <td>RT-PCR</td>
                <td>Apr 20, 2023</td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" data-id="1"><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" data-id="1"><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="pending">
                <td>REQ-2002</td>
                <td>Jane Smith</td>
                <td>28</td>
                <td>+1 345-678-9012</td>
                <td>Rapid Antigen</td>
                <td>Apr 21, 2023</td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" data-id="2"><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" data-id="2"><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="approved">
                <td>REQ-2003</td>
                <td>Robert Johnson</td>
                <td>42</td>
                <td>+1 456-789-0123</td>
                <td>RT-PCR</td>
                <td>Apr 19, 2023</td>
                <td><span class="status-badge status-approved">Approved</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" disabled><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" disabled><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="rejected">
                <td>REQ-2004</td>
                <td>Sarah Williams</td>
                <td>31</td>
                <td>+1 567-890-1234</td>
                <td>Rapid Antigen</td>
                <td>Apr 18, 2023</td>
                <td><span class="status-badge status-rejected">Rejected</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" disabled><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" disabled><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="pending">
                <td>REQ-2005</td>
                <td>Michael Brown</td>
                <td>52</td>
                <td>+1 678-901-2345</td>
                <td>RT-PCR</td>
                <td>Apr 22, 2023</td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" data-id="5"><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" data-id="5"><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="approved">
                <td>REQ-2006</td>
                <td>Emily Davis</td>
                <td>24</td>
                <td>+1 789-012-3456</td>
                <td>Rapid Antigen</td>
                <td>Apr 17, 2023</td>
                <td><span class="status-badge status-approved">Approved</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" disabled><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" disabled><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="pending">
                <td>REQ-2007</td>
                <td>David Wilson</td>
                <td>38</td>
                <td>+1 890-123-4567</td>
                <td>RT-PCR</td>
                <td>Apr 23, 2023</td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" data-id="7"><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" data-id="7"><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <tr data-status="rejected">
                <td>REQ-2008</td>
                <td>Jessica Taylor</td>
                <td>29</td>
                <td>+1 901-234-5678</td>
                <td>Rapid Antigen</td>
                <td>Apr 16, 2023</td>
                <td><span class="status-badge status-rejected">Rejected</span></td>
                <td>
                  <div class="action-buttons">
                    <button class="btn approve-btn" disabled><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn reject-btn" disabled><i class="fa-solid fa-xmark"></i> Reject</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Toggle sidebar on mobile
    document.getElementById('menuToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('open');
    });

    // Toggle theme
    document.getElementById('themeToggle').addEventListener('click', function() {
      document.body.classList.toggle('dark');
      const icon = document.getElementById('themeIcon');
      if (document.body.classList.contains('dark')) {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
      } else {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
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

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#approvalTableBody tr');

    searchInput.addEventListener('input', function() {
      const searchText = this.value.toLowerCase();
      
      tableRows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        if (name.includes(searchText)) {
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

    // Approve/Reject functionality
    const approveButtons = document.querySelectorAll('.approve-btn');
    const rejectButtons = document.querySelectorAll('.reject-btn');
    
    approveButtons.forEach(button => {
      button.addEventListener('click', function() {
        if (!this.disabled) {
          const requestId = this.getAttribute('data-id');
          const row = this.closest('tr');
          
          // Update status in the table
          row.setAttribute('data-status', 'approved');
          row.cells[6].innerHTML = '<span class="status-badge status-approved">Approved</span>';
          
          // Disable action buttons
          const actionButtons = row.querySelectorAll('.btn');
          actionButtons.forEach(btn => {
            btn.disabled = true;
          });
          
          // Show confirmation (in a real app, this would send data to the server)
          alert(`Request ${requestId} has been approved successfully.`);
        }
      });
    });
    
    rejectButtons.forEach(button => {
      button.addEventListener('click', function() {
        if (!this.disabled) {
          const requestId = this.getAttribute('data-id');
          const row = this.closest('tr');
          
          // Update status in the table
          row.setAttribute('data-status', 'rejected');
          row.cells[6].innerHTML = '<span class="status-badge status-rejected">Rejected</span>';
          
          // Disable action buttons
          const actionButtons = row.querySelectorAll('.btn');
          actionButtons.forEach(btn => {
            btn.disabled = true;
          });
          
          // Show confirmation (in a real app, this would send data to the server)
          alert(`Request ${requestId} has been rejected.`);
        }
      });
    });
  </script>
</body>
</html>