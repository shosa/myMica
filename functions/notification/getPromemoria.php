<?php
require '../../config/config.php';

$pdo = getDbInstance();
$query = $pdo->query("SELECT * FROM promemoria");
$result = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'promemoria' => $result]);
?>