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

// Fetch statistics from database
$stats = [];

// Total Patients
$sql = 'SELECT COUNT(*) as total FROM patient';
$result = $db_conn->query( $sql );
$stats[ 'total_patients' ] = $result->fetch_assoc()[ 'total' ];

// Total Hospitals
$sql = "SELECT COUNT(*) as total FROM hospital WHERE h_status = 'approved'";
$result = $db_conn->query( $sql );
$stats[ 'total_hospitals' ] = $result->fetch_assoc()[ 'total' ];

// Total Vaccinated ( assuming this is stored elsewhere, using used_stock as proxy )
$sql = 'SELECT SUM(used_stock) as total FROM vaccines';
$result = $db_conn->query( $sql );
$stats[ 'total_vaccinated' ] = $result->fetch_assoc()[ 'total' ];

// Pending Approvals
$sql = "SELECT COUNT(*) as total FROM hospital WHERE h_status = 'pending'";
$result = $db_conn->query( $sql );
$stats[ 'pending_approvals' ] = $result->fetch_assoc()[ 'total' ];

// Fetch recent activities
$activities = [];

// Recent patient registrations
$sql = 'SELECT p_firstname, p_lastname, created_at FROM patient ORDER BY created_at DESC LIMIT 5';
$result = $db_conn->query( $sql );
while ( $row = $result->fetch_assoc() ) {
    $activities[] = [
        'type' => 'patient_registration',
        'description' => 'New patient registered: ' . $row[ 'p_firstname' ] . ' ' . $row[ 'p_lastname' ],
        'time' => $row[ 'created_at' ]
    ];
}

// Recent hospital approvals
$sql = "SELECT h_firstname, h_lastname, created_at FROM hospital WHERE h_status = 'approved' ORDER BY created_at DESC LIMIT 3";
$result = $db_conn->query( $sql );
while ( $row = $result->fetch_assoc() ) {
    $activities[] = [
        'type' => 'hospital_approval',
        'description' => 'Hospital approved: ' . $row[ 'h_firstname' ] . ' ' . $row[ 'h_lastname' ],
        'time' => $row[ 'created_at' ]
    ];
}

// Fetch vaccine data for chart
$sql = 'SELECT name, used_stock FROM vaccines';
$result = $db_conn->query( $sql );
$vaccineData = [];
while ( $row = $result->fetch_assoc() ) {
    $vaccineData[] = $row;
}

