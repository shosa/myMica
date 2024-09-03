<?php
include("config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include("components/header.php");

// Recupera la data di oggi
$oggi = date('Y-m-d');

// Prepara e esegui la query per recuperare gli appuntamenti di oggi con dettagli cliente e servizio
$sql = "
    SELECT 
        a.data_appuntamento, 
        c.nome_cliente AS cliente_nome, 
        s.nome_servizio 
    FROM appuntamenti a
    JOIN clienti c ON a.id_cliente = c.id_cliente
    JOIN servizi s ON a.id_servizio = s.id_servizio
    WHERE DATE(a.data_appuntamento) = :oggi 
    ORDER BY a.data_appuntamento ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['oggi' => $oggi]);

// Ottieni i risultati
$appuntamentiOggi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("components/navbar.php"); //INCLUSIONE NAVBAR ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("components/topbar.php"); ?>
                <div class="container-fluid">
                    <?php require_once BASE_PATH . "/utils/alerts.php"; ?>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>
                    <!-- INIZIO ROW CARDS -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="m-0 font-weight-bold text-indigo">Appuntamenti di Oggi</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (count($appuntamentiOggi) > 0): ?>
                                        <ul class="list-group">
                                            <?php foreach ($appuntamentiOggi as $appuntamento): ?>
                                                <li class="list-group-item">
                                                    <strong><?php echo htmlspecialchars(date('H:i', strtotime($appuntamento['data_appuntamento']))); ?>:</strong>
                                                    <?php echo htmlspecialchars($appuntamento['cliente_nome'] . ' - ' . $appuntamento['nome_servizio']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>Nessun appuntamento per oggi.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FINE ROW CARDS -->
                    <div class="mx-auto text-center">
                        <img src="<?php echo BASE_URL ?>/img/logoMini.png" alt="Logo" style="max-height: 100px;">
                    </div>
                    <hr>
                </div>
            </div>
            <?php include(BASE_PATH . "/components/footer.php"); ?>
        </div>
    </div>

    <?php include(BASE_PATH . "/components/scripts.php"); ?>

</body>