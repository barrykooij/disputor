<?php

// autoload
require_once 'vendor/autoload.php';

// Create connection
$con = new mysqli('localhost', 'root', 'root', $argv[1]);

// Check connection
if($con->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// vars
$license = $con->real_escape_string($argv[2]);

// load template
$html = file_get_contents('html/template.html');

// set license key in html
$html = str_ireplace( '%LICENSE_KEY%', $license, $html );

// fetch general license data
$res = $con->query("SELECT * FROM `wp_license_wp_licenses` WHERE license_key='".$license."' LIMIT 0,1;");
if($res->num_rows > 0) {
  $o = $res->fetch_object();

  $dt_created = new \DateTime($o->date_created);
  $dt_expires = new \DateTime($o->date_expires);

  $html = str_ireplace( '%ACTIVATION_EMAIL%', $o->activation_email, $html );
  $html = str_ireplace( '%DATE_CREATED%', $dt_created->format('Y-m-d'), $html );
  $html = str_ireplace( '%DATE_EXPIRES%', $dt_expires->format('Y-m-d'), $html );
}

// fetch activations
$res = $con->query("SELECT * FROM `wp_license_wp_activations` WHERE license_key='".$license."' ORDER BY `activation_date` ASC;");
if($res->num_rows > 0) {

  // we found at least 1 activation, load block
  $activation_block = file_get_contents('html/activations.html');
  $activation_row = file_get_contents('html/activation_row.html');
  $rows_html = "";

  while($o = $res->fetch_object()) {

    $dt = new \DateTime($o->activation_date);

    // create row
    $row_html = (string)$activation_row;
    $row_html = str_ireplace( '%PRODUCT_ID%', $o->api_product_id, $row_html );
    $row_html = str_ireplace( '%WEBSITE%', $o->instance, $row_html );
    $row_html = str_ireplace( '%DATE%', $dt->format('Y-m-d'), $row_html );

    // add row to full rows html
    $rows_html .= $row_html;

  }

  // set rows in block
  $activation_block = str_ireplace( '%ACTIVATION_ROWS%', $rows_html, $activation_block );

  // set whole block in html
  $html = str_ireplace( '%ACTIVATIONS%', $activation_block, $html );

}else {
  // replace var with empty string if no activations found
  $html = str_ireplace( '%ACTIVATIONS%', "", $html );
}

// create PDF object
$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);

// put html into pdf
$mpdf->WriteHTML($html);

// create pdf ifle
$mpdf->Output('output/dispute-'.$license.'.pdf', \Mpdf\Output\Destination::FILE);

// close db connection
$con->close();
