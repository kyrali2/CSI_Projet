function ouvrirModifProduitForm() {
    var form = document.getElementById('modalEditProduit');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'flex';
    } else {
        form.style.display = 'none';
    }
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
    event.target.style.display = 'none';
    }
}
function ouvrirAjoutProduitForm() {
    var form = document.getElementById('modalAddProduitForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'flex';
    } else {
        form.style.display = 'none';
    }
}
function ouvrirAjoutCategorieForm() {
    var form = document.getElementById('modalAddCategorieForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'flex';
    } else {
        form.style.display = 'none';
    }
}
function ouvrirSuprProduitModal() {
    document.getElementById('deleteProduitModal').style.display = 'flex';
}

function fermerSuprProduitModal() {
    document.getElementById('deleteProduitModal').style.display = 'none';
}
function ouvrirSuprCategorieModal() {
    document.getElementById('deleteCategorieModal').style.display = 'flex';
}

function fermerSuprCategorieModal() {
    document.getElementById('deleteCategorieModal').style.display = 'none';
}