<?php
include '../auth.php';
checkRole('admin');
include '../db_conn.php';

$admin_id = $_SESSION['admin_id'];

// Fetch admin data
$sql = "SELECT * FROM admin WHERE admin_id = $admin_id";
$result = $db_conn->query($sql);

if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
    $full_name = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
    $email = $admin_data['admin_email'];
} else {
    die("Admin not found!");
}

// Get filter and search parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the base query
$query = "SELECT 
    a.id as appointment_id,
    p.p_firstname, 
    p.p_lastname, 
    h.h_firstname as hospital_firstname,
    h.h_lastname as hospital_lastname,
    a.test_type,
    a.result_status,
    a.scheduled_date,
    a.created_at
FROM appointments a
JOIN patient p ON a.patient_id = p.p_id
JOIN hospital h ON a.hospital_id = h.h_id
WHERE h.h_status = 'approved'";

// Apply filters
if ($filter !== 'all') {
    $query .= " AND a.result_status = '$filter'";
}

// Apply search - search by patient name
if (!empty($search)) {
    $query .= " AND (p.p_firstname LIKE '%$search%' OR p.p_lastname LIKE '%$search%')";
}

// Order by creation date
$query .= " ORDER BY a.created_at ASC";

$result = $db_conn->query($query);
$appointments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Handle export functionality
if (isset($_POST['export'])) {
    $exportFilter = $_POST['exportFilter'];
    $exportTimeRange = $_POST['exportTimeRange'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    
    // Build export query
    $exportQuery = "SELECT 
        a.id as appointment_id,
        CONCAT(p.p_firstname, ' ', p.p_lastname) as patient_name,
        CONCAT(h.h_firstname, ' ', h.h_lastname) as hospital_name,
        a.test_type,
        a.result_status,
        a.scheduled_date,
        a.created_at
    FROM appointments a
    JOIN patient p ON a.patient_id = p.p_id
    JOIN hospital h ON a.hospital_id = h.h_id
    WHERE h.h_status = 'approved'";
    
    // Apply status filter
    if ($exportFilter !== 'all') {
        $exportQuery .= " AND a.result_status = '$exportFilter'";
    }
    
    // Apply date filter
    if ($exportTimeRange !== 'custom') {
        $today = date('Y-m-d');
        switch($exportTimeRange) {
            case 'today':
                $exportQuery .= " AND DATE(a.created_at) = '$today'";
                break;
            case 'week':
                $weekStart = date('Y-m-d', strtotime('this week'));
                $weekEnd = date('Y-m-d', strtotime('this week +6 days'));
                $exportQuery .= " AND DATE(a.created_at) BETWEEN '$weekStart' AND '$weekEnd'";
                break;
            case 'month':
                $monthStart = date('Y-m-01');
                $monthEnd = date('Y-m-t');
                $exportQuery .= " AND DATE(a.created_at) BETWEEN '$monthStart' AND '$monthEnd'";
                break;
        }
    } else if (!empty($startDate) && !empty($endDate)) {
        $exportQuery .= " AND DATE(a.created_at) BETWEEN '$startDate' AND '$endDate'";
    }
    
    $exportResult = $db_conn->query($exportQuery);
    $exportData = [];
    if ($exportResult) {
        while ($row = $exportResult->fetch_assoc()) {
            $exportData[] = $row;
        }
    }
    
    // Generate Excel file
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="covid_reports_'.date('Y-m-d').'.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Appointment ID</th>';
    echo '<th>Patient Name</th>';
    echo '<th>Hospital Name</th>';
    echo '<th>Test Type</th>';
    echo '<th>Result Status</th>';
    echo '<th>Scheduled Date</th>';
    echo '<th>Created At</th>';
    echo '</tr>';
    
    foreach ($exportData as $row) {
        echo '<tr>';
        echo '<td>'.$row['appointment_id'].'</td>';
        echo '<td>'.$row['patient_name'].'</td>';
        echo '<td>'.$row['hospital_name'].'</td>';
        echo '<td>'.ucfirst(str_replace('_', ' ', $row['test_type'])).'</td>';
        echo '<td>'.ucfirst(str_replace('_', ' ', $row['result_status'])).'</td>';
        echo '<td>'.$row['scheduled_date'].'</td>';
        echo '<td>'.$row['created_at'].'</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Patient Reports</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Admin/css/header.css">
  <style>
    /* Reports Page Styles */
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
      text-decoration: none;
      display: inline-block;
    }

    .filter-btn.active {
      background: var(--primary-600);
      color: white;
    }

    .reports-table-container {
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
    
    .status-positive {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }
    
    .status-negative {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
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
    
    .export-btn {
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

    .export-btn:hover {
      background: linear-gradient(135deg, rgba(13,148,136,0.1), rgba(15,118,110,0.05));
      color: var(--primary-600);
    }
    
    .view-btn-lg {
      padding: 9px 8px;
      font-size: 13px;
    }

    /* Export Controls */
    .export-controls {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      background: var(--card);
      padding: 16px;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      align-items: center;
      flex-wrap: wrap;
    }

    .export-controls label {
      font-weight: 600;
      color: var(--muted);
      font-size: 14px;
    }

    .export-controls select, 
    .export-controls input {
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--glass);
      color: var(--text);
    }

    .date-range {
      display: flex;
      gap: 8px;
      align-items: center;
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
      z-index: 1002;
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
      z-index: 1003;
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

    .report-details {
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
      .sidebar{position:fixed; left:-100%; top:0; transition:left .25s; z-index: 1001; width:260px}
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
      .report-details {
        grid-template-columns: 1fr;
      }
      .filter-container {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
      }
      .export-controls {
        flex-direction: column;
        align-items: flex-start;
      }
      .date-range {
        flex-direction: column;
        align-items: flex-start;
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
  <!-- Overlay for closing sidebar on mobile -->
  <div class="overlay" id="overlay"></div>

  <div class="layout">
<?php include './a_sidebar.php'; ?>

    <div class="main">
      <?php include './a_header.php'; ?>

      <div class="content">
        <!-- Export Controls -->
        <form method="POST" action="">
          <div class="export-controls">
            <label>Export Reports:</label>
            <select style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;" name="exportFilter" id="exportFilter">
              <option value="all">All Reports</option>
              <option value="positive">Positive Only</option>
              <option value="negative">Negative Only</option>
              <option value="pending">Pending Only</option>
            </select>
            
            <select name="exportTimeRange" id="exportTimeRange">
              <option value="custom">Custom Date Range</option>
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
            </select>
            
            <div class="date-range">
              <input type="date" name="startDate" id="startDate">
              <span>to</span>
              <input type="date" name="endDate" id="endDate">
            </div>
            
            <button type="submit" name="export" class="export-btn" id="exportBtn">
              <i class="fa-solid fa-download"></i> Export to Excel
            </button>
          </div>
        </form>

        <div class="reports-table-container">
          <form method="GET" action="" id="searchForm">
            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
            <div class="table-header">
              <div class="table-search">
                <i class="fa-solid fa-search muted"></i>
                <input type="text" name="search" placeholder="Search by patient name..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput" />
                <button type="submit" style="display:none">Search</button>
              </div>
              <div class="filter-container">
                <div class="filter-buttons">
                  <a href="javascript:void(0)" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all">All</a>
                  <a href="javascript:void(0)" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>" data-filter="pending">Pending</a>
                  <a href="javascript:void(0)" class="filter-btn <?php echo $filter === 'positive' ? 'active' : ''; ?>" data-filter="positive">Positive</a>
                  <a href="javascript:void(0)" class="filter-btn <?php echo $filter === 'negative' ? 'active' : ''; ?>" data-filter="negative">Negative</a>
                </div>
              </div>
            </div>
          </form>

          <table>
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Patient Name</th>
                <th>Hospital Name</th>
                <th>Test Type</th>
                <th>Test Result</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="reportsTableBody">
              <?php if (count($appointments) > 0): ?>
                <?php foreach ($appointments as $appointment): ?>
                  <tr data-status="<?php echo $appointment['result_status']; ?>">
                    <td><?php echo $appointment['appointment_id']; ?></td>
                    <td><?php echo htmlspecialchars($appointment['p_firstname'] . ' ' . $appointment['p_lastname']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['hospital_firstname'] . ' ' . $appointment['hospital_lastname']); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $appointment['test_type'])); ?></td>
                    <td>
                      <?php 
                      $status_class = 'status-pending';
                      if ($appointment['result_status'] === 'positive') {
                        $status_class = 'status-positive';
                      } elseif ($appointment['result_status'] === 'negative') {
                        $status_class = 'status-negative';
                      }
                      ?>
                      <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $appointment['result_status'])); ?>
                      </span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="view-btn view-btn-lg" data-id="<?php echo $appointment['appointment_id']; ?>">
                          <i class="fa-regular fa-eye"></i> View
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 20px;">
                    No appointments found.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Report Details Modal -->
  <div class="modal" id="reportModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Appointment Details</h2>
        <button class="close-btn" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="report-details">
          <?php
          // Get appointment details for modal
          if (isset($_GET['view_id'])) {
            $view_id = $_GET['view_id'];
            $viewQuery = "SELECT 
                a.id as appointment_id,
                p.p_firstname, 
                p.p_lastname, 
                h.h_firstname as hospital_firstname,
                h.h_lastname as hospital_lastname,
                a.test_type,
                a.result_status,
                a.scheduled_date,
                a.created_at
            FROM appointments a
            JOIN patient p ON a.patient_id = p.p_id
            JOIN hospital h ON a.hospital_id = h.h_id
            WHERE a.id = $view_id";
            
            $viewResult = $db_conn->query($viewQuery);
            if ($viewResult && $viewResult->num_rows > 0) {
              $appointment = $viewResult->fetch_assoc();
              ?>
              <div class="detail-group">
                <div class="detail-label">Booking ID</div>
                <div class="detail-value"><?php echo $appointment['appointment_id']; ?></div>
              </div>
              <div class="detail-group">
                <div class="detail-label">Patient Name</div>
                <div class="detail-value"><?php echo htmlspecialchars($appointment['p_firstname'] . ' ' . $appointment['p_lastname']); ?></div>
              </div>
              <div class="detail-group">
                <div class="detail-label">Hospital Name</div>
                <div class="detail-value"><?php echo htmlspecialchars($appointment['hospital_firstname'] . ' ' . $appointment['hospital_lastname']); ?></div>
              </div>
              <div class="detail-group">
                <div class="detail-label">Test Type</div>
                <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $appointment['test_type'])); ?></div>
              </div>
              <div class="detail-group">
                <div class="detail-label">Test Result</div>
                <div class="detail-value">
                  <?php 
                  $status_class = 'status-pending';
                  if ($appointment['result_status'] === 'positive') {
                    $status_class = 'status-positive';
                  } elseif ($appointment['result_status'] === 'negative') {
                    $status_class = 'status-negative';
                  }
                  ?>
                  <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $appointment['result_status'])); ?>
                  </span>
                </div>
              </div>
              <div class="detail-group">
                <div class="detail-label">Test Date</div>
                <div class="detail-value"><?php echo $appointment['scheduled_date'] ? $appointment['scheduled_date'] : 'Not scheduled'; ?></div>
              </div>
              <div class="detail-group">
                <div class="detail-label">Result Date</div>
                <div class="detail-value"><?php echo date('M j, Y', strtotime($appointment['created_at'])); ?></div>
              </div>
              <?php
            }
          }
          ?>
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
      document.getElementById('overlay').classList.toggle('active');
    });

    // Close sidebar when clicking on overlay
    document.getElementById('overlay').addEventListener('click', function() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('overlay').classList.remove('active');
    });

    // Close sidebar with close button
    document.getElementById('sidebarClose').addEventListener('click', function() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('overlay').classList.remove('active');
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

    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const searchForm = document.getElementById('searchForm');
    const filterInput = searchForm.querySelector('input[name="filter"]');
    
    filterButtons.forEach(button => {
      button.addEventListener('click', function() {
        const filter = this.getAttribute('data-filter');
        filterInput.value = filter;
        searchForm.submit();
      });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
      if (this.value === '') {
        searchForm.submit();
      }
    });

    // Modal functionality
    const modal = document.getElementById('reportModal');
    const viewButtons = document.querySelectorAll('.view-btn');
    const closeModalBtn = document.getElementById('closeModal');
    const closeModalBtn2 = document.getElementById('closeModalBtn');

    viewButtons.forEach(button => {
      button.addEventListener('click', function() {
        const appointmentId = this.getAttribute('data-id');
        window.location.href = '?view_id=' + appointmentId + '&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>';
      });
    });

    <?php if (isset($_GET['view_id'])): ?>
      document.addEventListener('DOMContentLoaded', function() {
        modal.classList.add('active');
      });
    <?php endif; ?>

    closeModalBtn.addEventListener('click', function() {
      modal.classList.remove('active');
      window.location.href = '?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>';
    });

    closeModalBtn2.addEventListener('click', function() {
      modal.classList.remove('active');
      window.location.href = '?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === modal) {
        modal.classList.remove('active');
        window.location.href = '?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>';
      }
    });

    // Set default dates for date inputs
    document.getElementById('startDate').valueAsDate = new Date();
    document.getElementById('endDate').valueAsDate = new Date();
  </script>
</body>
</html>