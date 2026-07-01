<?php
session_start();

function checkRole( $role ) {
    if ( !isset( $_SESSION[ 'user_type' ] ) || $_SESSION[ 'user_type' ] != $role ) {
        header( 'Location: ../login_register.php' );
        exit();
    }
}
?>