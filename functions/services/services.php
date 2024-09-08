<?php
include ("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include (BASE_PATH . "/components/header.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['add_servizio'])) {
        // Aggiungi nuovo servizio
        $nome_servizio = $_POST['nome_servizio'];
        $costo = $_POST['costo'];
        $tempo_medio = $_POST['tempo_medio'];

        $stmt = $pdo->prepare("INSERT INTO servizi (nome_servizio, costo, tempo_medio) VALUES (?, ?, ?)");
        $stmt->execute([$nome_servizio, $costo, $tempo_medio]);
        header("Location: services.php");
        exit;
    } elseif (isset($_POST['update_servizio'])) {
        // Modifica servizio esistente
        $id_servizio = $_POST['id_servizio'];
        $nome_servizio = $_POST['nome_servizio'];
        $tempo_medio = $_POST['tempo_medio'];
        $costo = $_POST['costo'];

        $stmt = $pdo->prepare("UPDATE servizi SET nome_servizio = ?, costo = ?, tempo_medio = ? WHERE id_servizio = ?");
        $stmt->execute([$nome_servizio, $costo, $tempo_medio, $id_servizio]);
        header("Location: services.php");
        exit;
    } elseif (isset($_POST['delete_servizio'])) {
        // Elimina servizio
        $id_servizio = $_POST['id_servizio'];

        $stmt = $pdo->prepare("DELETE FROM servizi WHERE id_servizio = ?");
        $stmt->execute([$id_servizio]);
        header("Location: services.php");
        exit;
    }
}

$servizi = $pdo->query("SELECT * FROM servizi ORDER BY nome_servizio ASC")->fetchAll(PDO::FETCH_ASSOC);

// Recupera i clienti associati a ciascun servizio
$clientiPerServizio = [];
$sqlClienti = "
    SELECT 
        s.id_servizio,
        c.id_cliente,
        c.nome_cliente,
        a.data_appuntamento
    FROM appuntamenti a
    JOIN servizi s ON a.id_servizio = s.id_servizio
    JOIN clienti c ON a.id_cliente = c.id_cliente
    WHERE a.completato = 0
    ORDER BY s.id_servizio ASC, a.data_appuntamento ASC, c.nome_cliente ASC
";
$stmtClienti = $pdo->prepare($sqlClienti);
$stmtClienti->execute();
$risultatiClienti = $stmtClienti->fetchAll(PDO::FETCH_ASSOC);

foreach ($risultatiClienti as $cliente) {
    $id_servizio = $cliente['id_servizio'];
    $nome_cliente = $cliente['nome_cliente'];
    $data_appuntamento = $ora = date('d-m-y H:i', strtotime($cliente['data_appuntamento']));

    if (!isset($clientiPerServizio[$id_servizio])) {
        $clientiPerServizio[$id_servizio] = [];
    }
    $clientiPerServizio[$id_servizio][] = [
        'nome_cliente' => $nome_cliente,
        'data_appuntamento' => $data_appuntamento
    ];
}
?>

