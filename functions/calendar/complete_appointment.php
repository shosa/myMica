<?php
include("../../config/config.php");

$response = array('success' => false, 'message' => '');

try {
    $pdo = getDbInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Recupera l'ID dell'appuntamento
    $id_appuntamento = isset($_POST['id_appuntamento']) ? $_POST['id_appuntamento'] : (isset($_GET['id_appuntamento']) ? $_GET['id_appuntamento'] : null);

    if ($id_appuntamento === null) {
        throw new Exception("ID appuntamento mancante");
    }

    // Esegui l'aggiornamento del database
    $stmt = $pdo->prepare("UPDATE appuntamenti SET completato = 1 WHERE id_appuntamento = ?");
    $stmt->execute([$id_appuntamento]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Appuntamento completato con successo';
    } else {
        throw new Exception("Nessun appuntamento trovato con l'ID fornito");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Errore nel completamento dell\'appuntamento: ' . $e->getMessage();
}

echo json_encode($response);
