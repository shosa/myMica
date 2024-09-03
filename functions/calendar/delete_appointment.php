<?php
include("../../config/config.php");
header('Content-Type: application/json');

$pdo = getDbInstance();
$id_appuntamento = $_GET['id_appuntamento'] ?? null;

if (!$id_appuntamento) {
    echo json_encode(['success' => false, 'message' => 'ID appuntamento mancante.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id_appuntamento = ?");
    $stmt->execute([$id_appuntamento]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
        $_SESSION["danger"] = "Appuntamento Eliminato!";
    } else {
        echo json_encode(['success' => false, 'message' => 'Appuntamento non trovato.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Errore nella cancellazione: ' . $e->getMessage()]);
}
?>