<?php
require_once '../../config/config.php';

$pdo = getDbInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_annotazione'], $_POST['data'], $_POST['note'])) {
        $id_annotazione = intval($_POST['id_annotazione']);
        $data = $_POST['data']; // Formato: 'YYYY-MM-DD HH:MM'
        $note = $_POST['note'];

        try {
            $stmt = $pdo->prepare("UPDATE annotazioni SET data = :data, note = :note WHERE id_annotazione = :id_annotazione");
            $stmt->bindParam(':data', $data);
            $stmt->bindParam(':note', $note);
            $stmt->bindParam(':id_annotazione', $id_annotazione, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Annotazione modificata con successo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Errore nella modifica dell\'annotazione.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dati non completi.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
}
?>
