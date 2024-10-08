<?php include("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include(BASE_PATH . "/components/header.php");
setlocale(LC_TIME, 'it_IT.UTF-8');
$anno = isset($_GET['anno']) ? (int) $_GET['anno'] : date('Y');
$mese = isset($_GET['mese']) ? (int) $_GET['mese'] : date('n');
$giorno = isset($_GET['giorno']) ? (int) $_GET['giorno'] : date('j');
$data_corrente = new DateTime("$anno-$mese-$giorno");
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'next') {
        $data_corrente->modify('+7 days');
    } elseif ($_GET['action'] === 'prev') {
        $data_corrente->modify('-7 days');
    }
}
$anno = $data_corrente->format('Y');
$mese = $data_corrente->format('n');
$giorno = $data_corrente->format('j');
$clienti = $pdo->query("SELECT * FROM clienti ORDER BY nome_cliente ASC")->fetchAll(PDO::FETCH_ASSOC);
$servizi = $pdo->query("SELECT * FROM servizi ORDER BY nome_servizio ASC")->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_cliente = $_POST['id_cliente'];
    $servizi_selezionati = $_POST['id_servizio'];
    $data_appuntamento = $_POST['data_appuntamento'];
    $ora_appuntamento = $_POST['ora_appuntamento'];
    $data_completa = "$data_appuntamento $ora_appuntamento";

    foreach ($servizi_selezionati as $id_servizio) {
        // Recupera il tempo_servizio specifico per ogni servizio
        $stmt = $pdo->prepare("SELECT tempo_medio FROM servizi WHERE id_servizio = ?");
        $stmt->execute([$id_servizio]);
        $tempo_servizio = $stmt->fetchColumn();

        // Inserisci l'appuntamento con il tempo_servizio specifico per il servizio corrente
        $stmt = $pdo->prepare("INSERT INTO appuntamenti (id_cliente, id_servizio, data_appuntamento, tempo_servizio) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_cliente, $id_servizio, $data_completa, $tempo_servizio]);
    }

    $_SESSION["success"] = "Appuntamento creato.";
    header("Location: calendario.php?anno=$anno&mese=$mese&giorno=$giorno");
    exit;
}
function getTextColor($hexColor)
{
    // Rimuovi il simbolo '#' se presente
    $hexColor = ltrim($hexColor, '#');

    // Converte il colore esadecimale in RGB
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));

    // Calcola la luminosità
    $luminosity = (($r * 0.299) + ($g * 0.587) + ($b * 0.114));

    // Restituisce il colore del testo in base alla luminosità
    return ($luminosity > 186) ? 'black' : 'white';
}
function generaVistaSettimana($anno, $mese, $giorno, $appuntamenti, $annotazioni)
{
    $inizio_settimana = new DateTime("$anno-$mese-$giorno");
    $inizio_settimana->modify('monday this week');
    $giorni_settimana = [];
    $formatter = new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'eeee dd MMMM yyyy');
    $formatterCard = new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'eeee dd');
    $formatterNomeGiorno = new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'eeee');

    // Data di oggi
    $oggi = new DateTime();

    for ($i = 0; $i < 7; $i++) {
        $giorni_settimana[] = clone $inizio_settimana;
        $inizio_settimana->modify('+1 day');
    }

    echo "<h6 class='text-center mb-2'><span class='text-dark font-weight-bold'>" . strtoupper($formatter->format($giorni_settimana[0])) . "</span> <br> <span class='text-dark font-weight-bold'>" . strtoupper($formatter->format($giorni_settimana[6])) . "</span></h6>";
    echo '<div class="row row-cols-1 row-cols-md-12">';

    foreach ($giorni_settimana as $giorno) {
        $nomeGiorno = $formatterNomeGiorno->format($giorno);
        $class = '';
        $color = '';
        if ($nomeGiorno === 'sabato') {
            $class .= 'border rounded border-info ';
            $color .= 'text-light bg-info';
        }
        if ($nomeGiorno === 'domenica') {
            $class .= 'border rounded border-danger ';
            $color .= 'text-light bg-danger';
        }

        // Controlla se il giorno corrente è oggi
        $badgeOggi = $oggi->format('Y-m-d') === $giorno->format('Y-m-d') ? "<span class='badge badge-success float-right'>OGGI</span>" : '';

        echo '<div class="col">';
        echo '<div class="card mt-2 shadow-sm ' . htmlspecialchars(trim($class)) . '">';
        echo '<div class="card-header ' . htmlspecialchars(trim($color)) . '">';
        echo '<span class="card-title font-weight-bold">' . strtoupper($formatterCard->format($giorno)) . ' ' . $badgeOggi . '</span>';
        echo '</div>';
        echo '<div class="card-body ">';
        echo '<ul class="list-group list-group-flush">';

        // Raggruppa gli appuntamenti per ora, cliente e data
        $appuntamentiGiorno = array_filter($appuntamenti, function ($a) use ($giorno) {
            return (new DateTime($a['data_appuntamento']))->format('Y-m-d') === $giorno->format('Y-m-d');
        });

        $annotazioniGiorno = array_filter($annotazioni, function ($annotazione) use ($giorno) {
            return (new DateTime($annotazione['data']))->format('Y-m-d') === $giorno->format('Y-m-d');
        });

        // Raggruppa appuntamenti per cliente e ora
        $eventi = [];
        $appuntamentiPerCliente = [];

        foreach ($appuntamentiGiorno as $appuntamento) {
            $ora = (new DateTime($appuntamento['data_appuntamento']))->format('H:i');
            $cliente = $appuntamento['nome_cliente'];
            $chiave = "$ora-$cliente";

            if (!isset($eventi[$chiave])) {
                $eventi[$chiave] = ['tipo' => 'appuntamento', 'ora' => $ora, 'cliente' => $cliente, 'listaAppuntamenti' => [], 'tempoTotale' => 0, 'completato' => 1];
            }
            $eventi[$chiave]['listaAppuntamenti'][] = $appuntamento;
            $eventi[$chiave]['tempoTotale'] += $appuntamento['tempo_servizio'];

            // Raggruppa gli appuntamenti per cliente
            if (!isset($appuntamentiPerCliente[$cliente])) {
                $appuntamentiPerCliente[$cliente] = ['totale' => 0, 'completati' => 0];
            }
            $appuntamentiPerCliente[$cliente]['totale']++;
            if ($appuntamento['completato'] == 1) {
                $appuntamentiPerCliente[$cliente]['completati']++;
            }
        }

        foreach ($annotazioniGiorno as $annotazione) {
            $ora = (new DateTime($annotazione['data']))->format('H:i');
            $eventi[] = ['tipo' => 'annotazione', 'ora' => $ora, 'dati' => $annotazione];
        }

        // Ordina gli eventi per orario
        usort($eventi, function ($a, $b) {
            return strcmp($a['ora'], $b['ora']);
        });

        // Visualizza gli eventi
        foreach ($eventi as $evento) {
            if ($evento['tipo'] === 'appuntamento') {
                $cliente = $evento['cliente'];

                $ora = $evento['ora'];
                $tempoTotale = $evento['tempoTotale'];
                $stato = '';
                $badge = '';
                // Mostra il badge in base allo stato di completamento
                if ($appuntamentiPerCliente[$cliente]['totale'] === $appuntamentiPerCliente[$cliente]['completati']) {
                    $badge .= "<span class='badge badge-success'>FATTO</span>";
                    $stato .= 'border-success';
                } else {
                    $badge .= "<span class='badge badge-warning'>$tempoTotale min</span>";
                    $stato .= 'border-primary';
                }

                echo "<div class='border $stato rounded mb-1'>";
                echo "<li class='list-group-item d-flex justify-content-between align-items-center font-weight-bold text-dark border-0'>";
                echo "$ora - $cliente ";
                $stato = '';
                // Mostra il badge in base allo stato di completamento

                echo $badge;


                echo "</li>";
                echo '<ul class="list-group" style="border-radius: 0 0 5px 5px !important;">';

                foreach ($evento['listaAppuntamenti'] as $appuntamento) {
                    $icona = $appuntamento['completato'] == 0 ? '<span class="icon" ><i class="fal fa-clock a"></i></span>' : '<span class="icon"><i class="fal fa-check "></i></span>';
                    $coloreAppuntamento = $appuntamento['completato'] == 0 ? 'font-weight-normal text-primary' : 'font-weight-normal text-success';
                    $hoverAppuntamento = $appuntamento['completato'] == 0 ? 'appuntamento' : 'appuntamentoFatto';
                    $nome_servizio = htmlspecialchars($appuntamento['nome_servizio']);
                    $tempo_servizio = htmlspecialchars($appuntamento['tempo_servizio']);
                    $id_appuntamento = $appuntamento['id_appuntamento'];
                    echo "<li class='" . $hoverAppuntamento . " list-group-item border-0 " . $coloreAppuntamento . " appointment-item' data-id='$id_appuntamento' id='$id_appuntamento' data-cliente='$cliente' data-ora='$ora' data-servizio='$nome_servizio'>" . $icona . " <span class='appointment-text'>$nome_servizio <i>($tempo_servizio min)</i></span></li>";

                    if (isset($appuntamento['badge_text'])) {
                        $badgeColor = htmlspecialchars($appuntamento['badge_color']);
                        $textColor = getTextColor($badgeColor);
                        echo "<span class='badge m-1' style='background-color: $badgeColor; color: $textColor;'>" . htmlspecialchars($appuntamento['badge_text']) . "</span>";
                    }
                }

                echo "</ul>";
                echo "</div>"; // Chiudi il div per l'appuntamento
            } elseif ($evento['tipo'] === 'annotazione') {
                $ora = $evento['ora'];
                $note = nl2br(htmlspecialchars($evento['dati']['note']));
                $id_annotazione = $evento['dati']['id_annotazione'];

                echo "<div class='border border-orange rounded mb-1'>";
                echo "<li class='list-group-item border-0 text-orange annotation-item annotazione' data-ora='$ora' data-id='$id_annotazione' data-note='$note'><i class='fal fa-sticky-note'></i> $ora - $note</li>";
                echo "</div>";
            }
        }

        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
}



