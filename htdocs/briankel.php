<?php

error_reporting(E_ALL);
ini_set("display_errors", '1');
ini_set("display_startup_errors", '1');

$mysqlUser = 'SPBC_DASHBOARD_so';
$mysqlPass = 'Spbc2021';
$mysqlDb = 'SPBC_DASHBOARD';
$hostName = 'maria4011-lb-fm-in.dbaas.intel.com';
$hostPort = '3307';

print "Connecting...<br><br>";

$conn = mysqli_init();
mysqli_ssl_set($conn, "./client_key.pem", "./client_cert.pem", "./ca_cert.pem", NULL, NULL);
mysqli_real_connect($conn, $hostName, $mysqlUser, $mysqlPass, $mysqlDb, $hostPort, MYSQLI_CLIENT_SSL);

print "show tables:<br><br>";

$res = mysqli_query($conn, 'show tables');
print_r(mysqli_fetch_row($res));

mysqli_close($conn);
?>
