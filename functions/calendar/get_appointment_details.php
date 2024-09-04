<?php
include("../../config/config.php");
$pdo = getDbInstance();
$id_appuntamento = $_GET['id_appuntamento'];

$stmt = $pdo->prepare("SELECT a.id_appuntamento, c.nome_cliente, s.nome_servizio, a.data_appuntamento, a.tempo_servizio, c.numero_telefono as telefono_cliente , a.completato
                       FROM appuntamenti a
                       JOIN clienti c ON a.id_cliente = c.id_cliente
                       JOIN servizi s ON a.id_servizio = s.id_servizio
                       WHERE a.id_appuntamento = ?");
$stmt->execute([$id_appuntamento]);
$appuntamento = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($appuntamento);
?>