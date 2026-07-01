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

// Fetch hospitals from database
$hospitals = [];
$hospital_query = "SELECT h_id, CONCAT(h_firstname, ' ', h_lastname) AS hospital_name 
                   FROM hospital 
                   WHERE h_status = 'approved' 
                   ORDER BY hospital_name";
$hospital_result = $db_conn->query( $hospital_query );
if ( $hospital_result && $hospital_result->num_rows > 0 ) {
    while ( $row = $hospital_result->fetch_assoc() ) {
        $hospitals[] = $row;
    }
}

// Fetch vaccines from database
$vaccines = [];
$vaccine_query = "SELECT id, name FROM vaccines WHERE status = 'available' ORDER BY name";
$vaccine_result = $db_conn->query( $vaccine_query );
if ( $vaccine_result && $vaccine_result->num_rows > 0 ) {
    while ( $row = $vaccine_result->fetch_assoc() ) {
        $vaccines[] = $row;
    }
}

// Handle form submission
$message = '';
$message_type = '';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $hospital_id = $_POST[ 'hospital_id' ] ?? '';
    $test_type = $_POST[ 'test_type' ] ?? '';
    $vaccine_id = $_POST[ 'vaccine_id' ] ?? '';

    // Validate inputs
    if ( empty( $hospital_id ) || empty( $test_type ) || ( $test_type === 'vaccination' && empty( $vaccine_id ) ) ) {
        $message = 'Please fill all required fields';
        $message_type = 'error';
    } else {
        // Insert appointment into database
        $stmt = $db_conn->prepare( "INSERT INTO appointments (patient_id, hospital_id, test_type, vaccine_id, status) 
                               VALUES (?, ?, ?, ?, 'pending')" );

        if ( $test_type === 'covid_test' ) {
            $vaccine_id = NULL;
        }

        $stmt->bind_param( 'iisi', $patient_id, $hospital_id, $test_type, $vaccine_id );

        if ( $stmt->execute() ) {
            $message = 'Appointment request sent successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error sending appointment request. Please try again.';
            $message_type = 'error';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang = 'en'>
<head>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1' />
<title>CovidCare • Book Appointment</title>
<link href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css' rel = 'stylesheet'/>
<link rel="stylesheet" href="../Patient/css/header.css">
<link rel="stylesheet" href="../Patient/css/book_appointment.css">
</head>
 <body>
<div class = 'overlay' id = 'overlay'></div>

                                            <div class = 'layout'>
                                            <?php include './p_sidebar.php';
                                            ?>
                                            <div class = 'main'>
                                            <?php include './p_header.php';
                                            ?>

                                            <!-- Main Section -->
                                            <div class = 'content'>
                                            <div class = 'appointment-container'>

                                            <form class = 'appointment-form' method = 'POST' action = ''>
                                            <h2 class = 'form-heading'><i class = 'fa-solid fa-calendar-check'></i> Book Appointment</h2>

                                            <div class = 'form-grid'>
                                            <div class = 'form-row'>
                                            <div class = 'form-group'>
                                            <label class = 'form-label'>Patient Name:</label>
                                            <input type = 'text' class = 'form-input' value = '<?php echo $firstname . " " . $lastname; ?>' readonly>
                                            </div>

                                            <div class = 'form-group'>
                                            <label class = 'form-label'>Hospital Name:</label>
                                            <select style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"class = 'form-select' id = 'hospitalSelect' name = 'hospital_id' required>
                                            <option value = ''>Select a hospital</option>
                                            <?php foreach ( $hospitals as $hospital ): ?>
                                            <option value = '<?php echo $hospital['h_id']; ?>'><?php echo $hospital[ 'hospital_name' ];
                                            ?></option>
                                            <?php endforeach;
                                            ?>
                                            </select>
                                            </div>
                                            </div>

                                            <div class = 'form-row'>
                                            <div class = 'form-group'>
                                            <label class = 'form-label'>Test Type:</label>
                                            <select style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"class = 'form-select' id = 'testTypeSelect' name = 'test_type' required>
                                            <option value = ''>Select test type</option>
                                            <option value = 'covid_test'>COVID Test</option>
                                            <option value = 'vaccination'>Vaccination</option>
                                            </select>
                                            </div>

                                            <div class = 'form-group' id = 'vaccineGroup'>
                                            <label class = 'form-label'><i class = 'fa-solid fa-syringe'></i> Vaccine Name:</label>
                                            <select style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"class = 'form-select' id = 'vaccineSelect' name = 'vaccine_id'>
                                            <option value = ''>Select vaccine</option>
                                            <?php foreach ( $vaccines as $vaccine ): ?>
                                            <option value = '<?php echo $vaccine['id']; ?>'><?php echo $vaccine[ 'name' ];
                                            ?></option>
                                            <?php endforeach;
                                            ?>
                                            </select>
                                            </div>
                                            </div>
                                            </div>

                                            <button type = 'submit' class = 'submit-btn'>Book Now</button>

                                            <div class = 'message-container' id = 'messageContainer'>
                                            <?php if ( !empty( $message ) ): ?>
                                            <div class = 'message <?php echo $message_type; ?>'>
                                            <i class = 'fa-solid fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>'></i>
                                            <?php echo $message;
                                            ?>
                                            </div>
                                            <?php endif;
                                            ?>
                                            </div>
                                            </form>
                                            </div>
                                            </div>
                                            </div>
                                            </div>

                                            <script>
                                            // Function to apply theme based on saved preference

                                            function applyTheme() {
                                                const savedTheme = localStorage.getItem( 'theme' );
                                                const icon = document.getElementById( 'themeIcon' );

                                                if ( savedTheme === 'dark' ) {
                                                    document.body.classList.add( 'dark' );
                                                    icon.classList.remove( 'fa-moon' );
                                                    icon.classList.add( 'fa-sun' );
                                                } else {
                                                    document.body.classList.remove( 'dark' );
                                                    icon.classList.remove( 'fa-sun' );
                                                    icon.classList.add( 'fa-moon' );
                                                }
                                            }

                                            // Call this on page load
                                            applyTheme();

                                            // Toggle theme on button click
                                            document.getElementById( 'themeToggle' ).addEventListener( 'click', function() {
                                                document.body.classList.toggle( 'dark' );
                                                const icon = document.getElementById( 'themeIcon' );

                                                if ( document.body.classList.contains( 'dark' ) ) {
                                                    icon.classList.remove( 'fa-moon' );
                                                    icon.classList.add( 'fa-sun' );
                                                    localStorage.setItem( 'theme', 'dark' );
                                                } else {
                                                    icon.classList.remove( 'fa-sun' );
                                                    icon.classList.add( 'fa-moon' );
                                                    localStorage.setItem( 'theme', 'light' );
                                                }
                                            }
                                        );

                                        // Toggle profile dropdown
                                        document.getElementById( 'profileBtn' )?.addEventListener( 'click', function() {
                                            const dropdown = document.getElementById( 'profileDropdown' );
                                            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                                        }
                                    );

                                    // Close dropdown when clicking outside
                                    document.addEventListener( 'click', function( event ) {
                                        if ( !event.target.closest( '#profileBtn' ) ) {
                                            const dropdown = document.getElementById( 'profileDropdown' );
                                            if ( dropdown ) dropdown.style.display = 'none';
                                        }
                                    }
                                );

                                // Mobile sidebar functionality
                                const sidebar = document.getElementById( 'sidebar' );
                                const overlay = document.getElementById( 'overlay' );
                                const menuToggle = document.getElementById( 'menuToggle' );
                                const sidebarClose = document.getElementById( 'sidebarClose' );

                                menuToggle?.addEventListener( 'click', function() {
                                    sidebar.classList.add( 'open' );
                                    overlay.classList.add( 'active' );
                                }
                            );

                            sidebarClose?.addEventListener( 'click', function() {
                                sidebar.classList.remove( 'open' );
                                overlay.classList.remove( 'active' );
                            }
                        );

                        overlay?.addEventListener( 'click', function() {
                            sidebar.classList.remove( 'open' );
                            overlay.classList.remove( 'active' );
                        }
                    );

                    // Show/hide vaccine selection based on test type
                    const testTypeSelect = document.getElementById( 'testTypeSelect' );
                    const vaccineGroup = document.getElementById( 'vaccineGroup' );
                    const vaccineSelect = document.getElementById( 'vaccineSelect' );

                    // Initially hide vaccine selection
                    vaccineGroup.style.display = 'none';
                    vaccineSelect.removeAttribute( 'required' );

                    testTypeSelect.addEventListener( 'change', function() {
                        if ( this.value === 'vaccination' ) {
                            vaccineGroup.style.display = 'flex';
                            vaccineSelect.setAttribute( 'required', 'required' );
                        } else {
                            vaccineGroup.style.display = 'none';
                            vaccineSelect.removeAttribute( 'required' );
                            vaccineSelect.value = '';
                        }
                    }
                );

                // Add animation to form container on load
                document.addEventListener( 'DOMContentLoaded', function() {
                    const appointmentContainer = document.querySelector( '.appointment-container' );
                    if ( appointmentContainer ) {
                        appointmentContainer.style.opacity = 0;
                        appointmentContainer.style.transform = 'scale(0.95)';
                        appointmentContainer.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

                        setTimeout( function() {
                            appointmentContainer.style.opacity = 1;
                            appointmentContainer.style.transform = 'scale(1)';
                        }
                        , 100 );
                    }

                    // Auto-hide messages after 5 seconds
                    setTimeout( () => {
                        const messages = document.querySelectorAll( '.message' );
                        messages.forEach( message => {
                            message.style.opacity = '0';
                            message.style.transition = 'opacity 0.5s ease';
                            setTimeout( () => {
                                message.remove();
                            }
                            , 500 );
                        }
                    );
                }
                , 5000 );
            }
        );
        </script>
        </body>
        </html>