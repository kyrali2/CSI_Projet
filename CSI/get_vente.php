<?php
include 'connexion_db.php';

if (isset($_GET['id_vente'])) {
    $id_vente = $_GET['id_vente'];

    $sql = "SELECT 
                v.id_vente, 
                v.date_vente, 
                v.etat_vente, 
                v.id_utilisateur
            FROM Vente v
            WHERE v.id_vente = :id_vente";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_vente', $id_vente, PDO::PARAM_INT);
    $stmt->execute();
    $vente = $stmt->fetch(PDO::FETCH_ASSOC);

    $vente['date_vente'] = date('Y-m-d', strtotime($vente['date_vente']));

    $sql_produits = "SELECT 
                        p.id_produit, 
                        p.intitule_produit, 
                        vp.quantite_vendue
                     FROM Produit p
                     LEFT JOIN Vente_Produit vp ON p.id_produit = vp.id_produit
                     WHERE vp.id_vente = :id_vente";
    
    $stmt_produits = $conn->prepare($sql_produits);
    $stmt_produits->bindParam(':id_vente', $id_vente, PDO::PARAM_INT);
    $stmt_produits->execute();
    $produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

    $vente['produits'] = $produits;

    echo json_encode($vente);
}
?>
