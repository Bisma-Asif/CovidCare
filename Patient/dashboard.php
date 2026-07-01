<?php
include '../auth.php';
checkRole('patient');
include '../db_conn.php';
// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: ../login_register.php"); 
    exit();
}

$patient_id = $_SESSION['patient_id'];
$firstname = $_SESSION[ 'patient_firstname' ];
$lastname = $_SESSION[ 'patient_lastname' ];

// Fetch patient data
$sql = "SELECT * FROM patient WHERE p_id = $patient_id";
$result = $db_conn->query( $sql );

if ( $result->num_rows > 0 ) {
    $patient_data = $result->fetch_assoc();
    $full_name = $patient_data[ 'p_firstname' ] . ' ' . $patient_data[ 'p_lastname' ];
    $email = $patient_data[ 'p_email' ];
} else {
    die( 'Patient not found!' );
}

// Fetch statistics from database
$stats = [];

// Total Appointments
$sql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id";
$result = $db_conn->query( $sql );
$stats[ 'total_appointments' ] = $result->fetch_assoc()[ 'total' ];

// Approved Appointments
$sql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND status = 'approved'";
$result = $db_conn->query( $sql );
$stats[ 'approved_appointments' ] = $result->fetch_assoc()[ 'total' ];

// Vaccination Status
$sql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND test_type = 'vaccination' AND result_status = 'vaccinated'";
$result = $db_conn->query( $sql );
$stats[ 'vaccinated' ] = $result->fetch_assoc()[ 'total' ];

// Pending Results
$sql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND result_status = 'pending'";
$result = $db_conn->query( $sql );
$stats[ 'pending_results' ] = $result->fetch_assoc()[ 'total' ];

// Fetch notifications/activities
$activities = [];

// Recent appointment approvals
$sql = "SELECT a.*, h.h_firstname, h.h_lastname 
        FROM appointments a 
        JOIN hospital h ON a.hospital_id = h.h_id 
        WHERE a.patient_id = $patient_id 
        ORDER BY a.updated_at DESC 
        LIMIT 5";
$result = $db_conn->query( $sql );
while ( $row = $result->fetch_assoc() ) {
    if ( $row[ 'status' ] == 'approved' ) {
        $activities[] = [
            'type' => 'appointment_approval',
            'description' => 'Appointment approved by admin. Waiting for schedule from ' . $row[ 'h_firstname' ] . ' ' . $row[ 'h_lastname' ],
            'time' => $row[ 'updated_at' ]
        ];
    } elseif ( $row[ 'status' ] == 'confirmed' && $row[ 'scheduled_date' ] != NULL ) {
        $activities[] = [
            'type' => 'appointment_scheduled',
            'description' => 'Appointment scheduled for ' . date( 'M j, Y', strtotime( $row[ 'scheduled_date' ] ) ) . ' at ' . date( 'g:i A', strtotime( $row[ 'scheduled_time' ] ) ),
            'time' => $row[ 'updated_at' ]
        ];
    } elseif ( $row[ 'result_status' ] != 'pending' ) {
        $result_text = ucfirst( str_replace( '_', ' ', $row[ 'result_status' ] ) );
        $activities[] = [
            'type' => 'test_result',
            'description' => 'Test result: ' . $result_text . ' for your ' . $row[ 'test_type' ],
            'time' => $row[ 'updated_at' ]
        ];
    }
}

// Fetch vaccine data for chart
$sql = "SELECT v.name, COUNT(a.id) as count 
        FROM vaccines v 
        LEFT JOIN appointments a ON v.id = a.vaccine_id AND a.patient_id = $patient_id AND a.result_status = 'vaccinated'
        GROUP BY v.id";
$result = $db_conn->query( $sql );
$vaccineData = [];
while ( $row = $result->fetch_assoc() ) {
    $vaccineData[] = $row;
}

// Function to calculate time ago

function timeAgo( $timestamp ) {
    $time = strtotime( $timestamp );
    $now = time();
    $diff = $now - $time;

    if ( $diff < 60 ) {
        return 'just now';
    } elseif ( $diff < 3600 ) {
        $mins = floor( $diff / 60 );
        return "$mins minutes ago";
    } elseif ( $diff < 86400 ) {
        $hours = floor( $diff / 3600 );
        return "$hours hours ago";
    } else {
        $days = floor( $diff / 86400 );
        return "$days days ago";
    }
}
?>

