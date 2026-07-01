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
    $dob = $patient_data['p_dob'];
    $gender = $patient_data['p_gender'];
    $phone = $patient_data['p_phone'];
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

// Build query to fetch completed appointments with results
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
          AND a.result_status != 'pending'
          AND a.result_status != 'not_vaccinated'
          ORDER BY a.updated_at DESC";

$result = $db_conn->query($query);
$reports = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Filter reports based on test type
if ($filter !== 'all') {
    $reports = array_filter($reports, function($report) use ($filter) {
        if ($filter === 'covid_test') {
            return $report['test_type'] === 'covid_test';
        } elseif ($filter === 'vaccination') {
            return $report['test_type'] === 'vaccination';
        }
        return true;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Test Reports</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Patient/css/header.css">
  <link rel="stylesheet" href="../Patient/css/reports.css">
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
        <div class="reports-container">       
          <div class="controls-container">
            <form method="POST" class="filter-form">
              <input type="hidden" name="filter" id="filterInput" value="<?php echo $filter; ?>">
              <div class="filter-tabs">
                <button type="button" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="setFilter('all')">
                  <i class="fa-solid fa-list"></i> <span>All Reports</span>
                </button>
                <button type="button" class="filter-tab <?php echo $filter === 'covid_test' ? 'active' : ''; ?>" onclick="setFilter('covid_test')">
                  <i class="fa-solid fa-virus"></i> <span>COVID Test</span>
                </button>
                <button type="button" class="filter-tab <?php echo $filter === 'vaccination' ? 'active' : ''; ?>" onclick="setFilter('vaccination')">
                  <i class="fa-solid fa-syringe"></i> <span>Vaccination</span>
                </button>
              </div>
            </form>
                        <!-- Date filter on the left -->
            <form method="POST" class="date-filter">
              <input type="hidden" name="filter" value="<?php echo $filter; ?>">
              <label for="dateInput">Filter by Date:</label>
              <input type="date" id="dateInput" name="date_filter" value="<?php echo $date_filter; ?>">
              <button type="submit">Apply</button>
              <?php if (!empty($date_filter)): ?>
                <button type="button" onclick="clearDateFilter()">Clear</button>
              <?php endif; ?>
            </form>
          </div>

          <?php if (count($reports) > 0): ?>
            <div class="reports-grid">
              <?php foreach ($reports as $report): ?>
                <div class="report-card">
                  <div class="card-header">
                    <h3 class="hospital-name"><?php echo htmlspecialchars($report['hospital_name']); ?></h3>
                    <div class="hospital-info">
                      <i class="fa-solid fa-location-dot"></i>
                      <span><?php echo htmlspecialchars($report['h_city']); ?></span>
                    </div>
                    <span class="test-type-badge <?php echo $report['test_type'] === 'covid_test' ? 'badge-covid' : 'badge-vaccine'; ?>">
                      <i class="fa-solid fa-<?php echo $report['test_type'] === 'covid_test' ? 'virus' : 'syringe'; ?>"></i>
                      <?php echo $report['test_type'] === 'covid_test' ? 'COVID Test' : 'Vaccination'; ?>
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
                        <span class="detail-label">Test Date</span>
                        <span class="detail-value">
                          <i class="fa-regular fa-calendar"></i>
                          <?php echo date('M d, Y', strtotime($report['scheduled_date'])); ?>
                        </span>
                      </div>
                      
                      <div class="detail-item">
                        <span class="detail-label">Test Time</span>
                        <span class="detail-value">
                          <i class="fa-regular fa-clock"></i>
                          <?php echo date('h:i A', strtotime($report['scheduled_time'])); ?>
                        </span>
                      </div>
                      
                      <?php if ($report['test_type'] === 'vaccination' && $report['vaccine_name']): ?>
                      <div class="detail-item">
                        <span class="detail-label">Vaccine</span>
                        <span class="detail-value">
                          <i class="fa-solid fa-syringe"></i>
                          <?php echo htmlspecialchars($report['vaccine_name']); ?>
                        </span>
                      </div>
                      <?php endif; ?>
                    </div>
                    
                    <div class="result-section">
                      <div class="result-label">Test Result</div>
                      <?php
                      $result_class = '';
                      $result_message = '';
                      
                      switch ($report['result_status']) {
                          case 'positive':
                              $result_class = 'result-positive';
                              $result_message = 'POSITIVE - COVID-19 Detected';
                              break;
                          case 'negative':
                              $result_class = 'result-negative';
                              $result_message = 'NEGATIVE - COVID-19 Not Detected';
                              break;
                          case 'vaccinated':
                              $result_class = 'result-vaccinated';
                              $result_message = 'VACCINATION SUCCESSFUL';
                              break;
                      }
                      ?>
                      <div class="result-value <?php echo $result_class; ?>">
                        <?php echo $result_message; ?>
                      </div>
                      <div class="result-message">
                        Result updated on: <?php echo date('M d, Y', strtotime($report['updated_at'])); ?>
                      </div>
                    </div>
                  </div>
                  
                  <div class="card-footer">
                    <span class="report-id">Report #<?php echo $report['id']; ?></span>
                        <a href="../Patient/download_report.php?download=1&id=<?php echo $report['id']; ?>" class="download-btn">
                          <i class="fa-solid fa-download"></i>Download PDF
                        </a>

                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-results">
              <i class="fa-solid fa-file-medical"></i>
              <h3>No reports available</h3>
              <p>
                <?php if ($filter !== 'all'): ?>
                  You don't have any <?php echo $filter === 'covid_test' ? 'COVID test' : 'vaccination'; ?> reports yet.
                <?php else: ?>
                  You haven't received any test results yet. Results will appear here once they are available.
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
     // Set filter function
    function setFilter(filterValue) {
      document.querySelector('.filter-form input[name="filter"]').value = filterValue;
      document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelector(`.filter-tab[onclick="setFilter('${filterValue}')"]`).classList.add('active');
      
      // Submit the form to apply the filter
      document.querySelector('.filter-form').submit();
    }
    
    // Clear date filter
    function clearDateFilter() {
      document.querySelector('.date-filter input[name="date_filter"]').value = '';
      document.querySelector('.date-filter').submit();
    }
  </script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>