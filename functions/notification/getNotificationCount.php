<?php
require '../../config/config.php';

$pdo = getDbInstance();
$query = $pdo->prepare("SELECT COUNT(*) AS count FROM promemoria");
$query->execute();

$result = $query->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'count' => $result['count']]);
?>