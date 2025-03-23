function ouvrirModal() {
    document.getElementById('modalAjoutAtelier').style.display = 'block';
}

function fermerModal() {
    document.getElementById('modalAjoutAtelier').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modalAjoutAtelier')) {
        fermerModal();
    }
}

function ouvrirModalModifier(idSession) {
    fetch(`get_atelier.php?id_session=${idSession}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('id_session').value = data.id_session;
            document.getElementById('numero_session_modif').value = data.numero_session;
            document.getElementById('nom_atelier_modif').value = data.nom_atelier;
            document.getElementById('prix_atelier_modif').value = data.prix_atelier;
            document.getElementById('id_categorie_modif').value = data.id_categorie;
            document.getElementById('date_session_modif').value = data.date_session.replace(" ", "T").slice(0, 16);
            document.getElementById('etat_session_modif').value = data.etat_session;
            document.getElementById('id_woofer_modif').value = data.id_woofer;

            document.getElementById('modalModifierAtelier').style.display = 'block';
        });
}

function fermerModalModifier() {
    document.getElementById('modalModifierAtelier').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modalModifierAtelier')) {
        fermerModalModifier();
    }
}

function supprimerAtelier(id_session) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cette session?")) {
        
        let form = document.createElement("form");
        form.method = "POST";
        form.action = "atelier.php"; 

        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "id_session";
        input.value = id_session;
        form.appendChild(input);

        let actionInput = document.createElement("input");
        actionInput.type = "hidden";
        actionInput.name = "action";
        actionInput.value = "supprimer";
        form.appendChild(actionInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function ouvrirModalModifierInscrit(idSession) {
    document.getElementById('id_session_inscrits').value = idSession;
    fetch(`getListeInscrits.php?id_session=${idSession}`)
        .then(response => response.json())
        .then(data => {

            const participantsList = document.getElementById('participants_list');
            participantsList.innerHTML = ''; 

            data.forEach(participant => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <input type="checkbox" class="checkbox-participant" value="${participant.id_participant}" /> 
                    ${participant.nom} ${participant.prenom} - ${participant.tel}
                `;
                participantsList.appendChild(li);
            });

            document.getElementById('modalModifierInscrits').style.display = 'block';
        });
}

function fermerModalModifierInscrits() {
    document.getElementById('modalModifierInscrits').style.display = 'none';
}

function desinscrireParticipants() {
    var idSession = document.getElementById('id_session_inscrits').value; 
    var participantsToUnregister = [];

    document.querySelectorAll('.checkbox-participant:checked').forEach(function(checkbox) {
        participantsToUnregister.push(checkbox.value); 
    });

    if (participantsToUnregister.length === 0) {
        alert('Veuillez sélectionner des participants à désinscrire.');
        return;
    }

    var formData = new FormData();
    formData.append('id_session', idSession); 
    formData.append('participants', JSON.stringify(participantsToUnregister)); 

    fetch('desinscrire.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes("success")) {
            alert('Les participants ont été désinscrits avec succès.');
            fermerModalModifierInscrits();

            participantsToUnregister.forEach(function(id_participant) {
                const participantElement = document.querySelector(`input[value="${id_participant}"]`).closest('li');
                if (participantElement) {
                    participantElement.remove(); 
                }
            });
        } else {
            alert('Une erreur est survenue lors de la désinscription.');
        }
    });
}

function ajouterParticipant() {
    var idSession = document.getElementById('id_session_inscrits').value;
    var tel = document.getElementById('participant_tel').value;
    var nom = document.getElementById('participant_nom').value;
    var prenom = document.getElementById('participant_prenom').value;
    var email = document.getElementById('participant_email').value;

    var participant = {
        tel: tel,
        nom: nom,
        prenom: prenom,
        email: email
    };

    if (!tel || !nom || !prenom || !email) {
        alert('Veuillez remplir tous les champs.');
        return;
    }

    var existingParticipants = Array.from(document.querySelectorAll('.checkbox-participant')).map(function(checkbox) {
        var li = checkbox.closest('li');
        return {
            tel: li.getAttribute('data-tel'),  
            email: li.getAttribute('data-email')
        };
    });

    var isAlreadyRegistered = existingParticipants.some(function(participantData) {
        return participantData.tel === tel || participantData.email === email;
    });

    if (isAlreadyRegistered) {
        alert('Ce participant est deja inscrit (par telephone ou email).');
        return;
    }

    var formData = new FormData();
    formData.append('id_session', idSession);
    formData.append('participant', JSON.stringify(participant));

    fetch('ajouterParticipant.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {  
            return response.json().then(data => {
                alert(data.message);  
            });
        }
        return response.json();  
    })
    .then(data => {
        if (data) {
            alert(data.message);  
            fermerModalModifierInscrits();
            location.reload();  
        }
    })
    .catch(error => {
        alert("Erreur de connexion au serveur.");
    });
}



window.onclick = function(event) {
    if (event.target == document.getElementById('modalModifierInscrits')) {
        fermerModalModifierInscrits();
    }
}
