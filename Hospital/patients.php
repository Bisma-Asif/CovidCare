<?php
include '../auth.php';
checkRole('hospital');
include '../db_conn.php';

$hospital_id = $_SESSION['hospital_id'] ?? 0;

// Fetch hospital data
$sql = "SELECT * FROM hospital WHERE h_id = $hospital_id";
$result = $db_conn->query($sql);

if ($result && $result->num_rows > 0) {
    $hospital = $result->fetch_assoc();
    $full_name = ($hospital['h_firstname'] ?? 'N/A') . ' ' . ($hospital['h_lastname'] ?? '');
} else {
    die("Hospital not found!");
}

// Fetch approved patients for this hospital
$approved_patients_query = "
    SELECT p.p_id, p.p_firstname, p.p_lastname, p.p_email, p.p_address, p.p_city
    FROM patient p
    INNER JOIN appointments a ON p.p_id = a.patient_id
    WHERE a.hospital_id = $hospital_id AND a.status = 'approved'
    GROUP BY p.p_id
    ORDER BY p.p_firstname, p.p_lastname
";

$approved_patients_result = $db_conn->query($approved_patients_query);
$patients = [];

if ($approved_patients_result && $approved_patients_result->num_rows > 0) {
    while ($row = $approved_patients_result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Handle patient details view
$patient_details = null;
if (isset($_GET['view_patient'])) {
    $patient_id = intval($_GET['view_patient']);
    
    // Verify that this patient has an approved appointment with this hospital
    $verify_query = "
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE patient_id = $patient_id 
        AND hospital_id = $hospital_id 
        AND status = 'approved'
    ";
    
    $verify_result = $db_conn->query($verify_query);
    $verify_data = $verify_result->fetch_assoc();
    
    if ($verify_data['count'] > 0) {
        // Fetch patient details
        $patient_query = "SELECT * FROM patient WHERE p_id = $patient_id";
        $patient_result = $db_conn->query($patient_query);
        
        if ($patient_result && $patient_result->num_rows > 0) {
            $patient_details = $patient_result->fetch_assoc();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Patients</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Patient/css/header.css"> 
  <style>
    .content{
        margin: 10px 15px;
        cursor: pointer;
    }

    /* Table Styles */
    .patients-table-container {
      padding: 5px;
      margin: 0px auto;
      max-width: 1200px;
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

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }

    .table-search {
      display: flex;
      gap: 10px;
      align-items: center;
      position: relative;
    }

    .table-search input {
      padding: 10px 15px 10px 40px;
      border: 1px solid var(--primary-600);
      border-radius: 6px;
      background: var(--bg);
      color: var(--text);
      min-width: 250px;
      transition: all 0.3s ease;
    }
    
    .table-search input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
    }

    .table-search i {
      position: absolute;
      left: 15px;
      z-index: 1;
      color: var(--muted);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    table th {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 14px;
      letter-spacing: 0.5px;
    }

    table td {
      padding: 15px;
      border-bottom: 1px solid var(--border);
      transition: all 0.3s ease;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    table tr {
      transition: all 0.3s ease;
    }

    table tr:hover {
      background: rgba(13, 148, 136, 0.05);
      transform: translateY(-1px);
    }

    table tr:hover td {
      border-color: var(--accent);
    }

    .view-btn {
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
      text-decoration: none;
    }

    .view-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      background: linear-gradient(135deg, var(--accent), var(--accent));
    }

    .no-results {
      text-align: center;
      padding: 40px;
      color: var(--muted);
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
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background-color: var(--card);
      border-radius: var(--radius);
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
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
      text-decoration: none;
    }

    .close-btn:hover {
      transform: scale(1.1);
    }

    .modal-body {
      padding: 20px;
    }

    .patient-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .detail-group {
      margin-bottom: 15px;
    }

    .detail-label {
      font-weight: 600;
      color: var(--muted);
      font-size: 0.9rem;
      margin-bottom: 5px;
    }

    .detail-value {
      font-size: 1rem;
      color: var(--text);
      padding: 8px 12px;
      background: var(--bg);
      border-radius: 4px;
      border: 1px solid var(--border);
    }

    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border: none;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* Responsive table */
    @media (max-width: 1024px) {
      table {
        display: block;
        overflow-x: auto;
      }
      
      table th, 
      table td {
        min-width: 120px;
      }
      
      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .table-search {
        width: 100%;
      }
      
      .table-search input {
        min-width: auto;
        flex-grow: 1;
      }

      .patient-details {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .patients-table-container {
        padding: 15px;
        margin: 10px;
      }
      
      .page-title {
        font-size: 20px;
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

      <div class="content">
        <div class="patients-table-container">
          <div class="page-header">
            <div class="left"></div>
              <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search patients...">
              </div>
          </div>

          <?php if (count($patients) > 0): ?>
            <table id="patientsTable">
              <thead>
                <tr>
                  <th>Patient ID</th>
                  <th>Name</th>
                  <th>Address</th>
                  <th>City</th>
                  <th>Email</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($patients as $patient): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($patient['p_id']); ?></td>
                    <td><?php echo htmlspecialchars($patient['p_firstname'] . ' ' . $patient['p_lastname']); ?></td>
                    <td><?php echo htmlspecialchars($patient['p_address']); ?></td>
                    <td><?php echo htmlspecialchars($patient['p_city']); ?></td>
                    <td><?php echo htmlspecialchars($patient['p_email']); ?></td>
                    <td>
                      <a href="?view_patient=<?php echo $patient['p_id']; ?>" class="view-btn">
                        <i class="fas fa-eye"></i> View Details
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="no-results">
              <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
              <h3>No Approved Patients</h3>
              <p>Patients who have been approved for appointments will appear here.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Patient Details Modal -->
  <?php if ($patient_details): ?>
  <div class="modal active" id="patientModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Patient Details</h2>
        <a href="?" class="close-btn">&times;</a>
      </div>
      <div class="modal-body">
        <div class="patient-details">
          <div class="detail-group">
            <div class="detail-label">Patient ID</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_id']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">First Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_firstname']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Last Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_lastname']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Email</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_email']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Phone</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_phone']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Date of Birth</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_dob']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Gender</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_gender']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Address</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_address']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">City</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_city']); ?></div>
          </div>
          <div class="detail-group">
            <div class="detail-label">Registration Date</div>
            <div class="detail-value"><?php echo htmlspecialchars($patient_details['created_at']); ?></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="?" class="btn btn-primary">Close</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

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

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
      const searchText = this.value.toLowerCase();
      const rows = document.querySelectorAll('#patientsTable tbody tr');
      
      rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const address = row.cells[2].textContent.toLowerCase();
        const city = row.cells[3].textContent.toLowerCase();
        const email = row.cells[4].textContent.toLowerCase();
        
        if (name.includes(searchText) || address.includes(searchText) || 
            city.includes(searchText) || email.includes(searchText)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });

    // Close modal with escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        window.location.href = '?';
      }
    });
  </script>
</body>
</html>