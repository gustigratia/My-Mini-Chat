<?php
$conn = new mysqli("localhost", "root", "", "chatapp", port:3308);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
session_start();
?>