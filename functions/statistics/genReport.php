<?php
require_once('../../vendor/autoload.php');
require_once('../../config/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data_da = $_POST['data_da'];
    $data_a = $_POST['data_a'];

    // Controlla i toggle attivi
    $mostra_guadagni = isset($_POST['mostra_guadagni']) ? true : false;
    $raggruppa_servizi = isset($_POST['raggruppa_servizi']) ? true : false;
    $raggruppa_clienti = isset($_POST['raggruppa_clienti']) ? true : false;

    // Base della query con aggiunta del COUNT per servizi e clienti
    $query = "
        SELECT s.nome_servizio, a.tempo_servizio, COUNT(a.id_appuntamento) AS count_servizi, SUM(s.costo) AS totale_guadagno, c.nome_cliente, COUNT(a.id_cliente) AS count_clienti
        FROM appuntamenti a
        JOIN servizi s ON a.id_servizio = s.id_servizio
        JOIN clienti c ON a.id_cliente = c.id_cliente
        WHERE a.data_appuntamento BETWEEN :data_da AND :data_a
        AND a.completato = 1
    ";

    // Modifica della query in base ai toggle
    if ($raggruppa_servizi) {
        $query .= " GROUP BY s.nome_servizio";
    }
    if ($raggruppa_clienti) {
        $query .= $raggruppa_servizi ? ", c.nome_cliente" : " GROUP BY c.nome_cliente";
    }
    $query .= " ORDER BY s.nome_servizio";
    $pdo = getDbInstance();
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':data_da', $data_da);
    $stmt->bindParam(':data_a', $data_a);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcolo del totale complessivo
    $totale_complessivo = 0;
    if ($mostra_guadagni) {
        foreach ($result as $row) {
            $totale_complessivo += $row['totale_guadagno'];
        }
    }

    $totale_tempo = 0;
    foreach ($result as $row) {
        $totale_tempo += $row['tempo_servizio'];
    }

    // Inizializzazione TCPDF
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Titolo del PDF
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, "Report dal $data_da al $data_a", 0, 1, 'C');
    $pdf->Ln(5);

    // Impostazione colori e font per la tabella
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetFillColor(240, 240, 240); // Colore di sfondo alternato
    $pdf->SetTextColor(0); // Colore testo
    $pdf->SetDrawColor(200, 200, 200); // Colore bordi

    // Intestazione tabella
    $pdf->SetFont('helvetica', 'B', 12);
    if ($raggruppa_servizi) {
        $pdf->Cell(60, 10, 'Servizio', 1, 0, 'C', 1);
        // Mostra conteggio servizi solo se non è attivo il raggruppamento clienti
        if (!$raggruppa_clienti) {
            $pdf->Cell(10, 10, 'X', 1, 0, 'C', 1); // Colonna per conteggio servizi
        }
    }
    if ($raggruppa_clienti) {
        $pdf->Cell(80, 10, 'Cliente', 1, 0, 'C', 1);
        $pdf->Cell(10, 10, 'X', 1, 0, 'C', 1); // Colonna per conteggio clienti
    }
    if ($mostra_guadagni) {
        $pdf->Cell(40, 10, '€', 1, 1, 'C', 1);
    } else {
        $pdf->Ln(10);
    }

    // Riga alternata per ogni risultato
    $fill = 0;
    $pdf->SetFont('helvetica', '', 12);
    foreach ($result as $row) {
        if ($raggruppa_servizi) {
            $pdf->Cell(60, 10, $row['nome_servizio'], 1, 0, 'L', $fill);
            // Mostra conteggio servizi solo se non è attivo il raggruppamento clienti
            if (!$raggruppa_clienti) {
                $pdf->Cell(10, 10, $row['count_servizi'], 1, 0, 'C', $fill); // Mostra conteggio servizi
            }
        }
        if ($raggruppa_clienti) {
            $pdf->Cell(80, 10, $row['nome_cliente'], 1, 0, 'L', $fill);
            $pdf->Cell(10, 10, $row['count_clienti'], 1, 0, 'C', $fill); // Mostra conteggio clienti
        }
        if ($mostra_guadagni) {
            $pdf->Cell(40, 10, number_format($row['totale_guadagno'], 2), 1, 1, 'R', $fill);
        } else {
            $pdf->Ln(10);
        }
        $fill = !$fill; // Alterna il colore di sfondo
    }

    // Somma totale in fondo alla tabella se Mostra Guadagni è attivo
    if ($mostra_guadagni) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(160, 10, 'Totale Complessivo', 1, 0, 'L', 1);
        $pdf->Cell(30, 10, number_format($totale_complessivo, 2) . " €", 1, 1, 'R', 1);
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(160, 10, 'Totale Tempo', 1, 0, 'L', 1);
    $pdf->Cell(30, 10, $totale_tempo . " min", 1, 1, 'R', 1);
    // Output PDF
    $pdf->Output('Report_Guadagni.pdf', 'I');
}
?>