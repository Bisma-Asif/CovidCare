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

// Handle approval/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_hospital'])) {
        $hospital_id = $db_conn->real_escape_string($_POST['h_id']);
        $sql = "UPDATE hospital SET `h_status` = 'approved' WHERE h_id = $hospital_id";
        
        if ($db_conn->query($sql)) {
            $success_message = "Hospital approved successfully!";
        } else {
            $error_message = "Error: " . $db_conn->error;
        }
    }
    
    if (isset($_POST['reject_hospital'])) {
        $hospital_id = $db_conn->real_escape_string($_POST['h_id']);
        $sql = "UPDATE hospital SET `h_status` = 'rejected' WHERE h_id = $hospital_id";
        
        if ($db_conn->query($sql)) {
            $success_message = "Hospital rejected successfully!";
        } else {
            $error_message = "Error: " . $db_conn->error;
        }
    }
}


$search = "";

// Agar form submit hua hai aur search value aayi hai
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search = trim($db_conn->real_escape_string($_POST['search']));

    // Agar input empty nahi hai to search query lagao
    if (!empty($search)) {
        $sql = "SELECT * FROM hospital 
                WHERE h_firstname LIKE '%$search%' 
                   OR h_lastname LIKE '%$search%' 
                   OR h_email LIKE '%$search%' 
                   OR h_license LIKE '%$search%' 
                ORDER BY h_id ASC";
    } else {
        // Agar input empty hai → sab hospitals dikhao
        $sql = "SELECT * FROM hospital ORDER BY h_id ASC";
    }
} else {
    // First time page load pe bhi sab hospitals dikhane chahiye
    $sql = "SELECT * FROM hospital ORDER BY h_id ASC";
}

$result = $db_conn->query($sql);




