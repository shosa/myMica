<?php
require_once '../../config/config.php';

$pdo = getDbInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leggi i dati inviati
    $data = $_POST;

    if (isset($data['id_annotazione'])) {
        $id_annotazione = intval($data['id_annotazione']);

        try {
            $stmt = $pdo->prepare("DELETE FROM annotazioni WHERE id_annotazione = :id_annotazione");
            $stmt->bindParam(':id_annotazione', $id_annotazione, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Annotazione cancellata con successo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Errore nella cancellazione dell\'annotazione.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID annotazione non fornito.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
}
?>
