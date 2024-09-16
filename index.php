<?php
include("config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include("components/header.php");

// Recupera la data di oggi
$oggi = date('Y-m-d');

// Prepara e esegui la query per recuperare gli appuntamenti di oggi con dettagli cliente e servizio
$sqlOggi = "
    SELECT 
        a.data_appuntamento, 
        c.nome_cliente AS cliente_nome, 
        s.nome_servizio 
    FROM appuntamenti a
    JOIN clienti c ON a.id_cliente = c.id_cliente
    JOIN servizi s ON a.id_servizio = s.id_servizio
    WHERE DATE(a.data_appuntamento) = :oggi AND a.completato = 0
    ORDER BY a.data_appuntamento ASC
";
$stmtOggi = $pdo->prepare($sqlOggi);
$stmtOggi->execute(['oggi' => $oggi]);

// Ottieni i risultati per oggi
$appuntamentiOggi = $stmtOggi->fetchAll(PDO::FETCH_ASSOC);

// Prepara e esegui la query per gli appuntamenti della settimana prossima
$prossimaSettimana = date('Y-m-d', strtotime('+7 days'));
$sqlSettimanaProssima = "
    SELECT 
        a.data_appuntamento, 
        c.nome_cliente AS cliente_nome, 
        s.nome_servizio 
    FROM appuntamenti a
    JOIN clienti c ON a.id_cliente = c.id_cliente
    JOIN servizi s ON a.id_servizio = s.id_servizio
    WHERE DATE(a.data_appuntamento) > :oggi AND DATE(a.data_appuntamento) <= :prossimaSettimana AND a.completato = 0
    ORDER BY a.data_appuntamento ASC
";
$stmtSettimanaProssima = $pdo->prepare($sqlSettimanaProssima);
$stmtSettimanaProssima->execute(['oggi' => $oggi, 'prossimaSettimana' => $prossimaSettimana]);

// Ottieni i risultati per la settimana prossima
$appuntamentiSettimanaProssima = $stmtSettimanaProssima->fetchAll(PDO::FETCH_ASSOC);
?>

<body id="page-top">
    <div id="wrapper">
        <?php include("components/navbar.php"); ?>
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
                        <!-- Card Appuntamenti di Oggi -->
                        <div class="col-md-6">
                            <div class="card mb-4  shadow-sm">
                                <div class="card-header">
                                    <h5 class="m-0 font-weight-bold text-indigo">Appuntamenti di Oggi</h5>
                                </div>
                                <div class="card-body">
                               
                                    <?php
                                    // Raggruppa gli appuntamenti per ora e cliente
                                    $appuntamentiGiorno = [];
                                    foreach ($appuntamentiOggi as $appuntamento) {
                                        $ora = date('H:i', strtotime($appuntamento['data_appuntamento']));
                                        $cliente = $appuntamento['cliente_nome'];
                                        $chiave = "<span class='badge badge-indigo'>$ora </span> - $cliente";

                                        if (!isset($appuntamentiGiorno[$chiave])) {
                                            $appuntamentiGiorno[$chiave] = [];
                                        }
                                        $appuntamentiGiorno[$chiave][] = $appuntamento['nome_servizio'];
                                    }

                                    // Mostra gli appuntamenti raggruppati
                                    if (count($appuntamentiGiorno) > 0):
                                        ?>
                                        <ul class="list-group">
                                            <?php foreach ($appuntamentiGiorno as $chiave => $servizi): ?>
                                                <li class="list-group-item">
                                                    <strong><?php echo $chiave; ?>:</strong>
                                                    <ul>
                                                        <?php foreach ($servizi as $servizio): ?>
                                                            <li><?php echo htmlspecialchars($servizio); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>Nessun appuntamento per oggi.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php include(BASE_PATH . "/functions/notification/notification.php"); ?>

                        <!-- Card Appuntamenti della Settimana Prossima -->
                        <div class="col-md-6">
                            <div class="card mb-4 shadow-sm">
                                <div class="card-header">
                                    <h5 class="m-0 font-weight-bold text-orange">Appuntamenti prossimi 7 GG</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Raggruppa gli appuntamenti per ora e cliente
                                    $appuntamentiSettimana = [];
                                    foreach ($appuntamentiSettimanaProssima as $appuntamento) {
                                        $ora = date('H:i', strtotime($appuntamento['data_appuntamento']));
                                        $data = date('d-m', strtotime($appuntamento['data_appuntamento']));
                                        $cliente = $appuntamento['cliente_nome'];
                                        $chiave = "<span class='badge badge-orange'>$data</span> <span class='badge badge-dark'>$ora</span> - $cliente";

                                        if (!isset($appuntamentiSettimana[$chiave])) {
                                            $appuntamentiSettimana[$chiave] = [];
                                        }
                                        $appuntamentiSettimana[$chiave][] = $appuntamento['nome_servizio'];
                                    }

                                    // Mostra gli appuntamenti raggruppati
                                    if (count($appuntamentiSettimana) > 0):
                                        ?>
                                        <ul class="list-group">
                                            <?php foreach ($appuntamentiSettimana as $chiave => $servizi): ?>
                                                <li class="list-group-item">
                                                    <strong><?php echo $chiave; ?>:</strong>
                                                    <ul>
                                                        <?php foreach ($servizi as $servizio): ?>
                                                            <li><?php echo htmlspecialchars($servizio); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>Nessun appuntamento per la settimana prossima.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FINE ROW CARDS -->
                    <hr>
                    <div class="mx-auto text-center">
                        <img src="<?php echo BASE_URL ?>/img/logoMini.png" alt="Logo" style="max-height: 100px;">
                    </div>

                </div>
            </div>
            <?php include(BASE_PATH . "/components/footer.php"); ?>

        </div>
    </div>

    <?php include(BASE_PATH . "/components/scripts.php"); ?>
</body>