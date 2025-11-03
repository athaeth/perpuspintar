<?php
$db_host = '127.0.0.1'; 
$db_user = 'root';       
$db_pass = '';            
$db_name = 'projekperpustakaan';

// Membuat Koneksi (MySQLi Prosedural)
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Cek Koneksi
if (!$conn) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan error.
    die("Koneksi gagal: " . mysqli_connect_error());
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>