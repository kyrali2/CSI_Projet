<?php
session_start();
include 'connexion_db.php';

$sql = "SELECT v.id_vente, v.date_vente, v.etat_vente, u.nom, u.prenom, COALESCE(t.total_HT + t.TVA, 0) AS montant
        FROM Vente v 
        JOIN Utilisateur u ON v.id_utilisateur = u.id_utilisateur
        LEFT JOIN Ticket t ON v.id_vente = t.id_vente";  

$stmt = $conn->prepare($sql);
$stmt->execute();
$ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_users = "SELECT id_utilisateur, prenom, nom FROM Utilisateur";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

$sql_produits = "SELECT id_produit, intitule_produit, prix_unitaire_produit FROM Produit";
$stmt_produits = $conn->prepare($sql_produits);
$stmt_produits->execute();
$produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();

        if (isset($_POST['id_vente'])) {
            $id_vente = $_POST['id_vente'];
            $date_vente = $_POST['date_vente'];
            $id_utilisateur = $_POST['id_utilisateur'];
            $etat_vente = $_POST['etat_vente'];

            $sql_ancien_etat = "SELECT etat_vente FROM Vente WHERE id_vente = :id_vente";
            $stmt_ancien_etat = $conn->prepare($sql_ancien_etat);
            $stmt_ancien_etat->bindParam(':id_vente', $id_vente);
            $stmt_ancien_etat->execute();
            $ancien_etat = $stmt_ancien_etat->fetchColumn();

            $sql_update_vente = "UPDATE Vente SET date_vente = :date_vente, id_utilisateur = :id_utilisateur, etat_vente = :etat_vente WHERE id_vente = :id_vente";
            $stmt_update_vente = $conn->prepare($sql_update_vente);
            $stmt_update_vente->bindParam(':date_vente', $date_vente);
            $stmt_update_vente->bindParam(':id_utilisateur', $id_utilisateur);
            $stmt_update_vente->bindParam(':etat_vente', $etat_vente);
            $stmt_update_vente->bindParam(':id_vente', $id_vente);
            $stmt_update_vente->execute();

            if ($ancien_etat == 'Enregistrée') {
                if ($etat_vente == 'Annulée' || $etat_vente == 'EnCours') {
                    $sql_anciens_produits = "SELECT id_produit, quantite_vendue FROM Vente_Produit WHERE id_vente = :id_vente";
                    $stmt_anciens_produits = $conn->prepare($sql_anciens_produits);
                    $stmt_anciens_produits->bindParam(':id_vente', $id_vente);
                    $stmt_anciens_produits->execute();
                    $anciens_produits = $stmt_anciens_produits->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($anciens_produits as $produit) {
                        $sql_update_stock = "UPDATE Produit SET quantite_stock = quantite_stock + :quantite WHERE id_produit = :id_produit";
                        $stmt_update_stock = $conn->prepare($sql_update_stock);
                        $stmt_update_stock->bindParam(':quantite', $produit['quantite_vendue']);
                        $stmt_update_stock->bindParam(':id_produit', $produit['id_produit']);
                        $stmt_update_stock->execute();
                    }
                }
                if ($ancien_etat == 'Enregistrée' && $etat_vente == 'Enregistrée') {
                    $sql_anciens_produits = "SELECT id_produit, quantite_vendue FROM Vente_Produit WHERE id_vente = :id_vente";
                    $stmt_anciens_produits = $conn->prepare($sql_anciens_produits);
                    $stmt_anciens_produits->bindParam(':id_vente', $id_vente);
                    $stmt_anciens_produits->execute();
                    $anciens_produits = $stmt_anciens_produits->fetchAll(PDO::FETCH_ASSOC);
    
                    foreach ($anciens_produits as $produit) {
                        $nouvelle_quantite = $_POST['quantite_' . $produit['id_produit']];
                        
                        $difference = $produit['quantite_vendue']- $nouvelle_quantite;
    
                        $difference += $nouvelle_quantite;
    
                        if ($difference != 0) {
                            $sql_update_stock = "UPDATE Produit SET quantite_stock = quantite_stock + :difference WHERE id_produit = :id_produit";
                            $stmt_update_stock = $conn->prepare($sql_update_stock);
                            $stmt_update_stock->bindParam(':difference', $difference);
                            $stmt_update_stock->bindParam(':id_produit', $produit['id_produit']);
                            $stmt_update_stock->execute();
                        }
                    }
                }
            }

            $sql_delete_anciens_produits = "DELETE FROM Vente_Produit WHERE id_vente = :id_vente";
            $stmt_delete_anciens_produits = $conn->prepare($sql_delete_anciens_produits);
            $stmt_delete_anciens_produits->bindParam(':id_vente', $id_vente);
            $stmt_delete_anciens_produits->execute();

            if (isset($_POST['produits']) && is_array($_POST['produits'])) {
                foreach ($_POST['produits'] as $id_produit) {
                    $quantite = $_POST['quantite_' . $id_produit];

                    $sql_stock_check = "SELECT quantite_stock FROM Produit WHERE id_produit = :id_produit";
                    $stmt_stock_check = $conn->prepare($sql_stock_check);
                    $stmt_stock_check->bindParam(':id_produit', $id_produit);
                    $stmt_stock_check->execute();
                    $stock = $stmt_stock_check->fetchColumn();

                    if ($stock < $quantite) {
                        throw new Exception('Stock insuffisant pour le produit ' . $id_produit);
                    }

                    $sql_vente_produit = "INSERT INTO Vente_Produit (id_vente, id_produit, quantite_vendue) VALUES (:id_vente, :id_produit, :quantite_vendue)";
                    $stmt_vente_produit = $conn->prepare($sql_vente_produit);
                    $stmt_vente_produit->bindParam(':id_vente', $id_vente);
                    $stmt_vente_produit->bindParam(':id_produit', $id_produit);
                    $stmt_vente_produit->bindParam(':quantite_vendue', $quantite);
                    $stmt_vente_produit->execute();
                }
            }

            $conn->commit();
            header("Location: vente.php");
            exit();
        }else {

            $date_vente = $_POST['date_vente'];
            $id_utilisateur = $_POST['id_utilisateur'];
            $etat_vente = $_POST['etat_vente'];

            $sql_vente = "INSERT INTO Vente (date_vente, id_utilisateur, etat_vente) VALUES (:date_vente, :id_utilisateur, :etat_vente)";
            $stmt_vente = $conn->prepare($sql_vente);
            $stmt_vente->bindParam(':date_vente', $date_vente);
            $stmt_vente->bindParam(':id_utilisateur', $id_utilisateur);
            $stmt_vente->bindParam(':etat_vente', $etat_vente);
            $stmt_vente->execute();

            $id_vente = $conn->lastInsertId();


            if (isset($_POST['produits']) && is_array($_POST['produits'])) {
                foreach ($_POST['produits'] as $id_produit) {
                    $quantite = $_POST['quantite_' . $id_produit];


                    $sql_stock_check = "SELECT quantite_stock FROM Produit WHERE id_produit = :id_produit";
                    $stmt_stock_check = $conn->prepare($sql_stock_check);
                    $stmt_stock_check->bindParam(':id_produit', $id_produit);
                    $stmt_stock_check->execute();
                    $stock = $stmt_stock_check->fetchColumn();

                    if ($stock < $quantite) {
                        throw new Exception('Stock insuffisant pour le produit ' . $id_produit);
                    }


                    $sql_vente_produit = "INSERT INTO Vente_Produit (id_vente, id_produit, quantite_vendue) VALUES (:id_vente, :id_produit, :quantite_vendue)";
                    $stmt_vente_produit = $conn->prepare($sql_vente_produit);
                    $stmt_vente_produit->bindParam(':id_vente', $id_vente);
                    $stmt_vente_produit->bindParam(':id_produit', $id_produit);
                    $stmt_vente_produit->bindParam(':quantite_vendue', $quantite);
                    $stmt_vente_produit->execute();
                }
            }

            $conn->commit();
            header("Location: vente.php");
            exit();
        }
    } catch (Exception $e) {

        $conn->rollBack();
        echo "<script>alert('Erreur : " . $e->getMessage() . "');</script>";
    }
}
$sql_produits_vente = "SELECT vp.id_vente, p.intitule_produit, vp.quantite_vendue
                        FROM Vente_Produit vp
                        JOIN Produit p ON vp.id_produit = p.id_produit";
