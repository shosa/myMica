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

    $_SESSION["success"] = "Appuntamento con più servizi inserito!";
    header("Location: calendario.php?anno=$anno&mese=$mese&giorno=$giorno");
    exit;
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
                }

                echo "</ul>";
                echo "</div>"; // Chiudi il div per l'appuntamento
            } elseif ($evento['tipo'] === 'annotazione') {
                $ora = $evento['ora'];
                $note = htmlspecialchars($evento['dati']['note']);
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
$stmt = $pdo->prepare("SELECT a.id_appuntamento, c.nome_cliente, s.nome_servizio, a.data_appuntamento, a.tempo_servizio , a.completato\n                       FROM appuntamenti a\n                       JOIN clienti c ON a.id_cliente = c.id_cliente\n                       JOIN servizi s ON a.id_servizio = s.id_servizio\n                       WHERE DATE(a.data_appuntamento) BETWEEN ? AND ?\n                       ORDER BY a.data_appuntamento ASC");
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
                        </div><?php generaVistaSettimana($anno, $mese, $giorno, $appuntamenti, $annotazioni); ?><button
                            class="btn btn-primary btn-lg floating-btn" data-target="#newAppointmentModal"
                            data-toggle="modal"><i class="fa fa-plus"></i></button>
                        <button class="btn btn-orange btn-lg floating-btn2" data-target="#newAnnotationModal"
                            data-toggle="modal">
                            <i class="fa fa-sticky-note"></i>
                        </button>
                    </div>
                </div>
            </div><?php include("modals/modals.php") ?>
            <script>document.getElementById('search_cliente').addEventListener('input', function () {
                    const searchTerm = this.value;

                    if (searchTerm.length > 2) {
                        fetch(`searchCustomer.php?term=${searchTerm}`)
                            .then(response => response.json())
                            .then(data => {
                                const suggestions = document.getElementById('suggestions');
                                suggestions.innerHTML = '';
                                data.forEach(cliente => {
                                    const suggestionItem = document.createElement('a');
                                    suggestionItem.href = '#';
                                    suggestionItem.className = 'list-group-item list-group-item-action shadow';
                                    suggestionItem.textContent = cliente.nome_cliente;
                                    suggestionItem.dataset.id = cliente.id_cliente;
                                    suggestionItem.addEventListener('click', function () {
                                        document.getElementById('search_cliente').value = cliente.nome_cliente;
                                        document.getElementById('search_cliente').classList.add("text-success");
                                        document.getElementById('search_cliente').classList.add("border");
                                        document.getElementById('search_cliente').classList.add("border-success");
                                        document.getElementById('id_cliente').value = cliente.id_cliente;
                                        suggestions.innerHTML = '';
                                    });
                                    suggestions.appendChild(suggestionItem);
                                });
                            })
                            .catch(error => console.error('Errore:', error));
                    }
                });

                document.getElementById('newClienteForm').addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch('saveCustomer', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Errore nel salvataggio del cliente');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                document.getElementById('id_cliente').value = data.id_cliente;
                                document.getElementById('search_cliente').value = data.nome_cliente;
                                document.getElementById('search_cliente').classList.add("text-success");
                                document.getElementById('search_cliente').classList.add("border");
                                document.getElementById('search_cliente').classList.add("border-success");
                                $('#newClienteModal').modal('hide');
                            } else {
                                alert(data.message || 'Si è verificato un errore durante il salvataggio del cliente.');
                            }
                        })
                        .catch(error => {
                            console.error('Errore:', error);
                            alert('Si è verificato un errore durante il salvataggio del cliente.');
                        });
                });
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.appointment-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const idAppuntamento = this.dataset.id;

                            fetch(`getDetails?id_appuntamento=${idAppuntamento}`)
                                .then(response => response.json())
                                .then(data => {
                                    aggiornaDettagliAppuntamento(data);
                                    $('#appointmentDetailsModal').modal('show');
                                })
                                .catch(error => console.error('Errore:', error));
                        });
                    });

                    // Funzione per aggiornare i dettagli dell'appuntamento nel modal
                    function aggiornaDettagliAppuntamento(data) {
                        document.getElementById('detail_nome_cliente').textContent = data.nome_cliente;
                        document.getElementById('detail_nome_servizio').textContent = data.nome_servizio;
                        document.getElementById('detail_id_appuntamento').textContent = "#" + data.id_appuntamento;
                        document.getElementById('detail_stato').textContent = data.completato == 1 ? "COMPLETATO" : "IN PROGRAMMA";
                        document.getElementById('detail_stato').classList.remove(data.completato == 1 ? "bg-primary" : "bg-success");
                        document.getElementById('detail_stato').classList.add(data.completato == 1 ? "bg-success" : "bg-primary");
                        document.getElementById('modaleDettagli').classList.remove(data.completato == 1 ? "border-primary" : "border-success");
                        document.getElementById('modaleDettagli').classList.add(data.completato == 1 ? "border-success" : "border-primary");
                        document.getElementById('detail_id_cliente').value = data.id_cliente; // Salva l'id_cliente

                        const dataAppuntamento = new Date(data.data_appuntamento);
                        const dataFormat = `${dataAppuntamento.getDate()}/${dataAppuntamento.getMonth() + 1}/${dataAppuntamento.getFullYear()}`;
                        const oraFormat = dataAppuntamento.toTimeString().substring(0, 5);
                        const dataOraOriginale = data.data_appuntamento;
                        document.getElementById('detail_data_appuntamento').textContent = dataFormat;
                        document.getElementById('detail_ora_appuntamento').textContent = oraFormat;

                        const messaggio = `Ciao ti ricordo l'appuntamento del ${dataFormat} alle ${oraFormat}`;
                        const whatsappLink = `https://api.whatsapp.com/send?phone=39${data.telefono_cliente}&text=${encodeURIComponent(messaggio)}`;


                        document.getElementById('editAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;
                        document.getElementById('deleteAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;
                        document.getElementById('completeAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;

                        if (data.completato == 1) {
                            document.getElementById('editAppointmentBtn').disabled = true;
                            document.getElementById('deleteAppointmentBtn').disabled = true;
                            document.getElementById('completeAppointmentBtn').disabled = true;
                            document.getElementById('whatsappLink').classList.add("disabled");

                        } else {
                            document.getElementById('editAppointmentBtn').disabled = false;
                            document.getElementById('deleteAppointmentBtn').disabled = false;
                            document.getElementById('completeAppointmentBtn').disabled = false;
                            document.getElementById('whatsappLink').classList.remove("disabled");
                            document.getElementById('whatsappLink').href = whatsappLink;
                        }
                        document.getElementById('btnBill').dataset.dataOra = dataOraOriginale;
                    }

                    // Gestione della modifica dell'appuntamento
                    document.getElementById('editAppointmentBtn').addEventListener('click', function () {
                        const idAppuntamento = this.dataset.idAppuntamento;
                        window.location.href = `editAppointment?id_appuntamento=${idAppuntamento}`;
                    });

                    // Gestione della cancellazione dell'appuntamento
                    document.getElementById('deleteAppointmentBtn').addEventListener('click', function () {
                        const idAppuntamento = this.dataset.idAppuntamento;

                        if (confirm('Sei sicuro di voler cancellare questo appuntamento?')) {
                            fetch(`deleteAppointment?id_appuntamento=${idAppuntamento}`, { method: 'DELETE' })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Errore nella cancellazione dell\'appuntamento.');
                                    }
                                })
                                .catch(error => console.error('Errore:', error));
                        }
                    });

                    // Gestione del completamento dell'appuntamento
                    document.getElementById('completeAppointmentBtn').addEventListener('click', function () {
                        const idAppuntamento = this.dataset.idAppuntamento;

                        Swal.fire({
                            title: 'Sei sicuro?',
                            text: "Vuoi completare questo appuntamento?",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sì, completa',
                            cancelButtonText: 'Annulla'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                fetch('completeAppointment', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: new URLSearchParams({
                                        'id_appuntamento': idAppuntamento
                                    })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                title: 'Appuntamento completato!',
                                                text: data.message,
                                                icon: 'success'
                                            }).then(() => {
                                                // Aggiorna i dettagli dell'appuntamento nel modal
                                                fetch(`getDetails?id_appuntamento=${idAppuntamento}`)
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        aggiornaDettagliAppuntamento(data);
                                                    })
                                                    .catch(error => console.error('Errore:', error));
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Errore',
                                                text: data.message,
                                                icon: 'error'
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        Swal.fire({
                                            title: 'Errore',
                                            text: 'Si è verificato un errore durante il completamento dell\'appuntamento.',
                                            icon: 'error'
                                        });
                                        console.error('Errore:', error);
                                    });
                            }

                        });
                    });
                });
                document.addEventListener('DOMContentLoaded', function () {
                    // Apre il modale quando si clicca su una annotazione
                    document.querySelectorAll('.annotation-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const idAnnotazione = this.dataset.id;

                            fetch(`getAnnotationDetail?id_annotazione=${idAnnotazione}`)
                                .then(response => response.json())
                                .then(data => {
                                    aggiornaDettagliAnnotazione(data);
                                    $('#annotationDetailsModal').modal('show');
                                })
                                .catch(error => console.error('Errore:', error));
                        });
                    });

                    // Funzione per aggiornare i dettagli dell'annotazione nel modal
                    function aggiornaDettagliAnnotazione(data) {
                        const dataAnnotazione = new Date(data.data);

                        // Imposta il campo data e ora
                        document.getElementById('detail_data_annotazione').value = dataAnnotazione.toISOString().split('T')[0]; // Imposta la data in formato YYYY-MM-DD
                        document.getElementById('detail_ora_annotazione').value = dataAnnotazione.toTimeString().substring(0, 5); // Imposta l'ora in formato HH:MM

                        // Imposta il contenuto delle note
                        document.getElementById('detail_note_annotazione').value = data.note;

                        // Imposta l'ID annotazione visibile nel modale
                        document.getElementById('detail_id_annotazione').textContent = "#" + data.id_annotazione;

                        // Imposta i bottoni di modifica e cancellazione con il dataset ID annotazione
                        document.getElementById('editAnnotationBtn').dataset.idAnnotazione = data.id_annotazione;
                        document.getElementById('deleteAnnotationBtn').dataset.idAnnotazione = data.id_annotazione;
                    }

                    // Gestione della modifica dell'annotazione
                    document.getElementById('editAnnotationBtn').addEventListener('click', function () {
                        const idAnnotazione = this.dataset.idAnnotazione;
                        const data = document.getElementById('detail_data_annotazione').value;
                        const ora = document.getElementById('detail_ora_annotazione').value;
                        const note = document.getElementById('detail_note_annotazione').value;
                        const dataCompleta = `${data} ${ora}`;

                        fetch(`editAnnotation`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ id_annotazione: idAnnotazione, data: dataCompleta, note: note })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert('Errore nella modifica dell\'annotazione.');
                                }
                            })
                            .catch(error => console.error('Errore:', error));
                    });

                    // Gestione della cancellazione dell'annotazione
                    document.getElementById('deleteAnnotationBtn').addEventListener('click', function () {
                        const idAnnotazione = this.dataset.idAnnotazione;

                        if (confirm('Sei sicuro di voler cancellare questa annotazione?')) {
                            fetch('deleteAnnotation.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({ id_annotazione: idAnnotazione })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Errore nella cancellazione dell\'annotazione.');
                                    }
                                })
                                .catch(error => console.error('Errore:', error));
                        }
                    });
                    document.getElementById('btnBill').addEventListener('click', function () {
                        const idCliente = document.getElementById('detail_id_cliente').value;
                        const dataOraOriginale = this.dataset.dataOra; // Ottieni la data originale memorizzata

                        if (!idCliente || !dataOraOriginale) {
                            alert('Errore: Cliente o data non disponibile.');
                            return;
                        }

                        // Effettua una richiesta per ottenere il totale dei costi
                        fetch(`getTotalCost?cliente=${idCliente}&dataora=${encodeURIComponent(dataOraOriginale)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Creare il contenuto del popup in formato scontrino
                                    let scontrino = '<div style="text-align: left; font-family: Arial, sans-serif;">'; // Aggiungi un div con allineamento a sinistra
                                    data.servizi.forEach(servizio => {
                                        const costo = parseFloat(servizio.costo_servizio).toFixed(2); // Converti la stringa in numero e formatta
                                        scontrino += `
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-indigo">${servizio.nome_servizio}</span>
                            <span>${costo}€</span>
                        </div>`;
                                    });

                                    scontrino += '<hr>'; // Usando <hr> per la linea divisoria
                                    scontrino += `
                    <div style="display: flex; justify-content: space-between;">
                        <span>TOTALE</span>
                        <span class="font-weight-bold">${parseFloat(data.totale).toFixed(2)}€</span>
                    </div>`;
                                    scontrino += '</div>'; // Chiudi il div

                                    // Mostra il popup con SweetAlert
                                    Swal.fire({
                                        title: 'Dettaglio Servizi',
                                        html: scontrino,
                                        confirmButtonText: 'OK'
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Errore',
                                        text: 'Impossibile calcolare il totale.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Errore:', error);
                                Swal.fire({
                                    title: 'Errore',
                                    text: 'Si è verificato un errore durante il calcolo del totale.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            });
                    });




                });

            </script>
            <?php include(BASE_PATH . "/components/footer.php"); ?>
        </div>
    </div><?php include(BASE_PATH . "/components/scripts.php"); ?>
</body>