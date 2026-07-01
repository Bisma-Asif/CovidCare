<?php
include '../auth.php';
checkRole('hospital');
include '../db_conn.php';

$hospital_id = $_SESSION['hospital_id'] ?? 0; // fallback if session not set

// Fetch hospital data
$sql = "SELECT * FROM hospital WHERE h_id = $hospital_id";
$result = $db_conn->query($sql);

if ($result && $result->num_rows > 0) {
    $hospital = $result->fetch_assoc();
    $full_name = ($hospital['h_firstname'] ?? 'N/A') . ' ' . ($hospital['h_lastname'] ?? '');
} else {
    die("Hospital not found!");
}
// Fetch hospital data
$sql = "SELECT * FROM hospital WHERE h_id = $hospital_id";
$result = mysqli_query($db_conn, $sql);
$hospital = mysqli_fetch_assoc($result);

// Update profile if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = mysqli_real_escape_string($db_conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($db_conn, $_POST['lastname']);
    $phone = mysqli_real_escape_string($db_conn, $_POST['phone']);
    $email = mysqli_real_escape_string($db_conn, $_POST['email']);
    $license = mysqli_real_escape_string($db_conn, $_POST['license']);
    $address = mysqli_real_escape_string($db_conn, $_POST['address']);
    $city = mysqli_real_escape_string($db_conn, $_POST['city']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $hospital['h_password'];
    
    $update_sql = "UPDATE hospital SET 
                  h_firstname = '$firstname',
                  h_lastname = '$lastname', 
                  h_phone = '$phone',
                  h_email = '$email',
                  h_license = '$license',
                  h_address = '$address',
                  h_city = '$city',
                  h_password = '$password'
                  WHERE h_id = $hospital_id";
    
    if (mysqli_query($db_conn, $update_sql)) {
        $success = "Hospital profile updated successfully!";
        // Refresh hospital data
        $result = mysqli_query($db_conn, "SELECT * FROM hospital WHERE h_id = $hospital_id");
        $hospital = mysqli_fetch_assoc($result);
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
  <title>CovidCare • Hospital Profile</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../Hospital/css/header.css">
  <link rel="stylesheet" href="../Hospital/css/profile.css">
</head>
<body>
  <!-- Overlay for closing sidebar on mobile -->
  <div class="overlay" id="overlay"></div>

  <div class="layout">
<?php include './h_sidebar.php'; ?>

    <div class="main">
      <?php include './h_header.php'; ?>

      <div class="content">  
        <div class="profile-container">
          <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-hospital-user"></i>Hospital Profile</h2>
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
                <div class="profile-avatar"><?php echo substr($hospital['h_firstname'], 0, 1) . substr($hospital['h_lastname'], 0, 1); ?></div>
                <h3 class="profile-name"><?php echo $hospital['h_firstname'] . ' ' . $hospital['h_lastname']; ?></h3>
                <p class="profile-role">Hospital</p>
                <div class="profile-stats">
                  <div class="stat">
                    <div class="stat-value"><?php echo $hospital['h_status']; ?></div>
                    <div class="stat-label">Status</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="profile-details">
              <div class="details-grid">
                <div class="detail-card">
                  <div class="detail-header">
                    <div class="detail-icon">
                      <i class="fa-solid fa-building"></i>
                    </div>
                    <h3 class="detail-title">Hospital Information</h3>
                  </div>
                  <div class="detail-content">
                    <div class="detail-item">
                      <span class="detail-label">Hospital Name:</span>
                      <span class="detail-value"><?php echo $hospital['h_firstname'] . ' ' . $hospital['h_lastname']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Hospital ID:</span>
                      <span class="detail-value"><?php echo $hospital['h_id']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Email:</span>
                      <span class="detail-value"><?php echo $hospital['h_email']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Phone:</span>
                      <span class="detail-value"><?php echo $hospital['h_phone']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">License:</span>
                      <span class="detail-value"><?php echo $hospital['h_license']; ?></span>
                    </div>
                  </div>
                </div>
                
                <div class="detail-card">
                  <div class="detail-header">
                    <div class="detail-icon">
                      <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3 class="detail-title">Location Details</h3>
                  </div>
                  <div class="detail-content">
                    <div class="detail-item">
                      <span class="detail-label">Address:</span>
                      <span class="detail-value"><?php echo $hospital['h_address']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">City:</span>
                      <span class="detail-value"><?php echo $hospital['h_city']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Status:</span>
                      <span class="detail-value"><?php echo $hospital['h_status']; ?></span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Registered:</span>
                      <span class="detail-value"><?php echo date('d M Y', strtotime($hospital['created_at'])); ?></span>
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
        <h2 class="modal-title"><i class="fa-solid fa-hospital-user"></i> Edit Hospital Profile</h2>
        <button class="modal-close" id="modalClose">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      
      <form method="POST" action="">
        <div class="modal-body">
          <div class="modal-form" id="editProfileForm">
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-signature"></i> First Name</label>
              <input type="text" class="form-input" name="firstname" value="<?php echo $hospital['h_firstname']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-signature"></i> Last Name</label>
              <input type="text" class="form-input" name="lastname" value="<?php echo $hospital['h_lastname']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
              <input type="email" class="form-input" name="email" value="<?php echo $hospital['h_email']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-phone"></i> Phone Number</label>
              <input type="tel" class="form-input" name="phone" value="<?php echo $hospital['h_phone']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-id-card-clip"></i> License Number</label>
              <input type="text" class="form-input" name="license" value="<?php echo $hospital['h_license']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-location-dot"></i> Address</label>
              <input type="text" class="form-input" name="address" value="<?php echo $hospital['h_address']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-city"></i> City</label>
              <input type="text" class="form-input" name="city" value="<?php echo $hospital['h_city']; ?>" required>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-lock"></i> Password (leave blank to keep current)</label>
              <input type="password" class="form-input" name="password" placeholder="Enter new password">
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="modal-btn btn-cancel" id="cancelEdit">Cancel</button>
          <button type="submit" class="modal-btn btn-save" id="saveProfile">Save Changes</button>
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
    // Profile modal functionality
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    const modalClose = document.getElementById('modalClose');
    const cancelEdit = document.getElementById('cancelEdit');
    const saveProfile = document.getElementById('saveProfile');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');

    function openModal() {
      editProfileModal.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }

    function closeModal() {
      editProfileModal.classList.remove('active');
      document.body.style.overflow = ''; // Re-enable scrolling
      // Hide messages when modal is closed
      successMessage.style.display = 'none';
      errorMessage.style.display = 'none';
    }

    editProfileBtn.addEventListener('click', openModal);
    modalClose.addEventListener('click', closeModal);
    cancelEdit.addEventListener('click', closeModal);

    // Close modal when clicking outside
    editProfileModal.addEventListener('click', function(event) {
      if (event.target === editProfileModal) {
        closeModal();
      }
    });

    // Save profile functionality
    saveProfile.addEventListener('click', function() {
      // Get values from form
      const hospitalName = document.getElementById('hospitalName').value;
      const email = document.getElementById('email').value;
      const phone = document.getElementById('phone').value;
      const license = document.getElementById('license').value;
      const address = document.getElementById('address').value;
      const city = document.getElementById('city').value;
      const password = document.getElementById('password').value;
      
      // Simple validation
      if (!hospitalName || !email || !phone || !license || !address || !city || !password) {
        errorMessage.style.display = 'block';
        successMessage.style.display = 'none';
        return;
      }
      
      // Simulate API call with timeout
      setTimeout(function() {
        // For demo purposes, we'll assume the update is successful 80% of the time
        const isSuccess = Math.random() > 0.2;
        
        if (isSuccess) {
          successMessage.style.display = 'block';
          errorMessage.style.display = 'none';
          
          // Update the profile details with new values
          document.querySelector('.profile-name').textContent = hospitalName;
          document.querySelector('.profile-avatar').textContent = hospitalName.split(' ').map(word => word[0]).join('').substring(0, 2);
          
          // Update the detail cards
          const detailItems = document.querySelectorAll('.detail-value');
          detailItems[0].textContent = hospitalName;
          detailItems[3].textContent = email;
          detailItems[4].textContent = phone;
          detailItems[5].textContent = license;
          detailItems[6].textContent = address;
          detailItems[7].textContent = city;
          
          // Close modal after 2 seconds
          setTimeout(closeModal, 2000);
        } else {
          errorMessage.style.display = 'block';
          successMessage.style.display = 'none';
        }
      }, 1000);
    });

    // Add animation to profile container on load
    document.addEventListener('DOMContentLoaded', function() {
      const profileContainer = document.querySelector('.profile-container');
      profileContainer.style.opacity = 0;
      profileContainer.style.transform = 'translateY(20px)';
      profileContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      
      setTimeout(function() {
        profileContainer.style.opacity = 1;
        profileContainer.style.transform = 'translateY(0)';
      }, 100);
      
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
    });
  </script>
</body>
</html>