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

// Handle filter using POST to avoid URL parameters
$filter = "all";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['filter'])) {
        $filter = $_POST['filter'];
    }
}

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
          WHERE a.hospital_id = $hospital_id
          ORDER BY a.created_at DESC";

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
  <title>CovidCare • Hospital Appointments</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Hospital/css/header.css">
  <style>
    .content{
        margin: 10px 15px;
        cursor: pointer;
    }

    /* Container Styles */
    .appointments-container {
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

    .filter-form {
      display: flex;
      gap: 10px;
      align-items: center;
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

    /* Appointments Grid */
    .appointments-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .appointment-card {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      transition: all 0.3s ease;
      border: 1px solid var(--border);
    }

    .appointment-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      border-color: var(--accent);
    }

    .card-header {
      padding: 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
    }

    .patient-name {
      font-size: 18px;
      font-weight: 700;
      margin: 0;
    }

    .patient-info {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      margin-top: 5px;
    }

    .appointment-status {
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

    .status-approved {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .status-confirmed {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .status-rejected, .status-cancelled {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .card-body {
      padding: 20px;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .detail-label {
      font-size: 12px;
      color: var(--muted);
      font-weight: 600;
      text-transform: uppercase;
    }

    .detail-value {
      font-size: 14px;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .badge {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .badge-test {
      background: rgba(13, 148, 136, 0.1);
      color: var(--accent);
    }

    .badge-vaccine {
      background: rgba(139, 92, 246, 0.1);
      color: #8b5cf6;
    }

    .badge-date {
      background: rgba(245, 158, 11, 0.1);
      color: #f59e0b;
    }

    .badge-time {
      background: rgba(59, 130, 246, 0.1);
      color: #3b82f6;
    }

    .badge-result {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }

    .card-footer {
      padding: 15px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--bg);
    }

    .appointment-id {
      font-size: 12px;
      color: var(--muted);
    }

    .appointment-date {
      font-size: 12px;
      color: var(--muted);
    }

    .no-results {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
      grid-column: 1 / -1;
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

    /* Responsive design */
    @media (max-width: 1024px) {
      .appointments-grid {
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      }
      
      .controls-container {
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

      .detail-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .appointments-container {
        padding: 15px;
        margin: 10px;
      }
      
      .appointments-grid {
        grid-template-columns: 1fr;
      }
      
      .page-title {
        font-size: 20px;
      }
      
      .filter-tabs {
        width: 100%;
        justify-content: center;
      }
      
      .filter-tab {
        flex: 1;
        justify-content: center;
        min-width: 120px;
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
        <div class="appointments-container">
          <div class="page-header">
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
                  <button type="button" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>" onclick="setFilter('approved')">
                    <i class="fa-solid fa-check"></i> <span>Approved</span>
                  </button>
                  <button type="button" class="filter-tab <?php echo $filter === 'confirmed' ? 'active' : ''; ?>" onclick="setFilter('confirmed')">
                    <i class="fa-solid fa-calendar-check"></i> <span>Confirmed</span>
                  </button>
                  <button type="button" class="filter-tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" onclick="setFilter('cancelled')">
                    <i class="fa-solid fa-times-circle"></i> <span>Cancelled</span>
                  </button>
                </div>
              </form>
              
              <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name...">
              </div>
            </div>
          </div>

          <?php if (count($appointments) > 0): ?>
            <div class="appointments-grid">
              <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-card">
                  <div class="card-header">
                    <div>
                      <h3 class="patient-name"><?php echo htmlspecialchars($appointment['patient_name']); ?></h3>
                      <div class="patient-info">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?php echo htmlspecialchars($appointment['p_city']); ?></span>
                      </div>
                    </div>
                    <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                      <?php echo ucfirst($appointment['status']); ?>
                    </span>
                  </div>
                  
                  <div class="card-body">
                    <div class="detail-grid">
                      <div class="detail-item">
                        <span class="detail-label">Hospital</span>
                        <span class="detail-value">
                          <i class="fa-solid fa-hospital"></i>
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
                    <span class="appointment-date">Created: <?php echo date('M d, Y', strtotime($appointment['created_at'])); ?></span>
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
                  Your hospital doesn't have any appointments yet.
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

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const cards = document.querySelectorAll('.appointment-card');
        
        cards.forEach(card => {
          const cardText = card.textContent.toLowerCase();
          if (cardText.includes(searchText)) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
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