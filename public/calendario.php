<?php
require 'config/config.php';
require 'header.php';

setlocale(LC_TIME, 'it_IT.UTF-8');

function trovaPrimoSlotDisponibile($pdo, $data, $durata_servizio) {
    $giorno_settimana = date('N', strtotime($data));
    
    $stmt = $pdo->prepare("SELECT * FROM impostazioni_calendario WHERE giorno_settimana = ?");
    $stmt->execute([$giorno_settimana]);
    $impostazione = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$impostazione) {
        return null;
    }

    $ora_inizio = new DateTime($impostazione['ora_inizio']);
    $ora_fine = new DateTime($impostazione['ora_fine']);
    
    $stmt = $pdo->prepare("SELECT data_appuntamento, tempo_servizio FROM appuntamenti WHERE DATE(data_appuntamento) = ? ORDER BY data_appuntamento ASC");
    $stmt->execute([$data]);
    $appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appuntamenti as $appuntamento) {
        $inizio_appuntamento = new DateTime($appuntamento['data_appuntamento']);
        $fine_appuntamento = clone $inizio_appuntamento;
        $fine_appuntamento->modify("+{$appuntamento['tempo_servizio']} minutes");

        if ($ora_inizio < $inizio_appuntamento && $ora_inizio->diff($inizio_appuntamento)->i >= $durata_servizio) {
            return $ora_inizio->format('Y-m-d H:i:s');
        }

        $ora_inizio = max($ora_inizio, $fine_appuntamento);
    }

    if ($ora_inizio->diff($ora_fine)->i >= $durata_servizio) {
        return $ora_inizio->format('Y-m-d H:i:s');
    }

    return null;
}

$pdo = connectDB();

$anno = isset($_GET['anno']) ? (int)$_GET['anno'] : date('Y');
$mese = isset($_GET['mese']) ? (int)$_GET['mese'] : date('n');
$giorno = isset($_GET['giorno']) ? (int)$_GET['giorno'] : date('j');

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

$slot_suggerito = null;
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

    $slot_suggerito = trovaPrimoSlotDisponibile($pdo, $data_appuntamento, $tempo_servizio);

    $stmt = $pdo->prepare("INSERT INTO appuntamenti (id_cliente, id_servizio, data_appuntamento, tempo_servizio) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_cliente, $id_servizio, $data_completa, $tempo_servizio]);
    header("Location: calendario.php?anno=$anno&mese=$mese&giorno=$giorno");
    exit;
}

function generaVistaSettimana($anno, $mese, $giorno, $appuntamenti) {
    $inizio_settimana = new DateTime("$anno-$mese-$giorno");
    $inizio_settimana->modify('monday this week');
    $giorni_settimana = [];

    $formatter = new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'eeee dd MMMM yyyy');

    for ($i = 0; $i < 7; $i++) {
        $giorni_settimana[] = clone $inizio_settimana;
        $inizio_settimana->modify('+1 day');
    }

    echo "<h2 class='text-center mb-4'>Settimana dal " . $formatter->format($giorni_settimana[0]) . " al " . $formatter->format($giorni_settimana[6]) . "</h2>";

    echo '<div class="row row-cols-1 row-cols-md-3 g-4">';
    foreach ($giorni_settimana as $giorno) {
        echo '<div class="col">';
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo '<h5 class="card-title">' . $formatter->format($giorno) . '</h5>';
        echo '<ul class="list-group list-group-flush">';
        foreach ($appuntamenti as $appuntamento) {
            $data_appuntamento = new DateTime($appuntamento['data_appuntamento']);
            if ($data_appuntamento->format('Y-m-d') === $giorno->format('Y-m-d')) {
                $ora_appuntamento = $data_appuntamento->format('H:i');
                $nome_cliente = htmlspecialchars($appuntamento['nome_cliente']);
                $nome_servizio = htmlspecialchars($appuntamento['nome_servizio']);
                echo "<li class='list-group-item'>$ora_appuntamento - $nome_cliente ($nome_servizio)</li>";
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

$stmt = $pdo->prepare("SELECT a.id_appuntamento, c.nome_cliente, s.nome_servizio, a.data_appuntamento, a.tempo_servizio 
                       FROM appuntamenti a
                       JOIN clienti c ON a.id_cliente = c.id_cliente
                       JOIN servizi s ON a.id_servizio = s.id_servizio
                       WHERE DATE(a.data_appuntamento) BETWEEN ? AND ?
                       ORDER BY a.data_appuntamento ASC");
$stmt->execute([$inizio_settimana, $fine_settimana]);
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <a href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=prev" class="btn btn-primary">
            <span class="material-icons align-middle">arrow_back_ios</span>
        </a>
        <span class="text-center">
            <strong>Settimana <?php echo $numero_settimana; ?></strong><br>
            <?php echo "Dal " . (new IntlDateFormatter('it_IT', IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(new DateTime($inizio_settimana)) . " al " . (new IntlDateFormatter('it_IT', IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(new DateTime($fine_settimana)); ?>
        </span>
        <a href="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>&action=next" class="btn btn-primary">
            <span class="material-icons align-middle">arrow_forward_ios</span>
        </a>
    </div>
</div>

<?php generaVistaSettimana($anno, $mese, $giorno, $appuntamenti); ?>

<!-- Modale per il nuovo appuntamento -->
<button class="btn btn-primary mt-4" data-bs-toggle="modal" data-bs-target="#newAppointmentModal">Nuovo Appuntamento</button>

<div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-labelledby="newAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newAppointmentModalLabel">Nuovo Appuntamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="appointmentForm" action="calendario.php?anno=<?php echo $anno; ?>&mese=<?php echo $mese; ?>&giorno=<?php echo $giorno; ?>" method="POST">
                    <div class="mb-3">
                        <label for="id_cliente" class="form-label">Cliente</label>
                        <select name="id_cliente" id="id_cliente" class="form-select" required>
                            <option value="">Seleziona un cliente</option>
                            <?php foreach ($clienti as $cliente): ?>
                                <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome_cliente']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_servizio" class="form-label">Servizio</label>
                        <select name="id_servizio" id="id_servizio" class="form-select" required>
                            <option value="">Seleziona un servizio</option>
                            <?php foreach ($servizi as $servizio): ?>
                                <option value="<?php echo $servizio['id_servizio']; ?>"><?php echo htmlspecialchars($servizio['nome_servizio']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tempo_servizio" class="form-label">Tempo Servizio (minuti, opzionale)</label>
                        <input type="number" name="tempo_servizio" id="tempo_servizio" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="data_appuntamento" class="form-label">Data</label>
                        <input type="date" name="data_appuntamento" id="data_appuntamento" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="ora_appuntamento" class="form-label">Ora</label>
                        <input type="time" name="ora_appuntamento" id="ora_appuntamento" class="form-control" required>
                    </div>
                    <p id="slotSuggerito" class="text-success d-none">Suggerimento: Il primo slot disponibile è alle <span id="oraSuggerita"></span>.</p>
                    <button type="submit" class="btn btn-primary">Crea Appuntamento</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('id_servizio').addEventListener('change', function() {
    const idCliente = document.getElementById('id_cliente').value;
    const idServizio = this.value;

    if (idCliente && idServizio) {
        fetch(`trova_slot.php?id_cliente=${idCliente}&id_servizio=${idServizio}`)
            .then(response => response.json())
            .then(data => {
                if (data.slot) {
                    document.getElementById('oraSuggerita').textContent = data.slot;
                    document.getElementById('slotSuggerito').classList.remove('d-none');
                } else {
                    document.getElementById('slotSuggerito').classList.add('d-none');
                }
            });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
