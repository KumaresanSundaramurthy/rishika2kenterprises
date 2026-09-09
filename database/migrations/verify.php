<?php
$h = getenv('WRITE_DB_HOSTNAME') ?: getenv('READ_DB_HOSTNAME');
$u = getenv('DB_USERNAME'); $p = getenv('DB_PASSWORD');
$port = (int)(getenv('DB_PORT') ?: 3306);
$c = new mysqli($h, $u, $p, '', $port);

$r = $c->query("SELECT * FROM Global.TimezoneTbl WHERE CountryCode='IND' OR Timezone LIKE '%Kolkata%' OR TimezoneUID=181 LIMIT 5");
while ($row = $r->fetch_assoc()) print_r($row);

$r2 = $c->query("SELECT COUNT(*) AS cnt FROM Global.TimezoneTbl");
echo "Total: " . $r2->fetch_assoc()['cnt'] . "\n";

// Sample 5 rows to understand format
$r3 = $c->query("SELECT TimezoneUID, CountryCode, CountryName, Timezone, GmtOffset FROM Global.TimezoneTbl LIMIT 5");
echo "\nSample rows:\n";
while ($row = $r3->fetch_assoc()) echo $row['TimezoneUID'] . ' | ' . $row['CountryCode'] . ' | ' . $row['Timezone'] . ' | ' . $row['GmtOffset'] . "\n";
$c->close();