$stmt_produits_vente = $conn->prepare($sql_produits_vente);
$stmt_produits_vente->execute();
$produits_vente = $stmt_produits_vente->fetchAll(PDO::FETCH_ASSOC);

$ventes_produits = [];
foreach ($produits_vente as $produit) {
    $ventes_produits[$produit['id_vente']][] = [
        'intitule_produit' => $produit['intitule_produit'],
        'quantite_vendue' => $produit['quantite_vendue']
    ];
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ventes</title>
    <link rel="stylesheet" href="css/onglet.css">
    <link rel="stylesheet" href="css/vente.css">
    <script src="js/vente.js"></script>
    <script src="js/onglet.js"></script>
</head>
<body>
    <?php 
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] == "Responsable") {
                include 'ongletResponsable.php';
            } elseif ($_SESSION['role'] == "Woofer") {
                include 'ongletWoofer.php';
            }
        }
    ?>
    <h1>Gestion des Ventes</h1>
    
    <div class="container">
        <?php if (!empty($ventes) && is_array($ventes)): ?>
            <?php foreach ($ventes as $vente): ?>
                <div class="vente">
                    <h3>Numéro de la vente: <?= $vente['id_vente'] ?></h3>
                    <p>
                        Date de la vente: <?= $vente['date_vente'] ?><br>
                        Utilisateur associé: <?= $vente['prenom'] . " " . $vente['nom'] ?><br>
                        État de la vente: <?= $vente['etat_vente'] ?><br>
                        Montant de la vente: <?= $vente['montant'] ?> €
                    </p>
                    
                    <h4>Produits vendus :</h4>
                    <ul>
                        <?php if (!empty($ventes_produits[$vente['id_vente']])): ?>
                            <?php foreach ($ventes_produits[$vente['id_vente']] as $produit): ?>
                                <li>
                                    <?= $produit['intitule_produit'] ?> - 
                                    Quantité : <?= $produit['quantite_vendue'] ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>Aucun produit</li>
                        <?php endif; ?>
                    </ul>

                    <div class="buttons">
                        <button onclick="ouvrirModalModifier(<?= $vente['id_vente'] ?>)">Modifier vente</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucune vente enregistrée.</p>
        <?php endif; ?>
    </div>

    <div class="buttons">
        <button onclick="ouvrirModal()">Ajouter vente</button>
    </div>

    <div id="modal-ajout-vente" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="fermerModal()">&times;</span>
            <h2>Ajouter une Vente</h2>
            <form action="vente.php" method="POST">
                <label for="date_vente">Date de la vente :</label>
                <input type="date" id="date_vente" name="date_vente" required>

                <label for="id_utilisateur">Utilisateur associé :</label>
                <select id="id_utilisateur" name="id_utilisateur" required>
                    <option value="">Sélectionnez un utilisateur</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id_utilisateur'] ?>">
                            <?= $user['prenom'] . " " . $user['nom'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="etat_vente">État de la vente :</label>
                <select id="etat_vente" name="etat_vente" required>
                    <option value="Enregistrée">Enregistrée</option>
                    <option value="Annulée">Annulée</option>
                    <option value="EnCours">En cours</option>
                </select>

                <label>Produits vendus :</label>
                <div id="produits-container">
                    <?php foreach ($produits as $produit): ?>
                        <div>
                            <input type="checkbox" name="produits[]" value="<?= $produit['id_produit'] ?>">
                            <?= $produit['intitule_produit'] ?> - <?= $produit['prix_unitaire_produit'] ?> €
                            <input type="number" name="quantite_<?= $produit['id_produit'] ?>" min="1" placeholder="Quantité">
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit">Valider la vente</button>
            </form>
        </div>
    </div>

    <div id="modal-modifier-vente" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="fermerModalModifier()">&times;</span>
            <h2>Modifier une Vente</h2>
            <form action="vente.php" method="POST">
                <input type="hidden" id="id_vente_modifier" name="id_vente">
                
                <label for="date_vente_modifier">Date de la vente :</label>
                <input type="date" id="date_vente_modifier" name="date_vente" required>

                <label for="id_utilisateur_modifier">Utilisateur associé :</label>
                <select id="id_utilisateur_modifier" name="id_utilisateur" required>
                    <option value="">Sélectionnez un utilisateur</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id_utilisateur'] ?>">
                            <?= $user['prenom'] . " " . $user['nom'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="etat_vente_modifier">État de la vente :</label>
                <select id="etat_vente_modifier" name="etat_vente" required>
                    <option value="Enregistrée">Enregistrée</option>
                    <option value="Annulée">Annulée</option>
                    <option value="EnCours">En cours</option>
                </select>

                <label>Produits vendus :</label>
                <div id="produits-container-modifier">
                    <?php foreach ($produits as $produit): ?>
                        <div>
                            <input type="checkbox" name="produits[]" value="<?= $produit['id_produit'] ?>">
                            <?= $produit['intitule_produit'] ?> - <?= $produit['prix_unitaire_produit'] ?> €
                            <input type="number" name="quantite_<?= $produit['id_produit'] ?>" min="1" placeholder="Quantité">
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit">Modifier la vente</button>
            </form>
        </div>
    </div>

</body>
</html>

