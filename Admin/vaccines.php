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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stock'])) {
        $id = $_POST['vaccine_id'];
        $used_stock = $_POST['used_stock'];
        
        // Update the database
        $sql = "UPDATE vaccines SET used_stock = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db_conn->prepare($sql);
        $stmt->bind_param("ii", $used_stock, $id);
        
        if ($stmt->execute()) {
            $success_message = "Stock updated successfully!";
        } else {
            $error_message = "Error updating stock: " . $db_conn->error;
        }
        $stmt->close();
    }
}

// Fetch vaccines from database
$sql = "SELECT * FROM vaccines ORDER BY id";
$result = $db_conn->query($sql);
$vaccines = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vaccines[] = $row;
    }
}

$db_conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Vaccine Management</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Admin/css/header.css">
  <style>
    /* Vaccine Page Styles */
    .content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .page-title {
      font-size: 24px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      padding-left: 16px;
    }

    .page-title::before {
      content: "";
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      height: 24px;
      width: 4px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      border-radius: 2px;
    }

    .update-stock-btn {
      padding: 10px 16px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .update-stock-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(13, 148, 136, 0.2);
    }

    .vaccines-table-container {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      border: 1px solid var(--border);
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    .table-title {
      font-size: 20px;
      font-weight: 700;
    }

    .table-search {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: 8px;
      background: var(--glass);
      border: 1px solid var(--border);
      width: 300px;
    }

    .table-search input {
      border: 0;
      outline: 0;
      background: transparent;
      width: 100%;
      color: var(--text);
    }
    
    .table-search:hover{
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      border-color: var(--primary-600);
    }
    
    .table-search:focus-within {
      border-color: var(--primary-600);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      text-align: left;
      padding: 16px 20px;
     background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: var(--text);
      font-weight: 600;
      border-bottom: 1px solid var(--border);
      position: relative;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    table th:hover {
      background: linear-gradient(135deg, rgba(13,148,136,0.1), rgba(15,118,110,0.05));
      color: var(--primary-600);
    }

    th:hover::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
    }

    td {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr {
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
      font-size: 14px;
      font-weight: 700;
      display: inline-block;
    }
    
    .status-available {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }
    
    .status-unavailable {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }

    .filter-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .filter-buttons {
      display: flex;
      flex-wrap: wrap;
      background: var(--glass);
      border-radius: 8px;
      padding: 4px;
      border: 1px solid var(--border);
      gap: 4px;
    }

    .filter-btn {
      padding: 8px 16px;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      color: var(--muted);
      font-size: 14px;
    }

    .filter-btn.active {
      background: var(--primary-600);
      color: white;
    }

    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1002;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .modal.active {
      opacity: 1;
      visibility: visible;
    }

    .modal-content {
      background: var(--card);
      border-radius: var(--radius);
      width: 500px;
      max-width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      transform: translateY(-20px);
      transition: all 0.3s ease;
      z-index: 1003;
    }

    .modal.active .modal-content {
      transform: translateY(0);
    }

    .modal-header {
      padding: 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-title {
      font-size: 20px;
      font-weight: 700;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .close-btn {
      background: transparent;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--muted);
      transition: all 0.3s ease;
    }

    .close-btn:hover {
      color: var(--primary-600);
      transform: rotate(90deg);
    }

    .modal-body {
      padding: 20px;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text);
    }

    .form-select, .form-input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--card);
      color: var(--text);
      font-size: 16px;
    }

    .form-select:focus, .form-input:focus {
      outline: none;
      border-color: var(--primary-600);
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .modal-footer {
      padding: 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }

    .cancel-btn {
      padding: 10px 16px;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      color: var(--muted);
    }

    .cancel-btn:hover {
      background: var(--glass);
    }

    .save-btn {
      padding: 10px 16px;
      background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .save-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(13, 148, 136, 0.2);
    }

    /* responsive */
    @media (max-width: 1024px){
      .layout{grid-template-columns: 1fr}
      .sidebar{position:fixed; left:-100%; top:0; transition:left .25s; z-index:1001; width:260px}
      .sidebar.open{left:0}
      .menu-toggle{display:inline-block}
      .table-search{width: 200px;}
    }
    @media (max-width:768px){
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }
      .filter-container {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
      }
      .table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
      .filter-buttons {
        width: 100%;
        justify-content: center;
      }
    }
    @media (max-width:600px){
      .content{padding:12px}
      .table-search{width: 100%; margin-top: 12px;}
      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }
      th, td {
        padding: 12px 14px;
      }
    }
  </style>
