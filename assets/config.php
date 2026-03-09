<?php
ob_start();
    $server   = getenv('DB_HOST');
    $port     = (int) getenv('DB_PORT');
    $user     = getenv('DB_USER');
    $password = getenv('DB_PASS');
    $db       = getenv('DB_NAME');

    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
    $conn = mysqli_connect($server, $user, $password, $db, $port);

    if (!$conn) {
        header('Location: ../errors/error.html');
        exit();
    }