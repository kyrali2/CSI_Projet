function montrerModifWooferForm(wooferId) {
    var form = document.getElementById('modalModifWoofer' + wooferId);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
function montrerAjoutWooferForm() {
    var form = document.getElementById('modalAjoutWooferForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
function montrerAjoutTacheForm() {
    var form = document.getElementById("modalAjoutTacheForm");
    form.style.display = form.style.display === "none" ? "flex" : "none";
}
function toggleModifTache(id_tache) {
    document.getElementById('modalModifTache' + id_tache).style.display = 'flex';
}

function closeEditForm(id_tache) {
    document.getElementById('modalModifTache' + id_tache).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
    event.target.style.display = 'none';
    }
}
function montrerSuprTacheModal(id_tache) {
    document.getElementById('taskIdToDelete').value = id_tache; 
    document.getElementById('suprTacheModal').style.display = 'flex'; 
}

function fermerSuprTacheModal() {
    document.getElementById('suprTacheModal').style.display = 'none'; 
}

