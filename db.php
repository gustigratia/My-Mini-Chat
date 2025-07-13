<?php
$conn = new mysqli("localhost", "root", "", "minichat", port:3308);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
session_start();
?>