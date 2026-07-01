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

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['appointment_id'])) {
        $appointment_id = $_POST['appointment_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'reject') {
            $new_status = $action === 'approve' ? 'approved' : 'rejected';
            
            $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
            $stmt = $db_conn->prepare($update_sql);
            $stmt->bind_param("si", $new_status, $appointment_id);
            
            if ($stmt->execute()) {
                $message = "Appointment $new_status successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating appointment: " . $db_conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Build the base query
$query = "SELECT a.id, a.patient_id, a.hospital_id, a.test_type, a.vaccine_id, a.status, 
                 a.created_at, p.p_firstname, p.p_lastname, 
                 CONCAT(h.h_firstname, ' ', h.h_lastname) AS hospital_name,
                 v.name AS vaccine_name
          FROM appointments a
          LEFT JOIN patient p ON a.patient_id = p.p_id
          LEFT JOIN hospital h ON a.hospital_id = h.h_id
          LEFT JOIN vaccines v ON a.vaccine_id = v.id
          WHERE a.status IN ('pending', 'approved', 'rejected')";

// Apply filter
if ($filter !== 'all') {
    $query .= " AND a.status = '$filter'";
}

// Complete the query
$query .= " ORDER BY 
              CASE 
                  WHEN a.status = 'pending' THEN 1
                  WHEN a.status = 'approved' THEN 2
                  WHEN a.status = 'rejected' THEN 3
              END,
              a.created_at ASC";

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
  <title>CovidCare • Test Requests</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Admin/css/header.css">
  <style>

   .content{
    margin: 10px 20px;
    }

    /* Filter tabs */
    .filter-tabs {
      display: flex;
      background: var(--glass);
      border-radius: 8px;
      padding: 4px;
      border: 1px solid var(--border);
      margin-bottom: 20px;
      width: fit-content;
    }

    .filter-tab {
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

    .filter-tab.active {
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      color: white;
    }

    /* Table Styles */
    .requests-container {
      background: var(--card);
      border-radius: var(--radius);
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      border: 1px solid var(--border);
      margin: 20px auto;
      max-width: 1200px;
      animation: fadeInScale 0.6s ease;
    }

    .requests-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    .requests-table th {
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 14px;
      letter-spacing: 0.5px;
    }
    .requests-table th:hover {
      background: linear-gradient(135deg, rgba(13,148,136,0.1), rgba(15,118,110,0.05));
      color: var(--primary-600);
    }

    .requests-table td {
      font-size: 15px;
      padding: 15px;
      border-bottom: 1px solid var(--border);
      transition: all 0.3s ease;
    }

    .requests-table tr {
      transition: all 0.3s ease;
    }

    .requests-table tr:hover {
      background: rgba(13, 148, 136, 0.05);
      transform: translateY(-1px);
    }

    .requests-table tr:hover td {
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
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-approve {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .btn-reject {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn-done {
      background: rgba(0, 235, 157, 0.65);
      color: white;
      cursor: not-allowed;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .btn-done:hover {
      background: rgba(30, 138, 100, 0.65);
      color: white;
      cursor: not-allowed;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .message-container {
      margin: 20px 0;
      text-align: center;
    }

    .message {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 14px;
      display: inline-block;
      animation: fadeIn 0.5s ease, slideIn 0.5s ease;
    }

    .success {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .error {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.2);
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

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideIn {
      from { transform: translateY(-10px); }
      to { transform: translateY(0); }
    }

    /* Responsive table */
    @media (max-width: 1024px) {
      .requests-table {
        display: block;
        overflow-x: auto;
      }
      
      .requests-table th, 
      .requests-table td {
        min-width: 120px;
      }
    }

    @media (max-width: 768px) {
      .requests-container {
        padding: 15px;
        margin: 10px;
      }
      
      .action-buttons {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
      
      .filter-tabs {
        width: 100%;
        justify-content: center;
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
        <div class="requests-container">
          <!-- Filter tabs -->
          <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=approved" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?filter=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
          </div>

          <?php if (isset($message)): ?>
            <div class="message-container">
              <div class="message <?php echo $message_type; ?>">
                <i class="fa-solid fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
                <?php echo $message; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (count($appointments) > 0): ?>
            <table class="requests-table">
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>Patient Name</th>
                  <th>Hospital</th>
                  <th>Test Type</th>
                  <th>Vaccine</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($appointments as $appointment): ?>
                  <tr>
                    <td><?php echo $appointment['id']; ?></td>
                    <td><?php echo htmlspecialchars($appointment['p_firstname'] . ' ' . $appointment['p_lastname']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['hospital_name']); ?></td>
                    <td>
                      <?php 
                      if ($appointment['test_type'] === 'covid_test') {
                          echo 'COVID Test';
                      } else {
                          echo 'Vaccination';
                      }
                      ?>
                    </td>
                    <td>
                      <?php 
                      if ($appointment['vaccine_name']) {
                          echo htmlspecialchars($appointment['vaccine_name']);
                      } else {
                          echo 'N/A';
                      }
                      ?>
                    </td>
                    <td>
                      <span class="status-badge status-<?php echo $appointment['status']; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                      </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($appointment['created_at'])); ?></td>
                    <td>
                      <?php if ($appointment['status'] === 'pending'): ?>
                        <div class="action-buttons">
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Are you sure you want to approve this request?')">
                              <i class="fa-solid fa-check"></i> Approve
                            </button>
                          </form>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-reject" onclick="return confirm('Are you sure you want to reject this request?')">
                              <i class="fa-solid fa-times"></i> Reject
                            </button>
                          </form>
                        </div>
                      <?php else: ?>
                        <button class="btn btn-done" disabled>Done</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--muted);">
              <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 16px;"></i>
              <h3>No test requests found</h3>
              <p>There are no <?php echo $filter !== 'all' ? $filter : ''; ?> test requests at the moment.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // JavaScript for sidebar, theme toggle, etc. (same as before)
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

    // Auto-hide messages after 5 seconds
    setTimeout(() => {
      const messages = document.querySelectorAll('.message');
      messages.forEach(message => {
        message.style.opacity = '0';
        message.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
          message.remove();
        }, 500);
      });
    }, 5000);
  </script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>