<!DOCTYPE html>
<html lang = 'en'>
<head>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1' />
<title>CovidCare • Patient Dashboard</title>
<link href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css' rel = 'stylesheet'/>
<script src = 'https://cdn.jsdelivr.net/npm/chart.js'></script>
<link rel="stylesheet" href="../Patient/css/header.css">
<style>
/* --------------------Header + Sidebar Styles-------------------- */
/* Basic Styles*/
:root {
    --bg: #f8fafc;
    --card: #ffffff;
    --muted: #64748b;
    --logo: #0f766e;
    --text: #0f172a;
    --accent: #0d9488;
    --accent-2: #14b8a6;
    --border: #e2e8f0;
    --radius: 12px;
    --glass: rgba( 255, 255, 255, 0.6 );
    --primary-600: #0d9488;
    --primary-700: #0f766e;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --nav: linear-gradient( 90deg, rgba( 13, 148, 136, 0.12 ), rgba( 15, 118, 110, 0.08 ) );
}
.dark {
    --bg: #0e1117;
    --card: #0f1724;
    --muted: #94a3b8;
    --logo: #e6eef6;
    --text: #e6eef6;
    --accent: #00B092;
    --accent-2: #06b6d4;
    --border: rgba( 255, 255, 255, 0.06 );
    --glass: rgba( 255, 255, 255, 0.03 );
    --primary-600: #14b8a6;
    --primary-700: #0d9488;
    --success: #34d399;
    --danger: #f87171;
    --warning: #fbbf24;
    --nav:  #0e1117;
}

                    /* Dashboard styles */
                    .content {
                        flex: 1;
                        padding: 20px;
                        overflow-y: auto;
                    }

                    .page-title {
                        font-size: 24px;
                        font-weight: 700;
                        margin-bottom: 20px;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }

                    .welcome-card {
                        background: linear-gradient( 135deg, var( --accent-2 ), var( --accent ) );
                        border-radius: var( --radius );
                        padding: 24px;
                        margin-bottom: 24px;
                        color: white;
                        box-shadow: 0 10px 30px rgba( 13, 148, 136, 0.2 );
                        position: relative;
                        overflow: hidden;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    }

                    .welcome-card::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        right: -50%;
                        width: 100%;
                        height: 200%;
                        background: rgba( 255, 255, 255, 0.1 );
                        transform: rotate( 45deg );
                    }

                    .welcome-card h2 {
                        font-size: 28px;
                        margin: 0 0 8px 0;
                        position: relative;
                        z-index: 1;
                        text-transform: uppercase;
                        animation: fadeIn 1s ease 0.2s both;
                    }

                    .welcome-card p {
                        margin: 0;
                        opacity: 0.9;
                        position: relative;
                        z-index: 1;
                        animation: fadeIn 1s ease 0.3s both;
                    }

                    .stats-grid {
                        display: grid;
                        grid-template-columns: repeat( auto-fit, minmax( 240px, 1fr ) );
                        gap: 20px;
                        margin-bottom: 24px;
                    }

                    .stat-card {
                        background: var( --card );
                        border-radius: var( --radius );
                        padding: 20px;
                        box-shadow: 0 4px 12px rgba( 0, 0, 0, 0.03 );
                        border: 1px solid var( --border );
                        transition: all 0.3s ease;
                        position: relative;
                        overflow: hidden;
                    }

                    .stat-card::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient( 135deg, var( --primary-600 ), var( --primary-700 ) );
                        opacity: 0;
                        transition: opacity 0.3s ease;
                        z-index: 0;
                    }

                    .stat-card:hover {
                        transform: translateY( -5px );
                        box-shadow: 0 8px 25px rgba( 0, 0, 0, 0.1 );
                        border-color: var( --primary-600 );
                    }

                    .stat-card:hover::before {
                        opacity: 0.03;
                    }

                    .stat-card:hover .stat-icon {
                        background: linear-gradient( 135deg, var( --primary-600 ), var( --primary-700 ) );
                        color: white;
                    }

                    .stat-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 12px;
                        position: relative;
                        z-index: 1;
                    }

                    .stat-title {
                        font-size: 14px;
                        color: var( --muted );
                        font-weight: 600;
                        transition: color 0.3s ease;
                    }

                    .stat-card:hover .stat-title {
                        color: var( --primary-700 );
                    }

                    .stat-icon {
                        width: 40px;
                        height: 40px;
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: linear-gradient( 135deg, rgba( 20, 184, 166, 0.12 ), rgba( 13, 148, 136, 0.06 ) );
                        color: var( --accent );
                        transition: all 0.3s ease;
                    }

                    .stat-value {
                        font-size: 22px;
                        font-weight: 700;
                        margin-bottom: 6px;
                        position: relative;
                        z-index: 1;
                        animation: fadeIn 1s ease 0.2s both;
                    }

                    .stat-card:hover .stat-value {
                        color: var( --primary-700 );
                    }

                    .stat-change {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        font-size: 13px;
                        font-weight: 600;
                        position: relative;
                        z-index: 1;
                    }

                    .change-positive {
                        color: #10b981;
                    }

                    .change-negative {
                        color: #ef4444;
                    }

                    .charts-container {
                        display: grid;
                        grid-template-columns: 2fr 1fr;
                        gap: 20px;
                        margin-bottom: 24px;
                    }

                    .chart-card {
                        background: var( --card );
                        border-radius: var( --radius );
                        padding: 20px;
                        box-shadow: 0 4px 12px rgba( 0, 0, 0, 0.03 );
                        border: 1px solid var( --border );
                        transition: all 0.3s ease;
                    }

                    .chart-card:hover {
                        transform: translateY( -3px );
                        box-shadow: 0 8px 20px rgba( 0, 0, 0, 0.08 );
                        border-color: var( --primary-600 );
                    }

                    .chart-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 16px;
                    }

                    .chart-title {
                        font-size: 16px;
                        font-weight: 700;
                    }

                    .chart-actions {
                        display: flex;
                        gap: 8px;
                    }

                    .chart-actions button {
                        background: transparent;
                        border: 1px solid var( --border );
                        border-radius: 8px;
                        padding: 6px 10px;
                        font-size: 13px;
                        cursor: pointer;
                        color: var( --muted );
                        transition: all 0.3s ease;
                    }

                    .chart-actions button:hover {
                        background: var( --primary-600 );
                        color: white;
                        border-color: var( --primary-600 );
                    }

                    .chart-container {
                        position: relative;
                        height: 300px;
                    }

                    .activity-container {
                        background: var( --card );
                        border-radius: var( --radius );
                        padding: 20px;
                        box-shadow: 0 4px 12px rgba( 0, 0, 0, 0.03 );
                        border: 1px solid var( --border );
                        margin-bottom: 24px;
                        transition: all 0.3s ease;
                    }

                    .activity-container:hover {
                        transform: translateY( -3px );
                        box-shadow: 0 8px 20px rgba( 0, 0, 0, 0.08 );
                        border-color: var( --primary-600 );
                    }

                    .activity-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 16px;
                    }

                    .activity-title {
                        font-size: 16px;
                        font-weight: 700;
                    }

                    .activity-list {
                        display: flex;
                        flex-direction: column;
                        gap: 16px;
                    }

                    .activity-item {
                        display: flex;
                        gap: 12px;
                        padding: 12px;
                        border-radius: 10px;
                        transition: all 0.3s ease;
                        cursor: pointer;
                    }

                    .activity-item:hover {
                        background: linear-gradient( 90deg, rgba( 13, 148, 136, 0.08 ), rgba( 15, 118, 110, 0.05 ) );
                        transform: translateX( 5px );
                    }

                    .activity-icon {
                        width: 40px;
                        height: 40px;
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: linear-gradient( 135deg, rgba( 20, 184, 166, 0.12 ), rgba( 13, 148, 136, 0.06 ) );
                        color: var( --accent );
                        flex-shrink: 0;
                        transition: all 0.3s ease;
                    }

                    .activity-item:hover .activity-icon {
                        background: linear-gradient( 135deg, var( --primary-600 ), var( --primary-700 ) );
                        color: white;
                        transform: scale( 1.1 );
                    }

                    .activity-content {
                        flex: 1;
                    }

                    .activity-desc {
                        font-size: 14px;
                        margin-bottom: 4px;
                        transition: color 0.3s ease;
                    }

                    .activity-item:hover .activity-desc {
                        color: var( --primary-700 );
                    }

                    .activity-time {
                        font-size: 12px;
                        color: var( --muted );
                    }

                    .quick-actions {
                        display: grid;
                        grid-template-columns: repeat( auto-fit, minmax( 200px, 1fr ) );
                        gap: 16px;
                    }

                    .action-button {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        padding: 20px;
                        background: var( --card );
                        border-radius: var( --radius );
                        border: 1px solid var( --border );
                        cursor: pointer;
                        transition: all 0.3s ease;
                        position: relative;
                        overflow: hidden;
                        text-decoration: none;
                        color: inherit;
                    }

                    .action-button::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient( 135deg, var( --primary-600 ), var( --primary-700 ) );
                        opacity: 0;
                        transition: opacity 0.3s ease;
                        z-index: 0;
                    }

                    .action-button:hover {
                        transform: translateY( -5px );
                        box-shadow: 0 8px 25px rgba( 0, 0, 0, 0.1 );
                        border-color: var( --primary-600 );
                    }

                    .action-button:hover::before {
                        opacity: 0.05;
                    }

                    .action-icon {
                        width: 50px;
                        height: 50px;
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: linear-gradient( 135deg, var( --accent-2 ), var( --accent ) );
                        color: white;
                        font-size: 20px;
                        transition: all 0.3s ease;
                        position: relative;
                        z-index: 1;
                    }

                    .action-button:hover .action-icon {
                        transform: scale( 1.1 ) rotate( 5deg );
                        box-shadow: 0 5px 15px rgba( 13, 148, 136, 0.3 );
                    }

                    .action-text {
                        font-size: 14px;
                        font-weight: 600;
                        transition: color 0.3s ease;
                        position: relative;
                        z-index: 1;
                    }

                    .action-button:hover .action-text {
                        color: var( --primary-700 );
                    }

                    @media ( max-width: 1024px ) {
                        .charts-container {
                            grid-template-columns: 1fr;
                        }
                    }
                    @media ( max-width: 768px ) {
                        .stats-grid {
                            grid-template-columns: 1fr 1fr;
                        }
                        .quick-actions {
                            grid-template-columns: 1fr 1fr;
                        }
                    }
                    @media ( max-width: 600px ) {
                        .content {
                            padding: 12px;
                        }
                        .stats-grid {
                            grid-template-columns: 1fr;
                        }
                        .quick-actions {
                            grid-template-columns: 1fr;
                        }
                    }
                    </style>
                    </head>
                    <body>
  <!-- Overlay for closing sidebar on mobile -->
  <div class="overlay" id="overlay"></div>

  <div class="layout">
    <?php include './p_sidebar.php'; ?>
    
    <div class="main">
      <?php include './p_header.php'; ?>

      <!-- Main Section --> 
                    <div class = 'content'>
                    <h1 class = 'page-title'><i class = 'fa-solid fa-gauge'></i> Patient Dashboard</h1>

                    <!-- Welcome Card -->
                    <div class = 'welcome-card'>
                    <h2>Welcome, <?php echo $patient_data[ 'p_firstname' ];
                    ?>!</h2>
                    <p>Here's your COVID-19 health management dashboard. Stay updated with your appointments and test results.</p>
      </div>

      <div class='stats-grid'>
        <div class='stat-card'>
          <div class='stat-header'>
            <div class='stat-title'>TOTAL APPOINTMENTS</div>
            <div class='stat-icon'><i class='fa-solid fa-calendar-check'></i></div>
          </div>
          <div class='stat-value'><?php echo $stats['total_appointments']; ?></div>
          <div class='stat-change change-positive'><i class='fa-solid fa-arrow-up'></i> All Time</div>
        </div>

        <div class='stat-card'>
          <div class='stat-header'>
            <div class='stat-title'>VACCINATION STATUS</div>
            <div class='stat-icon'><i class='fa-solid fa-syringe'></i></div>
          </div>
          <div class='stat-value'><?php echo $stats['vaccinated'] > 0 ? 'Vaccinated' : 'Not Vaccinated'; ?></div>
          <div class='stat-change <?php echo $stats[ 'vaccinated' ] > 0 ? 'change-positive' : 'change-negative';
                    ?>'>
            <i class='fa-solid <?php echo $stats[ 'vaccinated' ] > 0 ? 'fa-check' : 'fa-clock';
                    ?>'></i> 
            <?php echo $stats['vaccinated'] > 0 ? 'Completed' : 'Pending'; ?>
          </div>
        </div>

        <div class='stat-card'>
          <div class='stat-header'>
            <div class='stat-title'>TEST RESULTS</div>
            <div class='stat-icon'><i class='fa-solid fa-vial'></i></div>
          </div>
          <div class='stat-value'><?php echo $stats['pending_results'] > 0 ? 'Pending' : 'Received'; ?></div>
          <div class='stat-change <?php echo $stats[ 'pending_results' ] > 0 ? 'change-negative' : 'change-positive';
                    ?>'>
            <i class='fa-solid <?php echo $stats[ 'pending_results' ] > 0 ? 'fa-clock' : 'fa-check';
                    ?>'></i> 
            <?php echo $stats['pending_results'] > 0 ? 'Awaiting Results' : 'All Results In'; ?>
          </div>
        </div>

        <div class='stat-card'>
          <div class='stat-header'>
            <div class='stat-title'>APPROVED APPOINTMENTS</div>
            <div class='stat-icon'><i class='fa-solid fa-user-check'></i></div>
          </div>
          <div class='stat-value'><?php echo $stats['approved_appointments']; ?></div>
          <div class='stat-change change-positive'><i class='fa-solid fa-calendar'></i> Confirmed</div>
        </div>
      </div>

      <div class='charts-container'>
        <div class='chart-card'>
          <div class='chart-header'>
            <div class='chart-title'>Appointment Status</div>
            <div class='chart-actions'>
              <button>View All</button>
            </div>
          </div>
          <div class='chart-container'>
            <canvas id='appointmentsChart'></canvas>
          </div>
        </div>

        <div class='chart-card'>
          <div class='chart-header'>
            <div class='chart-title'>Vaccine Status</div>
            <div class='chart-actions'>
              <button>View</button>
            </div>
          </div>
          <div class='chart-container'>
            <canvas id='vaccineChart'></canvas>
          </div>
        </div>
      </div>

      <div class='activity-container'>
        <div class='activity-header'>
          <div class='activity-title' id="notification">Recent Notifications</div>
          <div class='chart-actions'>
            <button>View All</button>
          </div>
        </div>
        <div class='activity-list'>
          <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $activity): ?>
              <div class='activity-item'>
                <div class='activity-icon'>
                  <i class="fa-solid <?php 
                    if ($activity['type'] == 'appointment_approval') echo 'fa-user-check';
                    else if ($activity['type'] == 'appointment_scheduled') echo 'fa-calendar-day';
                    else if ($activity['type'] == 'test_result') echo 'fa-vial';
                  ?>"></i>
                </div>
                <div class='activity-content'>
                  <div class='activity-desc'><?php echo $activity['description']; ?></div>
                  <div class='activity-time'><?php echo timeAgo($activity['time']); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class='activity-item'>
              <div class='activity-icon'>
                <i class='fa-solid fa-bell-slash'></i>
              </div>
              <div class='activity-content'>
                <div class='activity-desc'>No notifications yet</div>
                <div class='activity-time'>Your activity will appear here</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class='quick-actions'>
        <a href='search_hospitals.php' class='action-button'>
          <div class='action-icon'><i class='fa-solid fa-hospital'></i></div>
          <div class='action-text'>Find Hospitals</div>
        </a>
        
        <a href='book_appointment.php' class='action-button'>
          <div class='action-icon'><i class='fa-solid fa-calendar-plus'></i></div>
          <div class='action-text'>Book Appointment</div>
        </a>

        <a href='my_appointment.php' class='action-button'>
          <div class='action-icon'><i class="fa-solid fa-book-medical"></i></div>
          <div class='action-text'>View Appointments</div>
        </a>

        <a href='reports.php' class='action-button'>
          <div class='action-icon'><i class='fa-solid fa-file-medical'></i></div>
          <div class='action-text'>Test Results</div>
        </a>


      </div>
    </div>
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

  // Initialize charts
  document.addEventListener('DOMContentLoaded', function() {
    // Appointments Chart
    const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
    const appointmentsChart = new Chart(appointmentsCtx, {
      type: 'doughnut',
      data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
          data: [
            <?php echo $stats['approved_appointments']; ?>,
            <?php echo $stats['total_appointments'] - $stats['approved_appointments']; ?>,
            0 // Assuming 0 rejected from your sample data
          ],
          backgroundColor: [
            '#0d9488',
            '#f59e0b',
            '#ef4444'
          ],
          borderWidth: 0,
          hoverOffset: 12
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 12
            }
          }
        }
      }
    });

    // Vaccine Chart
    const vaccineCtx = document.getElementById('vaccineChart').getContext('2d');
    const vaccineChart = new Chart(vaccineCtx, {
      type: 'bar',
      data: {
        labels: [
          <?php
          foreach ($vaccineData as $vaccine) {
            echo "'" . $vaccine['name'] . "',";
          }
          ?>
        ],
        datasets: [{
          label: 'Vaccines Administered',
          data: [
            <?php
            foreach ($vaccineData as $vaccine) {
              echo $vaccine['count'] . ', ';
            }
            ?>
          ],
          backgroundColor: '#0d9488',
          borderWidth: 0,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba( 0, 0, 0, 0.05 )'
            },
            ticks: {
              stepSize: 1
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    // Add animation to stat cards on scroll
    const statCards = document.querySelectorAll('.stat-card');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
          entry.target.style.transform = 'translateY( 0 )';
        }
      });
    }, { threshold: 0.1 });

    statCards.forEach(card => {
      card.style.opacity = 0;
      card.style.transform = 'translateY( 20px )';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    observer.observe( card );
                }
            );
        }
    );
    </script>
    </body>
    </html>