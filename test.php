<?php
ob_start();
$server   = getenv('DB_HOST');
$port     = (int) getenv('DB_PORT');
$user     = getenv('DB_USER');
$password = getenv('DB_PASS');
$db       = getenv('DB_NAME');

echo "Host: " . $server . "<br>";
echo "Port: " . $port . "<br>";
echo "User: " . $user . "<br>";
echo "DB: " . $db . "<br>";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
$connected = mysqli_real_connect($conn, $server, $user, $password, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if ($connected) {
    echo "<b style='color:green'>Database connected successfully!</b>";
} else {
    echo "<b style='color:red'>Connection failed: " . mysqli_connect_error() . "</b>";
}
?>