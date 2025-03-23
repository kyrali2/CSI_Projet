<?php

session_start();

include('connexion_db.php');

$query_produits = "SELECT p.id_produit, p.intitule_produit nom_produit, p.prix_unitaire_produit, p.photo_produit,p.quantite_stock,p.id_categorie
                        FROM produit p
                        JOIN categorie c ON p.id_categorie = c.id_categorie";
$stmt_produits = $conn->prepare($query_produits);
$stmt_produits->execute();
$produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

$query_categories = "SELECT c.id_categorie, c.nom_categorie
                        FROM categorie c";
$stmt_categories = $conn->prepare($query_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modif_produit'])) {
    $id_produit = $_POST['id_produit'];
    $nom_produit = $_POST['nom'];
    $prix_unitaire_produit = $_POST['prix'];
    $quantite_stock = $_POST['quantite_stock'];
    $id_categorie = $_POST['id_categorie'];
    $query_check_intitule_produit = "SELECT COUNT(*) FROM produit WHERE intitule_produit = :intitule_produit";
        $stmt_check_intitule_produit = $conn->prepare($query_check_intitule_produit);
        $stmt_check_intitule_produit->execute([':intitule_produit' => $nom_produit]);
        $intitule_produit_exists = $stmt_check_intitule_produit->fetchColumn();
        $modif = $stmt_check_intitule_produit->fetchAll(PDO::FETCH_ASSOC);
        
        if ($intitule_produit_exists > 0) {
            $query_check_produit = "SELECT id_produit FROM produit WHERE intitule_produit = :intitule_produit";
            $stmt_check_produit = $conn->prepare($query_check_produit);
            $stmt_check_produit->execute([':intitule_produit' => $nom_produit]);
            $prod = $stmt_check_produit->fetchAll(PDO::FETCH_ASSOC);
            if($prod[0]['id_produit']!=$id_produit){
            echo "Ce produit existe déjà.";
            exit;
            }
        }
    $query_update_produit = "UPDATE produit 
                                    SET intitule_produit = :nom_produit, 
                                        prix_unitaire_produit = :prix_unitaire_produit, 
                                        quantite_stock = :quantite_stock, 
                                        id_categorie = :id_categorie 
                                    WHERE id_produit = :id_produit";

$stmt_update_produit = $conn->prepare($query_update_produit);
$stmt_update_produit->execute([
    ':nom_produit' => $nom_produit,
    ':prix_unitaire_produit' => $prix_unitaire_produit,
    ':quantite_stock' => $quantite_stock,
    ':id_categorie' => $id_categorie,
    ':id_produit' => $id_produit
]);


    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_produit'])) {
    $nom_produit = $_POST['nom'];
    $prix_unitaire_produit = $_POST['prix'];
    $quantite_stock = $_POST['quantite_stock'];
    $id_categorie = $_POST['id_categorie'];
    $query_check_intitule_produit = "SELECT COUNT(*) FROM produit WHERE intitule_produit = :intitule_produit";
        $stmt_check_intitule_produit = $conn->prepare($query_check_intitule_produit);
        $stmt_check_intitule_produit->execute([':intitule_produit' => $nom_produit]);
        $intitule_produit_exists = $stmt_check_intitule_produit->fetchColumn();
        
        if ($intitule_produit_exists > 0) {
            echo "Ce produit existe déjà.";
            exit;
        }
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $autorisé = ['jpg', 'jpeg', 'png'];
        $fileInfo = pathinfo($_FILES['photo']['name']);
        $extension = strtolower($fileInfo['extension']);

        if (in_array($extension, $autorisé)) {
            $chemin = "img/produit/" . $_FILES['photo']['name'];

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $chemin)) {
                $photo_produit = $_FILES['photo']['name'];
            } else {
                echo "Erreur lors de l'upload de l'image.";
                exit;
            }
        } else {
            echo "Type de fichier non autorisé.";
            exit;
        }
    } else {       
        echo "Erreur lors de l'upload du fichier.";
        exit;
    }

    $query_add_produit = "INSERT INTO produit (intitule_produit, prix_unitaire_produit, quantite_stock, id_categorie, photo_produit)
                             VALUES (:nom_produit, :prix_unitaire_produit, :quantite_stock, :id_categorie, :photo_produit)";

