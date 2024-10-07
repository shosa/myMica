<?php
// Connessione al database
require_once '../../config/config.php';
$pdo = getDbInstance();

// Attiva il reporting degli errori PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se i parametri sono stati inviati
    if (isset($_POST['appointment_id'], $_POST['badge_color'], $_POST['badge_text'])) {
        // Preleva i dati inviati
        $appointmentId = $_POST['appointment_id'];
        $badgeColor = $_POST['badge_color'];
        $badgeText = $_POST['badge_text'];

        try {
            // Controlla se il testo del badge è vuoto
            if (empty($badgeText)) {
                // Se il testo è vuoto, imposta entrambi a NULL
                $badgeColor = null;
                $badgeText = null;
                $sql = "UPDATE appuntamenti SET badge_color = NULL, badge_text = NULL WHERE id_appuntamento = :appointment_id";
            } else {
                // Se il testo non è vuoto, aggiorna con i valori inviati
                $sql = "UPDATE appuntamenti SET badge_color = :badge_color, badge_text = :badge_text WHERE id_appuntamento = :appointment_id";
            }

            // Prepara la query
            $stmt = $pdo->prepare($sql);

            // Bind dei parametri, salta il bind se si stanno impostando valori null
            if ($badgeColor !== null && $badgeText !== null) {
                $stmt->bindParam(':badge_color', $badgeColor);
                $stmt->bindParam(':badge_text', $badgeText);
            }

            $stmt->bindParam(':appointment_id', $appointmentId);

            // Esegui l'aggiornamento nel database
            if ($stmt->execute()) {
                echo 'Success';
            } else {
                echo 'Error';
            }
        } catch (PDOException $e) {
            // Stampa l'errore PDO
            echo 'PDO Error: ' . $e->getMessage();
        }
    } else {
        echo 'Missing data';
    }
}
