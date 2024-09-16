<?php
require '../../config/config.php'; // Connessione al DB

$cliente = $_GET['cliente'];
$dataora = $_GET['dataora']; // Data e ora nel formato 'YYYY-MM-DD HH:MM:SS'
$pdo = getDbInstance();

// Query per ottenere il totale e i dettagli dei servizi
$query = $pdo->prepare("
    SELECT servizi.nome_servizio AS nome_servizio, servizi.costo AS costo_servizio
    FROM appuntamenti
    JOIN servizi ON appuntamenti.id_servizio = servizi.id_servizio
    WHERE appuntamenti.id_cliente = :cliente 
    AND appuntamenti.data_appuntamento = :dataora
");
$query->bindParam(':cliente', $cliente);
$query->bindParam(':dataora', $dataora);
$query->execute();

// Ottieni i dettagli di ogni servizio
$servizi = $query->fetchAll(PDO::FETCH_ASSOC);

// Calcola il totale sommando i costi dei servizi
$totale = 0;
foreach ($servizi as $servizio) {
    $totale += $servizio['costo_servizio'];
}

if ($servizi) {
    echo json_encode([
        'success' => true,
        'servizi' => $servizi,
        'totale' => $totale
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nessun risultato trovato.']);
}
?>