<?php
$host = getenv('WRITE_DB_HOSTNAME') ?: getenv('READ_DB_HOSTNAME');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');
$port = (int)(getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_errno) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    exit(1);
}
$conn->set_charset('utf8mb4');
$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

$sql = file_get_contents(__DIR__ . '/001_subscription_tables.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    if ($conn->query($stmt) === false) {
        echo "FAIL: " . $conn->error . "\n  -> " . substr($stmt, 0, 80) . "\n";
    } else {
        echo "OK:   " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . "\n";
    }
}

$conn->close();
echo "\nDone.\n";
