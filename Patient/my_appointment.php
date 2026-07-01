<?php
include '../auth.php';
checkRole('patient');
include '../db_conn.php';

if (!isset($_SESSION['patient_id'])) {
    header('Location: ../login_register.php');
    exit();
}

$patient_id = $_SESSION['patient_id'];
$firstname = $_SESSION['patient_firstname'];
$lastname = $_SESSION['patient_lastname'];

// Fetch patient data
$sql = "SELECT * FROM patient WHERE p_id = $patient_id";
$result = $db_conn->query($sql);

if ($result->num_rows > 0) {
    $patient_data = $result->fetch_assoc();
    $full_name = $patient_data['p_firstname'] . ' ' . $patient_data['p_lastname'];
    $email = $patient_data['p_email'];
} else {
    die("Patient not found!");
}

// Handle filter using POST to avoid URL parameters
$filter = "all";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['filter'])) {
        $filter = $_POST['filter'];
    }
}

// Build query to fetch appointments for the logged-in patient
$query = "SELECT a.id, a.patient_id, a.hospital_id, a.test_type, a.vaccine_id, 
                 a.status, a.result_status, a.scheduled_date, a.scheduled_time,
                 a.created_at, a.updated_at,
                 CONCAT(h.h_firstname, ' ', h.h_lastname) AS hospital_name,
                 h.h_phone, h.h_address, h.h_city,
                 v.name AS vaccine_name
          FROM appointments a
          LEFT JOIN hospital h ON a.hospital_id = h.h_id
          LEFT JOIN vaccines v ON a.vaccine_id = v.id
          WHERE a.patient_id = $patient_id
          ORDER BY a.created_at ASC";

$result = $db_conn->query($query);
$appointments = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Filter appointments based on status
if ($filter !== 'all') {
    $appointments = array_filter($appointments, function($appointment) use ($filter) {
        return $appointment['status'] === $filter;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • My Appointments</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Patient/css/header.css">
  <link rel="stylesheet" href="../Patient/css/my_appointment.css">
</head>
<body>
  <!-- Overlay for closing sidebar on mobile -->
  <div class="overlay" id="overlay"></div>

  <div class="layout">
    <?php include './p_sidebar.php'; ?>
    
    <div class="main">
      <?php include './p_header.php'; ?>

      <!-- Main Section --> 
      <div class="content">
        <div class="appointments-container">
          <div class="controls-container">
            <form method="POST" class="filter-form">
              <input type="hidden" name="filter" id="filterInput" value="<?php echo $filter; ?>">
              <div class="filter-tabs">
                <button type="button" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="setFilter('all')">
                  <i class="fa-solid fa-list"></i> <span>All</span>
                </button>
                <button type="button" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>" onclick="setFilter('pending')">
                  <i class="fa-regular fa-clock"></i> <span>Pending</span>
                </button>
                <button type="button" class="filter-tab <?php echo $filter === 'confirmed' ? 'active' : ''; ?>" onclick="setFilter('confirmed')">
                  <i class="fa-solid fa-check-circle"></i> <span>Confirmed</span>
                </button>
                <button type="button" class="filter-tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" onclick="setFilter('cancelled')">
                  <i class="fa-solid fa-times-circle"></i> <span>Cancelled</span>
                </button>
              </div>
            </form>
          </div>

          <?php if (count($appointments) > 0): ?>
            <div class="appointments-grid">
              <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-card">
                  <div class="card-header">
                    <h3 class="hospital-name"><?php echo htmlspecialchars($appointment['hospital_name']); ?></h3>
                    <div class="hospital-info">
                      <i class="fa-solid fa-location-dot"></i>
                      <span><?php echo htmlspecialchars($appointment['h_city']); ?></span>
                    </div>
                    <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                      <?php echo ucfirst($appointment['status']); ?>
                    </span>
                  </div>
                  
                  <div class="card-body">
                    <div class="detail-grid">
                      <div class="detail-item">
                        <span class="detail-label">Patient Name</span>
                        <span class="detail-value">
                          <i class="fa-solid fa-user"></i>
                          <?php echo htmlspecialchars($full_name); ?>
                        </span>
                      </div>
                      
                      <div class="detail-item">
                        <span class="detail-label">Test Type</span>
                        <span class="badge badge-test">
                          <i class="fa-solid fa-vial"></i>
                          <?php 
                          if ($appointment['test_type'] === 'covid_test') {
                              echo 'COVID Test';
                          } else {
                              echo 'Vaccination';
                          }
                          ?>
                        </span>
                      </div>
                      
                      <?php if ($appointment['test_type'] === 'vaccination' && $appointment['vaccine_name']): ?>
                      <div class="detail-item">
                        <span class="detail-label">Vaccine Type</span>
                        <span class="badge badge-vaccine">
                          <i class="fa-solid fa-syringe"></i>
                          <?php echo htmlspecialchars($appointment['vaccine_name']); ?>
                        </span>
                      </div>
                      <?php endif; ?>
                      
                      <div class="detail-item">
                        <span class="detail-label">Appointment Date</span>
                        <?php if ($appointment['scheduled_date']): ?>
                          <span class="badge badge-date">
                            <i class="fa-regular fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($appointment['scheduled_date'])); ?>
                          </span>
                        <?php else: ?>
                          <span class="badge badge-result">
                            <i class="fa-regular fa-calendar"></i>
                            Not scheduled
                          </span>
                        <?php endif; ?>
                      </div>
                      
                      <div class="detail-item">
                        <span class="detail-label">Time</span>
                        <?php if ($appointment['scheduled_time']): ?>
                          <span class="badge badge-time">
                            <i class="fa-regular fa-clock"></i>
                            <?php echo date('h:i A', strtotime($appointment['scheduled_time'])); ?>
                          </span>
                        <?php else: ?>
                          <span class="badge badge-result">
                            <i class="fa-regular fa-clock"></i>
                            Not scheduled
                          </span>
                        <?php endif; ?>
                      </div>
                      
                      <div class="detail-item">
                        <span class="detail-label">Result Status</span>
                        <span class="badge badge-result">
                          <i class="fa-solid fa-file-medical"></i>
                          <?php echo ucfirst(str_replace('_', ' ', $appointment['result_status'])); ?>
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="card-footer">
                    <span class="appointment-id">Appointment #<?php echo $appointment['id']; ?></span>
                    <span class="appointment-date">Appointment Date: <?php echo date('M d, Y', strtotime($appointment['created_at'])); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-results">
              <i class="fa-regular fa-calendar-xmark"></i>
              <h3>No appointments found</h3>
              <p>
                <?php if ($filter !== 'all'): ?>
                  You don't have any <?php echo $filter; ?> appointments at the moment.
                <?php else: ?>
                  You haven't made any appointments yet. Book your first appointment today!
                <?php endif; ?>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
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

    // Set filter function
    function setFilter(filterValue) {
      document.getElementById('filterInput').value = filterValue;
      document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelector(`.filter-tab[onclick="setFilter('${filterValue}')"]`).classList.add('active');
      
      // Submit the form to apply the filter
      document.querySelector('.filter-form').submit();
    }
  </script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>