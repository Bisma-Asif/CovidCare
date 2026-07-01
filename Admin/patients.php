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
    $password = $admin_data['admin_password'];
} else {
    die("Admin not found!");
}

// Fetch all patients from database
$sql = "SELECT * FROM patient ORDER BY p_id ASC";
$result = $db_conn->query($sql);


$search = "";

// Step 1: POST handle karke session me save karo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $_SESSION['search'] = $_POST['search'];
    header("Location: patients.php"); // redirect to avoid resubmission
    exit;
}

// Step 2: Session se value uthao
if (isset($_SESSION['search']) && !empty($_SESSION['search'])) {
    $search = $db_conn->real_escape_string($_SESSION['search']);
    $sql = "SELECT * FROM patient WHERE 
            p_firstname LIKE '%$search%' OR 
            p_lastname LIKE '%$search%' OR 
            p_email LIKE '%$search%' 
            ORDER BY p_id ASC";
            unset($_SESSION['search']); 
}
 else {
    $sql = "SELECT * FROM patient ORDER BY p_id ASC";
}

$result = $db_conn->query($sql);

// Fetch patient details for modal
$patient_details = null;
if (isset($_GET['p_id']) && !empty($_GET['p_id'])) {
    $patient_id = $db_conn->real_escape_string($_GET['p_id']);
    $details_sql = "SELECT * FROM patient WHERE p_id = $patient_id";
    $details_result = $db_conn->query($details_sql);
    
    if ($details_result->num_rows > 0) {
        $patient_details = $details_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Patients</title>
  <!-- Font Awesome Cdn -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Admin/css/header.css">
  <style>
    .content{
        margin: 10px 15px;
        cursor: pointer;
    }

    /* Table Styles */
    .patients-table-container {
      background: var(--card);
      border-radius: var(--radius);
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      border: 1px solid var(--border);
      margin: 20px auto;
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
    }
    
    .table-search input:hover {
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      border-color: var(--primary-600);
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
     table th:hover {
      background: linear-gradient(135deg, rgba(13,148,136,0.1), rgba(15,118,110,0.05));
      color: var(--primary-600);
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
     <div class = 'overlay' id = 'overlay'></div>
  <div class="layout">
    <?php include './a_sidebar.php'; ?>

    <div class="main">
      <?php include './a_header.php'; ?>

      <div class="content">

        <div class="patients-table-container">
          <div class="table-header">
            <div class="left"></div>
            <form method="POST" action="" class="table-search">
              <i class="fa-solid fa-search muted"></i>
               <input type="text" name="search" placeholder="Search patient..."
         value="<?php echo htmlspecialchars($search); ?>">
            </form>
          </div>

          <table>
            <thead>
              <tr>
                <th>Patient ID</th>
                <th>Patient Name</th>
                <th>Phone Number</th>
                <th>Email</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $row['p_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['p_firstname'] . ' ' . $row['p_lastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['p_phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['p_email']); ?></td>
                    <td>
                      <button class="view-btn view-btn-lg" onclick="viewPatient(<?php echo $row['p_id']; ?>)">
                        <i class="fa-regular fa-eye"></i> View
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 20px;">No patients found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Patient Details Modal -->
  <div class="modal <?php echo $patient_details ? 'active' : ''; ?>" id="patientModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Patient Details</h2>
        <button class="close-btn" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <?php if ($patient_details): ?>
          <div class="patient-details">
            <div class="detail-group">
              <div class="detail-label">Patient ID</div>
              <div class="detail-value">PT-<?php echo $patient_details['p_id']; ?></div>
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
              <div class="detail-label">Phone Number</div>
              <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_phone']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Email Address</div>
              <div class="detail-value"><?php echo htmlspecialchars($patient_details['p_email']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Date of Birth</div>
              <div class="detail-value"><?php echo date('F j, Y', strtotime($patient_details['p_dob'])); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Gender</div>
              <div class="detail-value"><?php echo ucfirst($patient_details['p_gender']); ?></div>
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
              <div class="detail-value"><?php echo date('F j, Y', strtotime($patient_details['created_at'])); ?></div>
            </div>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 20px;">Patient details not found</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" id="closeModalBtn">Close</button>
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

    // Function to view patient details
    function viewPatient(p_id) {
      window.location.href = 'patients.php?p_id=' + p_id;
    }

    // Close modal functionality
    document.getElementById('closeModal').addEventListener('click', function() {
      window.location.href = 'patients.php';
    });

    document.getElementById('closeModalBtn').addEventListener('click', function() {
      window.location.href = 'patients.php';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('patientModal');
      if (event.target === modal) {
        window.location.href = 'patients.php';
      }
    });
    
    // Close sidebar when modal is open on mobile
    window.addEventListener('load', function() {
      const modal = document.getElementById('patientModal');
      if (modal.classList.contains('active')) {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('active');
      }
    });
</script>
</body>
</html>