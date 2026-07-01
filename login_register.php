<?php
session_start();
include 'db_conn.php';

// Initialize variables
$login_error = '';
$hospital_success = '';
$patient_success = '';

// ------------------ LOGIN PROCESSING ------------------
if ( isset( $_POST[ 'login_submit' ] ) ) {
    $email = strtolower( trim( $_POST[ 'email' ] ) );
    $password = $_POST[ 'password' ];

    // First check if it's admin
    $admin_query = mysqli_query($db_conn, "SELECT * FROM admin WHERE admin_email='$email' AND admin_password='$password'");
    
    if (mysqli_num_rows($admin_query) > 0) {
        // Admin login successful
        $_SESSION['admin_id'] = mysqli_fetch_assoc($admin_query)['admin_id'];
        $_SESSION['user_type'] = 'admin';
        header("Location: Admin/dashboard.php");
        exit();
    } else {
        // Check if it's a patient
    $patient_query = mysqli_query( $db_conn, "SELECT * FROM patient WHERE p_email='$email' AND p_password='$password'" );

    if ( mysqli_num_rows( $patient_query ) > 0 ) {
        $patient = mysqli_fetch_assoc( $patient_query );
        $_SESSION[ 'patient_id' ] = $patient[ 'p_id' ];
        $_SESSION[ 'patient_firstname' ] = $patient[ 'p_firstname' ];
        $_SESSION[ 'patient_lastname' ]  = $patient[ 'p_lastname' ];
        $_SESSION[ 'user_type' ] = 'patient';
        header( 'Location: Patient/dashboard.php' );
        exit();
    } else {
        // Check if it's an approved hospital
            $hospital_query = mysqli_query($db_conn, "SELECT * FROM hospital WHERE h_email='$email' AND h_password='$password' AND h_status='approved'");
            
            if (mysqli_num_rows($hospital_query) > 0) {
                $hospital = mysqli_fetch_assoc($hospital_query);
                $_SESSION['hospital_id'] = $hospital['h_id'];
                $_SESSION['user_type'] = 'hospital';
                header("Location: Hospital/dashboard.php");
                exit();
            } else {
                // Check if hospital exists but not approved
                $hospital_check = mysqli_query($db_conn, "SELECT * FROM hospital WHERE h_email='$email'");
                
                if (mysqli_num_rows($hospital_check) > 0) {
                    $hospital = mysqli_fetch_assoc($hospital_check);
                    if ($hospital['h_status'] == 'pending') {
                        $login_error = "Your hospital account is pending approval. Please wait for admin approval.";
                    } else if ($hospital['h_status'] == 'rejected') {
                        $login_error = "Your hospital account was rejected. Please register again.";
                    }
                } else {
                    $login_error = "Invalid email or password!";
                }
            }
        }
    }
}

// ------------------ HOSPITAL REGISTER ------------------
if (isset($_POST['hospital_submit'])) {
    // Collect form data
    $h_firstname = trim($_POST['h_firstName']);
    $h_lastname = trim($_POST['h_lastName']);
    $h_phone = trim($_POST['h_phone']);
    $h_license = trim($_POST['h_license']);
    $h_address = trim($_POST['h_address']);
    $h_city = trim($_POST['h_city']);
    $h_email = strtolower(trim($_POST['h_email']));
    $h_password = $_POST['h_password'];
    $h_cnfrmpass = $_POST['h_confirmPassword'];

    // Validate password match
    if ($h_password !== $h_cnfrmpass) {
        echo "<script>
            document.getElementById('h-confirm-password-error').style.display='block';
            document.getElementById('h-confirm-password-error').innerText='Passwords do not match';
            document.getElementById('hospital-success').style.display='none';
            
            // Switch to hospital tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('hospital-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'hospital\' ]').classList.add('active');
        </script>";
        exit;
    }

    // Check for duplicate email
    $check = mysqli_query($db_conn, "SELECT * FROM hospital WHERE h_email='$h_email'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>
            alert('Email already registered! Please use another email.');
            
            // Switch to hospital tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('hospital-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'hospital\' ]').classList.add('active');
        </script>";
        exit;
    }

    // Insert query with pending status
    $h_insert = "INSERT INTO `hospital`(`h_firstname`,`h_lastname`,`h_phone`,`h_license`,`h_address`,`h_city`,`h_email`,`h_password`,`h_status`)
                 VALUES ('$h_firstname','$h_lastname','$h_phone','$h_license','$h_address','$h_city','$h_email','$h_password','pending')";
    $h_result = mysqli_query($db_conn, $h_insert);

    if ($h_result) {
        echo "<script>
            document.getElementById('hospital-success').style.display='block';
            document.getElementById('hospital-form').reset();

            // Switch to hospital tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('hospital-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'hospital\' ]').classList.add('active');

            // Hide success message after 3 seconds
            setTimeout(function(){
                document.getElementById('hospital-success').style.display='none';
            }, 3000);
        </script>";
    } else {
        $error_msg = 'Error: ' . mysqli_error($db_conn);
        echo "<script>
            alert('Registration failed: $error_msg');
            
            // Switch to hospital tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('hospital-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'hospital\' ]').classList.add('active');
        </script>";
    }
}

