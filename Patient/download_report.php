<?php
include '../auth.php';
checkRole( 'patient' );
include '../db_conn.php';

if ( !isset( $_SESSION[ 'patient_id' ] ) ) {
    header( 'Location: ../login_register.php' );
    exit();
}

$patient_id = $_SESSION[ 'patient_id' ];

// Handle PDF generation
if ( isset( $_GET[ 'download' ] ) && isset( $_GET[ 'id' ] ) ) {
    $report_id = intval( $_GET[ 'id' ] );
    $pdf_query = "SELECT a.*, 
                         CONCAT(h.h_firstname, ' ', h.h_lastname) AS hospital_name,
                         h.h_address, h.h_city, h.h_phone,
                         v.name AS vaccine_name,
                         CONCAT(p.p_firstname, ' ', p.p_lastname) AS patient_name,
                         p.p_dob, p.p_gender, p.p_phone, p.p_address
                  FROM appointments a
                  LEFT JOIN hospital h ON a.hospital_id = h.h_id
                  LEFT JOIN vaccines v ON a.vaccine_id = v.id
                  LEFT JOIN patient p ON a.patient_id = p.p_id
                  WHERE a.id = $report_id AND a.patient_id = $patient_id";

    $pdf_result = $db_conn->query( $pdf_query );

    if ( $pdf_result && $pdf_result->num_rows > 0 ) {
        $report_data = $pdf_result->fetch_assoc();

        // Generate PDF content
        require_once( '../tcpdf/tcpdf.php' );

        $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
        $pdf->SetCreator( 'CovidCare System' );
        $pdf->SetAuthor( 'CovidCare' );
        $pdf->SetTitle( 'Medical Report - ' . $report_data[ 'patient_name' ] );
        $pdf->SetHeaderData( '', 0, 'COVIDCARE MEDICAL REPORT', '' );

        $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
        $pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );
        $pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );
        $pdf->SetMargins( 15, 25, 15 );
        $pdf->SetHeaderMargin( 10 );
        $pdf->SetFooterMargin( 10 );
        $pdf->SetAutoPageBreak( TRUE, 25 );
        $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );
        $pdf->SetFont( 'helvetica', '', 10 );

        $pdf->AddPage();

        // PDF content
        $html = '
        <style>
            .header { text-align: center; margin-bottom: 20px; }
            .section { margin-bottom: 15px; }
            .label { font-weight: bold; color: #333; }
            .value { margin-bottom: 5px; }
            .result { font-size: 16px; font-weight: bold; padding: 10px; border-radius: 5px; text-align: center; margin: 20px 0; }
            .positive { background-color: #ffebee; color: #c62828; border: 2px solid #c62828; }
            .negative { background-color: #e8f5e8; color: #2e7d32; border: 2px solid #2e7d32; }
            .vaccinated { background-color: #e3f2fd; color: #1565c0; border: 2px solid #1565c0; }
            .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9px; color: #666; }
        </style>
        
        <div class="header">
            <h1>COVIDCARE MEDICAL REPORT</h1>
            <p>Official Medical Test Report</p>
        </div>
        
        <div class="section">
            <h2>Patient Information</h2>
            <p><span class="label">Name:</span> <span class="value">' . $report_data[ 'patient_name' ] . '</span></p>
            <p><span class="label">Date of Birth:</span> <span class="value">' . date( 'M d, Y', strtotime( $report_data[ 'p_dob' ] ) ) . '</span></p>
            <p><span class="label">Gender:</span> <span class="value">' . ucfirst( $report_data[ 'p_gender' ] ) . '</span></p>
            <p><span class="label">Phone:</span> <span class="value">' . $report_data[ 'p_phone' ] . '</span></p>
        </div>
        
        <div class="section">
            <h2>Test Details</h2>
            <p><span class="label">Hospital:</span> <span class="value">' . $report_data[ 'hospital_name' ] . '</span></p>
            <p><span class="label">Test Type:</span> <span class="value">' . ( $report_data[ 'test_type' ] === 'covid_test' ? 'COVID-19 Test' : 'Vaccination' ) . '</span></p>';

        if ( $report_data[ 'test_type' ] === 'vaccination' ) {
            $html .= '<p><span class="label">Vaccine:</span> <span class="value">' . $report_data[ 'vaccine_name' ] . '</span></p>';
        }

        $html .= '
            <p><span class="label">Test Date:</span> <span class="value">' . date( 'M d, Y', strtotime( $report_data[ 'scheduled_date' ] ) ) . '</span></p>
            <p><span class="label">Test Time:</span> <span class="value">' . date( 'h:i A', strtotime( $report_data[ 'scheduled_time' ] ) ) . '</span></p>
        </div>
        
        <div class="section">
            <h2>Test Result</h2>';

        $result_class = '';
        $result_text = '';

        switch ( $report_data[ 'result_status' ] ) {
            case 'positive':
            $result_class = 'positive';
            $result_text = 'POSITIVE - COVID-19 Detected';
            break;
            case 'negative':
            $result_class = 'negative';
            $result_text = 'NEGATIVE - COVID-19 Not Detected';
            break;
            case 'vaccinated':
            $result_class = 'vaccinated';
            $result_text = 'VACCINATED - Successfully Administered';
            break;
        }

        $html .= '<div class="result ' . $result_class . '">' . $result_text . '</div>';

        $html .= '
        </div>
        
        <div class="footer">
            <p>Report generated on: ' . date( 'M d, Y h:i A' ) . '</p>
            <p>This is an official medical report from CovidCare System. Please keep this document safe.</p>
            <p>Hospital Contact: ' . $report_data[ 'h_phone' ] . ' | ' . $report_data[ 'h_address' ] . ', ' . $report_data[ 'h_city' ] . '</p>
        </div>';

        $pdf->writeHTML( $html, true, false, true, false, '' );

        $filename = 'medical_report_' . $report_data[ 'patient_name' ] . '_' . date( 'Ymd_His' ) . '.pdf';
        $pdf->Output( $filename, 'D' );
        exit();
    }
}
?>