<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "resq_db"; // Replace with your actual database name if different

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database link failure."]));
}
?>