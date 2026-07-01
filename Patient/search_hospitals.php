<?php
include '../auth.php';
checkRole( 'patient' );
include '../db_conn.php';

if ( !isset( $_SESSION[ 'patient_id' ] ) ) {
    header( 'Location: ../login_register.php' );
    exit();
}

$patient_id = $_SESSION[ 'patient_id' ];
$firstname = $_SESSION[ 'patient_firstname' ];
$lastname = $_SESSION[ 'patient_lastname' ];

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

// Handle search and filter using POST to avoid URL parameters
$search = "";
$filter = "all";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $search = trim($_POST['search']);
    }
    if (isset($_POST['filter'])) {
        $filter = $_POST['filter'];
    }
}

// Build query to fetch approved hospitals
$query = "SELECT h_id, CONCAT(h_firstname, ' ', h_lastname) AS hospital_name, 
                 h_phone, h_address, h_city 
          FROM hospital 
          WHERE h_status = 'approved'";

// Add search filter if provided
if (!empty($search)) {
    $query .= " AND CONCAT(h_firstname, ' ', h_lastname) LIKE '%" . $db_conn->real_escape_string($search) . "%'";
}

$result = $db_conn->query($query);
$hospitals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get vaccine availability status for this hospital
        $vaccine_query = "SELECT COUNT(*) as available_vaccines 
                          FROM vaccines 
                          WHERE status = 'available' AND total_stock > used_stock";
        $vaccine_result = $db_conn->query($vaccine_query);
        $vaccine_data = $vaccine_result->fetch_assoc();
        
        $row['vaccine_status'] = ($vaccine_data['available_vaccines'] > 0) ? 'Available' : 'Not Available';
        $hospitals[] = $row;
    }
}

// Filter hospitals based on vaccine availability
if ($filter !== 'all') {
    $hospitals = array_filter($hospitals, function($hospital) use ($filter) {
        if ($filter === 'available') {
            return $hospital['vaccine_status'] === 'Available';
        } elseif ($filter === 'not-available') {
            return $hospital['vaccine_status'] === 'Not Available';
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
  <title>CovidCare • Hospitals</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Patient/css/header.css">
  <link rel="stylesheet" href="../Patient/css/search_hospitals.css">
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
        <h2 class="page-title">Select Hospital - Book Appointment</h2>
        <div class="hospitals-container">
          <div class="page-header">
            <div class="controls-container">
              <div class="filter-tabs">
                <button type="button" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="setFilter('all')">All Hospitals</button>
                <button type="button" class="filter-tab <?php echo $filter === 'available' ? 'active' : ''; ?>" onclick="setFilter('available')">Vaccines Available</button>
                <button type="button" class="filter-tab <?php echo $filter === 'not-available' ? 'active' : ''; ?>" onclick="setFilter('not-available')">Vaccines Not Available</button>
              </div>
              
              <div class="search-container">
                <form method="POST" class="search-form">
                  <input type="text" name="search" class="search-input" placeholder="Search by hospital name..." value="<?php echo htmlspecialchars($search); ?>">
                  <input type="hidden" name="filter" id="filterInput" value="<?php echo $filter; ?>">
                  <button type="submit" class="search-btn"><i class="fa-solid fa-search"></i></button>
                </form>
              </div>
            </div>
          </div>

          <?php if (count($hospitals) > 0): ?>
            <table class="hospitals-table">
              <thead>
                <tr>
                  <th>Hospital Name</th>
                  <th>City</th>
                  <th>Address</th>
                  <th>Phone No</th>
                  <th>Vaccine Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($hospitals as $hospital): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($hospital['hospital_name']); ?></td>
                    <td><?php echo htmlspecialchars($hospital['h_city']); ?></td>
                    <td><?php echo htmlspecialchars($hospital['h_address']); ?></td>
                    <td><?php echo htmlspecialchars($hospital['h_phone']); ?></td>
                    <td>
                      <span class="vaccine-status status-<?php echo strtolower(str_replace(' ', '-', $hospital['vaccine_status'])); ?>">
                        <?php echo $hospital['vaccine_status']; ?>
                      </span>
                    </td>
                    <td>
                      <a href="book_appointment.php?hospital_id=<?php echo $hospital['h_id']; ?>" class="action-btn">
                        <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="no-results">
              <h3>No hospitals found</h3>
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
      document.querySelector('.search-form').submit();
    }
  </script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>