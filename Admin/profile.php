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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $admin_email = $_POST['admin_email'];
    $admin_password = !empty($_POST['admin_password']) ? $_POST['admin_password'] : $password;
    
    // Update query
    $update_sql = "UPDATE admin SET first_name = '$first_name', last_name = '$last_name', 
                  admin_email = '$admin_email', admin_password = '$admin_password' 
                  WHERE admin_id = $admin_id";
    
    if ($db_conn->query($update_sql)) {
        // Refresh page to show updated data
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Error updating profile: " . $db_conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • Admin Profile</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../Admin/css/header.css">
   <link rel="stylesheet" href="../Admin/css/profile.css">
  <style>
      .error-message {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.1);
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 16px;
      display: <?php echo isset($error) ? 'block' : 'none'; ?>;
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
        <div class="profile-card">
          <div class="profile-header">
            <div class="profile-avatar"><?php echo substr($admin_data['first_name'], 0, 1) . substr($admin_data['last_name'], 0, 1); ?></div>
            <div class="profile-info">
              <h2 id="adminName"><?php echo $full_name; ?></h2>
              <p>Administrator</p>
            </div>
          </div>
          
          <div class="profile-details">
            <div class="detail-row">
              <span class="detail-label">First Name</span>
              <span class="detail-value" id="displayFirstName"><?php echo $admin_data['first_name']; ?></span>
            </div>
            
            <div class="detail-row">
              <span class="detail-label">Last Name</span>
              <span class="detail-value" id="displayLastName"><?php echo $admin_data['last_name']; ?></span>
            </div>
            
            <div class="detail-row">
              <span class="detail-label">Email</span>
              <span class="detail-value" id="displayEmail"><?php echo $email; ?></span>
            </div>
            
            <div class="detail-row">
              <span class="detail-label">Password</span>
              <span class="detail-value">
                <div class="password-field">
                  <span class="password-text" id="displayPassword">••••••••</span>
                  <button class="toggle-password" id="togglePassword">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
              </span>
            </div>
          </div>
          
          <div class="profile-actions">
            <button class="btn btn-primary" id="editProfileBtn">Edit Profile</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Edit Profile</h2>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      
      <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <form id="profileForm" method="POST">
        <div class="form-group">
          <label class="form-label" for="first_name">First Name</label>
          <input type="text" class="form-input" id="first_name" name="first_name" value="<?php echo $admin_data['first_name']; ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="last_name">Last Name</label>
          <input type="text" class="form-input" id="last_name" name="last_name" value="<?php echo $admin_data['last_name']; ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="admin_email">Email</label>
          <input type="email" class="form-input" id="admin_email" name="admin_email" value="<?php echo $email; ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="admin_password">Password</label>
          <input type="password" class="form-input" id="admin_password" name="admin_password" placeholder="Enter new password">
          <small style="color: var(--muted); display: block; margin-top: 5px;">Leave blank to keep current password</small>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-outline" id="cancelEdit">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
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

    // Profile functionality
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editModal = document.getElementById('editModal');
    const closeModal = document.getElementById('closeModal');
    const cancelEdit = document.getElementById('cancelEdit');
    const profileForm = document.getElementById('profileForm');
    const togglePassword = document.getElementById('togglePassword');
    
    // Open edit modal
    editProfileBtn.addEventListener('click', function() {
      editModal.classList.add('active');
    });
    
    // Close edit modal
    closeModal.addEventListener('click', function() {
      editModal.classList.remove('active');
    });
    
    cancelEdit.addEventListener('click', function() {
      editModal.classList.remove('active');
    });
    
    // Toggle password visibility
    let passwordVisible = false;
    togglePassword.addEventListener('click', function() {
      const passwordElement = document.getElementById('displayPassword');
      
      if (passwordVisible) {
        passwordElement.textContent = '••••••••';
        togglePassword.innerHTML = '<i class="fa-solid fa-eye"></i>';
      } else {
        // In a real application, you would retrieve the actual password from a secure source
        passwordElement.textContent = '<?php echo $password; ?>';
        togglePassword.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
      }
      
      passwordVisible = !passwordVisible;
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === editModal) {
        editModal.classList.remove('active');
      }
    });
  </script>

</body>
</html>
<?php
// Close database connection
$db_conn->close();
?>