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
    $full_name = $hospital['h_firstname'] . ' ' . $hospital['h_lastname'];
} else {
    die("Hospital not found!");
}

// Fetch statistics from database
$stats = [];

// Total Appointments
$sql = "SELECT COUNT(*) as total FROM appointments WHERE hospital_id = $hospital_id";
$result = $db_conn->query($sql);
$stats['total_appointments'] = $result->fetch_assoc()['total'];

// Pending Approvals
$sql = "SELECT COUNT(*) as total FROM appointments WHERE hospital_id = $hospital_id AND status = 'pending'";
$result = $db_conn->query($sql);
$stats['pending_approvals'] = $result->fetch_assoc()['total'];

// Confirmed Appointments
$sql = "SELECT COUNT(*) as total FROM appointments WHERE hospital_id = $hospital_id AND status = 'confirmed'";
$result = $db_conn->query($sql);
$stats['confirmed_appointments'] = $result->fetch_assoc()['total'];

// Completed Tests/Vaccinations
$sql = "SELECT COUNT(*) as total FROM appointments WHERE hospital_id = $hospital_id AND result_status != 'pending'";
$result = $db_conn->query($sql);
$stats['completed_procedures'] = $result->fetch_assoc()['total'];

// Fetch vaccine stock data
$sql = "SELECT name, total_stock, used_stock, status FROM vaccines ORDER BY name";
$result = $db_conn->query($sql);
$vaccineData = [];
while ($row = $result->fetch_assoc()) {
    $vaccineData[] = $row;
}

// Fetch recent appointments
$sql = "SELECT a.*, CONCAT(p.p_firstname, ' ', p.p_lastname) as patient_name, 
               p.p_phone, p.p_city, v.name as vaccine_name
        FROM appointments a
        LEFT JOIN patient p ON a.patient_id = p.p_id
        LEFT JOIN vaccines v ON a.vaccine_id = v.id
        WHERE a.hospital_id = $hospital_id
        ORDER BY a.created_at DESC 
        LIMIT 5";
$result = $db_conn->query($sql);
$recentAppointments = [];
while ($row = $result->fetch_assoc()) {
    $recentAppointments[] = $row;
}

// Function to calculate time ago
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "$mins minutes ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "$hours hours ago";
    } else {
        $days = floor($diff / 86400);
        return "$days days ago";
    }
}
?>

