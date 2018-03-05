<?php

// autoload
require_once 'vendor/autoload.php';

// Create connection
$con = new mysqli('localhost', 'root', 'root', $argv[1]);

// Check connection
if($con->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getProductNameById($id) {
  global $con;
  $id = intval($id);
  $res = $con->query("SELECT `post_title` FROM `wp_posts` WHERE `ID` = ".$id.";");
  if($res->num_rows > 0) {
    $o = $res->fetch_object();
    return $o->post_title;
  }
  return "";
}

function getProductNameBySlug($slug) {
  global $con;
  $slug = $con->real_escape_string($slug);
  $res = $con->query("SELECT `post_title` FROM `wp_posts` WHERE `post_name` = '".$slug."';");
  if($res->num_rows > 0) {
    $o = $res->fetch_object();
    return $o->post_title;
  }
  return "";
}

function getOrderIPAddress($id) {
  global $con;
  $id = intval($id);
  $res = $con->query("SELECT `meta_value` FROM `wp_postmeta` WHERE `post_id` =  ".$id." AND `meta_key` = '_customer_ip_address';");
  if($res->num_rows > 0) {
    $o = $res->fetch_object();
    return $o->meta_value;
  }
  return "";
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
  $html = str_ireplace( '%USER_IP%', getOrderIPAddress($o->order_id), $html );
}

// fetch downloads
$res = $con->query("SELECT * FROM `wp_license_wp_download_log` WHERE license_key='".$license."' ORDER BY `date_downloaded` ASC;");
if($res->num_rows > 0) {

  // we found at least 1 activation, load block
  $download_block = file_get_contents('html/downloads.html');
  $download_row = file_get_contents('html/download_row.html');
  $rows_html = "";

  while($o = $res->fetch_object()) {

    $dt = new \DateTime($o->date_downloaded);

    // create row
    $row_html = (string)$download_row;
    $row_html = str_ireplace( '%PRODUCT%', getProductNameById($o->api_product_id), $row_html );
    $row_html = str_ireplace( '%IP%', $o->user_ip_address, $row_html );
    $row_html = str_ireplace( '%DATE%', $dt->format('Y-m-d'), $row_html );

    // add row to full rows html
    $rows_html .= $row_html;

  }

  // set rows in block
  $download_block = str_ireplace( '%DOWNLOAD_ROWS%', $rows_html, $download_block );

  // set whole block in html
  $html = str_ireplace( '%DOWNLOADS%', $download_block, $html );

}else {
  // replace var with empty string if no activations found
  $html = str_ireplace( '%ACTIVATIONS%', "", $html );
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
    $row_html = str_ireplace( '%PRODUCT_ID%', getProductNameBySlug($o->api_product_id), $row_html );
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
