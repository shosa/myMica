<?php
require '../../config/config.php';

// Funzione per arrotondare il tempo alla mezz'ora più vicina
function arrotondaMezzora($datetime)
{
    $minuti = (int) $datetime->format('i');
    $ore = (int) $datetime->format('H');

    if ($minuti % 30 === 0) {
        return $datetime;
    }

    $minuti_arrotondati = ceil($minuti / 30) * 30;
    if ($minuti_arrotondati === 60) {
        $minuti_arrotondati = 0;
        $ore += 1;
    }
    $datetime->setTime($ore, $minuti_arrotondati);
    return $datetime;
}

function trovaPrimoSlotDisponibile($pdo, $data, $durata_servizio)
{
    $giorno_settimana = date('l', strtotime($data)); // Modificato a 'l' per nome del giorno completo

    // Log del giorno della settimana
    error_log("Giorno della settimana: " . $giorno_settimana);

    // Recupera le impostazioni per il giorno specificato
    $stmt = $pdo->prepare("SELECT * FROM impostazioni_calendario WHERE giorno_settimana = ?");
    $stmt->execute([$giorno_settimana]);
    $impostazione = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$impostazione) {
        error_log("Nessuna impostazione trovata per il giorno: " . $giorno_settimana);
        return null;
    }

    $ora_inizio = new DateTime($data . ' ' . $impostazione['ora_inizio']);
    $ora_fine = new DateTime($data . ' ' . $impostazione['ora_fine']);
    
    // Log degli orari di apertura
    error_log("Orario di inizio: " . $ora_inizio->format('H:i'));
    error_log("Orario di fine: " . $ora_fine->format('H:i'));

    // Recupera gli appuntamenti del giorno
    $stmt = $pdo->prepare("SELECT data_appuntamento, tempo_servizio FROM appuntamenti WHERE DATE(data_appuntamento) = ? ORDER BY data_appuntamento ASC");
    $stmt->execute([$data]);
    $appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log degli appuntamenti trovati
    error_log("Appuntamenti trovati: " . print_r($appuntamenti, true));

    // Arrotonda l'orario di inizio
    $ora_inizio = arrotondaMezzora($ora_inizio);

    foreach ($appuntamenti as $appuntamento) {
        $inizio_appuntamento = new DateTime($appuntamento['data_appuntamento']);
        $fine_appuntamento = clone $inizio_appuntamento;
        $fine_appuntamento->modify("+{$appuntamento['tempo_servizio']} minutes");

        // Log di ogni intervallo di appuntamento
        error_log("Appuntamento inizia a: " . $inizio_appuntamento->format('H:i') . " e finisce a: " . $fine_appuntamento->format('H:i'));

        // Controlla se c'è spazio prima dell'appuntamento
        if ($ora_inizio < $inizio_appuntamento && $ora_inizio->diff($inizio_appuntamento)->i >= $durata_servizio) {
            // Arrotonda il tempo suggerito
            $slot_disponibile = arrotondaMezzora($ora_inizio);
            error_log("Slot disponibile trovato: " . $slot_disponibile->format('H:i'));
            return $slot_disponibile->format('H:i');
        }

        // Aggiorna l'orario di inizio per il prossimo slot
        $ora_inizio = max($ora_inizio, $fine_appuntamento);
        $ora_inizio = arrotondaMezzora($ora_inizio);
    }

    // Controlla se c'è spazio dopo l'ultimo appuntamento
    if ($ora_inizio < $ora_fine && $ora_inizio->diff($ora_fine)->i >= $durata_servizio) {
        // Arrotonda il tempo suggerito
        $slot_disponibile = arrotondaMezzora($ora_inizio);
        error_log("Slot disponibile trovato: " . $slot_disponibile->format('H:i'));
        return $slot_disponibile->format('H:i');
    }

    error_log("Nessun slot disponibile trovato.");
    return null;
}

if (isset($_GET['id_cliente']) && isset($_GET['id_servizio'])) {
    $pdo = getDbInstance();
    $id_cliente = $_GET['id_cliente'];
    $id_servizio = $_GET['id_servizio'];

    // Recupera la durata del servizio
    $stmt = $pdo->prepare("SELECT tempo_medio FROM servizi WHERE id_servizio = ?");
    $stmt->execute([$id_servizio]);
    $tempo_servizio = $stmt->fetchColumn();

    // Controlla se la durata del servizio è valida
    if (!$tempo_servizio) {
        error_log("Durata del servizio non trovata per id_servizio: " . $id_servizio);
        echo json_encode(['slot' => null]);
        exit;
    }

    // Calcola il giorno successivo
    $data_odierna = date('Y-m-d');
    $giorno_successivo = date('Y-m-d', strtotime($data_odierna . ' +1 day'));

    // Trova il primo slot disponibile a partire dal giorno successivo
    $slot_suggerito = trovaPrimoSlotDisponibile($pdo, $giorno_successivo, $tempo_servizio);

    echo json_encode(['slot' => $slot_suggerito]);

    // Debugging
    error_log("Slot suggerito: " . print_r($slot_suggerito, true));
}
?>
