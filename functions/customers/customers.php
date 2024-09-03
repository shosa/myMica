<?php
include("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include(BASE_PATH . "/components/header.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['add_cliente'])) {
        // Aggiungi nuovo cliente
        $nome_cliente = $_POST['nome_cliente'];
        $numero_telefono = $_POST['numero_telefono'];

        $stmt = $pdo->prepare("INSERT INTO clienti (nome_cliente, numero_telefono) VALUES (?, ?)");
        $stmt->execute([$nome_cliente, $numero_telefono]);
        header("Location: customers.php");
        exit;
    } elseif (isset($_POST['update_cliente'])) {
        // Modifica cliente esistente
        $id_cliente = $_POST['id_cliente'];
        $nome_cliente = $_POST['nome_cliente'];
        $numero_telefono = $_POST['numero_telefono'];

        $stmt = $pdo->prepare("UPDATE clienti SET nome_cliente = ?, numero_telefono = ? WHERE id_cliente = ?");
        $stmt->execute([$nome_cliente, $numero_telefono, $id_cliente]);
        header("Location: customers.php");
        exit;
    } elseif (isset($_POST['delete_cliente'])) {
        // Elimina cliente
        $id_cliente = $_POST['id_cliente'];

        $stmt = $pdo->prepare("DELETE FROM clienti WHERE id_cliente = ?");
        $stmt->execute([$id_cliente]);
        header("Location: customers.php");
        exit;
    }
}

// Recupera e ordina i clienti
$clienti = $pdo->query("SELECT * FROM clienti ORDER BY nome_cliente")->fetchAll(PDO::FETCH_ASSOC);
?>

<body>
    <div id="wrapper">
        <?php include(BASE_PATH . "/components/navbar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Gestione Clienti</h1>

                    <!-- Campo di ricerca -->
                    <div class="mb-4">
                        <input type="text" id="searchClient" class="form-control" placeholder="Cerca cliente...">
                    </div>

                    <!-- Tabella Clienti -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <button class="btn btn-success btn-block" data-toggle="modal"
                                data-target="#addClienteModal">Aggiungi Cliente</button>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover table-striped table-bordered" id="clientTable">
                                <thead>
                                    <tr>

                                        <th>Nome</th>

                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clienti as $cliente): ?>
                                        <tr data-name="<?php echo htmlspecialchars($cliente['nome_cliente']); ?>">

                                            <td><?php echo htmlspecialchars($cliente['nome_cliente']); ?></td>

                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-circle btn-secondary btn-sm dropdown"
                                                        type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fal fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item edit-btn" href="#" data-toggle="modal"
                                                            data-target="#editClienteModal"
                                                            data-id="<?php echo $cliente['id_cliente']; ?>"
                                                            data-nome="<?php echo htmlspecialchars($cliente['nome_cliente']); ?>"
                                                            data-telefono="<?php echo htmlspecialchars($cliente['numero_telefono']); ?>"><i
                                                                class="fal fa-pencil"></i> Modifica</a>
                                                        <a class="dropdown-item delete-btn" href="#" data-toggle="modal"
                                                            data-target="#deleteClienteModal"
                                                            data-id="<?php echo $cliente['id_cliente']; ?>"><i
                                                                class="fal fa-trash"></i> Elimina</a>
                                                    </div>
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

        <!-- Modale Aggiungi Cliente -->
        <div class="modal fade" id="addClienteModal" tabindex="-1" aria-labelledby="addClienteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addClienteModalLabel">Aggiungi Cliente</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="customers.php" method="POST">
                            <div class="mb-3">
                                <label for="nome_cliente" class="form-label">Nome Cliente</label>
                                <input type="text" name="nome_cliente" id="nome_cliente" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="numero_telefono" class="form-label">Numero di Telefono</label>
                                <input type="text" name="numero_telefono" id="numero_telefono" class="form-control"
                                    required>
                            </div>
                            <button type="submit" name="add_cliente" class="btn btn-primary">Aggiungi Cliente</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Modifica Cliente -->
        <div class="modal fade" id="editClienteModal" tabindex="-1" aria-labelledby="editClienteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editClienteModalLabel">Modifica Cliente</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="customers.php" method="POST">
                            <input type="hidden" name="id_cliente" id="edit_id_cliente">
                            <div class="mb-3">
                                <label for="edit_nome_cliente" class="form-label">Nome Cliente</label>
                                <input type="text" name="nome_cliente" id="edit_nome_cliente" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_numero_telefono" class="form-label">Numero di Telefono</label>
                                <input type="text" name="numero_telefono" id="edit_numero_telefono" class="form-control"
                                    required>
                            </div>
                            <button type="submit" name="update_cliente" class="btn btn-warning">Aggiorna
                                Cliente</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Elimina Cliente -->
        <div class="modal fade" id="deleteClienteModal" tabindex="-1" aria-labelledby="deleteClienteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteClienteModalLabel">Elimina Cliente</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="customers.php" method="POST">
                            <input type="hidden" name="id_cliente" id="delete_id_cliente">
                            <p>Sei sicuro di voler eliminare questo cliente?</p>
                            <button type="submit" name="delete_cliente" class="btn btn-danger">Elimina</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include(BASE_PATH . "/components/footer.php"); ?>

    <?php include(BASE_PATH . "/components/scripts.php"); ?>

    <script>
        // Imposta i dati per la modale di modifica
        $('#editClienteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var nome = button.data('nome');
            var telefono = button.data('telefono');

            var modal = $(this);
            modal.find('#edit_id_cliente').val(id);
            modal.find('#edit_nome_cliente').val(nome);
            modal.find('#edit_numero_telefono').val(telefono);
        });

        // Imposta i dati per la modale di eliminazione
        $('#deleteClienteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');

            var modal = $(this);
            modal.find('#delete_id_cliente').val(id);
        });

        // Ricerca clienti
        $('#searchClient').on('keyup', function () {
            var searchValue = $(this).val().toLowerCase();
            $('#clientTable tbody tr').each(function () {
                var name = $(this).data('name').toLowerCase();
                if (name.indexOf(searchValue) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    </script>
</body>

</html>