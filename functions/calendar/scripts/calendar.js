document.getElementById('search_cliente').addEventListener('input', function () {
    const searchTerm = this.value;

    if (searchTerm.length > 2) {
        fetch(`searchCustomer.php?term=${searchTerm}`)
            .then(response => response.json())
            .then(data => {
                const suggestions = document.getElementById('suggestions');
                suggestions.innerHTML = '';
                data.forEach(cliente => {
                    const suggestionItem = document.createElement('a');
                    suggestionItem.href = '#';
                    suggestionItem.className = 'list-group-item list-group-item-action shadow';
                    suggestionItem.textContent = cliente.nome_cliente;
                    suggestionItem.dataset.id = cliente.id_cliente;
                    suggestionItem.addEventListener('click', function () {
                        document.getElementById('search_cliente').value = cliente.nome_cliente;
                        document.getElementById('search_cliente').classList.add("text-success");
                        document.getElementById('search_cliente').classList.add("border");
                        document.getElementById('search_cliente').classList.add("border-success");
                        document.getElementById('id_cliente').value = cliente.id_cliente;
                        suggestions.innerHTML = '';
                    });
                    suggestions.appendChild(suggestionItem);
                });
            })
            .catch(error => console.error('Errore:', error));
    }
});

document.getElementById('newClienteForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('saveCustomer', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nel salvataggio del cliente');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('id_cliente').value = data.id_cliente;
                document.getElementById('search_cliente').value = data.nome_cliente;
                document.getElementById('search_cliente').classList.add("text-success");
                document.getElementById('search_cliente').classList.add("border");
                document.getElementById('search_cliente').classList.add("border-success");
                $('#newClienteModal').modal('hide');
            } else {
                alert(data.message || 'Si è verificato un errore durante il salvataggio del cliente.');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Si è verificato un errore durante il salvataggio del cliente.');
        });
});
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.appointment-item').forEach(item => {
        item.addEventListener('click', function () {
            const idAppuntamento = this.dataset.id;

            fetch(`getDetails?id_appuntamento=${idAppuntamento}`)
                .then(response => response.json())
                .then(data => {
                    aggiornaDettagliAppuntamento(data);
                    $('#appointmentDetailsModal').modal('show');
                })
                .catch(error => console.error('Errore:', error));
        });
    });

    // Funzione per aggiornare i dettagli dell'appuntamento nel modal
    function aggiornaDettagliAppuntamento(data) {
        document.getElementById('detail_nome_cliente').textContent = data.nome_cliente;
        document.getElementById('detail_nome_servizio').textContent = data.nome_servizio;
        document.getElementById('detail_id_appuntamento').textContent = "#" + data.id_appuntamento;
        document.getElementById('detail_stato').textContent = data.completato == 1 ? "COMPLETATO" : "IN PROGRAMMA";
        document.getElementById('detail_stato').classList.remove(data.completato == 1 ? "bg-primary" : "bg-success");
        document.getElementById('detail_stato').classList.add(data.completato == 1 ? "bg-success" : "bg-primary");
        document.getElementById('modaleDettagli').classList.remove(data.completato == 1 ? "border-primary" : "border-success");
        document.getElementById('modaleDettagli').classList.add(data.completato == 1 ? "border-success" : "border-primary");
        document.getElementById('detail_id_cliente').value = data.id_cliente; // Salva l'id_cliente
        document.getElementById('detail_badgeColor').value = data.badge_color;
        document.getElementById('detail_badgeText').value = data.badge_text;
        const dataAppuntamento = new Date(data.data_appuntamento);
        const dataFormat = `${dataAppuntamento.getDate()}/${dataAppuntamento.getMonth() + 1}/${dataAppuntamento.getFullYear()}`;
        const oraFormat = dataAppuntamento.toTimeString().substring(0, 5);
        const dataOraOriginale = data.data_appuntamento;
        document.getElementById('detail_data_appuntamento').textContent = dataFormat;
        document.getElementById('detail_ora_appuntamento').textContent = oraFormat;

        const messaggio = `Ciao ti ricordo l'appuntamento del ${dataFormat} alle ${oraFormat}`;
        const whatsappLink = `https://api.whatsapp.com/send?phone=39${data.telefono_cliente}&text=${encodeURIComponent(messaggio)}`;


        document.getElementById('editAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;
        document.getElementById('deleteAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;
        document.getElementById('completeAppointmentBtn').dataset.idAppuntamento = data.id_appuntamento;

        if (data.completato == 1) {
            document.getElementById('editAppointmentBtn').disabled = true;
            document.getElementById('deleteAppointmentBtn').disabled = true;
            document.getElementById('completeAppointmentBtn').disabled = true;
            document.getElementById('whatsappLink').classList.add("disabled");

        } else {
            document.getElementById('editAppointmentBtn').disabled = false;
            document.getElementById('deleteAppointmentBtn').disabled = false;
            document.getElementById('completeAppointmentBtn').disabled = false;
            document.getElementById('whatsappLink').classList.remove("disabled");
            document.getElementById('whatsappLink').href = whatsappLink;
        }
        document.getElementById('btnBill').dataset.dataOra = dataOraOriginale;
    }

    // Gestione della modifica dell'appuntamento
    document.getElementById('editAppointmentBtn').addEventListener('click', function () {
        const idAppuntamento = this.dataset.idAppuntamento;
        window.location.href = `editAppointment?id_appuntamento=${idAppuntamento}`;
    });

    // Gestione della cancellazione dell'appuntamento
    document.getElementById('deleteAppointmentBtn').addEventListener('click', function () {
        const idAppuntamento = this.dataset.idAppuntamento;

        if (confirm('Sei sicuro di voler cancellare questo appuntamento?')) {
            fetch(`deleteAppointment?id_appuntamento=${idAppuntamento}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Errore nella cancellazione dell\'appuntamento.');
                    }
                })
                .catch(error => console.error('Errore:', error));
        }
    });

    // Gestione del completamento dell'appuntamento
    document.getElementById('completeAppointmentBtn').addEventListener('click', function () {
        const idAppuntamento = this.dataset.idAppuntamento;

        Swal.fire({
            title: 'Sei sicuro?',
            text: "Vuoi completare questo appuntamento?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sì, completa',
            cancelButtonText: 'Annulla'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('completeAppointment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'id_appuntamento': idAppuntamento
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Appuntamento completato!',
                                text: data.message,
                                icon: 'success'
                            }).then(() => {
                                // Aggiorna i dettagli dell'appuntamento nel modal
                                fetch(`getDetails?id_appuntamento=${idAppuntamento}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        aggiornaDettagliAppuntamento(data);
                                    })
                                    .catch(error => console.error('Errore:', error));
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Errore',
                                text: data.message,
                                icon: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Errore',
                            text: 'Si è verificato un errore durante il completamento dell\'appuntamento.',
                            icon: 'error'
                        });
                        console.error('Errore:', error);
                    });
            }

        });
    });
});



