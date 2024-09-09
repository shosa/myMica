<?php
require_once '../../config/config.php';

$pdo = getDbInstance();

if (isset($_GET['id_annotazione'])) {
    $id_annotazione = intval($_GET['id_annotazione']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM annotazioni WHERE id_annotazione = :id_annotazione");
        $stmt->bindParam(':id_annotazione', $id_annotazione, PDO::PARAM_INT);
        $stmt->execute();

        $annotazione = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($annotazione) {
            echo json_encode($annotazione);
        } else {
            echo json_encode(['success' => false, 'message' => 'Annotazione non trovata.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID annotazione non fornito.']);
}
?>
