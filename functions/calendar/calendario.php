<?php include ("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include (BASE_PATH . "/components/header.php");
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
function generaVistaSettimana($anno, $mese, $giorno, $appuntamenti)
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

    echo "<h5 class='text-center mt-2 mb-2'>Dal " . $formatter->format($giorni_settimana[0]) . "</h5><h5 class='text-center mb-2 '> Al " . $formatter->format($giorni_settimana[6]) . "</h5>";
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
        $badgeOggi = $oggi->format('Y-m-d') === $giorno->format('Y-m-d') ? "<span class=' badge badge-success float-right '>OGGI</span>" : '';

        echo '<div class="col">';
        echo '<div class="card mt-2 ' . htmlspecialchars(trim($class)) . '">';
        echo '<div class="card-header ' . htmlspecialchars(trim($color)) . '">';
        echo '<span class="card-title font-weight-bold">' . strtoupper($formatterCard->format($giorno)) . ' ' . $badgeOggi . '</span>';
        echo '</div>';
        echo '<div class="card-body ">';
        echo '<ul class="list-group list-group-flush">';

        // Raggruppa gli appuntamenti per ora, cliente e data
        $appuntamentiGiorno = array_filter($appuntamenti, function ($a) use ($giorno) {
            return (new DateTime($a['data_appuntamento']))->format('Y-m-d') === $giorno->format('Y-m-d');
        });

        $appuntamentiRaggruppati = [];
        foreach ($appuntamentiGiorno as $appuntamento) {
            $ora = (new DateTime($appuntamento['data_appuntamento']))->format('H:i');
            $cliente = $appuntamento['nome_cliente'];
            $chiave = "$ora-$cliente";

            if (!isset($appuntamentiRaggruppati[$chiave])) {
                $appuntamentiRaggruppati[$chiave] = [];
            }
            $appuntamentiRaggruppati[$chiave][] = $appuntamento;
        }

        foreach ($appuntamentiRaggruppati as $chiave => $listaAppuntamenti) {
            [$ora, $cliente] = explode('-', $chiave);

            // Calcola la somma dei tempi di servizio per il cliente e l'orario
            $tempoTotale = array_reduce($listaAppuntamenti, function ($carry, $item) {
                return $carry + $item['tempo_servizio'];
            }, 0);

            echo "<li class='list-group-item d-flex justify-content-between align-items-center font-weight-bold text-dark'>$ora - $cliente <span class='badge badge-warning'>$tempoTotale min</span></li>";
            echo "<ul class='list-group'>";
            foreach ($listaAppuntamenti as $appuntamento) {
                $icona = $appuntamento['completato'] == 0 ? '<span class="icon"><i class="fal fa-clock text-primary"></i></span>' : '<span class="icon"><i class="fal fa-check text-success"></i></span>';
                $coloreAppuntamento = $appuntamento['completato'] == 0 ? 'font-weight-normal text-primary' : 'font-weight-normal text-grey';
                $ora_appuntamento = (new DateTime($appuntamento['data_appuntamento']))->format('H:i');
                $nome_servizio = htmlspecialchars($appuntamento['nome_servizio']);
                $tempo_servizio = htmlspecialchars($appuntamento['tempo_servizio']);
                $id_appuntamento = $appuntamento['id_appuntamento'];
                echo "<li class='appuntamento list-group-item border-0 " . $coloreAppuntamento . " appointment-item' data-id='$id_appuntamento' data-cliente='$cliente' data-ora='$ora_appuntamento' data-servizio='$nome_servizio'>" . $icona . " <span class='appointment-text'>$nome_servizio <i>($tempo_servizio min)</i></span></li>";
            }
            echo "</ul>";
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
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC); ?>
<link href="custom.css" rel="stylesheet">

<body id="page-top">
    <div id="wrapper"><?php include (BASE_PATH . "/components/navbar.php"); ?>
        <div class="d-flex flex-column mb-2" id="content-wrapper">
            <div id="content"><?php include (BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid"><?php require_once BASE_PATH . "/utils/alerts.php"; ?>
                    <div class="align-items-center justify-content-between">
                        <div class="card mb-4">
                            <div class="align-items-center justify-content-between card-body d-flex"><a
                                    class="btn btn-indigo btn-circle"
                                    href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=prev"><i
                                        class="far fa-chevron-left"></i> </a><span class="text-center">
                                    <h4><b>Settimana <?php echo $numero_settimana; ?></b></h4>
                                </span><a class="btn btn-indigo btn-circle"
                                    href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=next"><i
                                        class="far fa-chevron-right"></i></a></div>
                        </div><?php generaVistaSettimana($anno, $mese, $giorno, $appuntamenti); ?><button
                            class="btn btn-indigo btn-lg floating-btn" data-target="#newAppointmentModal"
                            data-toggle="modal"><i class="fa fa-plus"></i></button>
                    </div>
                </div>
            </div><?php include ("modals/modals.php") ?>
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


                        const dataAppuntamento = new Date(data.data_appuntamento);
                        const dataFormat = `${dataAppuntamento.getDate()}/${dataAppuntamento.getMonth() + 1}/${dataAppuntamento.getFullYear()}`;
                        const oraFormat = dataAppuntamento.toTimeString().substring(0, 5);

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
                });</script><?php include (BASE_PATH . "/components/footer.php"); ?>
        </div>
    </div><?php include (BASE_PATH . "/components/scripts.php"); ?>
</body>