// Fetch hospital details for modal
$hospital_details = null;
if (isset($_GET['h_id']) && !empty($_GET['h_id'])) {
    $hospital_id = $db_conn->real_escape_string($_GET['h_id']);
    $details_sql = "SELECT * FROM hospital WHERE h_id = $hospital_id";
    $details_result = $db_conn->query($details_sql);
    
    if ($details_result->num_rows > 0) {
        $hospital_details = $details_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Hospital Requests</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Admin/css/header.css">
  <style>
    .content{
        margin: 10px 20px;
        cursor: pointer;
    }

    /* Table Styles */
    .hospitals-table-container {
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

    .filter-buttons {
      display: flex;
      gap: 10px;
    }

    .filter-btn {
      padding: 8px 16px;
      border: 1px solid var(--border);
      border-radius: 20px;
      background: var(--bg);
      color: var(--text);
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .filter-btn.active {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border-color: var(--accent);
    }

    .filter-btn:hover:not(.active) {
      transition: all 0.5s ease-out;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      border-color: var(--primary-600);
    }

    .table-search {
      display: flex;
      gap: 10px;
      align-items: center;
      position: relative;
    }

    .table-search input {
      padding: 10px 15px 10px 40px;
      border: 1px solid var(--border);
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

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.1);
      color: #f59e0b;
    }

    .status-approved {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }

    .status-rejected {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .approve-btn {
      padding: 8px 16px;
      background: linear-gradient(135deg, #10b981, #059669);
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

    .approve-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
      background: linear-gradient(135deg, #059669, #047857);
    }

    .reject-btn {
      padding: 8px 16px;
      background: linear-gradient(135deg, #ef4444, #dc2626);
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

    .reject-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
      background: linear-gradient(135deg, #dc2626, #b91c1c);
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

    .hospital-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .detail-group {
      margin-bottom: 15px;
    }

    .full-width {
      grid-column: 1 / -1;
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

    /* Message Styles */
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      padding: 12px 20px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 4px solid #28a745;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      padding: 12px 20px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 4px solid #dc3545;
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
      
      .filter-buttons {
        width: 100%;
        flex-wrap: wrap;
      }
      
      .table-search {
        width: 100%;
      }
      
      .table-search input {
        min-width: auto;
        flex-grow: 1;
      }

      .hospital-details {
        grid-template-columns: 1fr;
      }

      .action-buttons {
        flex-direction: column;
      }
    }

    @media (max-width: 768px) {
      .hospitals-table-container {
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
        <?php if (isset($success_message)): ?>
          <div class="alert-success">
            <?php echo $success_message; ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="alert-error">
            <?php echo $error_message; ?>
          </div>
        <?php endif; ?>

        <div class="hospitals-table-container">
          <div class="table-header">
            <div class="filter-buttons">
              <button class="filter-btn active" data-filter="all">All</button>
              <button class="filter-btn" data-filter="pending">Pending</button>
              <button class="filter-btn" data-filter="approved">Approved</button>
              <button class="filter-btn" data-filter="rejected">Rejected</button>
            </div>
            <form method="POST" action="" class="table-search">
              <i class="fa-solid fa-search muted"></i>
              <input type="text" name="search" placeholder="Search by hospital name..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>" />
            </form>
          </div>

          <table>
            <thead>
              <tr>
                <th>Hospital ID</th>
                <th>Hospital Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="hospitalsTableBody">
              <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                  <?php 
                  $status_class = '';
                  if ($row['h_status'] == 'pending') $status_class = 'status-pending';
                  if ($row['h_status'] == 'approved') $status_class = 'status-approved';
                  if ($row['h_status'] == 'rejected') $status_class = 'status-rejected';
                  ?>
                  <tr data-status="<?php echo $row['h_status']; ?>">
                    <td><?php echo $row['h_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['h_firstname'] . ' ' . $row['h_lastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['h_email']); ?></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($row['h_status']); ?></span></td>
                    <td>
  <div class="action-buttons">
    <?php if ($row['h_status'] == 'pending'): ?>
      <form method="POST" style="display: inline;">
        <input type="hidden" name="h_id" value="<?php echo $row['h_id']; ?>">
        <button type="submit" name="approve_hospital" class="approve-btn">
          <i class="fa-solid fa-check"></i> Approve
        </button>
      </form>
      <form method="POST" style="display: inline;">
        <input type="hidden" name="h_id" value="<?php echo $row['h_id']; ?>">
        <button type="submit" name="reject_hospital" class="reject-btn">
          <i class="fa-solid fa-xmark"></i> Reject
        </button>
      </form>
    <?php endif; ?>

    <!-- View button hamesha show hoga -->
    <button class="view-btn" onclick="viewHospital(<?php echo $row['h_id']; ?>)">
      <i class="fa-regular fa-eye"></i> View
    </button>
  </div>
</td>

                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 20px;">No hospital requests found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Hospital Details Modal -->
  <div class="modal <?php echo $hospital_details ? 'active' : ''; ?>" id="hospitalModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Hospital Details</h2>
        <button class="close-btn" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <?php if ($hospital_details): ?>
          <div class="hospital-details">
            <div class="detail-group">
              <div class="detail-label">Hospital ID</div>
              <div class="detail-value"><?php echo $hospital_details['h_id']; ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">First Name</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_firstname']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Last Name</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_lastname']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Email Address</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_email']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Phone Number</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_phone']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">License Number</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_license']); ?></div>
            </div>
               <div class="detail-group">
              <div class="detail-label">City</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_city']); ?></div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Status</div>
              <div class="detail-value">
                <?php 
                $status_class = '';
                if ($hospital_details['h_status'] == 'pending') $status_class = 'status-pending';
                if ($hospital_details['h_status'] == 'approved') $status_class = 'status-approved';
                if ($hospital_details['h_status'] == 'rejected') $status_class = 'status-rejected';
                ?>
                <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($hospital_details['h_status']); ?></span>
              </div>
            </div>
            <div class="detail-group">
              <div class="detail-label">Registration Date</div>
              <div class="detail-value"><?php echo date('F j, Y', strtotime($hospital_details['created_at'])); ?></div>
            </div>
               <div class="detail-group full-width">
              <div class="detail-label">Address</div>
              <div class="detail-value"><?php echo htmlspecialchars($hospital_details['h_address']); ?></div>
            </div>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 20px;">Hospital details not found</div>
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

    // Function to view hospital details
    function viewHospital(hospitalId) {
      window.location.href = 'login_approval.php?h_id=' + hospitalId;
    }

    // Close modal functionality
    document.getElementById('closeModal').addEventListener('click', function() {
      window.location.href = 'login_approval.php';
    });

    document.getElementById('closeModalBtn').addEventListener('click', function() {
      window.location.href = 'login_approval.php';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('hospitalModal');
      if (event.target === modal) {
        window.location.href = 'login_approval.php';
      }
    });

    // Search functionality
document.getElementById('searchInput').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    this.form.submit();
  }
});
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const tableRows = document.querySelectorAll('#hospitalsTableBody tr');
    
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

<?php
// Close database connection
$db_conn->close();
?>