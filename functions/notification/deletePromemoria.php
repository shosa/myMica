<?php
require '../../config/config.php';

$id_promemoria = $_POST['id_promemoria'];

$pdo = getDbInstance();
$query = $pdo->prepare("DELETE FROM promemoria WHERE id_promemoria = :id_promemoria");
$query->bindParam(':id_promemoria', $id_promemoria);

if ($query->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>