$stmt_add_produit = $conn->prepare($query_add_produit);
$stmt_add_produit->execute([
    ':nom_produit' => $nom_produit,
    ':prix_unitaire_produit' => $prix_unitaire_produit,
    ':quantite_stock' => $quantite_stock,
    ':id_categorie' => $id_categorie,
    ':photo_produit' => $photo_produit
]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supprimer_produit'])) {
    $id_produit = $_POST['id_produit'];

    $query_get_photo = "SELECT photo_produit FROM produit WHERE id_produit = :id_produit";
    $stmt_get_photo = $conn->prepare($query_get_photo);
    $stmt_get_photo->execute([':id_produit'=> $id_produit]);
    $photo_produit = $stmt_get_photo->fetchColumn();

    $query_delete_produit = "DELETE FROM produit WHERE id_produit = :id_produit";
    $stmt_delete_produit = $conn->prepare($query_delete_produit);
    $stmt_delete_produit->execute([':id_produit'=> $id_produit]);

    if ($photo_produit) {
        $cheminImage = 'img/produit/' . $photo_produit;
        if (file_exists($cheminImage)) {
            unlink($cheminImage); 
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_produit']) && isset($_POST['supprimer_produit'])) {
    $id_produit = $_POST['id_produit'];

    $query_delete_produit = "DELETE FROM produit WHERE id_produit = :id_produit";
    $stmt_delete_produit = $conn->prepare($query_delete_produit);
    $stmt_delete_produit->execute([':id_produit'=> $id_produit]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_categorie'])) {
    $nom_categorie = $_POST['nom'];

    $query_check_nom_categorie = "SELECT COUNT(*) FROM categorie WHERE nom_categorie = :nom_categorie";
        $stmt_check_nom_categorie = $conn->prepare($query_check_nom_categorie);
        $stmt_check_nom_categorie->execute([':nom_categorie' => $nom_categorie]);
        $categorie_exists = $stmt_check_nom_categorie->fetchColumn();
        
        if ($categorie_exists > 0) {
            echo "Cette categorie existe déjà.";
            exit;
        }
    $query_update_categorie = "INSERT INTO categorie (nom_categorie)
                                    VALUES (:nom_categorie)";
    $stmt_add_categorie = $conn->prepare($query_update_categorie);
    $stmt_add_categorie->execute([':nom_categorie'=> $nom_categorie]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supprimer_categorie'])) {
    $id_categorie = $_POST['id_categorie'];

    try {
        $conn->beginTransaction();

        $query_delete_produits = "DELETE FROM produit WHERE id_categorie = :id_categorie";
        $stmt_delete_produits = $conn->prepare($query_delete_produits);
        $stmt_delete_produits->execute([':id_categorie'=> $id_categorie]);


            $query_delete_categorie = "DELETE FROM categorie WHERE id_categorie = :id_categorie";
            $stmt_delete_categorie = $conn->prepare($query_delete_categorie);
            $stmt_delete_categorie->execute([':id_categorie'=> $id_categorie]);
    
            $conn->commit();
    
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            echo "Erreur lors de la suppression : " . $e->getMessage();
        }
    }    
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion des stocks</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/stock.css">
        <link rel="stylesheet" href="css/onglet.css">
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
    <div class="container mt-5">
        <div class="text-center">
            <h2 class="fond">Gestion des stocks</h2>
        </div>
        <div class="row">
            <?php foreach ($categories as $categorie) { ?>
                <div class="col-md-4">
                    <div class="produit-card">
                        <h4><?= ($categorie['nom_categorie']) ?></h4>
                        <table class="table table-bordered text-center tableau">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Nom</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $produitsTrouves = false;

                                foreach ($produits as $produit) {
                                    if ($produit['id_categorie'] == $categorie['id_categorie']) {
                                        $produitsTrouves = true; 
                                        $cheminImage = "img/produit/" . ($produit['photo_produit']);
                                        ?>
                                        <tr>
                                        <td>
                                            <img src="<?= $cheminImage ?>" alt="<?= ($produit['nom_produit']) ?>" 
                                                 class="img-thumbnail" width="80">
                                        </td>
                                            <td><strong><?= ($produit['nom_produit']) ?></strong></td>
                                            <td><?= ($produit['prix_unitaire_produit']) ?></td>
                                            <td><?= ($produit['quantite_stock']) ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php if (!$produitsTrouves) { ?>
                            <p class="text-muted">Aucun produit en stock dans cette catégorie.</p>
                        <?php } ?>

                    </div>
                </div>
            <?php } ?>

            <?php if (empty($produits)) { ?>
                <p class="text-center text-danger">Aucun produit trouvé.</p>
            <?php } ?>
        </div>
        <button class="btn btn-success mb-3" onclick="ouvrirAjoutProduitForm()">Ajouter un Produit</button>
        <div id="modalAddProduitForm" class="modal" style="display: none;">
            <div class="modal-content">
                <h4>Ajouter un nouveau Produit</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ajouter_produit" value="1">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du produit</label>
                        <input type="text" class="form-control" id="nom" name="nom"
                            value="<?= ($produit['nom_produit']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="prix" class="form-label">Prix</label>
                        <input type="number" class="form-control" id="prix" name="prix"
                            value="<?= ($produit['prix_unitaire_produit']) ?>" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="quantite_stock" class="form-label">Quantité en stock</label>
                        <input type="number" class="form-control" id="quantite_stock" name="quantite_stock"
                            value="<?= ($produit['quantite_stock']) ?>" required min="0" step="1">
                    </div>
                    <div class="mb-3">
                        <label for="id_categorie" class="form-label">Catégorie</label>
                        <select class="form-control" id="id_categorie" name="id_categorie" required>
                            <?php foreach ($categories as $cat) { ?>
                                <option value="<?= ($cat['id_categorie']) ?>"
                                    <?= ($produit['id_categorie'] == $cat['id_categorie']) ? 'selected' : '' ?>>
                                    <?= ($cat['nom_categorie']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo du produit</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer le produit</button>
                </form>
            </div>
        </div>
        <button class="btn btn-success mb-3" onclick="ouvrirModifProduitForm()">Modifier un produit</button>
        <div id="modalEditProduit" class="modal" style="display: none;">
            <div class="modal-content">
                <h4>Modifier <?= ($produit['nom_produit']) ?></h4>
                <form method="POST">
                    <input type="hidden" name="modif_produit" value="1">
                    <div class="mb-3">
                        <label for="id_produit" class="form-label">Sélectionner le produit à modifier</label>
                        <select class="form-control" id="id_produit" name="id_produit" required>
                            <?php foreach ($produits as $produit) { ?>
                                <option value="<?= ($produit['id_produit']) ?>"
                                    <?= ($produit['id_produit'] == $produit['id_produit']) ? 'selected' : '' ?>>
                                    <?= ($produit['nom_produit']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du produit</label>
                        <input type="text" class="form-control" id="nom" name="nom"
                            value="<?= ($produit['nom_produit']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="prix" class="form-label">Prix</label>
                        <input type="number" class="form-control" id="prix" name="prix"
                            value="<?= ($produit['prix_unitaire_produit']) ?>" required min="0" step="0.01">
                    </div>

                    <div class="mb-3">
                        <label for="quantite_stock" class="form-label">Quantité en stock</label>
                        <input type="number" class="form-control" id="quantite_stock" name="quantite_stock"
                            value="<?= ($produit['quantite_stock']) ?>" required min="0" step="1">
                    </div>

                    <div class="mb-3">
                        <label for="id_categorie" class="form-label">Catégorie</label>
                        <select class="form-control" id="id_categorie" name="id_categorie" required>
                            <?php foreach ($categories as $cat) { ?>
                                <option value="<?= ($cat['id_categorie']) ?>"
                                    <?= ($produit['id_categorie'] == $cat['id_categorie']) ? 'selected' : '' ?>>
                                    <?= ($cat['nom_categorie']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                </form>
            </div>
        </div>
        <button type="button" class="btn btn-danger mb-3" onclick="ouvrirSuprProduitModal()">Supprimer un
            produit</button>
        <div id="deleteProduitModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="fermerSuprProduitModal()">&times;</span>
                <form method="POST">
                    <div class="mb-3">
                        <label for="id_produit" class="form-label">Selectionnez le produit : </label>
                        <select class="form-control" id="id_produit" name="id_produit" required>
                            <?php foreach ($produits as $produit) { ?>
                                <option value="<?= ($produit['id_produit']) ?>"
                                    <?= ($produit['id_produit'] == $produit['id_produit']) ? 'selected' : '' ?>>
                                    <?= ($produit['nom_produit']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <h4>Êtes-vous sûr de vouloir supprimer ce produit ?</h4>
                    <button type="submit" class="btn btn-danger" name="supprimer_produit">Supprimer</button>
                    <button type="button" class="btn btn-secondary" onclick="fermerSuprProduitModal()">Annuler</button>
                </form>
            </div>
        </div>
        <br>
        <button class="btn btn-success mb-3" onclick="ouvrirAjoutCategorieForm()">Ajouter une Catégorie</button>
        <div id="modalAddCategorieForm" class="modal" style="display: none;">
            <div class="modal-content">
                <h4>Ajouter une nouvelle catégorie</h4>
                <form method="POST">
                    <input type="hidden" name="ajouter_categorie" value="1">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="nom" name="nom"
                            value="<?= ($categorie['nom_categorie']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer la catégorie</button>
                </form>
            </div>
        </div>
        <button type="button" class="btn btn-danger mb-3" onclick="ouvrirSuprCategorieModal()">Supprimer une
            catégorie</button>
        <div id="deleteCategorieModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="fermerSuprCategorieModal()">&times;</span>
                <form method="POST">
                    <div class="mb-3">
                        <label for="id_categorie" class="form-label">Selectionnez la catégorie : </label>
                        <select class="form-control" id="id_categorie" name="id_categorie" required>
                            <?php foreach ($categories as $cat) { ?>
                                <option value="<?= ($cat['id_categorie']) ?>"
                                    <?= ($cat['id_categorie'] == $cat['id_categorie']) ? 'selected' : '' ?>>
                                    <?= ($cat['nom_categorie']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <h4>Êtes-vous sûr de vouloir supprimer cette categorie ?</h4>
                    <button type="submit" class="btn btn-danger" name="supprimer_categorie">Supprimer</button>
                    <button type="button" class="btn btn-secondary" onclick="fermerSuprCategorieModal()">Annuler</button>
                </form>
            </div>
        </div>
</body>
<script src="js/stock.js"></script>
<script src="js/onglet.js"></script>

</html>