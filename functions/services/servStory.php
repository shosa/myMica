<?php
include("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include(BASE_PATH . "/components/header.php");

$id_servizio = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_servizio <= 0) {
    echo "<p>ID servizio non valido.</p>";
    exit;
}

// Recupera il nome del cliente
$sqlServizio = "SELECT nome_servizio FROM servizi WHERE id_servizio = ?";
$stmtServizio = $pdo->prepare($sqlServizio);
$stmtServizio->execute([$id_servizio]);
$cliente = $stmtServizio->fetch(PDO::FETCH_ASSOC);

// Recupera gli appuntamenti completati per il cliente con la somma dei prezzi e del tempo_servizio
$sqlAppuntamenti = "
    SELECT 
        a.data_appuntamento, 
        c.nome_cliente, 
        SUM(a.tempo_servizio) AS totale_tempo_servizio
       
    FROM appuntamenti a
    JOIN clienti c ON a.id_cliente = c.id_cliente
    WHERE a.id_servizio = ? AND a.completato = 1
    GROUP BY a.data_appuntamento, c.nome_cliente
    ORDER BY a.data_appuntamento ASC
";
$stmtAppuntamenti = $pdo->prepare($sqlAppuntamenti);
$stmtAppuntamenti->execute([$id_servizio]);
$appuntamentiCompletati = $stmtAppuntamenti->fetchAll(PDO::FETCH_ASSOC);

// Somma totale tempo_servizio e prezzo
$sqlSomme = "
    SELECT 
        SUM(a.tempo_servizio) AS totale_tempo_servizio, 
        SUM(s.costo) AS totale_prezzo
    FROM appuntamenti a
    JOIN servizi s ON a.id_servizio = s.id_servizio
    WHERE a.id_servizio = ? AND a.completato = 1
";
$stmtSomme = $pdo->prepare($sqlSomme);
$stmtSomme->execute([$id_servizio]);
$totali = $stmtSomme->fetch(PDO::FETCH_ASSOC);

?>
<link href="custom.css" rel="stylesheet">

<body>

    <div id="wrapper">
        <?php include(BASE_PATH . "/components/navbar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(BASE_PATH . "/components/topbar.php"); ?>

                <div class="container-fluid">
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="services">Servizi</a></li>
                        <li class="breadcrumb-item active">Resoconto</li>
                    </ol>
                    <h1 class="h3 mb-4 text-gray-800 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas text-indigo fa-spa fa-xl mr-2"></i>
                            <span class="h2 font-weight-bold">
                                <?php echo htmlspecialchars($cliente['nome_servizio']); ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-info">
                                <?php echo $totali['totale_tempo_servizio'] ? $totali['totale_tempo_servizio'] : '0'; ?>
                                min
                            </span>
                            <span class="badge badge-warning">
                                <?php echo $totali['totale_prezzo'] !== null ? number_format($totali['totale_prezzo'], 2) : '0.00'; ?>
                                €
                            </span>
                        </div>
                    </h1>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header border-indigo">
                            <span class="text-indigo font-weight-bold">STORICO</span>

                        </div>

                        <?php if (empty($appuntamentiCompletati)): ?>
                            <p class="p-4">Nessun appuntamento completato trovato per questo cliente.</p>
                        <?php else: ?>

                            <?php foreach ($appuntamentiCompletati as $appuntamento): ?>
                                <li class="list-group-item m-1 border-0" style="border-bottom: solid 1px #ededed !important;">
                                    <span class="badge badge-success">
                                        <?php echo date('d/m/Y', strtotime($appuntamento['data_appuntamento'])); ?>
                                    </span>
                                    <span class="badge badge-primary">
                                        <?php echo date('H:i', strtotime($appuntamento['data_appuntamento'])); ?>
                                    </span>
                                    <?php echo htmlspecialchars($appuntamento['nome_cliente']); ?>
                                </li>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <?php include(BASE_PATH . "/components/footer.php"); ?>
    <?php include(BASE_PATH . "/components/scripts.php"); ?>
</body>

</html>