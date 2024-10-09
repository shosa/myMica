<?php
include("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
include(BASE_PATH . "/components/header.php");
include(BASE_PATH . "/vendor/autoload.php");
?>

<body>
    <div id="wrapper">
        <?php include(BASE_PATH . "/components/navbar.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(BASE_PATH . "/components/topbar.php"); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Statistiche</h1>

                    <?php include(BASE_PATH . "/functions/notification/notification.php"); ?>
                    <!-- Card Report Guadagni -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header border-danger">
                            <span class="text-danger font-weight-bold">Crea nuovo report</span>
                        </div>
                        <div class="card-body">
                            <!-- Form per selezionare il periodo e i toggle -->
                            <form action="genReport.php" method="POST" target="_blank">
                                <div class="form-group">
                                    <label for="data_da">Data Da</label>
                                    <input type="date" class="form-control" id="data_da" name="data_da" required>
                                </div>
                                <div class="form-group">
                                    <label for="data_a">Data A</label>
                                    <input type="date" class="form-control" id="data_a" name="data_a" required>
                                </div>

                                <!-- Toggle per selezionare cosa mostrare -->
                                <div class="form-group">
                                    <div class="form-check">
                                        <label class="switch">
                                            <input type="checkbox" id="mostra_guadagni" name="mostra_guadagni"
                                                value="1">
                                            <span class="slider round"></span>
                                        </label>
                                        <label class="form-check-label h5 ml-2" for="mostra_guadagni">Mostra
                                            Guadagni</label>
                                    </div>
                                    <hr>
                                    <div class="form-check">
                                        <label class="switch">
                                            <input type="checkbox" id="raggruppa_servizi" name="raggruppa_servizi"
                                                value="1">
                                            <span class="slider round"></span>
                                        </label>
                                        <label class="form-check-label h5 ml-2" for="raggruppa_servizi">Raggruppa
                                            Servizi</label>
                                    </div>
                                    <hr>
                                    <div class="form-check">
                                        <label class="switch">
                                            <input type="checkbox" id="raggruppa_clienti" name="raggruppa_clienti"
                                                value="1">
                                            <span class="slider round"></span>
                                        </label>
                                        <label class="form-check-label h5 ml-2" for="raggruppa_clienti">Raggruppa
                                            Clienti</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-danger btn-block" id="submitBtn" disabled>GENERA
                                    REPORT</button>
                            </form>

                        </div>
                        <div class="alert alert-warning m-1">Seleziona il periodo e i dati per generare un report.<br>Vengono considerati solo appuntamenti completati.
                        </div>
                    </div>

                    <!-- Altri report potrebbero essere aggiunti in altre card come questa -->
                </div>
            </div>
        </div>
    </div>

    <?php include(BASE_PATH . "/components/footer.php"); ?>
    <?php include(BASE_PATH . "/components/scripts.php"); ?>

    <script>
        // Funzione per abilitare il pulsante solo se almeno un toggle è selezionato
      
        document.addEventListener('DOMContentLoaded', function () {
            const submitBtn = document.getElementById('submitBtn');
            const checkboxes = document.querySelectorAll('.form-check input[type="checkbox"]');

            // Funzione per controllare se almeno una checkbox è selezionata
            function toggleSubmitButton() {
                submitBtn.disabled = !Array.from(checkboxes).some(checkbox => checkbox.checked);
            }

            // Imposta l'evento "change" per abilitare/disabilitare il pulsante
            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', toggleSubmitButton);
            });

            // Chiamata iniziale per disabilitare/abilitare il pulsante in base allo stato corrente
            toggleSubmitButton();
        });
    </script>

    <style>
        /* Style per il toggle switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 25px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 25px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 19px;
            width: 19px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
</body>

</html>