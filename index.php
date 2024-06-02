<?php
/*
Plugin Name: Custom Form Plugin
Description: A custom form plugin that generates a PDF and sends form data to admin email.
Version: 1.0
Author: AbdurRehman
 */

// Include the Composer autoload file
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

// Enqueue scripts and styles
function custom_form_enqueue_scripts()
{
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'custom_form_enqueue_scripts');

// Shortcode to display the form
function custom_form_shortcode()
{
    ob_start();
    ?>
<form id="custom-form" method="post">
    <label for="phone">Phone (Required):</label>
    <input type="text" id="phone" name="phone" required><br><br>

    <label for="email">Email (Required):</label>
    <input type="email" id="email" name="email" required><br><br>

    <label for="address">Address (Required):</label>
    <input type="text" id="address" name="address" required><br><br>

    <label for="address2">Address Line 2:</label>
    <input type="text" id="address2" name="address2"><br><br>

    <label for="city">City:</label>
    <input type="text" id="city" name="city" required><br><br>

    <label for="state">State:</label>
    <input type="text" id="state" name="state" required><br><br>

    <label for="zip">ZIP Code:</label>
    <input type="text" id="zip" name="zip" required><br><br>

    <label for="dob">Date of Birth (Required):</label>
    <input type="date" id="dob" name="dob" required><br><br>

    <label for="party">Political Party Affiliation (Required):</label><br>
    <input type="radio" id="independent" name="party" value="Independent" required>
    <label for="independent">Independent</label><br>
    <input type="radio" id="republican" name="party" value="Republican">
    <label for="republican">Republican</label><br>
    <input type="radio" id="democrat" name="party" value="Democrat">
    <label for="democrat">Democrat</label><br>
    <input type="radio" id="unaffiliated" name="party" value="Unaffiliated">
    <label for="unaffiliated">Unaffiliated</label><br><br>

    <input type="submit" name="submit" value="Submit">
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        custom_form_handle_submission();
    }
    return ob_get_clean();
}
add_shortcode('custom_form', 'custom_form_shortcode');

// Handle form submission
function custom_form_handle_submission()
{
    if (!isset($_POST['submit'])) {
        return;
    }

    // Validate and sanitize form inputs
    $phone = sanitize_text_field($_POST['phone']);
    $email = sanitize_email($_POST['email']);
    $address = sanitize_text_field($_POST['address']);
    $address2 = sanitize_text_field($_POST['address2']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);
    $zip = sanitize_text_field($_POST['zip']);
    $dob = sanitize_text_field($_POST['dob']);
    $party = sanitize_text_field($_POST['party']);

    // Create PDF content
    $pdf_content = "
    <h1>Form Submission Details</h1>
    <p><strong>Phone:</strong> $phone</p>
    <p><strong>Email:</strong> $email</p>
    <p><strong>Address:</strong> $address</p>
    <p><strong>Address Line 2:</strong> $address2</p>
    <p><strong>City:</strong> $city</p>
    <p><strong>State:</strong> $state</p>
    <p><strong>ZIP Code:</strong> $zip</p>
    <p><strong>Date of Birth:</strong> $dob</p>
    <p><strong>Political Party Affiliation:</strong> $party</p>
    ";

    // Create and stream PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($pdf_content);
    $dompdf->render();
    $pdf_output = $dompdf->output();

    // Save PDF temporarily
    $upload_dir = wp_upload_dir();
    $pdf_file_path = $upload_dir['path'] . '/form-submission.pdf';
    file_put_contents($pdf_file_path, $pdf_output);

    // Generate CSV content
    $csv_content = [
        ['Phone', 'Email', 'Address', 'Address Line 2', 'City', 'State', 'ZIP Code', 'Date of Birth', 'Political Party Affiliation'],
        [$phone, $email, $address, $address2, $city, $state, $zip, $dob, $party],
    ];
    $csv_file_path = $upload_dir['path'] . '/form-submission.csv';
    $file = fopen($csv_file_path, 'w');
    foreach ($csv_content as $row) {
        fputcsv($file, $row);
    }
    fclose($file);

    // Send email with CSV attachment
    //$admin_email = get_option('admin_email');
    $admin_email = 'rehmanzilon@gmail.com';
    $subject = 'New Form Submission';
    $message = 'A new form submission has been received. Please find the attached CSV file for details.';
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $attachments = [$csv_file_path];

    wp_mail($admin_email, $subject, $message, $headers, $attachments);

    // Clear output buffer
    ob_end_clean();

    // Output the PDF to the browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="form-submission.pdf"');
    echo $pdf_output;
    exit;
}
?>