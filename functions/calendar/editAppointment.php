<?php
include("../../config/config.php");
session_start();
$pdo = getDbInstance();
include(BASE_PATH . "/components/header.php");

$id_appuntamento = $_GET['id_appuntamento'] ?? null;

if (!$id_appuntamento) {
    die("ID appuntamento mancante.");
}

// Recupera i dettagli dell'appuntamento
$stmt = $pdo->prepare("SELECT a.id_appuntamento, a.id_cliente, a.id_servizio, a.data_appuntamento, a.tempo_servizio, c.nome_cliente, s.nome_servizio
                       FROM appuntamenti a
                       JOIN clienti c ON a.id_cliente = c.id_cliente
                       JOIN servizi s ON a.id_servizio = s.id_servizio
                       WHERE a.id_appuntamento = ?");
$stmt->execute([$id_appuntamento]);
$appuntamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appuntamento) {
    die("Appuntamento non trovato.");
}

// Recupera i clienti e servizi
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

    $stmt = $pdo->prepare("UPDATE appuntamenti 
                           SET id_cliente = ?, id_servizio = ?, data_appuntamento = ?, tempo_servizio = ?
                           WHERE id_appuntamento = ?");
    $stmt->execute([$id_cliente, $id_servizio, $data_completa, $tempo_servizio, $id_appuntamento]);
    $_SESSION["success"]="Appuntamento aggiornato!";
    header("Location: calendario.php?anno=" . date('Y') . "&mese=" . date('n') . "&giorno=" . date('j'));
    exit;
}
?>

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
                            <div class="card-body align-items-center">
                                <div class="mb-3">
                                    <form
                                        action="editAppointment?id_appuntamento=<?php echo $id_appuntamento; ?>"
                                        method="POST">
                                        <label for="id_cliente" class="form-label">Cliente</label>
                                        <select name="id_cliente" id="id_cliente" class="form-control" required>
                                            <?php foreach ($clienti as $cliente): ?>
                                                <option value="<?php echo $cliente['id_cliente']; ?>" <?php if ($cliente['id_cliente'] == $appuntamento['id_cliente'])
                                                       echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($cliente['nome_cliente']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                </div>

                                <div class="mb-3">
                                    <label for="id_servizio" class="form-label">Servizio</label>
                                    <select name="id_servizio" id="id_servizio" class="form-control" required>
                                        <?php foreach ($servizi as $servizio): ?>
                                            <option value="<?php echo $servizio['id_servizio']; ?>" <?php if ($servizio['id_servizio'] == $appuntamento['id_servizio'])
                                                   echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($servizio['nome_servizio']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="tempo_servizio" class="form-label">Tempo Servizio (minuti)</label>
                                    <input type="number" name="tempo_servizio" id="tempo_servizio" class="form-control"
                                        value="<?php echo htmlspecialchars($appuntamento['tempo_servizio']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="data_appuntamento" class="form-label">Data</label>
                                    <input type="date" name="data_appuntamento" id="data_appuntamento"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars(substr($appuntamento['data_appuntamento'], 0, 10)); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="ora_appuntamento" class="form-label">Ora</label>
                                    <input type="time" name="ora_appuntamento" id="ora_appuntamento"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars(substr($appuntamento['data_appuntamento'], 11, 5)); ?>">
                                </div>

                                <button type="submit" class="btn btn-block btn-primary">Aggiorna Appuntamento</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include(BASE_PATH . "/components/footer.php"); ?>
            </div>
        </div>

        <?php include(BASE_PATH . "/components/scripts.php"); ?>
    </div>
</body>