// ------------------ PATIENT REGISTER ------------------
if (isset($_POST['patient_submit'])) {
    // Collect form data
    $p_firstname = trim($_POST['p_firstName']);
    $p_lastname = trim($_POST['p_lastName']);
    $p_phone = trim($_POST['p_phone']);
    $p_dob = $_POST['p_dob'];
    $p_gender = $_POST['p_gender'];
    $p_address = trim($_POST['p_address']);
    $p_city = trim($_POST['p_city']);
    $p_email = strtolower(trim($_POST['p_email']));
    $p_password = $_POST['p_password'];
    $p_cnfrmpass = $_POST['p_confirmPassword'];

    // Validate password match
    if ($p_password !== $p_cnfrmpass) {
        echo "<script>
            document.getElementById('p-confirm-password-error').style.display='block';
            document.getElementById('p-confirm-password-error').innerText='Passwords do not match';
            document.getElementById('patient-success').style.display='none';
            
            // Switch to patient tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('patient-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'patient\' ]').classList.add('active');
        </script>";
        exit;
    }

    // Check for duplicate email
    $check = mysqli_query($db_conn, "SELECT * FROM patient WHERE p_email='$p_email'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>
            alert('Email already registered! Please use another email.');
            
            // Switch to patient tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('patient-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'patient\' ]').classList.add('active');
        </script>";
        exit;
    }

    // Insert query
    $p_insert = "INSERT INTO `patient`(`p_firstname`,`p_lastname`,`p_email`,`p_password`,`p_phone`,`p_dob`,`p_gender`,`p_address`,`p_city`)
                 VALUES ('$p_firstname','$p_lastname','$p_email','$p_password','$p_phone','$p_dob','$p_gender','$p_address','$p_city')";
    $p_result = mysqli_query($db_conn, $p_insert);

    if ($p_result) {
        echo "<script>
            document.getElementById('patient-success').style.display='block';
            document.getElementById('patient-form').reset();

            // Switch to patient tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('patient-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'patient\' ]').classList.add('active');

            // Hide success message after 3 seconds
            setTimeout(function(){
                document.getElementById('patient-success').style.display='none';
            }, 3000);
        </script>";
    } else {
        $error_msg = 'Error: ' . mysqli_error($db_conn);
        echo "<script>
            alert('Registration failed: $error_msg');
            
            // Switch to patient tab
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById('patient-form').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[ data-tab = \'patient\' ]').classList.add('active');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang='en'>

<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width = device-width, initial-scale = 1.0'>
<title>CovidCare - Login | Register</title>
<!-- Font Awesome CDN -->
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
<link rel='stylesheet' href='./css/login_register.css'>
</head>

<body>
<div class='medical-icons'>
<div class='icon icon-1'>
<i class='fas fa-virus-covid'></i>
</div>
<div class='icon icon-2'>
<i class='fas fa-virus-covid'></i>
</div>
<div class='icon icon-3'>
<i class='fas fa-virus-covid'></i>
</div>
<div class='icon icon-4'>
<i class='fas fa-virus-covid'></i>
</div>
<div class='icon icon-5'>
<i class='fas fa-virus-covid'></i>
</div>
<div class='icon icon-6'>
<i class='fas fa-virus-covid'></i>
</div>
</div>
<div class='auth-container'>
<div class='auth-card'>
<div class='logo-section'>
<div class='logo'>
<div class='logo-icon'>CC</div>
<h1>CovidCare</h1>
</div>
<p>Covid Test & Vaccination System</p>
</div>
<div class='auth-tabs'>
<button class='tab-btn active' data-tab='login'>Login</button>
<button class='tab-btn' data-tab='hospital'>Hospital Register</button>
<button class='tab-btn' data-tab='patient'>Patient Register</button>
</div>

<!-- Login Form -->
<form method='post' action='' class='auth-form active' id='login-form'>
<div class='header-container'>
<h2>Welcome Back!</h2>
<p>Please login to access your dashboard.</p>
</div>
<?php if (!empty($login_error)): ?>
<div class='error-message' style='display:block;
        margin-bottom:15px;
        '><?php echo $login_error; ?></div>
<?php endif; ?>
<div class='form-group'>
<label for='login-email' class='required'>Email Address</label>
<input
type='email'
id='login-email'
name='email'
required>
<div class='error-message' id='login-email-error'>Please enter a valid email address</div>
</div>
<div class='form-group'>
<label for='login-password' class='required'>Password</label>
<div class='password-container'>
<input
type='password'
id='login-password'
name='password'
required>
<button type='button' class='toggle-password' id='toggle-login-password'>
<i class='fas fa-eye'></i>
</button>
</div>
<div class='error-message' id='login-password-error'>Password must be at least 6 characters</div>
</div>
<button type='submit' class='auth-btn' name='login_submit'>Login</button>
<div class='forgot-password'>
<a href='#'>Forgot your password?</a>
</div>
</form>

<!-- Hospital Register Form -->
<form method='post' action='' class='auth-form' id='hospital-form'>
<div class='header-container'>
<h2>Hospital Registration</h2>
<p>Register your hospital with CovidCare</p>
</div>
<div class='form-content'>
<div class='form-row'>
<div class='form-group'>
<label for='h-first-name' class='required'>First Name</label>
<input type='text' id='h-first-name' name='h_firstName' required>
<div class='error-message' id='h-first-name-error'>First name is required</div>
</div>
<div class='form-group'>
<label for='h-last-name' class='required'>Last Name</label>
<input type='text' id='h-last-name' name='h_lastName' required>
<div class='error-message' id='h-last-name-error'>Last name is required</div>
</div>
</div>
<div class='form-group'>
<label for='h-phone' class='required'>Phone Number</label>
<input type='tel' id='h-phone' name='h_phone' required>
<div class='error-message' id='h-phone-error'>Please enter a valid 11-digit phone number</div>
</div>
<div class='form-group'>
<label for='h-license' class='required'>License Number</label>
<input type='text' id='h-license' name='h_license' required>
<div class='error-message' id='h-license-error'>License number is required</div>
</div>
<div class='form-group'>
<label for='h-address' class='required'>Address</label>
<textarea id='h-address' name='h_address' rows='2' required></textarea>
<div class='error-message' id='h-address-error'>Address is required</div>
</div>
<div class='form-group'>
<label for='h-city' class='required'>City</label>
<input type='text' id='h-city' name='h_city' required>
<div class='error-message' id='h-city-error'>City is required</div>
</div>
<div class='form-group'>
<label for='h-email' class='required'>Email Address</label>
<input type='email' id='h-email' name='h_email' required>
<div class='error-message' id='h-email-error'>Please enter a valid email address</div>
</div>
<div class='form-group'>
<label for='h-password' class='required'>Password</label>
<div class='password-container'>
<input type='password' id='h-password' name='h_password' required>
<button type='button' class='toggle-password' id='toggle-h-password'>
<i class='fas fa-eye'></i>
</button>
</div>
<div class='error-message' id='h-password-error'>Password must be at least 8 characters</div>
</div>
<div class='form-group'>
<label for='h-confirm-password' class='required'>Confirm Password</label>
<div class='password-container'>
<input type='password' id='h-confirm-password' name='h_confirmPassword' required>
<button type='button' class='toggle-password' id='toggle-h-confirm-password'>
<i class='fas fa-eye'></i>
</button>
</div>
<div class='error-message' id='h-confirm-password-error'>Passwords do not match</div>
</div>
</div>
<div class='form-footer'>
<div class='success-message' id='hospital-success' style='display:none;
        '>Hospital account created successfully! Waiting for admin approval.</div>
<button type='submit' name='hospital_submit' class='auth-btn'>Create Hospital Account</button>
</div>
</form>

<!-- Patient Register Form -->
<form method='post' action='' class='auth-form' id='patient-form'>
<div class='header-container'>
<h2>Patient Registration</h2>
<p>Create your patient account with CovidCare</p>
</div>
<div class='form-content'>
<div class='form-row'>
<div class='form-group'>
<label for='p-first-name' class='required'>First Name</label>
<input type='text' id='p-first-name' name='p_firstName' required>
<div class='error-message' id='p-first-name-error'>First name is required</div>
</div>
<div class='form-group'>
<label for='p-last-name' class='required'>Last Name</label>
<input type='text' id='p-last-name' name='p_lastName' required>
<div class='error-message' id='p-last-name-error'>Last name is required</div>
</div>
</div>
<div class='form-group'>
<label for='p-phone' class='required'>Phone Number</label>
<input type='tel' id='p-phone' name='p_phone' required>
<div class='error-message' id='p-phone-error'>Please enter a valid 11-digit phone number</div>
</div>
<div class='form-group'>
<label for='p-dob' class='required'>Date of Birth</label>
<input type='date' id='p-dob' name='p_dob' required>
<div class='error-message' id='p-dob-error'>Please enter a valid date of birth</div>
</div>
<div class='form-group'>
<label class='required'>Gender</label>
<div class='gender-options'>
<div class='gender-option'>
<input type='radio' id='p-gender-male' name='p_gender' value='male' required>
<label for='p-gender-male' style='display: inline;
        '>Male</label>
</div>
<div class='gender-option'>
<input type='radio' id='p-gender-female' name='p_gender' value='female'>
<label for='p-gender-female' style='display: inline;
        '>Female</label>
</div>
</div>
<div class='error-message' id='p-gender-error'>Please select your gender</div>
</div>
<div class='form-group'>
<label for='p-address' class='required'>Address</label>
<textarea id='p-address' name='p_address' rows='2' required></textarea>
<div class='error-message' id='p-address-error'>Address is required</div>
</div>
<div class='form-group'>
<label for='p-city' class='required'>City</label>
<input type='text' id='p-city' name='p_city' required>
<div class='error-message' id='p-city-error'>City is required</div>
</div>
<div class='form-group'>
<label for='p-email' class='required'>Email Address</label>
<input type='email' id='p-email' name='p_email' required>
<div class='error-message' id='p-email-error'>Please enter a valid email address</div>
</div>
<div class='form-group'>
<label for='p-password' class='required'>Password</label>
<div class='password-container'>
<input type='password' id='p-password' name='p_password' required>
<button type='button' class='toggle-password' id='toggle-p-password'>
<i class='fas fa-eye'></i>
</button>
</div>
<div class='error-message' id='p-password-error'>Password must be at least 8 characters</div>
</div>
<div class='form-group'>
<label for='p-confirm-password' class='required'>Confirm Password</label>
<div class='password-container'>
<input type='password' id='p-confirm-password' name='p_confirmPassword' required>
<button type='button' class='toggle-password' id='toggle-p-confirm-password'>
<i class='fas fa-eye'></i>
</button>
</div>
<div class='error-message' id='p-confirm-password-error'>Passwords do not match</div>
</div>
</div>
<div class='form-footer'>
<div class='success-message' id='patient-success' style='display:none;
        '>Patient account created successfully! You can now login.</div>
<button type='submit' name='patient_submit' class='auth-btn'>Create Patient Account</button>
</div>
</form>
</div>
</div>

<script src='./js/login_register.js'></script>
        </body>
        </html>