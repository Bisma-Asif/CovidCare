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
$result = mysqli_query($db_conn, $sql);
$patient = mysqli_fetch_assoc($result);

// Calculate age from date of birth
$dob = new DateTime($patient['p_dob']);
$now = new DateTime();
$age = $now->diff($dob)->y;

// Update profile if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = mysqli_real_escape_string($db_conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($db_conn, $_POST['lastname']);
    $phone = mysqli_real_escape_string($db_conn, $_POST['phone']);
    $email = mysqli_real_escape_string($db_conn, $_POST['email']);
    $dob = mysqli_real_escape_string($db_conn, $_POST['dob']);
    $gender = mysqli_real_escape_string($db_conn, $_POST['gender']);
    $address = mysqli_real_escape_string($db_conn, $_POST['address']);
    $city = mysqli_real_escape_string($db_conn, $_POST['city']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $patient['p_password'];
    
    $update_sql = "UPDATE patient SET 
                  p_firstname = '$firstname',
                  p_lastname = '$lastname', 
                  p_phone = '$phone',
                  p_email = '$email',
                  p_dob = '$dob',
                  p_gender = '$gender',
                  p_address = '$address',
                  p_city = '$city',
                  p_password = '$password'
                  WHERE p_id = $patient_id";
    
    if (mysqli_query($db_conn, $update_sql)) {
        $success = "Profile updated successfully!";
        // Refresh patient data
        $result = mysqli_query($db_conn, "SELECT * FROM patient WHERE p_id = $patient_id");
        $patient = mysqli_fetch_assoc($result);
        
        // Recalculate age
        $dob = new DateTime($patient['p_dob']);
        $age = $now->diff($dob)->y;
    } else {
        $error = "Error updating profile: " . mysqli_error($db_conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CovidCare • My Profile</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Patient/css/header.css">
  <link rel="stylesheet" href="../Patient/css/profile.css">
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
        <div class="profile-container">
          <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-hospital-user"></i>Patient Details</h2>
            <button class="edit-btn" id="editProfileBtn">
              <i class="fa-solid fa-pen-to-square"></i> Edit Profile
            </button>
          </div>          
          
          <?php if (isset($success)): ?>
          <div class="message success" id="successMessage">
            <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
          </div>
          <?php endif; ?>
          
          <?php if (isset($error)): ?>
          <div class="message error" id="errorMessage">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
          </div>
          <?php endif; ?>
          
          <div class="profile-content">
            <div class="profile-sidebar">
              <div class="profile-card">
                <div class="profile-avatar"><?php echo substr($patient['p_firstname'], 0, 1) . substr($patient['p_lastname'], 0, 1); ?></div>
                <h3 class="profile-name"><?php echo $patient['p_firstname'] . ' ' . $patient['p_lastname']; ?></h3>
                <p class="profile-role">Patient</p>
                <div class="profile-stats">
                  <div class="stat">
                    <div class="stat-value"><?php echo $age; ?></div>
                    <div class="stat-label">Age</div>
                  </div>
                  <div class="stat">
                    <div class="stat-value"><?php echo $patient['p_gender']; ?></div>
                    <div class="stat-label">Gender</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="profile-details">
              <div class="details-grid">
                <div class="detail-card">
                  <div class="detail-header">
                    <div class="detail-icon">
                      <i class="fa-solid fa-user"></i>
                    </div>
                    <h3 class="detail-title">Personal Information</h3>
                  </div>
                  <div class="detail-content">
                    <div class="detail-item">
                      <span class="detail-label">First Name:</span>
                      <span class="detail-value"><?php echo $patient['p_firstname']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Last Name:</span>
                      <span class="detail-value"><?php echo $patient['p_lastname']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Age:</span>
                      <span class="detail-value"><?php echo $age; ?> years</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Date of Birth:</span>
                      <span class="detail-value"><?php echo date('d M Y', strtotime($patient['p_dob'])); ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Gender:</span>
                      <span class="detail-value"><?php echo $patient['p_gender']; ?></span>
                    </div>
                  </div>
                </div>
                
                <div class="detail-card">
                  <div class="detail-header">
                    <div class="detail-icon">
                      <i class="fa-solid fa-phone"></i>
                    </div>
                    <h3 class="detail-title">Contact Information</h3>
                  </div>
                  <div class="detail-content">
                    <div class="detail-item">
                      <span class="detail-label">Phone:</span>
                      <span class="detail-value"><?php echo $patient['p_phone']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Email:</span>
                      <span class="detail-value"><?php echo $patient['p_email']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">City:</span>
                      <span class="detail-value"><?php echo $patient['p_city']; ?></span>
                    </div>
                  </div>
                </div>
                
                <div class="detail-card full-width">
                  <div class="detail-header">
                    <div class="detail-icon">
                      <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3 class="detail-title">Address</h3>
                  </div>
                  <div class="detail-content">
                    <p><?php echo $patient['p_address']; ?></p>
                  </div>
                </div>
                
                <div class="detail-card full-width">
                  <div class="detail-header">
                    <div class="detail-icon">
                      <i class="fa-solid fa-lock"></i>
                    </div>
                    <h3 class="detail-title">Security</h3>
                  </div>
                  <div class="detail-content">
                    <div class="detail-item">
                      <span class="detail-label">Password:</span>
                      <span class="detail-value">••••••••</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Registration Date:</span>
                      <span class="detail-value"><?php echo date('d M Y, H:i', strtotime($patient['created_at'])); ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal-overlay" id="editProfileModal">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title"><i class="fa-solid fa-user-pen"></i> Edit Profile</h2>
        <button class="modal-close" id="modalClose">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      
      <form method="POST" action="">
        <div class="modal-body">
          <div class="modal-form" id="editProfileForm">
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-signature"></i> First Name</label>
              <input type="text" class="form-input" name="firstname" value="<?php echo $patient['p_firstname']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-signature"></i> Last Name</label>
              <input type="text" class="form-input" name="lastname" value="<?php echo $patient['p_lastname']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-phone"></i> Phone Number</label>
              <input type="tel" class="form-input" name="phone" value="<?php echo $patient['p_phone']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-cake-candles"></i> Date of Birth</label>
              <input type="date" class="form-input" name="dob" value="<?php echo $patient['p_dob']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-venus-mars"></i> Gender</label>
              <select style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"class="form-input" name="gender" required>
                <option value="Male" <?php echo $patient['p_gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $patient['p_gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo $patient['p_gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
              <input type="email" class="form-input" name="email" value="<?php echo $patient['p_email']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-lock"></i> Password (leave blank to keep current)</label>
              <input type="password" class="form-input" name="password" placeholder="Enter new password">
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-city"></i> City</label>
              <input type="text" class="form-input" name="city" value="<?php echo $patient['p_city']; ?>" required>
            </div>
            
            <div class="form-group full-width">
              <label class="form-label"><i class="fa-solid fa-location-dot"></i> Address</label>
              <input type="text" class="form-input" name="address" value="<?php echo $patient['p_address']; ?>" required>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="modal-btn btn-cancel" id="cancelEdit">Cancel</button>
          <button type="submit" class="modal-btn btn-save">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
 
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
// Profile modal functionality
const editProfileBtn = document.getElementById('editProfileBtn');
const editProfileModal = document.getElementById('editProfileModal');
const modalClose = document.getElementById('modalClose');
const cancelEdit = document.getElementById('cancelEdit');

function openModal() {
  editProfileModal?.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  editProfileModal?.classList.remove('active');
  document.body.style.overflow = '';
}

editProfileBtn?.addEventListener('click', openModal);
modalClose?.addEventListener('click', closeModal);
cancelEdit?.addEventListener('click', closeModal);

// Close modal when clicking outside
editProfileModal?.addEventListener('click', function(event) {
  if (event.target === editProfileModal) {
    closeModal();
  }
});

// Add animation to profile container on load
document.addEventListener('DOMContentLoaded', function() {
  const profileContainer = document.querySelector('.profile-container');
  if (profileContainer) {
    profileContainer.style.opacity = 0;
    profileContainer.style.transform = 'translateY(20px)';
    profileContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    
    setTimeout(function() {
      profileContainer.style.opacity = 1;
      profileContainer.style.transform = 'translateY(0)';
    }, 100);
  }
  
  // Add animations to detail cards
  const detailCards = document.querySelectorAll('.detail-card');
  detailCards.forEach((card, index) => {
    card.style.opacity = 0;
    card.style.transform = 'translateY(20px)';
    card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
    
    setTimeout(() => {
      card.style.opacity = 1;
      card.style.transform = 'translateY(0)';
    }, 200 + (index * 100));
  });
  
  // Auto-hide messages after 5 seconds
  setTimeout(() => {
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
      message.style.display = 'none';
    });
  }, 5000);
});
  </script>
  <script src="./js/header.js"></script>
</body>
</html>