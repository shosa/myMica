<!-- Modale per nuovo appuntamento -->
<div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-labelledby="newAppointmentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <span class="h6 font-weight-bold text-white p-1 text-center bg-primary "
                style="widht:100%;">APPUNTAMENTO</span>
            <div class="modal-header">
                <h5 class="modal-title" id="newAppointmentModalLabel">Nuovo</h5>
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
                                <button class="btn btn-success" type="button" id="addClienteBtn" data-toggle="modal"
                                    data-target="#newClienteModal"><i class="fal fa-plus fa-s"></i></button>
                            </div>
                        </div>
                        <input type="hidden" name="id_cliente" id="id_cliente" required>
                        <div id="suggestions" class="list-group"></div>
                    </div>

                    <div class="mb-3">
                        <label for="id_servizio" class="form-label">Servizi</label>
                        <select name="id_servizio[]" id="id_servizio" class="form-select form-control" multiple
                            required>
                            <option value="">Seleziona uno o più servizi</option>
                            <?php foreach ($servizi as $servizio): ?>
                                <option value="<?php echo $servizio['id_servizio']; ?>">
                                    <?php echo htmlspecialchars($servizio['nome_servizio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tempo_servizio" class="form-label">Tempo (in minuti)</label>
                        <input type="number" name="tempo_servizio" id="tempo_servizio" class="form-control"
                            placeholder="Solo se diverso da quello standard">
                    </div>
                    <div class="mb-3">
                        <label for="data_appuntamento" class="form-label">Data</label>
                        <input type="date" name="data_appuntamento" id="data_appuntamento" class="form-control"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="ora_appuntamento" class="form-label">Ora</label>
                        <input type="time" name="ora_appuntamento" id="ora_appuntamento" class="form-control" required>
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
            <span class="h6 font-weight-bold text-white p-1 text-center bg-success " style="widht:100%;">CLIENTE</span>
            <div class="modal-header">
                <h5 class="modal-title" id="newClienteModalLabel">Nuovo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="newClienteForm">
                    <div class="mb-3">
                        <label for="nome_cliente" class="form-label">Nome Cliente</label>
                        <input type="text" name="nome_cliente" id="nome_cliente" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono_cliente" class="form-label">Telefono</label>
                        <input type="text" name="telefono_cliente" id="telefono_cliente" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success btn-lg  btn-block">Salva Cliente</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modale per i dettagli dell'appuntamento -->
<div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-labelledby="appointmentDetailsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="modaleDettagli">
            <!-- Stato appuntamento -->
            <span class="h6 font-weight-bold text-white p-2 bg-primary d-block text-center" id="detail_stato"></span>

            <!-- Header del modale -->
            <div class="modal-header">
                <h5 class="modal-title" id="appointmentDetailsModalLabel">
                    <span class="h4 text-dark font-weight-bold ml-2" id="detail_id_appuntamento"></span> - <span
                        class="h4 text-dark font-weight-bold ml-2" id="detail_data_appuntamento"></span>
                </h5>
                <input type="hidden" id="detail_id_cliente">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Corpo del modale -->
            <div class="modal-body">
                <!-- Nome Cliente -->
                <p class="mb-2"><span class="text-dark h3 font-weight-bold" id="detail_nome_cliente"></span></p>

                <!-- Nome Servizio -->
                <p class="mb-3"><i><span class="text-dark h4" id="detail_nome_servizio"></span></i></p>

                <!-- Data e ora dell'appuntamento -->
                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <!-- Form per il badge -->
                    <div class="d-flex align-items-center">

                        <!-- Select personalizzato per i colori -->
                        <input type="color" id="detail_badgeColor" class="form-control mr-2 p-0 border-0"
                            style="width:4rem">

                        <!-- Campo di testo per il badge -->
                        <input type="text" id="detail_badgeText" class="form-control" maxlength="10"
                            placeholder="Testo">

                        <!-- Pulsante salva -->
                        <button id="saveBadgeBtn" class="btn btn-lg text-success"><i class="fal fa-save"></i></button>
                    </div>

                    <!-- Ora allineata a destra, grigio chiaro e più grande -->
                    <p class="mb-1 ml-auto" style="font-size: 2rem; color: lightgray; font-weight: bold;">
                        <span id="detail_ora_appuntamento"></span>
                    </p>
                </div>
                <hr>

                <!-- Pulsanti di azione -->
                <div class="d-flex justify-content-center align-items-center mt-3">
                    <a id="whatsappLink" class="btn btn-white text-success btn-lg shadow-sm btn-circle mr-2"
                        target="_blank">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                    <button class="btn btn-white text-warning btn-lg shadow-sm btn-circle mr-2" id="editAppointmentBtn">
                        <i class="fal fa-pencil-alt"></i>
                    </button>
                    <button class="btn btn-white text-primary btn-lg shadow-sm btn-circle mr-2" id="btnBill">
                        <i class="fal fa-euro-sign"></i>
                    </button>
                    <button class="btn btn-white text-danger btn-lg shadow-sm btn-circle mr-2"
                        id="deleteAppointmentBtn">
                        <i class="fal fa-trash"></i>
                    </button>
                    <button class="btn btn-success btn-lg shadow-sm btn-circle ml-2" id="completeAppointmentBtn">
                        <i class="fal fa-check"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modale nuova annotazione -->
<div class="modal fade" id="newAnnotationModal" tabindex="-1" role="dialog" aria-labelledby="newAnnotationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <span class="h6 font-weight-bold text-white p-1 text-center bg-orange "
                style="widht:100%;">ANNOTAZIONE</span>
            <div class="modal-header">
                <h5 class="modal-title" id="newAnnotationModalLabel">Nuova</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="newAnnotationForm" method="POST" action="saveAnnotation.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="dataAnnotazione">Data</label>
                        <input type="date" class="form-control" id="dataAnnotazione" name="dataAnnotazione" required>
                        <div class="mb-3">
                            <label for="oraAnnotazione" class="form-label">Ora</label>
                            <input type="time" name="oraAnnotazione" id="oraAnnotazione" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nota">Nota</label>
                        <textarea class="form-control" id="nota" name="nota" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-orange btn-block btn-lg">Crea Annotazione</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modale modifica annotazione -->
<div class="modal fade" id="annotationDetailsModal" tabindex="-1" aria-labelledby="annotationDetailsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content border-orange">
            <span class="h6 font-weight-bold text-white p-1 text-center bg-orange "
                style="widht:100%;">ANNOTAZIONE</span>
            <div class="modal-header">
                <h5 class="modal-title" id="annotationDetailsModalLabel">ANNOTAZIONE </h5><span
                    class="h4 text-dark font-weight-bold ml-2" id="detail_id_annotazione"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="annotationDetailsForm">
                    <div class="form-group">
                        <label for="detail_data_annotazione">Data</label>
                        <input type="date" class="form-control" id="detail_data_annotazione" required>
                        <label for="detail_ora_annotazione">Ora</label>
                        <input type="time" class="form-control" id="detail_ora_annotazione" required>
                    </div>
                    <div class="form-group">
                        <label for="detail_note_annotazione">Note</label>
                        <textarea class="form-control" id="detail_note_annotazione" rows="3"
                            placeholder="Inserisci il contenuto"></textarea>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button id="deleteAnnotationBtn" type="button"
                    class="btn btn-white text-danger btn-lg shadow-sm btn-circle mr-4"><i
                        class="fal fa-trash"></i></button>
                <button id="editAnnotationBtn" type="button"
                    class="btn btn-success text-white btn-lg shadow-sm btn-circle mr-4"><i
                        class="fal fa-save"></i></button>
            </div>
        </div>
    </div>
</div>