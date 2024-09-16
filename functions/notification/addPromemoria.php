<?php
require '../../config/config.php';

$titolo = $_POST['titolo'];
$nota = $_POST['nota'];

$pdo = getDbInstance();
$query = $pdo->prepare("INSERT INTO promemoria (titolo, nota) VALUES (:titolo, :nota)");
$query->bindParam(':titolo', $titolo);
$query->bindParam(':nota', $nota);

if ($query->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>