document.addEventListener('DOMContentLoaded', function () {
    // Apre il modale quando si clicca su una annotazione
    document.querySelectorAll('.annotation-item').forEach(item => {
        item.addEventListener('click', function () {
            const idAnnotazione = this.dataset.id;

            fetch(`getAnnotationDetail?id_annotazione=${idAnnotazione}`)
                .then(response => response.json())
                .then(data => {
                    aggiornaDettagliAnnotazione(data);
                    $('#annotationDetailsModal').modal('show');
                })
                .catch(error => console.error('Errore:', error));
        });
    });

    // Funzione per aggiornare i dettagli dell'annotazione nel modal
    function aggiornaDettagliAnnotazione(data) {
        const dataAnnotazione = new Date(data.data);

        // Imposta il campo data e ora
        document.getElementById('detail_data_annotazione').value = dataAnnotazione.toISOString().split('T')[0]; // Imposta la data in formato YYYY-MM-DD
        document.getElementById('detail_ora_annotazione').value = dataAnnotazione.toTimeString().substring(0, 5); // Imposta l'ora in formato HH:MM

        // Imposta il contenuto delle note
        document.getElementById('detail_note_annotazione').value = data.note;

        // Imposta l'ID annotazione visibile nel modale
        document.getElementById('detail_id_annotazione').textContent = "#" + data.id_annotazione;

        // Imposta i bottoni di modifica e cancellazione con il dataset ID annotazione
        document.getElementById('editAnnotationBtn').dataset.idAnnotazione = data.id_annotazione;
        document.getElementById('deleteAnnotationBtn').dataset.idAnnotazione = data.id_annotazione;
    }

    // Gestione della modifica dell'annotazione
    document.getElementById('editAnnotationBtn').addEventListener('click', function () {
        const idAnnotazione = this.dataset.idAnnotazione;
        const data = document.getElementById('detail_data_annotazione').value;
        const ora = document.getElementById('detail_ora_annotazione').value;
        const note = document.getElementById('detail_note_annotazione').value;
        const dataCompleta = `${data} ${ora}`;

        fetch(`editAnnotation`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id_annotazione: idAnnotazione, data: dataCompleta, note: note })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore nella modifica dell\'annotazione.');
                }
            })
            .catch(error => console.error('Errore:', error));
    });

    document.getElementById('saveBadgeBtn').addEventListener('click', function() {
        var badgeColor = document.getElementById('detail_badgeColor').value;
        var badgeText = document.getElementById('detail_badgeText').value;
        var appointmentId = document.getElementById('detail_id_appuntamento').textContent.trim();
    
        // Rimuovi il simbolo # se presente
        if (appointmentId.startsWith('#')) {
            appointmentId = appointmentId.substring(1);
        }
    
        console.log("Badge Color:", badgeColor);
        console.log("Badge Text:", badgeText);
        console.log("Appointment ID:", appointmentId);
    
        var postData = "appointment_id=" + encodeURIComponent(appointmentId) +
                       "&badge_color=" + encodeURIComponent(badgeColor) +
                       "&badge_text=" + encodeURIComponent(badgeText);
    
        console.log("Post Data:", postData);
    
        fetch('saveBadge.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postData,
        })
        .then(response => response.text())
        .then(data => {
            location.reload();
            
        })
        .catch(error => console.error('Error:', error));
    });
    // Gestione della cancellazione dell'annotazione
    document.getElementById('deleteAnnotationBtn').addEventListener('click', function () {
        const idAnnotazione = this.dataset.idAnnotazione;

        if (confirm('Sei sicuro di voler cancellare questa annotazione?')) {
            fetch('deleteAnnotation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id_annotazione: idAnnotazione })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Errore nella cancellazione dell\'annotazione.');
                    }
                })
                .catch(error => console.error('Errore:', error));
        }
    });
    document.getElementById('btnBill').addEventListener('click', function () {
        const idCliente = document.getElementById('detail_id_cliente').value;
        const dataOraOriginale = this.dataset.dataOra; // Ottieni la data originale memorizzata

        if (!idCliente || !dataOraOriginale) {
            alert('Errore: Cliente o data non disponibile.');
            return;
        }

        // Effettua una richiesta per ottenere il totale dei costi
        fetch(`getTotalCost?cliente=${idCliente}&dataora=${encodeURIComponent(dataOraOriginale)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Creare il contenuto del popup in formato scontrino
                    let scontrino = '<div style="text-align: left; font-family: Arial, sans-serif;">'; // Aggiungi un div con allineamento a sinistra
                    data.servizi.forEach(servizio => {
                        const costo = parseFloat(servizio.costo_servizio).toFixed(2); // Converti la stringa in numero e formatta
                        scontrino += `
        <div style="display: flex; justify-content: space-between;">
            <span class="text-indigo">${servizio.nome_servizio}</span>
            <span>${costo}€</span>
        </div>`;
                    });

                    scontrino += '<hr>'; // Usando <hr> per la linea divisoria
                    scontrino += `
    <div style="display: flex; justify-content: space-between;">
        <span>TOTALE</span>
        <span class="font-weight-bold">${parseFloat(data.totale).toFixed(2)}€</span>
    </div>`;
                    scontrino += '</div>'; // Chiudi il div

                    // Mostra il popup con SweetAlert
                    Swal.fire({
                        title: 'Dettaglio Servizi',
                        html: scontrino,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Errore',
                        text: 'Impossibile calcolare il totale.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                Swal.fire({
                    title: 'Errore',
                    text: 'Si è verificato un errore durante il calcolo del totale.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    });



});