$inizio_settimana = (new DateTime("$anno-$mese-$giorno"))->modify('monday this week')->format('Y-m-d');
$fine_settimana = (new DateTime("$anno-$mese-$giorno"))->modify('sunday this week')->format('Y-m-d');
$numero_settimana = (new DateTime("$anno-$mese-$giorno"))->format('W');
$stmt = $pdo->prepare("SELECT a.id_appuntamento, a.badge_color, a.badge_text, c.nome_cliente, s.nome_servizio, a.data_appuntamento, a.tempo_servizio , a.completato\n                       FROM appuntamenti a\n                       JOIN clienti c ON a.id_cliente = c.id_cliente\n                       JOIN servizi s ON a.id_servizio = s.id_servizio\n                       WHERE DATE(a.data_appuntamento) BETWEEN ? AND ?\n                       ORDER BY a.data_appuntamento ASC");
$stmt->execute([$inizio_settimana, $fine_settimana]);
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT *\n                       FROM annotazioni \n                         WHERE DATE(data) BETWEEN ? AND ?\n                       ORDER BY data ASC");
$stmt->execute([$inizio_settimana, $fine_settimana]);
$annotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC); ?>
<link href="custom.css" rel="stylesheet">

<body id="page-top">
    <div id="wrapper"><?php include(BASE_PATH . "/components/navbar.php"); ?>
        <div class="d-flex flex-column mb-2" id="content-wrapper">
            <div id="content"><?php include(BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid"><?php require_once BASE_PATH . "/utils/alerts.php"; ?>
                    <div class="align-items-center justify-content-between">
                        <div class="card mb-4 shadow-sm">
                            <div class="align-items-center justify-content-between card-body d-flex"><a
                                    class="btn btn-indigo btn-circle"
                                    href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=prev"><i
                                        class="far fa-chevron-left"></i> </a><span class="text-center">
                                    <h4><b>Settimana <?php echo $numero_settimana; ?></b></h4>
                                </span><a class="btn btn-indigo btn-circle"
                                    href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=next"><i
                                        class="far fa-chevron-right"></i></a></div>
                        </div><?php generaVistaSettimana($anno, $mese, $giorno, $appuntamenti, $annotazioni); ?>
                        <button class="btn btn-indigo btn-lg floating-btn dropdown" type="button"
                            id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-plus fa-xl"></i>
                        </button>
                        <div class="dropdown-menu mb-2 mr-1 shadow-sm" aria-labelledby="dropdownMenuButton">
                            <button class="dropdown-item p-2 h5 btn-block text-primary dropdown-custom-item"
                                data-target="#newAppointmentModal" data-toggle="modal"><i class="fa fa-plus"></i>
                                APPUNTAMENTO</button>
                            <hr class="dropdown-divider">
                            <button class="dropdown-item p-2 h5 btn-block text-orange dropdown-custom-item"
                                data-target="#newAnnotationModal" data-toggle="modal">
                                <i class="fa fa-sticky-note"></i> PROMEMORIA
                            </button>
                        </div>


                        <?php include(BASE_PATH . "/functions/notification/notification.php"); ?>
                    </div>
                </div>
            </div><?php include("modals/modals.php") ?>
            <script src="scripts/calendar.js"></script>

            <?php include(BASE_PATH . "/components/footer.php"); ?>
        </div>
    </div><?php include(BASE_PATH . "/components/scripts.php"); ?>
</body>