<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='utf-8' />
  <meta name='viewport' content='width=device-width, initial-scale=1' />
  <title>CovidCare • Hospital Dashboard</title>
  <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css' rel='stylesheet'/>
  <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
  <link rel="stylesheet" href="../Hospital/css/header.css">
  <style>
    /* --------------------Dashboard Styles-------------------- */
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
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
      color: white;
      box-shadow: 0 10px 30px rgba(13, 148, 136, 0.2);
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
      background: rgba(255, 255, 255, 0.1);
      transform: rotate(45deg);
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
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
      border: 1px solid var(--border);
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
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 0;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-600);
    }

    .stat-card:hover::before {
      opacity: 0.03;
    }

    .stat-card:hover .stat-icon {
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
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
      color: var(--muted);
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .stat-card:hover .stat-title {
      color: var(--primary-700);
    }

    .stat-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(13, 148, 136, 0.06));
      color: var(--accent);
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
      color: var(--primary-700);
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
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }

    .chart-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
      border: 1px solid var(--border);
      transition: all 0.3s ease;
    }

    .chart-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      border-color: var(--primary-600);
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
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 6px 10px;
      font-size: 13px;
      cursor: pointer;
      color: var(--muted);
      transition: all 0.3s ease;
    }

    .chart-actions button:hover {
      background: var(--primary-600);
      color: white;
      border-color: var(--primary-600);
    }

    .chart-container {
      position: relative;
      height: 300px;
    }

    .activity-container {
      background: var(--card);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
      border: 1px solid var(--border);
      margin-bottom: 24px;
      transition: all 0.3s ease;
    }

    .activity-container:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      border-color: var(--primary-600);
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
      background: linear-gradient(90deg, rgba(13, 148, 136, 0.08), rgba(15, 118, 110, 0.05));
      transform: translateX(5px);
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(13, 148, 136, 0.06));
      color: var(--accent);
      flex-shrink: 0;
      transition: all 0.3s ease;
    }

    .activity-item:hover .activity-icon {
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      color: white;
      transform: scale(1.1);
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
      color: var(--primary-700);
    }

    .activity-time {
      font-size: 12px;
      color: var(--muted);
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }

    .action-button {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 20px;
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
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
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 0;
    }

    .action-button:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-600);
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
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      font-size: 20px;
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
    }

    .action-button:hover .action-icon {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 5px 15px rgba(13, 148, 136, 0.3);
    }

    .action-text {
      font-size: 14px;
      font-weight: 600;
      transition: color 0.3s ease;
      position: relative;
      z-index: 1;
    }

    .action-button:hover .action-text {
      color: var(--primary-700);
    }

    /* Status badges */
    .status-badge {
      display: inline-block;
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

    .status-approved, .status-confirmed {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .status-rejected, .status-cancelled {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .result-pending {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
    }

    .result-positive {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .result-negative {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .result-vaccinated {
      background: rgba(139, 92, 246, 0.2);
      color: #8b5cf6;
    }

    .result-not_vaccinated {
      background: rgba(107, 114, 128, 0.2);
      color: #6b7280;
    }

    @media (max-width: 1024px) {
      .charts-container {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: 1fr 1fr;
      }
      .quick-actions {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 600px) {
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
    <?php include './h_sidebar.php'; ?>
    
    <div class="main">
      <?php include './h_header.php'; ?>

      <!-- Main Section --> 
      <div class='content'>
        <h1 class='page-title'><i class='fa-solid fa-gauge'></i> Hospital Dashboard</h1>

        <!-- Welcome Card -->
        <div class='welcome-card'>
          <h2>Welcome, <?php echo $full_name; ?>!</h2>
          <p>Manage your hospital's COVID-19 testing and vaccination services efficiently.</p>
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
              <div class='stat-title'>PENDING APPROVALS</div>
              <div class='stat-icon'><i class='fa-solid fa-clock'></i></div>
            </div>
            <div class='stat-value'><?php echo $stats['pending_approvals']; ?></div>
            <div class='stat-change <?php echo $stats['pending_approvals'] > 0 ? 'change-negative' : 'change-positive'; ?>'>
              <i class='fa-solid <?php echo $stats['pending_approvals'] > 0 ? 'fa-clock' : 'fa-check'; ?>'></i> 
              <?php echo $stats['pending_approvals'] > 0 ? 'Needs Review' : 'All Caught Up'; ?>
            </div>
          </div>

          <div class='stat-card'>
            <div class='stat-header'>
              <div class='stat-title'>CONFIRMED APPOINTMENTS</div>
              <div class='stat-icon'><i class='fa-solid fa-user-check'></i></div>
            </div>
            <div class='stat-value'><?php echo $stats['confirmed_appointments']; ?></div>
            <div class='stat-change change-positive'><i class='fa-solid fa-calendar'></i> Scheduled</div>
          </div>

          <div class='stat-card'>
            <div class='stat-header'>
              <div class='stat-title'>COMPLETED PROCEDURES</div>
              <div class='stat-icon'><i class='fa-solid fa-check-circle'></i></div>
            </div>
            <div class='stat-value'><?php echo $stats['completed_procedures']; ?></div>
            <div class='stat-change change-positive'><i class='fa-solid fa-chart-line'></i> Completed</div>
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
              <div class='chart-title'>Vaccine Stock</div>
              <div class='chart-actions'>
                <button>Manage</button>
              </div>
            </div>
            <div class='chart-container'>
              <canvas id='vaccineChart'></canvas>
            </div>
          </div>
        </div>

        <div class='activity-container'>
          <div class='activity-header'>
            <div class='activity-title'>Recent Appointments</div>
            <div class='chart-actions'>
              <button>View All</button>
            </div>
          </div>
          <div class='activity-list'>
            <?php if (!empty($recentAppointments)): ?>
              <?php foreach ($recentAppointments as $appointment): ?>
                <div class='activity-item'>
                  <div class='activity-icon'>
                    <i class="fa-solid <?php 
                      if ($appointment['test_type'] === 'covid_test') echo 'fa-vial';
                      else echo 'fa-syringe';
                    ?>"></i>
                  </div>
                  <div class='activity-content'>
                    <div class='activity-desc'>
                      <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong> - 
                      <?php echo $appointment['test_type'] === 'covid_test' ? 'COVID Test' : 'Vaccination'; ?>
                      <?php if ($appointment['test_type'] === 'vaccination' && $appointment['vaccine_name']): ?>
                        (<?php echo htmlspecialchars($appointment['vaccine_name']); ?>)
                      <?php endif; ?>
                      - <span class="status-badge status-<?php echo $appointment['status']; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                      </span>
                    </div>
                    <div class='activity-time'><?php echo timeAgo($appointment['created_at']); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class='activity-item'>
                <div class='activity-icon'>
                  <i class='fa-solid fa-calendar-times'></i>
                </div>
                <div class='activity-content'>
                  <div class='activity-desc'>No recent appointments</div>
                  <div class='activity-time'>Appointments will appear here</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class='quick-actions'>
          <a href='appointments.php' class='action-button'>
            <div class='action-icon'><i class='fa-solid fa-calendar'></i></div>
            <div class='action-text'>Manage Appointments</div>
          </a>
          
          <a href='update_results.php' class='action-button'>
            <div class='action-icon'><i class='fa-solid fa-file-medical'></i></div>
            <div class='action-text'>Update Results</div>
          </a>

          <a href='vaccine_stock.php' class='action-button'>
            <div class='action-icon'><i class='fa-solid fa-syringe'></i></div>
            <div class='action-text'>Vaccine Stock</div>
          </a>

          <a href='patients.php' class='action-button'>
            <div class='action-icon'><i class='fa-solid fa-users'></i></div>
            <div class='action-text'>Patient Records</div>
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
          labels: ['Pending', 'Confirmed', 'Completed'],
          datasets: [{
            data: [
              <?php echo $stats['pending_approvals']; ?>,
              <?php echo $stats['confirmed_appointments']; ?>,
              <?php echo $stats['completed_procedures']; ?>
            ],
            backgroundColor: [
              '#f59e0b',
              '#0d9488',
              '#10b981'
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
            label: 'Available Stock',
            data: [
              <?php
              foreach ($vaccineData as $vaccine) {
                echo ($vaccine['total_stock'] - $vaccine['used_stock']) . ', ';
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
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                stepSize: 5
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
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, { threshold: 0.1 });

      statCards.forEach(card => {
        card.style.opacity = 0;
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
      });
    });
  </script>
</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>