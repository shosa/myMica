<?php
include("../../config/config.php");

if (isset($_GET['term'])) {
    $term = $_GET['term'];

    $pdo = getDbInstance();
    $stmt = $pdo->prepare("SELECT id_cliente, nome_cliente FROM clienti WHERE nome_cliente LIKE ? LIMIT 10");
    $stmt->execute(['%' . $term . '%']);
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clienti);
}
?>
