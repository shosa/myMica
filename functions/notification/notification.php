<link href="<?php BASE_PATH ?>/functions/notification/notification.css" rel="stylesheet">
<button id="notificationBtn" class="btn rounded-circle p-2 shadow-sm">
    <i class="fa fa-bell"></i>
    <span id="notificationBadge" class="badge badge-danger">0</span>
</button>

<!-- Modale per visualizzare e aggiungere promemoria -->
<div class="modal fade" id="promemoriaModal" tabindex="-1" aria-labelledby="promemoriaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-warning">
            <span class="h6 font-weight-bold text-white p-1 text-center bg-warning"
                style="width:100%;">PROMEMORIA</span>
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="promemoriaModalLabel">PROMEMORIA</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                <div id="promemoriaList"></div>
                <hr>
                <button class="btn btn-warning btn-block" id="addPromemoriaBtn">NUOVO</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale per aggiungere un nuovo promemoria -->
<div class="modal fade" id="addPromemoriaModal" tabindex="-1" aria-labelledby="addPromemoriaModalLabel"
    aria-hidden="true" style="background: rgba(0, 0, 0, 0.35) !important;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-info">
            <span class="h6 font-weight-bold text-white p-1 text-center bg-info" style="width:100%;">NUOVO</span>
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="addPromemoriaModalLabel">PROMEMORIA</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addPromemoriaForm">
                    <div class="form-group">
                        <label for="promemoriaTitolo">Titolo</label>
                        <input type="text" class="form-control" id="promemoriaTitolo" required>
                    </div>
                    <div class="form-group">
                        <label for="promemoriaNota">Testo</label>
                        <textarea class="form-control" id="promemoriaNota" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-info btn-block">Salva</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const notificationBtn = document.getElementById('notificationBtn');
        const notificationBadge = document.getElementById('notificationBadge');
        const promemoriaModal = new bootstrap.Modal(document.getElementById('promemoriaModal'));
        const addPromemoriaModal = new bootstrap.Modal(document.getElementById('addPromemoriaModal'));

        function updateNotificationStatus() {
            fetch('<?php BASE_PATH; ?>/functions/notification/getNotificationCount') // Percorso per ottenere il conteggio delle notifiche
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const count = data.count;
                        if (count > 0) {
                            notificationBtn.classList.add('active');
                            notificationBadge.textContent = count;
                            notificationBadge.style.display = 'block';
                        } else {
                            notificationBtn.classList.remove('active');
                            notificationBadge.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                });
        }

        function loadPromemoria() {
            fetch('<?php BASE_PATH; ?>/functions/notification/getPromemoria') // Percorso per ottenere i promemoria
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const promemoriaList = document.getElementById('promemoriaList');
                        promemoriaList.innerHTML = ''; // Pulisce la lista esistente
                        data.promemoria.forEach(item => {
                            const formattedNota = item.nota.replace(/\n/g, '<br>');
                            promemoriaList.innerHTML += `
                        <div class="alert alert-warning promemoria-item" role="alert">
                            <button class="delete-btn" data-id="${item.id_promemoria}">&times;</button>
                            <h4 class="alert-heading">${item.titolo}</h4>
                            <p>${formattedNota}</p>
                        </div>
                    `;
                        });

                        // Aggiungi l'evento per il pulsante di eliminazione
                        document.querySelectorAll('.delete-btn').forEach(button => {
                            button.addEventListener('click', function () {
                                const idPromemoria = this.dataset.id;
                                fetch('<?php BASE_PATH ?>/functions/notification/deletePromemoria', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: new URLSearchParams({
                                        id_promemoria: idPromemoria
                                    })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                title: 'Successo',
                                                text: 'Promemoria eliminato con successo.',
                                                icon: 'success',
                                                confirmButtonText: 'OK'
                                            }).then(() => {
                                                updateNotificationStatus(); // Ricarica lo stato delle notifiche
                                                loadPromemoria(); // Ricarica i promemoria
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Errore',
                                                text: 'Impossibile eliminare il promemoria.',
                                                icon: 'error',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Errore:', error);
                                    });
                            });
                        });
                    } else {
                        Swal.fire({
                            title: 'Errore',
                            text: 'Impossibile caricare i promemoria.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                });
        }

        notificationBtn.addEventListener('click', function () {
            loadPromemoria();
            promemoriaModal.show();
        });

        document.getElementById('addPromemoriaBtn').addEventListener('click', function () {
            addPromemoriaModal.show();
        });

        document.getElementById('addPromemoriaForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const titolo = document.getElementById('promemoriaTitolo').value;
            const nota = document.getElementById('promemoriaNota').value;

            fetch('<?php BASE_PATH; ?>/functions/notification/addPromemoria', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    titolo: titolo,
                    nota: nota
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Successo',
                            text: 'Promemoria aggiunto con successo.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            addPromemoriaModal.hide();
                            updateNotificationStatus(); // Ricarica lo stato delle notifiche
                            loadPromemoria(); // Ricarica la lista dei promemoria
                        });
                    } else {
                        Swal.fire({
                            title: 'Errore',
                            text: 'Impossibile aggiungere il promemoria.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                });
        });

        // Chiama la funzione per aggiornare lo stato al caricamento della pagina
        updateNotificationStatus();

        // Aggiorna lo stato del pulsante ogni minuto (opzionale)
        setInterval(updateNotificationStatus, 60000);
    });
</script>