<body>
    <div id="wrapper">
        <?php include (BASE_PATH . "/components/navbar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include (BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Gestione Servizi</h1>
                    <!-- Tabella Servizi -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <button class="btn btn-primary btn-block" data-toggle="modal"
                                data-target="#addServizioModal">Aggiungi Servizio</button>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome Servizio</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servizi as $servizio): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                // Verifica se il servizio ha clienti associati
                                                $hasClienti = isset($clientiPerServizio[$servizio['id_servizio']]);
                                                if ($hasClienti):
                                                    ?>
                                                    <a href="#" class="servizio-link"
                                                        data-id="<?php echo $servizio['id_servizio']; ?>">
                                                        <?php echo htmlspecialchars($servizio['nome_servizio']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($servizio['nome_servizio']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-circle btn-secondary btn-sm dropdown" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    <i class="fal fa-ellipsis-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <a class="dropdown-item" href="#" data-toggle="modal"
                                                        data-target="#editServizioModal"
                                                        data-id="<?php echo $servizio['id_servizio']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($servizio['nome_servizio']); ?>"
                                                        data-tempo="<?php echo htmlspecialchars($servizio['tempo_medio']); ?>"
                                                        data-prezzo="<?php echo htmlspecialchars($servizio['costo']); ?>">
                                                        <i class="fal fa-pencil"></i> Modifica
                                                    </a>
                                                    <a class="dropdown-item" href="#" data-toggle="modal"
                                                        data-target="#deleteServizioModal"
                                                        data-id="<?php echo $servizio['id_servizio']; ?>">
                                                        <i class="fal fa-trash"></i> Elimina
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Clienti Servizio -->
        <div class="modal fade" id="clientiServizioModal" tabindex="-1" aria-labelledby="clientiServizioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clientiServizioModalLabel">Clienti per Servizio</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="clientiServizioList"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Aggiungi Servizio -->
        <div class="modal fade" id="addServizioModal" tabindex="-1" aria-labelledby="addServizioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addServizioModalLabel">Aggiungi Servizio</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="services.php" method="POST">
                            <div class="mb-3">
                                <label for="nome_servizio" class="form-label">Nome Servizio</label>
                                <input type="text" name="nome_servizio" id="nome_servizio" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="tempo_medio" class="form-label">Tempo Medio</label>
                                <input type="number" name="tempo_medio" id="tempo_medio" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="costo" class="form-label">Prezzo</label>
                                <input type="number" name="costo" id="costo" class="form-control" required>
                            </div>
                            <button type="submit" name="add_servizio" class="btn btn-block btn-primary">Aggiungi
                                Servizio</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Modifica Servizio -->
        <div class="modal fade" id="editServizioModal" tabindex="-1" aria-labelledby="editServizioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editServizioModalLabel">Modifica Servizio</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="services.php" method="POST">
                            <input type="hidden" name="id_servizio" id="edit_id_servizio">
                            <div class="mb-3">
                                <label for="edit_nome_servizio" class="form-label">Nome Servizio</label>
                                <input type="text" name="nome_servizio" id="edit_nome_servizio" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_tempo_medio" class="form-label">Tempo Medio</label>
                                <input type="number" name="tempo_medio" id="edit_tempo_medio" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_costo" class="form-label">Prezzo</label>
                                <input type="number" name="costo" id="edit_costo" class="form-control" required>
                            </div>
                            <button type="submit" name="update_servizio" class="btn btn-warning btn-block">Aggiorna
                                Servizio</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Elimina Servizio -->
        <div class="modal fade" id="deleteServizioModal" tabindex="-1" aria-labelledby="deleteServizioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteServizioModalLabel">Elimina Servizio</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="services.php" method="POST">
                            <input type="hidden" name="id_servizio" id="delete_id_servizio">
                            <p>Sei sicuro di voler eliminare questo servizio?</p>
                            <button type="submit" name="delete_servizio" class="btn btn-danger">Elimina</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include (BASE_PATH . "/components/footer.php"); ?>
    </div>
    <?php include (BASE_PATH . "/components/scripts.php"); ?>

    <script>
        // Imposta i dati per la modale di modifica
        $('#editServizioModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var nome = button.data('nome');
            var tempo = button.data('tempo');
            var prezzo = button.data('prezzo');

            var modal = $(this);
            modal.find('#edit_id_servizio').val(id);
            modal.find('#edit_nome_servizio').val(nome);
            modal.find('#edit_tempo_medio').val(tempo);
            modal.find('#edit_costo').val(prezzo);
        });

        // Imposta i dati per la modale di eliminazione
        $('#deleteServizioModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');

            var modal = $(this);
            modal.find('#delete_id_servizio').val(id);
        });
        $('.servizio-link').on('click', function (event) {
            event.preventDefault();
            var servizioId = $(this).data('id');
            var clienti = <?php echo json_encode($clientiPerServizio); ?>;

            if (clienti[servizioId]) {
                var clientiHtml = clienti[servizioId].map(function (cliente) {
                    return '<li><span class="text-indigo font-weight-bold">' + cliente.nome_cliente + '</span> - ' + cliente.data_appuntamento + '</li>';
                }).join('');

                $('#clientiServizioList').html('<ul>' + clientiHtml + '</ul>');
            } else {
                $('#clientiServizioList').html('<p>Nessun cliente trovato.</p>');
            }

            $('#clientiServizioModal').modal('show');
        });
    </script>
</body>

</html>