$db_conn->close();

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
<title>CovidCare • Enhanced Dashboard</title>
<link href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css' rel = 'stylesheet'/>
<script src = 'https://cdn.jsdelivr.net/npm/chart.js'></script>
<link rel = 'stylesheet' href = '../Admin/css/header.css'>
<link rel = 'stylesheet' href = '../Admin/css/dashboard.css'>
                                                </head>
                                                <body>
                                                <!-- Overlay for closing sidebar on mobile -->
                                                <div class = 'overlay' id = 'overlay'></div>

                                                <div class = 'layout'>
                                                <?php include './a_sidebar.php'; ?>

                                                <div class = 'main'>
                                                <?php include './a_header.php'; ?>

                                                <div class = 'content'>
                                                <h1 class = 'page-title'><i class = 'fa-solid fa-gauge'></i> Dashboard Overview</h1>

                                                <div class = 'stats-grid'>
                                                <div class = 'stat-card'>
                                                <div class = 'stat-header'>
                                                <div class = 'stat-title'>TOTAL PATIENTS</div>
                                                <div class = 'stat-icon'><i class = 'fa-regular fa-user'></i></div>
                                                </div>
                                                <div class = 'stat-value'><?php echo $stats[ 'total_patients' ];
                                                ?></div>
                                                <div class = 'stat-change change-positive'><i class = 'fa-solid fa-arrow-up'></i> Registered</div>
                                                </div>

                                                <div class = 'stat-card'>
                                                <div class = 'stat-header'>
                                                <div class = 'stat-title'>VACCINATED</div>
                                                <div class = 'stat-icon'><i class = 'fa-solid fa-syringe'></i></div>
                                                </div>
                                                <div class = 'stat-value'><?php echo $stats[ 'total_vaccinated' ];
                                                ?></div>
                                                <div class = 'stat-change change-positive'><i class = 'fa-solid fa-arrow-up'></i> Doses Administered</div>
                                                </div>

                                                <div class = 'stat-card'>
                                                <div class = 'stat-header'>
                                                <div class = 'stat-title'>APPROVED HOSPITALS</div>
                                                <div class = 'stat-icon'><i class = 'fa-solid fa-hospital'></i></div>
                                                </div>
                                                <div class = 'stat-value'><?php echo $stats[ 'total_hospitals' ];
                                                ?></div>
                                                <div class = 'stat-change change-positive'><i class = 'fa-solid fa-arrow-up'></i> Active</div>
                                                </div>

                                                <div class = 'stat-card'>
                                                <div class = 'stat-header'>
                                                <div class = 'stat-title'>PENDING APPROVALS</div>
                                                <div class = 'stat-icon'><i class = 'fa-solid fa-user-shield'></i></div>
                                                </div>
                                                <div class = 'stat-value'><?php echo $stats[ 'pending_approvals' ];
                                                ?></div>
                                                <div class = 'stat-change change-negative'><i class = 'fa-solid fa-clock'></i> Awaiting Review</div>
                                                </div>
                                                </div>

                                                <div class = 'charts-container'>
                                                <div class = 'chart-card'>
                                                <div class = 'chart-header'>
                                                <div class = 'chart-title'>Hospital Distribution</div>
                                                <div class = 'chart-actions'>
                                                <button>View All</button>
                                                </div>
                                                </div>
                                                <div class = 'chart-container'>
                                                <canvas id = 'hospitalsChart'></canvas>
                                                </div>
                                                </div>

                                                <div class = 'chart-card'>
                                                <div class = 'chart-header'>
                                                <div class = 'chart-title'>Vaccine Distribution</div>
                                                <div class = 'chart-actions'>
                                                <button>View</button>
                                                </div>
                                                </div>
                                                <div class = 'chart-container'>
                                                <canvas id = 'vaccineChart'></canvas>
                                                </div>
                                                </div>
                                                </div>

                                                <div class = 'activity-container'>
                                                <div class = 'activity-header'>
                                                <div class = 'activity-title'>Recent Activities</div>
                                                <div class = 'chart-actions'>
                                                <button>View All</button>
                                                </div>
                                                </div>
                                                <div class = 'activity-list'>
                                                <?php foreach ( $activities as $activity ): ?>
                                                <div class = 'activity-item'>
                                                <div class = 'activity-icon'>
                                                <i class = "fa-solid <?php 
                  if ($activity['type'] == 'patient_registration') echo 'fa-user-plus';
                  else if ($activity['type'] == 'hospital_approval') echo 'fa-hospital';
                ?>"></i>
                                                </div>
                                                <div class = 'activity-content'>
                                                <div class = 'activity-desc'><?php echo $activity[ 'description' ];
                                                ?></div>
                                                <div class = 'activity-time'><?php echo timeAgo( $activity[ 'time' ] );
                                                ?></div>
                                                </div>
                                                </div>
                                                <?php endforeach;
                                                ?>
                                                </div>
                                                </div>

                                                <div class = 'quick-actions'>
                                                <a href = 'patients.php' class = 'action-button'>
                                                <div class = 'action-icon'><i class = 'fa-regular fa-user'></i></div>
                                                <div class = 'action-text'>Manage Patients</div>
                                                </a>

                                                <a href = 'hospitals.php' class = 'action-button'>
                                                <div class = 'action-icon'><i class = 'fa-solid fa-hospital'></i></div>
                                                <div class = 'action-text'>Hospital Status</div>
                                                </a>

                                                <a href = 'approval.php' class = 'action-button'>
                                                <div class = 'action-icon'><i class = 'fa-solid fa-user-shield'></i></div>
                                                <div class = 'action-text'>Approval Requests</div>
                                                </a>

                                                <a href = 'vaccines.php' class = 'action-button'>
                                                <div class = 'action-icon'><i class = 'fa-solid fa-syringe'></i></div>
                                                <div class = 'action-text'>Manage Vaccine</div>
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
                            document.addEventListener( 'DOMContentLoaded', function() {
                                // Hospitals Chart
                                const hospitalsCtx = document.getElementById( 'hospitalsChart' ).getContext( '2d' );
                                const hospitalsChart = new Chart( hospitalsCtx, {
                                    type: 'bar',
                                    data: {
                                        labels: [ 'Approved', 'Pending', 'Rejected' ],
                                        datasets: [ {
                                            label: 'Hospitals',
                                            data: [
                                                <?php echo $stats[ 'total_hospitals' ];
                                                ?>,
                                                <?php echo $stats[ 'pending_approvals' ];
                                                ?>,
                                                1 // Assuming 1 rejected from your sample data
                                            ],
                                            backgroundColor: [
                                                '#0d9488',
                                                '#f59e0b',
                                                '#ef4444'
                                            ],
                                            borderWidth: 0,
                                            borderRadius: 6
                                        }
                                    ]
                                }
                                ,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    }
                                    ,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: {
                                                color: 'rgba(0, 0, 0, 0.05)'
                                            }
                                        }
                                        ,
                                        x: {
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                }
                            }
                        );

                        // Vaccine Chart
                        const vaccineCtx = document.getElementById( 'vaccineChart' ).getContext( '2d' );
                        const vaccineChart = new Chart( vaccineCtx, {
                            type: 'doughnut',
                            data: {
                                labels: [
                                    <?php
                                    foreach ( $vaccineData as $vaccine ) {
                                        echo "'" . $vaccine[ 'name' ] . "',";
                                    }
                                    ?>
                                ],
                                datasets: [ {
                                    data: [
                                        <?php
                                        foreach ( $vaccineData as $vaccine ) {
                                            echo $vaccine[ 'used_stock' ] . ',';
                                        }
                                        ?>
                                    ],
                                    backgroundColor: [
                                        '#0d9488',
                                        '#14b8a6',
                                        '#10b981',
                                        '#f59e0b',
                                        '#ef4444',
                                        '#8b5cf6',
                                        '#06b6d4'
                                    ],
                                    borderWidth: 0,
                                    hoverOffset: 12
                                }
                            ]
                        }
                        ,
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
                    }
                );

                // Add animation to stat cards on scroll
                const statCards = document.querySelectorAll( '.stat-card' );
                const observer = new IntersectionObserver( ( entries ) => {
                    entries.forEach( entry => {
                        if ( entry.isIntersecting ) {
                            entry.target.style.opacity = 1;
                            entry.target.style.transform = 'translateY(0)';
                        }
                    }
                );
            }
            , {
                threshold: 0.1 }
            );

            statCards.forEach( card => {
                card.style.opacity = 0;
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe( card );
            }
        );
    }
);
</script>
</body>
</html>