</head>
<body>
   <!-- Overlay for closing sidebar on mobile -->
   <div class='overlay' id='overlay'></div>
   
  <div class="layout">
  <?php include './a_sidebar.php'; ?>

    <div class="main">
      <?php include './a_header.php'; ?>


      <div class="content">
        <div class="page-header">
          <h1 class="page-title">COVID Vaccines</h1>
        </div>

        <!-- Display messages -->
        <?php if (isset($success_message)): ?>
          <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="vaccines-table-container">
          <div class="table-header">
            <div class="filter-container">
              <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="available">Available</button>
                <button class="filter-btn" data-filter="unavailable">Unavailable</button>
              </div>
            </div>
            <div class="table-search">
              <i class="fa-solid fa-search muted"></i>
              <input type="text" placeholder="Search vaccine..." id="searchInput" />
            </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Vaccine Name</th>
                <th>Total Stock</th>
                <th>Used Stock</th>
                <th>Remaining Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="vaccinesTableBody">
              <?php foreach ($vaccines as $vaccine): 
                $remaining_stock = $vaccine['total_stock'] - $vaccine['used_stock'];
                $status = $remaining_stock > 0 ? 'available' : 'unavailable';
              ?>
                <tr data-status="<?php echo $status; ?>">
                  <td><?php echo $vaccine['id']; ?></td>
                  <td><?php echo htmlspecialchars($vaccine['name']); ?></td>
                  <td><?php echo $vaccine['total_stock']; ?></td>
                  <td><?php echo $vaccine['used_stock']; ?></td>
                  <td><?php echo $remaining_stock; ?></td>
                  <td>
                    <span class="status-badge status-<?php echo $status; ?>">
                      <?php echo ucfirst($status); ?>
                    </span>
                  </td>
                  <td>
                    <button class="update-stock-btn" data-id="<?php echo $vaccine['id']; ?>" data-name="<?php echo htmlspecialchars($vaccine['name']); ?>" data-total="<?php echo $vaccine['total_stock']; ?>" data-used="<?php echo $vaccine['used_stock']; ?>">
                      <i class="fa-solid fa-pen-to-square"></i> Update
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Stock Modal -->
  <div class="modal" id="updateStockModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Update Vaccine Stock</h2>
        <button class="close-btn" id="closeModal">&times;</button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="vaccine_id" id="vaccineId">
          
          <div class="form-group">
            <label class="form-label">Vaccine Name</label>
            <input type="text" class="form-input" id="vaccineName" readonly>
          </div>
          
          <div class="form-group">
            <label class="form-label">Total Stock</label>
            <input type="number" class="form-input" id="totalStock" readonly>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="usedStock">Used Stock</label>
            <input type="number" class="form-input" name="used_stock" id="usedStock" min="0" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Remaining Stock</label>
            <input type="number" class="form-input" id="remainingStock" readonly>
          </div>
          
          <div class="form-group">
            <label class="form-label">Status</label>
            <input type="text" class="form-input" id="statusDisplay" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn" id="cancelUpdateBtn">Cancel</button>
          <button type="submit" name="update_stock" class="save-btn">Save Changes</button>
        </div>
      </form>
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

    // Modal functionality
    const modal = document.getElementById('updateStockModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelUpdateBtn = document.getElementById('cancelUpdateBtn');

    // Open modal when update button is clicked
    document.querySelectorAll('.update-stock-btn').forEach(button => {
      button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');
        const total = parseInt(this.getAttribute('data-total'));
        const used = parseInt(this.getAttribute('data-used'));
        const remaining = total - used;
        const status = remaining > 0 ? 'Available' : 'Unavailable';
        
        document.getElementById('vaccineId').value = id;
        document.getElementById('vaccineName').value = name;
        document.getElementById('totalStock').value = total;
        document.getElementById('usedStock').value = used;
        document.getElementById('remainingStock').value = remaining;
        document.getElementById('statusDisplay').value = status;
        
        modal.classList.add('active');
      });
    });

    closeModalBtn.addEventListener('click', function() {
      modal.classList.remove('active');
    });

    cancelUpdateBtn.addEventListener('click', function() {
      modal.classList.remove('active');
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === modal) {
        modal.classList.remove('active');
      }
    });

    // Calculate remaining stock automatically
    const usedStockInput = document.getElementById('usedStock');
    const totalStockInput = document.getElementById('totalStock');
    const remainingStockInput = document.getElementById('remainingStock');
    const statusDisplay = document.getElementById('statusDisplay');

    function calculateRemainingStock() {
      const total = parseInt(totalStockInput.value) || 0;
      const used = parseInt(usedStockInput.value) || 0;
      const remaining = total - used;
      remainingStockInput.value = remaining;
      
      // Update status
      if (remaining > 0) {
        statusDisplay.value = 'Available';
      } else {
        statusDisplay.value = 'Unavailable';
      }
    }

    usedStockInput.addEventListener('input', calculateRemainingStock);

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#vaccinesTableBody tr');

    searchInput.addEventListener('input', function() {
      const searchText = this.value.toLowerCase();
      
      tableRows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const id = row.cells[0].textContent.toLowerCase();
        if (name.includes(searchText) || id.includes(searchText)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });

    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    
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