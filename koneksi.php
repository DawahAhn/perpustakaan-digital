<?php
$host     = "sql312.infinityfree.com";
$user     = "if0_41808303";
$password = "jvY3rVavKG2kU";
$database = "if0_41808303_perpustakaan_digital";
$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>