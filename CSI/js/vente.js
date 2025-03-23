function ouvrirModal() {
    document.getElementById('modal-ajout-vente').style.display = 'flex';
}

function fermerModal() {
    document.getElementById('modal-ajout-vente').style.display = 'none';
}


function ouvrirModalModifier(id_vente) {
    const modal = document.getElementById("modal-modifier-vente");
    const form = modal.querySelector('form');


    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    form.querySelectorAll('input[type="number"]').forEach(input => {
        input.value = '';
    });

    fetch(`get_vente.php?id_vente=${id_vente}`)
        .then(response => response.json())
        .then(data => {


            document.getElementById("id_vente_modifier").value = data.id_vente;
            document.getElementById("date_vente_modifier").value = data.date_vente;
            document.getElementById("id_utilisateur_modifier").value = data.id_utilisateur;
            document.getElementById("etat_vente_modifier").value = data.etat_vente;

 
            data.produits.forEach(produit => {
                const checkbox = form.querySelector(`input[type="checkbox"][value="${produit.id_produit}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    const quantiteInput = form.querySelector(`input[name="quantite_${produit.id_produit}"]`);
                    if (quantiteInput) {
                        quantiteInput.value = produit.quantite_vendue;
                    }
                }
            });

            modal.style.display = "block";
        });
}


function fermerModalModifier() {
    const modal = document.getElementById("modal-modifier-vente");
    modal.style.display = "none";
}

window.onclick = function(event) {
    let modalAjout = document.getElementById('modal-ajout-vente');
    let modalModifier = document.getElementById('modal-modifier-vente');
    if (event.target === modalAjout) {
        fermerModal();
    } else if (event.target === modalModifier) {
        fermerModalModifier();
    }
};
