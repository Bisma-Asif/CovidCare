<?php
include '../auth.php';
checkRole( 'hospital' );
include '../db_conn.php';

$hospital_id = $_SESSION[ 'hospital_id' ] ?? 0;

// Fetch hospital data
$sql = "SELECT * FROM hospital WHERE h_id = $hospital_id";
$result = $db_conn->query( $sql );

if ( $result && $result->num_rows > 0 ) {
    $hospital = $result->fetch_assoc();
    $full_name = $hospital[ 'h_firstname' ] . ' ' . $hospital[ 'h_lastname' ];
} else {
    die( 'Hospital not found!' );
}

// Handle result update
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'update_result' ] ) ) {
    $appointment_id = $_POST[ 'appointment_id' ];
    $result_status = $_POST[ 'result_status' ];

    $update_sql = "UPDATE appointments SET result_status = '$result_status', 
                  updated_at = NOW() 
                  WHERE id = $appointment_id AND hospital_id = $hospital_id";

    if ( $db_conn->query( $update_sql ) ) {
        $success_message = 'Result updated successfully!';

        // If it's a vaccination and status is vaccinated, update vaccine stock
        if ($result_status === 'vaccinated') {
            // Get vaccine ID from appointment
            $vaccine_sql = "SELECT vaccine_id FROM appointments WHERE id = $appointment_id";
            $vaccine_result = $db_conn->query($vaccine_sql);
            
            if ($vaccine_result && $vaccine_result->num_rows > 0) {
                $appointment_data = $vaccine_result->fetch_assoc();
                $vaccine_id = $appointment_data['vaccine_id'];
                
                if ($vaccine_id) {
                    // Update vaccine stock
                    $update_stock_sql = "UPDATE vaccines SET used_stock = used_stock + 1 
                                        WHERE id = $vaccine_id";
                    $db_conn->query($update_stock_sql);
                }
            }
        }
    } else {
        $error_message = "Error updating result: " . $db_conn->error;
    }
}

