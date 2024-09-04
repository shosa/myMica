<?php
include("../../config/config.php");
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

$clienti = $pdo->query("SELECT * FROM clienti")->fetchAll(PDO::FETCH_ASSOC);
$servizi = $pdo->query("SELECT * FROM servizi")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_cliente = $_POST['id_cliente'];
    $id_servizio = $_POST['id_servizio'];
    $tempo_servizio = $_POST['tempo_servizio'] ?: null;
    $data_appuntamento = $_POST['data_appuntamento'];
    $ora_appuntamento = $_POST['ora_appuntamento'];

    $data_completa = "$data_appuntamento $ora_appuntamento";

    if (!$tempo_servizio) {
        $stmt = $pdo->prepare("SELECT tempo_medio FROM servizi WHERE id_servizio = ?");
        $stmt->execute([$id_servizio]);
        $tempo_servizio = $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("INSERT INTO appuntamenti (id_cliente, id_servizio, data_appuntamento, tempo_servizio) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_cliente, $id_servizio, $data_completa, $tempo_servizio]);
    $_SESSION["success"] = "Appuntamento Inserito!";
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
    for ($i = 0; $i < 7; $i++) {
        $giorni_settimana[] = clone $inizio_settimana;
        $inizio_settimana->modify('+1 day');
    }

    echo "<h5 class='text-center mt-2 mb-2'>Dal " . $formatter->format($giorni_settimana[0]) . "</h5><h5 class='text-center mb-2 '> Al " . $formatter->format($giorni_settimana[6]) . "</h5>";

    echo '<div class="row row-cols-1 row-cols-md-12">';
    foreach ($giorni_settimana as $giorno) {
        $nomeGiorno = $formatterNomeGiorno->format($giorno);

        // Determina le classi in base ai giorni della settimana
        $class = '';
        if ($nomeGiorno === 'sabato') {
            $class .= 'border rounded border-info ';
        }
        if ($nomeGiorno === 'domenica') {
            $class .= 'border rounded border-danger ';
        }

        echo '<div class="col">';
        echo '<div class="card mt-1">';
        echo '<div class="card-body ' . htmlspecialchars(trim($class)) . '">';
        echo '<h5 class="card-title text-dark font-weight-bold">' . $formatterCard->format($giorno) . '</h5>';
        echo '<ul class="list-group list-group-flush">';
        foreach ($appuntamenti as $appuntamento) {
            $icona = '';
            $coloreAppuntamento = '';
            if ($appuntamento['completato'] == 0) { // Confronto non rigoroso
                $icona = '<span class="icon"><i class="fal fa-clock text-primary"></i></span>';
                $coloreAppuntamento = 'font-weight-normal text-primary';
            }
            if ($appuntamento['completato'] == 1) {
                $icona = '<span class="icon"><i class="fal fa-check text-success"></i></span>';
                $coloreAppuntamento = 'font-weight-normal text-grey';
            }
            $data_appuntamento = new DateTime($appuntamento['data_appuntamento']);
            if ($data_appuntamento->format('Y-m-d') === $giorno->format('Y-m-d')) {
                $ora_appuntamento = $data_appuntamento->format('H:i');
                $nome_cliente = htmlspecialchars($appuntamento['nome_cliente']);
                $nome_servizio = htmlspecialchars($appuntamento['nome_servizio']);
                $id_appuntamento = $appuntamento['id_appuntamento'];
                echo "<li class='appuntamento list-group-item " . $coloreAppuntamento . " appointment-item' data-id='$id_appuntamento' data-cliente='$nome_cliente' data-ora='$ora_appuntamento' data-servizio='$nome_servizio'>"
                    . $icona .
                    " <span class='appointment-text'>$ora_appuntamento - $nome_cliente |<i> $nome_servizio </i></span></li>";
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

$stmt = $pdo->prepare("SELECT a.id_appuntamento, c.nome_cliente, s.nome_servizio, a.data_appuntamento, a.tempo_servizio , a.completato
                       FROM appuntamenti a
                       JOIN clienti c ON a.id_cliente = c.id_cliente
                       JOIN servizi s ON a.id_servizio = s.id_servizio
                       WHERE DATE(a.data_appuntamento) BETWEEN ? AND ?
                       ORDER BY a.data_appuntamento ASC");
$stmt->execute([$inizio_settimana, $fine_settimana]);
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="custom.css">

<body id="page-top">
    <div id="wrapper">
        <?php include(BASE_PATH . "/components/navbar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column mb-2">
            <div id="content">
                <?php include(BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid">
                    <?php require_once BASE_PATH . "/utils/alerts.php"; ?>
                    <div class="align-items-center justify-content-between ">
                        <div class="card mb-4">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <a href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=prev"
                                    class="btn btn-indigo btn-circle">
                                    <i class="far fa-chevron-left"></i>
                                </a>
                                <span class="text-center">
                                    <h4><b>Settimana <?php echo $numero_settimana; ?></b></h4>

                                </span>
                                <a href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=next"
                                    class="btn btn-indigo btn-circle">
                                    <i class="far fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                        <?php generaVistaSettimana($anno, $mese, $giorno, $appuntamenti); ?>

                        <!-- Modale per il nuovo appuntamento -->
                        <button class="btn btn-lg btn-indigo floating-btn" data-toggle="modal"
                            data-target="#newAppointmentModal"><i class="fa fa-plus"></i></button>

                    </div>
                </div>
            </div>
            <!-- Modale per nuovo appuntamento -->
            <div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-labelledby="newAppointmentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newAppointmentModalLabel">Nuovo Appuntamento</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="appointmentForm"
                                action="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>"
                                method="POST">

                                <div class="mb-3">
                                    <label for="search_cliente" class="form-label">Cliente</label>
                                    <div class="input-group">
                                        <input type="text" name="cliente" id="search_cliente" class="form-control"
                                            placeholder="Cerca cliente..." required>
                                        <div class="input-group-append">
                                            <button class="btn btn-success" type="button" id="addClienteBtn"
                                                data-toggle="modal" data-target="#newClienteModal"><i
                                                    class="fal fa-plus fa-s"></i></button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="id_cliente" id="id_cliente" required>
                                    <div id="suggestions" class="list-group"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="id_servizio" class="form-label">Servizio</label>
                                    <select name="id_servizio" id="id_servizio" class="form-select form-control"
                                        required>
                                        <option value="">Seleziona un servizio</option>
                                        <?php foreach ($servizi as $servizio): ?>
                                            <option value="<?php echo $servizio['id_servizio']; ?>">
                                                <?php echo htmlspecialchars($servizio['nome_servizio']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tempo_servizio" class="form-label">Tempo Servizio (minuti,
                                        opzionale)</label>
                                    <input type="number" name="tempo_servizio" id="tempo_servizio" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="data_appuntamento" class="form-label">Data</label>
                                    <input type="date" name="data_appuntamento" id="data_appuntamento"
                                        class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="ora_appuntamento" class="form-label">Ora</label>
                                    <input type="time" name="ora_appuntamento" id="ora_appuntamento"
                                        class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-lg btn-block btn-primary">Crea
                                    Appuntamento</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modale per nuovo cliente -->
            <div class="modal fade blur" id="newClienteModal" tabindex="-1" aria-labelledby="newClienteModalLabel"
                aria-hidden="true" style="background: rgba(0, 0, 0, 0.35) !important;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newClienteModalLabel">Nuovo Cliente</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="newClienteForm">
                                <div class="mb-3">
                                    <label for="nome_cliente" class="form-label">Nome Cliente</label>
                                    <input type="text" name="nome_cliente" id="nome_cliente" class="form-control"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="telefono_cliente" class="form-label">Telefono</label>
                                    <input type="text" name="telefono_cliente" id="telefono_cliente"
                                        class="form-control">
                                </div>
                                <button type="submit" class="btn btn-success btn-lg  btn-block">Salva Cliente</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modale per i dettagli dell'appuntamento -->
            <div class="modal fade" id="appointmentDetailsModal" tabindex="-1"
                aria-labelledby="appointmentDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="appointmentDetailsModalLabel">APPUNTAMENTO <span
                                    class="text-indigo font-weight-bold" id="detail_id_appuntamento"></span> </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p><strong>CLIENTE: </strong> <span id="detail_nome_cliente"></span></p>
                            <p><strong>SERVIZIO: </strong> <span id="detail_nome_servizio"></span></p>
                            <p><strong>DATA: </strong> <span id="detail_data_appuntamento"></span></p>
                            <p><strong>ORA: </strong> <span class="mr-4" id="detail_ora_appuntamento"></span><span
                                    class="h5 font-weight-bold text-white p-1 rounded"
                                    style="margin-left:20% !important;" id="detail_stato"></span>
                            </p>

                            <hr>
                            <div class="mt-3 align-items-center text-center">
                                <a id="whatsappLink"
                                    class="btn btn-light border border-success text-success btn-lg shadow btn-circle mr-2"
                                    target="_blank"><i class="fa-brands fa-whatsapp "></i></a>
                                <button class="btn btn-primary btn-lg shadow btn-circle mr-2 "
                                    id="editAppointmentBtn"><i class="fa fa-pencil-alt"></i></button>
                                <button class="btn btn-danger btn-lg shadow btn-circle mr-4"
                                    id="deleteAppointmentBtn"><i class="fa fa-trash"></i></button>


                                <button class="btn btn-success btn-lg shadow btn-circle ml-4"
                                    id="completeAppointmentBtn"><i class="fa fa-check"></i></button>
                                <!-- Pulsante Completa -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.getElementById('search_cliente').addEventListener('input', function () {
                    const searchTerm = this.value;

                    if (searchTerm.length > 2) {
                        fetch(`search_clienti.php?term=${searchTerm}`)
                            .then(response => response.json())
                            .then(data => {
                                const suggestions = document.getElementById('suggestions');
                                suggestions.innerHTML = '';
                                data.forEach(cliente => {
                                    const suggestionItem = document.createElement('a');
                                    suggestionItem.href = '#';
                                    suggestionItem.className = 'list-group-item list-group-item-action';
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

                    fetch('salva_cliente.php', {
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

                            fetch(`get_appointment_details.php?id_appuntamento=${idAppuntamento}`)
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
                        document.getElementById('detail_stato').classList.remove(data.completato == 1 ? "bg-dark" : "bg-success");
                        document.getElementById('detail_stato').classList.add(data.completato == 1 ? "bg-success" : "bg-dark");


                        const dataAppuntamento = new Date(data.data_appuntamento);
                        const dataFormat = `${dataAppuntamento.getDate()}/${dataAppuntamento.getMonth() + 1}/${dataAppuntamento.getFullYear()}`;
                        const oraFormat = dataAppuntamento.toTimeString().substring(0, 5);

                        document.getElementById('detail_data_appuntamento').textContent = dataFormat;
                        document.getElementById('detail_ora_appuntamento').textContent = oraFormat;

                        const messaggio = `Ciao ti ricordo l'appuntamento del ${dataFormat} alle ${oraFormat}`;
                        const whatsappLink = `https://api.whatsapp.com/send?phone=39${data.telefono_cliente}&text=${encodeURIComponent(messaggio)}`;

                        document.getElementById('whatsappLink').href = whatsappLink;

                        document.getElementById('editAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;
                        document.getElementById('deleteAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;
                        document.getElementById('completeAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;

                        if (data.completato == 1) {
                            document.getElementById('editAppointmentBtn').disabled = true;
                            document.getElementById('deleteAppointmentBtn').disabled = true;
                            document.getElementById('completeAppointmentBtn').disabled = true;
                        } else {
                            document.getElementById('editAppointmentBtn').disabled = false;
                            document.getElementById('deleteAppointmentBtn').disabled = false;
                            document.getElementById('completeAppointmentBtn').disabled = false;
                        }
                    }

                    // Gestione della modifica dell'appuntamento
                    document.getElementById('editAppointmentBtn').addEventListener('click', function () {
                        const idAppuntamento = this.dataset.idAppuntamento;
                        window.location.href = `modifica_appuntamento.php?id_appuntamento=${idAppuntamento}`;
                    });

                    // Gestione della cancellazione dell'appuntamento
                    document.getElementById('deleteAppointmentBtn').addEventListener('click', function () {
                        const idAppuntamento = this.dataset.idAppuntamento;

                        if (confirm('Sei sicuro di voler cancellare questo appuntamento?')) {
                            fetch(`delete_appointment.php?id_appuntamento=${idAppuntamento}`, { method: 'DELETE' })
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
                                fetch('complete_appointment.php', {
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
                                                fetch(`get_appointment_details.php?id_appuntamento=${idAppuntamento}`)
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
                });



            </script>

            <?php include(BASE_PATH . "/components/footer.php"); ?>
        </div>
    </div>

    <?php include(BASE_PATH . "/components/scripts.php"); ?>

</body>

</html>