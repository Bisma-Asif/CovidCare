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

// Handle form submission for scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $scheduled_date = $_POST['scheduled_date'];
    $scheduled_time = $_POST['scheduled_time'];
    
    $update_sql = "UPDATE appointments SET scheduled_date = '$scheduled_date', 
                  scheduled_time = '$scheduled_time', status = 'confirmed' 
                  WHERE id = $appointment_id AND hospital_id = $hospital_id";
    
    if ($db_conn->query($update_sql)) {
        $success_message = "Appointment scheduled successfully!";
    } else {
        $error_message = "Error scheduling appointment: " . $db_conn->error;
    }
}

// Fetch approved appointments for this hospital
$appointments_sql = "SELECT a.id as booking_id, 
                    CONCAT(p.p_firstname, ' ', p.p_lastname) as patient_name,
                    h.h_firstname as hospital_name,
                    a.test_type,
                    v.name as vaccine_name,
                    a.scheduled_date,
                    a.scheduled_time
                    FROM appointments a
                    JOIN patient p ON a.patient_id = p.p_id
                    JOIN hospital h ON a.hospital_id = h.h_id
                    LEFT JOIN vaccines v ON a.vaccine_id = v.id
                    WHERE a.hospital_id = $hospital_id 
                    AND a.status = 'approved'
                    ORDER BY a.created_at DESC";
                    
$appointments_result = $db_conn->query($appointments_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Approved Appointments</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../Hospital/css/header.css">
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

    /* Status badge styles */
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .status-approved {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
    }

    /* Form styles for modal */
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text);
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--bg);
      color: var(--text);
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--primary-600);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
    }
    
    .form-control:disabled {
      background: rgba(0, 0, 0, 0.05);
      cursor: not-allowed;
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
          
          <?php if ($appointments_result && $appointments_result->num_rows > 0): ?>
            <table>
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>Patient Name</th>
                  <th>Hospital Name</th>
                  <th>Test Type</th>
                  <th>Vaccine Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = $appointments_result->fetch_assoc()): ?>
                  <tr>
                    <td>#<?php echo $row['booking_id']; ?></td>
                    <td><?php echo $row['patient_name']; ?></td>
                    <td><?php echo $row['hospital_name']; ?></td>
                    <td>
                      <span class="status-badge status-approved">
                        <?php echo ucfirst(str_replace('_', ' ', $row['test_type'])); ?>
                      </span>
                    </td>
                    <td><?php echo $row['vaccine_name'] ? $row['vaccine_name'] : 'N/A'; ?></td>
                    <td>
                      <button class="view-btn schedule-btn" 
                              data-id="<?php echo $row['booking_id']; ?>"
                              data-patient="<?php echo $row['patient_name']; ?>"
                              data-hospital="<?php echo $row['hospital_name']; ?>"
                              data-test-type="<?php echo $row['test_type']; ?>"
                              data-vaccine="<?php echo $row['vaccine_name'] ? $row['vaccine_name'] : 'N/A'; ?>"
                              data-date="<?php echo $row['scheduled_date']; ?>"
                              data-time="<?php echo $row['scheduled_time']; ?>">
                        <i class="fa-solid fa-calendar-plus"></i> Schedule
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="no-results">
              <i class="fa-regular fa-calendar-check" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
              <p>No approved appointments found</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Schedule Modal -->
  <div class="modal" id="scheduleModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Schedule Appointment</h2>
        <button class="close-btn" id="closeModal">&times;</button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="appointment_id" id="appointment_id">
          
          <div class="patient-details">
            <div class="form-group">
              <label class="form-label" for="patient_name">Patient Name</label>
              <input type="text" class="form-control" id="patient_name" disabled>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="hospital_name">Hospital Name</label>
              <input type="text" class="form-control" id="hospital_name" disabled>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="test_type">Test Type</label>
              <input type="text" class="form-control" id="test_type" disabled>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="vaccine_name">Vaccine Name</label>
              <input type="text" class="form-control" id="vaccine_name" disabled>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="scheduled_date">Schedule Date</label>
              <input type="date" class="form-control" name="scheduled_date" id="scheduled_date" required 
                     min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
              <label class="form-label" for="scheduled_time">Schedule Time</label>
              <input type="time" class="form-control" name="scheduled_time" id="scheduled_time" required>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelSchedule">Cancel</button>
          <button type="submit" class="btn btn-primary" name="schedule_appointment">Schedule Appointment</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // -----------------Header + Sidebar JS--------------------
    // Toggle sidebar on mobile
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
        localStorage.setitem('theme', 'light');
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

    // -----------------Appointment Scheduling JS--------------------
    const scheduleModal = document.getElementById('scheduleModal');
    const scheduleButtons = document.querySelectorAll('.schedule-btn');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelScheduleBtn = document.getElementById('cancelSchedule');
    
    // Open modal with appointment data
    scheduleButtons.forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('appointment_id').value = this.getAttribute('data-id');
        document.getElementById('patient_name').value = this.getAttribute('data-patient');
        document.getElementById('hospital_name').value = this.getAttribute('data-hospital');
        document.getElementById('test_type').value = this.getAttribute('data-test-type');
        document.getElementById('vaccine_name').value = this.getAttribute('data-vaccine');
        
        // Set existing date/time if available
        const existingDate = this.getAttribute('data-date');
        const existingTime = this.getAttribute('data-time');
        
        if (existingDate && existingDate !== '0000-00-00') {
          document.getElementById('scheduled_date').value = existingDate;
        } else {
          document.getElementById('scheduled_date').value = '';
        }
        
        if (existingTime) {
          document.getElementById('scheduled_time').value = existingTime;
        } else {
          document.getElementById('scheduled_time').value = '';
        }
        
        scheduleModal.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    });
    
    // Close modal
    function closeModal() {
      scheduleModal.classList.remove('active');
      document.body.style.overflow = '';
    }
    
    closeModalBtn.addEventListener('click', closeModal);
    cancelScheduleBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === scheduleModal) {
        closeModal();
      }
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');
        
        rows.forEach(row => {
          const rowText = row.textContent.toLowerCase();
          if (rowText.includes(searchText)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    }
  </script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>