// Handle filters
$test_type_filter = isset($_GET['test_type']) ? $_GET['test_type'] : 'all';
$result_filter = isset($_GET['result_status']) ? $_GET['result_status'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build query to fetch appointments for the logged-in hospital
$query = "SELECT a.id, a.patient_id, a.hospital_id, a.test_type, a.vaccine_id, 
                 a.status, a.result_status, a.scheduled_date, a.scheduled_time,
                 a.created_at, a.updated_at,
                 CONCAT(p.p_firstname, ' ', p.p_lastname) AS patient_name,
                 p.p_phone, p.p_address, p.p_city, p.p_dob, p.p_gender,
                 v.name AS vaccine_name
          FROM appointments a
          LEFT JOIN patient p ON a.patient_id = p.p_id
          LEFT JOIN vaccines v ON a.vaccine_id = v.id
          WHERE a.hospital_id = $hospital_id AND a.status = 'confirmed'";

// Apply filters
if ($test_type_filter !== 'all') {
    $query .= " AND a.test_type = '$test_type_filter'";
}

if ($result_filter !== 'all') {
    $query .= " AND a.result_status = '$result_filter'";
}

if (!empty($search_query)) {
    $query .= " AND (CONCAT(p.p_firstname, ' ', p.p_lastname) LIKE '%$search_query%' OR p.p_firstname LIKE '%$search_query%' OR p.p_lastname LIKE '%$search_query%')";
}

$query .= " ORDER BY a.created_at ASC";

$result = $db_conn->query($query);
$appointments = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Update Results</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Hospital/css/header.css">
  <style>
    .content{
        margin: 10px 15px;
        cursor: pointer;
    }

    /* Container Styles */
    .results-container {
      padding: 5px;
      margin: 0px auto;
      max-width: 1400px;
      animation: fadeInScale 0.6s ease;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 15px;
      padding: 15px;
      border-bottom: 1px solid var(--border);
    }

    .page-title {
      font-size: 24px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      text-align: center;
      justify-content: center;
      margin-bottom: 20px;
      position: relative;
    }
    .page-title:after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 3px;
      background: linear-gradient(135deg, var(--accent), var(--accent));
      border-radius: 2px;
    }

    .controls-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }

    .filter-tabs {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .filter-tab {
      padding: 10px 20px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: var(--bg);
      color: var(--text);
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      text-decoration: none;
    }

    .filter-tab:hover {
      border-color: var(--accent);
      transform: translateY(-2px);
    }

    .filter-tab.active {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border-color: var(--accent);
    }

    .search-form {
      display: flex;
      gap: 10px;
      align-items: center;
      position: relative;
    }

    .search-form input {
      padding: 10px 15px 10px 40px;
      border: 1px solid var(--primary-600);
      border-radius: 6px;
      background: var(--bg);
      color: var(--text);
      min-width: 250px;
      transition: all 0.3s ease;
    }
    
    .search-form input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
    }

    .search-form button {
      padding: 10px 20px;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .search-form button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .search-form i {
      position: absolute;
      left: 15px;
      z-index: 1;
      color: var(--muted);
    }

    /* Table Styles */
    .results-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    .results-table th {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 14px;
      letter-spacing: 0.5px;
    }

    .results-table td {
      padding: 15px;
      border-bottom: 1px solid var(--border);
      transition: all 0.3s ease;
    }

    .results-table tr {
      transition: all 0.3s ease;
    }

    .results-table tr:hover {
      background: rgba(13, 148, 136, 0.05);
      transform: translateY(-1px);
    }

    .results-table tr:hover td {
      border-color: var(--accent);
    }

    .update-btn {
      padding: 8px 16px;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .update-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      background: linear-gradient(135deg, var(--accent), var(--accent));
    }

    .update-btn:disabled {
      background: var(--muted);
      cursor: not-allowed;
      opacity: 0.7;
    }

    /* Status badges */
    .status-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
    }

    .status-approved, .status-confirmed {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .status-rejected, .status-cancelled {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .result-pending {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
    }

    .result-positive {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .result-negative {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .result-vaccinated {
      background: rgba(139, 92, 246, 0.2);
      color: #8b5cf6;
    }

    .result-not_vaccinated {
      background: rgba(107, 114, 128, 0.2);
      color: #6b7280;
    }

    .no-results {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }

    .no-results i {
      font-size: 64px;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .no-results h3 {
      font-size: 20px;
      margin-bottom: 10px;
      color: var(--text);
    }

    .no-results p {
      font-size: 16px;
      max-width: 400px;
      margin: 0 auto;
    }

    @keyframes fadeInScale {
      from {
        opacity: 0;
        transform: scale(0.95);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1002;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background-color: var(--card);
      border-radius: var(--radius);
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      animation: modalFadeIn 0.3s;
      z-index: 1003;
    }

    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal-header {
      padding: 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border-radius: var(--radius) var(--radius) 0 0;
    }

    .modal-title {
      margin: 0;
      font-size: 1.5rem;
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: white;
      cursor: pointer;
      transition: transform 0.2s;
    }

    .close-btn:hover {
      transform: scale(1.1);
    }

    .modal-body {
      padding: 20px;
    }

    .patient-details {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
      margin-bottom: 20px;
    }

    @media (min-width: 768px) {
      .patient-details {
        grid-template-columns: 1fr 1fr;
      }
    }

    .detail-group {
      margin-bottom: 15px;
    }

    .detail-label {
      font-weight: 600;
      color: var(--muted);
      font-size: 0.9rem;
      margin-bottom: 5px;
      display: block;
    }

    .detail-value {
      font-size: 1rem;
      color: var(--text);
      padding: 10px;
      background: var(--bg);
      border-radius: 6px;
      border: 1px solid var(--border);
      display: block;
      width: 100%;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text);
    }

    .form-select {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--bg);
      color: var(--text);
      transition: all 0.3s ease;
      font-size: 16px;
    }

    .form-select:focus {
      outline: none;
      border-color: var(--primary-600);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
    }

    .modal-footer {
      padding: 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn {
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      font-size: 16px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .btn-secondary {
      background: var(--muted);
      color: white;
    }

    .btn-secondary:hover {
      background: #475569;
    }

    /* Alert styles */
    .alert {
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .alert-success {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* Responsive design */
    @media (max-width: 1024px) {
      .results-table {
        display: block;
        overflow-x: auto;
      }
      
      .results-table th, 
      .results-table td {
        min-width: 120px;
      }
      
      .controls-container {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .filter-tabs {
        width: 100%;
        justify-content: center;
      }
      
      .search-form {
        width: 100%;
      }
      
      .search-form input {
        min-width: auto;
        flex-grow: 1;
      }
    }

    @media (max-width: 768px) {
      .results-container {
        padding: 15px;
        margin: 10px;
      }
      
      .page-title {
        font-size: 20px;
      }
      
      .filter-tabs {
        flex-direction: column;
        gap: 10px;
      }
      
      .filter-tab {
        width: 100%;
        justify-content: center;
      }
      
      .search-form {
        flex-direction: column;
        width: 100%;
      }
      
      .search-form input {
        width: 100%;
      }
      
      .search-form button {
        width: 100%;
      }
      
      .modal-content {
        margin: 10px;
      }
    }
  </style>
</head>
<body>
  <!-- Overlay for closing sidebar on mobile -->
  <div class="overlay" id="overlay"></div>

  <div class="layout">
    <?php include './h_sidebar.php'; ?>
    
    <div class="main">
      <?php include './h_header.php'; ?>

      <!-- Main Section --> 
      <div class="content">
        <div class="results-container">
          <div class="page-header">
            <h1 class="page-title">
              <i class="fa-solid fa-file-medical"></i>
              Update Test Results
            </h1>
            
            <div class="controls-container">
              <div class="filter-tabs">
                <a href="?test_type=all&result_status=<?php echo $result_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-tab <?php echo $test_type_filter === 'all' ? 'active' : ''; ?>">
                  <i class="fa-solid fa-list"></i> All Types
                </a>
                <a href="?test_type=covid_test&result_status=<?php echo $result_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-tab <?php echo $test_type_filter === 'covid_test' ? 'active' : ''; ?>">
                  <i class="fa-solid fa-vial"></i> COVID Tests
                </a>
                <a href="?test_type=vaccination&result_status=<?php echo $result_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-tab <?php echo $test_type_filter === 'vaccination' ? 'active' : ''; ?>">
                  <i class="fa-solid fa-syringe"></i> Vaccinations
                </a>
              </div>
              
              <form method="GET" class="search-form">
                <input type="hidden" name="test_type" value="<?php echo $test_type_filter; ?>">
                <input type="hidden" name="result_status" value="<?php echo $result_filter; ?>">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by patient name..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
                <?php if (!empty($search_query)): ?>
                  <a href="?test_type=<?php echo $test_type_filter; ?>&result_status=<?php echo $result_filter; ?>" class="filter-tab" style="margin-left: 10px;">
                    <i class="fa-solid fa-times"></i> Clear
                  </a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
              <i class="fa-solid fa-circle-check"></i> <?php echo $success_message; ?>
            </div>
          <?php endif; ?>
          
          <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
              <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?>
            </div>
          <?php endif; ?>

          <?php if (count($appointments) > 0): ?>
            <table class="results-table">
              <thead>
                <tr>
                  <th>Appointment ID</th>
                  <th>Patient Name</th>
                  <th>Test Type</th>
                  <th>Vaccine</th>
                  <th>Appointment Date</th>
                  <th>Current Result</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($appointments as $appointment): 
                  // Check if result is already final (not pending)
                  $is_final_result = !in_array($appointment['result_status'], ['pending']);
                ?>
                  <tr>
                    <td>#<?php echo $appointment['id']; ?></td>
                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                    <td>
                      <span class="status-badge">
                        <?php echo $appointment['test_type'] === 'covid_test' ? 'COVID Test' : 'Vaccination'; ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($appointment['test_type'] === 'vaccination' && $appointment['vaccine_name']): ?>
                        <?php echo htmlspecialchars($appointment['vaccine_name']); ?>
                      <?php else: ?>
                        N/A
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($appointment['scheduled_date']): ?>
                        <?php echo date('M d, Y', strtotime($appointment['scheduled_date'])); ?>
                      <?php else: ?>
                        Not scheduled
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="status-badge result-<?php echo $appointment['result_status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $appointment['result_status'])); ?>
                      </span>
                    </td>
                    <td>
                      <button class="update-btn update-result-btn" 
                              data-id="<?php echo $appointment['id']; ?>"
                              data-patient="<?php echo htmlspecialchars($appointment['patient_name']); ?>"
                              data-test-type="<?php echo $appointment['test_type']; ?>"
                              data-vaccine="<?php echo $appointment['vaccine_name'] ? htmlspecialchars($appointment['vaccine_name']) : 'N/A'; ?>"
                              data-current-result="<?php echo $appointment['result_status']; ?>"
                              <?php echo $is_final_result ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-pen-to-square"></i> 
                        <?php echo $is_final_result ? 'Completed' : 'Update'; ?>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="no-results">
              <i class="fa-regular fa-file-medical"></i>
              <h3>No appointments found</h3>
              <p>
                <?php if ($test_type_filter !== 'all' || $result_filter !== 'all' || !empty($search_query)): ?>
                  No appointments match your current filters.
                <?php else: ?>
                  No confirmed appointments found for your hospital.
                <?php endif; ?>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Result Modal -->
  <div class="modal" id="updateResultModal">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h2 class="modal-title">Update Test Result</h2>
          <button type="button" class="close-btn" id="closeModal">&times;</button>
        </div>
        
        <div class="modal-body">
          <input type="hidden" name="appointment_id" id="appointment_id">
          
          <div class="patient-details">
            <div class="detail-group">
              <span class="detail-label">Patient Name</span>
              <span class="detail-value" id="modal-patient-name"></span>
            </div>
            
            <div class="detail-group">
              <span class="detail-label">Test Type</span>
              <span class="detail-value" id="modal-test-type"></span>
            </div>
            
            <div class="detail-group">
              <span class="detail-label">Vaccine</span>
              <span class="detail-value" id="modal-vaccine"></span>
            </div>
            
            <div class="detail-group">
              <span class="detail-label">Current Result</span>
              <span class="detail-value" id="modal-current-result"></span>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="result_status">Update Result To</label>
            <select class="form-select" name="result_status" id="result_status" required>
              <option value="">Select Result</option>
              <option value="pending">Pending</option>
              <option value="positive">Positive (COVID Test)</option>
              <option value="negative">Negative (COVID Test)</option>
              <option value="vaccinated">Vaccinated</option>
              <option value="not_vaccinated">Not Vaccinated</option>
            </select>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelUpdate">Cancel</button>
          <button type="submit" class="btn btn-primary" name="update_result">Update Result</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // JavaScript for sidebar, theme toggle, etc.
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const overlay = document.getElementById('overlay');

    function openSidebar() {
      sidebar?.classList.add('open');
      overlay?.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      sidebar?.classList.remove('open');
      overlay?.classList.remove('active');
      document.body.style.overflow = '';
    }

    menuToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // Close sidebar when a navigation link is clicked (on mobile)
    const navLinks = document.querySelectorAll('.nav a');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 1024) {
          closeSidebar();
        }
      });
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
    document.getElementById('profileBtn')?.addEventListener('click', function() {
      const dropdown = document.getElementById('profileDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.closest('#profileBtn')) {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown) dropdown.style.display = 'none';
      }
    });

    // Update Result Modal
    const updateModal = document.getElementById('updateResultModal');
    const updateButtons = document.querySelectorAll('.update-result-btn:not( :disabled )');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelUpdateBtn = document.getElementById('cancelUpdate');
    
    // Open modal with appointment data
    updateButtons.forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('appointment_id').value = this.getAttribute('data-id');
        document.getElementById('modal-patient-name').textContent = this.getAttribute('data-patient');
        
        const testType = this.getAttribute('data-test-type');
        document.getElementById('modal-test-type').textContent = testType === 'covid_test' ? 'COVID Test' : 'Vaccination';
        
        document.getElementById('modal-vaccine').textContent = this.getAttribute('data-vaccine');
        
        const currentResult = this.getAttribute('data-current-result');
        document.getElementById('modal-current-result').textContent = currentResult.replace('_', ' ');
        
        // Reset and set appropriate options based on test type
        const resultSelect = document.getElementById('result_status');
        resultSelect.innerHTML = '';
        
        const options = {
          'covid_test': [
            {value: 'pending', text: 'Pending'},
            {value: 'positive', text: 'Positive'},
            {value: 'negative', text: 'Negative'}
          ],
          'vaccination': [
            {value: 'pending', text: 'Pending'},
            {value: 'vaccinated', text: 'Vaccinated'},
            {value: 'not_vaccinated', text: 'Not Vaccinated'}
          ]
        };
        
        options[testType].forEach(option => {
          const opt = document.createElement('option');
          opt.value = option.value;
          opt.textContent = option.text;
          if (option.value === currentResult) {
            opt.selected = true;
          }
          resultSelect.appendChild(opt);
        });
        
        updateModal.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    });
    
    // Close modal
    function closeModal() {
      updateModal.classList.remove('active');
      document.body.style.overflow = '';
    }
    
    closeModalBtn.addEventListener('click', closeModal);
    cancelUpdateBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function( event ) {
        if ( event.target === updateModal ) {
            closeModal();
        }
    }
);
</script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>