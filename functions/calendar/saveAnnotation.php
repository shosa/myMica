<?php
include("../../config/config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dataAnnotazione = $_POST['dataAnnotazione'];
    $oraAnnotazione = $_POST['oraAnnotazione'];
    $nota = $_POST['nota'];
    // Corretto per concatenare la data con l'ora
    $data_completa = "$dataAnnotazione $oraAnnotazione";

    $pdo = getDbInstance();

    // Prepara l'inserimento nel database
    $stmt = $pdo->prepare("INSERT INTO annotazioni (data, note) VALUES (?, ?)");
    $stmt->execute([$data_completa, $nota]);

    // Reindirizza l'utente alla vista del calendario con un messaggio di successo
    $_SESSION['success'] = 'Annotazione creata con successo!';
    header('Location: calendario.php');
    exit;
}
?>