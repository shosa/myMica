<?php
include("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include(BASE_PATH . "/components/header.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['add_servizio'])) {
        // Aggiungi nuovo servizio
        $nome_servizio = $_POST['nome_servizio'];
        $costo = $_POST['costo'];

        $stmt = $pdo->prepare("INSERT INTO servizi (nome_servizio, costo) VALUES (?, ?)");
        $stmt->execute([$nome_servizio, $costo]);
        header("Location: services.php");
        exit;
    } elseif (isset($_POST['update_servizio'])) {
        // Modifica servizio esistente
        $id_servizio = $_POST['id_servizio'];
        $nome_servizio = $_POST['nome_servizio'];
        $costo = $_POST['costo'];

        $stmt = $pdo->prepare("UPDATE servizi SET nome_servizio = ?, costo = ? WHERE id_servizio = ?");
        $stmt->execute([$nome_servizio, $costo, $id_servizio]);
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

$servizi = $pdo->query("SELECT * FROM servizi")->fetchAll(PDO::FETCH_ASSOC);
?>


<body>
    <div id="wrapper">
        <?php include(BASE_PATH . "/components/navbar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Gestione Servizi</h1>
                    <!-- Tabella Servizi -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <button class="btn btn-primary btn-block" data-toggle="modal"
                                data-target="#addServizioModal">Aggiungi
                                Servizio</button>
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

                                            <td><?php echo htmlspecialchars($servizio['nome_servizio']); ?></td>

                                            <td class="text-center">
                                                <button class="btn  btn-warning btn-sm" data-toggle="modal"
                                                    data-target="#editServizioModal"
                                                    data-id="<?php echo $servizio['id_servizio']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($servizio['nome_servizio']); ?>"
                                                    data-prezzo="<?php echo htmlspecialchars($servizio['costo']); ?>"><i
                                                        class="fal fa-pencil"></i></button>
                                                <button class="btn  btn-danger btn-sm" data-toggle="modal"
                                                    data-target="#deleteServizioModal"
                                                    data-id="<?php echo $servizio['id_servizio']; ?>"><i
                                                        class="fal fa-trash"></i></button>
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

        <!-- Modale Aggiungi Servizio -->
        <div class="modal fade" id="addServizioModal" tabindex="-1" aria-labelledby="addServizioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
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
                                <label for="costo" class="form-label">Prezzo</label>
                                <input type="text" name="costo" id="costo" class="form-control" required>
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editServizioModalLabel">Modifica Servizio</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
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
                                <label for="edit_costo" class="form-label">Prezzo</label>
                                <input type="text" name="costo" id="edit_costo" class="form-control" required>
                            </div>
                            <button type="submit" name="update_servizio" class="btn btn-warning">Aggiorna
                                Servizio</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Elimina Servizio -->
        <div class="modal fade" id="deleteServizioModal" tabindex="-1" aria-labelledby="deleteServizioModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteServizioModalLabel">Elimina Servizio</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
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
    <?php include(BASE_PATH . "/components/footer.php"); ?>
    </div>
    <?php include(BASE_PATH . "/components/scripts.php"); ?>

    <script>
        // Imposta i dati per la modale di modifica
        $('#editServizioModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var nome = button.data('nome');
            var prezzo = button.data('prezzo');

            var modal = $(this);
            modal.find('#edit_id_servizio').val(id);
            modal.find('#edit_nome_servizio').val(nome);
            modal.find('#edit_costo').val(prezzo);
        });

        // Imposta i dati per la modale di eliminazione
        $('#deleteServizioModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');

            var modal = $(this);
            modal.find('#delete_id_servizio').val(id);
        });
    </script>
</body>

</html>