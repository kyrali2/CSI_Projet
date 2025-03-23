<?php

session_start();

include('connexion_db.php');

$query_woofers = "SELECT w.id_woofer, u.nom, u.prenom, u.email, u.tel, w.date_arrivee, w.date_depart, u.adresse,u.photo,
                GROUP_CONCAT(c.nom_competence SEPARATOR ', ') AS competences
                FROM woofer w
                JOIN utilisateur u ON u.id_utilisateur = w.id_woofer
                LEFT JOIN wooferpossedecompetence wp ON w.id_woofer = wp.id_woofer
                LEFT JOIN competence c ON wp.id_competence = c.id_competence
                WHERE w.date_depart > CURDATE()
                GROUP BY w.id_woofer";
$stmt_woofers = $conn->prepare($query_woofers);
$stmt_woofers->execute();
$woofers = $stmt_woofers->fetchAll(PDO::FETCH_ASSOC);


$query_taches = "SELECT t.intitule_tache, t.duree_tache, t.etat_tache, u.nom, u.prenom, t.jour,t.id_tache,w.id_woofer
                FROM tache t
                JOIN wooferfaittache w ON t.id_tache = w.id_tache
                JOIN utilisateur u ON w.id_woofer = u.id_utilisateur";
$stmt_taches = $conn->prepare($query_taches);
$stmt_taches->execute();
$taches = $stmt_taches->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modif_woofer'])) {

    $id_utilisateur = $_POST['id_utilisateur'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $tel = $_POST['tel'];
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart = $_POST['date_depart'];
    $adresse = $_POST['adresse'];
    $competences_text = isset($_POST['competences']) ? $_POST['competences'] : '';

        $query_check_email = "SELECT COUNT(*) FROM utilisateur WHERE email = :email";
        $stmt_check_email = $conn->prepare($query_check_email);
        $stmt_check_email->execute([':email' => $email]);
        $email_exists = $stmt_check_email->fetchColumn();
        
        if ($email_exists > 0) {
            $query_check_utilisateur = "SELECT id_utilisateur FROM utilisateur WHERE email = :email";
            $query_check_utilisateur = $conn->prepare($query_check_utilisateur);
            $query_check_utilisateur->execute([':email' => $email]);
            $uti = $query_check_utilisateur->fetchAll(PDO::FETCH_ASSOC);
            if($uti[0]['id_utilisateur']!=$id_utilisateur){
            echo "Cet email est déjà utilisé.";
            exit;
            }
        }

        $query_check_tel = "SELECT COUNT(*) FROM utilisateur WHERE tel = :tel";
        $stmt_check_tel = $conn->prepare($query_check_tel);
        $stmt_check_tel->execute([':tel' => $tel]);
        $tel_exists = $stmt_check_tel->fetchColumn();
        
        if ($tel_exists > 0) {
            $query_check_utilisateur = "SELECT id_utilisateur FROM utilisateur WHERE tel = :tel";
            $query_check_utilisateur = $conn->prepare($query_check_utilisateur);
            $query_check_utilisateur->execute([':tel' => $tel]);
            $uti = $query_check_utilisateur->fetchAll(PDO::FETCH_ASSOC);
            if($uti[0]['id_utilisateur']!=$id_utilisateur){
            echo "Ce numéro de téléphone est déjà utilisé.";
            exit;
            }
        }

    $query_update_utilisateur = "UPDATE utilisateur 
                                SET nom = :nom, prenom = :prenom, email = :email, tel = :tel, adresse = :adresse
                                WHERE id_utilisateur = :id_utilisateur";
    $stmt_update_utilisateur = $conn->prepare($query_update_utilisateur);
    $stmt_update_utilisateur->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':email' => $email,
        ':tel' => $tel,
        ':id_utilisateur' => $id_utilisateur,
        ':adresse' => $adresse
    ]);

    $query_update_woofer = "UPDATE woofer 
                            SET date_arrivee = :date_arrivee, date_depart = :date_depart 
                            WHERE id_woofer = :id_utilisateur";
    $stmt_update_woofer = $conn->prepare($query_update_woofer);
    $stmt_update_woofer->execute([
        ':date_arrivee' => $date_arrivee,
        ':date_depart' => $date_depart,
        ':id_utilisateur' => $id_utilisateur
    ]);

    $query_delete_competences = "DELETE FROM wooferpossedecompetence WHERE id_woofer = :id_utilisateur";
    $stmt_delete_competences = $conn->prepare($query_delete_competences);
    $stmt_delete_competences->execute([':id_utilisateur' => $id_utilisateur]);

    if (!empty($competences_text)) {
        $competences = array_map('trim', explode(',', $competences_text));

        foreach ($competences as $competence) {
            if (!empty($competence)) {
                $query_check_competence = "SELECT id_competence FROM competence WHERE nom_competence = :nom_competence";
                $stmt_check_competence = $conn->prepare($query_check_competence);
                $stmt_check_competence->execute([':nom_competence' => $competence]);
                $result = $stmt_check_competence->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $id_competence = $result['id_competence'];
                } else {
                    $query_insert_competence = "INSERT INTO competence (nom_competence) VALUES (:nom_competence)";
                    $stmt_insert_competence = $conn->prepare($query_insert_competence);
                    $stmt_insert_competence->execute([':nom_competence' => $competence]);
                    $id_competence = $conn->lastInsertId();
                }

                $query_insert_relation = "INSERT INTO wooferpossedecompetence (id_woofer, id_competence) VALUES (:id_woofer, :id_competence)";
                $stmt_insert_relation = $conn->prepare($query_insert_relation);
                $stmt_insert_relation->execute([
                    ':id_woofer' => $id_utilisateur,
                    ':id_competence' => $id_competence
                ]);
            }
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_woofer'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $tel = $_POST['tel'];
    $login = $_POST['login'];
    $mdp = $_POST['mdp'];
    $adresse = $_POST['adresse'];
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart = $_POST['date_depart'];
    $competences = isset($_POST['competences']) ? array_map('trim', explode(',', $_POST['competences'])) : [];

    try {
        $query_check_login = "SELECT COUNT(*) FROM utilisateur WHERE login = :login";
        $stmt_check_login = $conn->prepare($query_check_login);
        $stmt_check_login->execute([':login' => $login]);
        $login_exists = $stmt_check_login->fetchColumn();
        
        if ($login_exists > 0) {
            echo "Ce login est déjà pris.";
            exit;
        }

        $query_check_email = "SELECT COUNT(*) FROM utilisateur WHERE email = :email";
        $stmt_check_email = $conn->prepare($query_check_email);
        $stmt_check_email->execute([':email' => $email]);
        $email_exists = $stmt_check_email->fetchColumn();
        
        if ($email_exists > 0) {
            echo "Cet email est déjà utilisé.";
            exit;
        }

        $query_check_tel = "SELECT COUNT(*) FROM utilisateur WHERE tel = :tel";
        $stmt_check_tel = $conn->prepare($query_check_tel);
        $stmt_check_tel->execute([':tel' => $tel]);
        $tel_exists = $stmt_check_tel->fetchColumn();
        
        if ($tel_exists > 0) {
            echo "Ce numéro de téléphone est déjà enregistré.";
            exit;
        }

        $conn->beginTransaction();

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $fileInfo = pathinfo($_FILES['photo']['name']);
            $fileExt = strtolower($fileInfo['extension']);
    
            if (in_array($fileExt, $allowed)) {
                $uploadPath = "img/woofer/" . $_FILES['photo']['name'];
    
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $photo_woofer = $_FILES['photo']['name'];
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

        $query_insert_utilisateur = "INSERT INTO utilisateur (nom, prenom, email, tel, photo) 
                                    VALUES (:nom, :prenom, :email, :tel, :photo)";
        $stmt_insert_utilisateur = $conn->prepare($query_insert_utilisateur);
        $stmt_insert_utilisateur->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':tel' => $tel,
            ':photo' => $photo_woofer
        ]);

        $id_utilisateur = $conn->lastInsertId();

        $query_insert_user = "UPDATE utilisateur 
                            SET login = :login, mot_de_passe = :mdp, adresse = :adresse 
                            WHERE id_utilisateur = :id_utilisateur";
        $stmt_insert_user = $conn->prepare($query_insert_user);
        $stmt_insert_user->execute([
            ':id_utilisateur' => $id_utilisateur,
            ':login' => $login,
            ':mdp' => password_hash($mdp, PASSWORD_BCRYPT),
            ':adresse' => $adresse
        ]);

        $query_insert_woofer = "INSERT INTO woofer (id_woofer, date_arrivee, date_depart) 
                                VALUES (:id_woofer, :date_arrivee, :date_depart)";
        $stmt_insert_woofer = $conn->prepare($query_insert_woofer);
        $stmt_insert_woofer->execute([
            ':id_woofer' => $id_utilisateur,
            ':date_arrivee' => $date_arrivee,
            ':date_depart' => $date_depart
        ]);

        if (!empty($competences)) {
            $query_insert_competences = "INSERT INTO wooferpossedecompetence (id_woofer, id_competence) 
                                        VALUES (:id_woofer, :id_competence)";
            $stmt_insert_competences = $conn->prepare($query_insert_competences);

            foreach ($competences as $competence) {
                $query_check_competence = "SELECT id_competence FROM competence WHERE nom_competence = :nom_competence";
                $stmt_check_competence = $conn->prepare($query_check_competence);
                $stmt_check_competence->execute([':nom_competence' => $competence]);
                $result = $stmt_check_competence->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $id_competence = $result['id_competence'];
                } else {
                    $query_insert_new_competence = "INSERT INTO competence (nom_competence) VALUES (:nom_competence)";
                    $stmt_insert_new_competence = $conn->prepare($query_insert_new_competence);
                    $stmt_insert_new_competence->execute([':nom_competence' => $competence]);

                    $id_competence = $conn->lastInsertId();
                }

                $stmt_insert_competences->execute([
                    ':id_woofer' => $id_utilisateur,
                    ':id_competence' => $id_competence
                ]);
            }
        }

        $conn->commit();

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Erreur lors de l'ajout du woofer : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_tache'])) {

    $intitule_tache = $_POST['intitule_tache'];
    $duree_tache = $_POST['duree_tache'];
    $jour = $_POST['jour'];
    $id_woofer = $_POST['id_woofer'];

    try {
        $conn->beginTransaction();

        $query_insert_tache = "INSERT INTO tache (intitule_tache, duree_tache, etat_tache, jour) 
                            VALUES (:intitule_tache, :duree_tache, 'a réaliser', :jour)";
        $stmt_insert_tache = $conn->prepare($query_insert_tache);
        $stmt_insert_tache->execute([
            ':intitule_tache' => $intitule_tache,
            ':duree_tache' => $duree_tache,
            ':jour' => $jour
        ]);

        $id_tache = $conn->lastInsertId();

        $query_insert_relation = "INSERT INTO wooferfaittache (id_woofer, id_tache) 
                                VALUES (:id_woofer, :id_tache)";
        $stmt_insert_relation = $conn->prepare($query_insert_relation);
        $stmt_insert_relation->execute([
            ':id_woofer' => $id_woofer,
            ':id_tache' => $id_tache
        ]);

        $conn->commit();

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Erreur lors de l'ajout de la tâche : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modif_tache'])) {
    $id_tache = $_POST['id_tache'];
    $intitule_tache = $_POST['intitule_tache'];
    $duree_tache = $_POST['duree_tache'];
    $jour = $_POST['jour'];
    $etat_tache = $_POST['etat_tache'];
    $id_woofer = $_POST['id_woofer'];

    $query_update_tache = "UPDATE tache SET intitule_tache = :intitule_tache, duree_tache = :duree_tache, jour = :jour, etat_tache = :etat_tache WHERE id_tache = :id_tache";
    $stmt_update_tache = $conn->prepare($query_update_tache);
$stmt_update_tache->execute([
    ':intitule_tache' => $intitule_tache,
    ':duree_tache' => $duree_tache,
    ':jour' => $jour,
    ':etat_tache' => $etat_tache,
    ':id_tache' => $id_tache
]);

    $query_update_woofer = "UPDATE wooferfaittache SET id_woofer = :id_woofer WHERE id_tache = :id_tache";
    $stmt_update_woofer = $conn->prepare($query_update_woofer);
$stmt_update_woofer->execute([
    ':id_woofer' => $id_woofer,
    ':id_tache' => $id_tache
]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_tache']) && isset($_POST['supprimer_tache'])) {
    $id_tache = $_POST['id_tache'];

    $query_delete_tache = "DELETE FROM tache WHERE id_tache = :id_tache";
    $stmt_delete_tache = $conn->prepare($query_delete_tache);
    $stmt_delete_tache->execute([':id_tache'=> $id_tache]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Woofers et Planning</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/gestionWoofer.css">
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
    <h2 class="fond">Liste des Woofers</h2>
</div>
<div class="row">
    <?php foreach ($woofers as $woofer) { 
        $cheminImage = "img/woofer/" . ($woofer['photo']);?>
        <div class="col-md-4">
            <div class="woofer-card">
                <h4><?= ($woofer['nom']) ?> <?= ($woofer['prenom']) ?></h4>
                <img src="<?= $cheminImage ?>" alt="<?= ($woofer['nom']) ?>" class="img-thumbnail" width="80">
                <p><strong>Email : </strong> <?= ($woofer['email']) ?></p>
                <p><strong>Tel : </strong> <?= ($woofer['tel']) ?></p>
                <p><strong>Date arrivée : </strong> <?= ($woofer['date_arrivee']) ?></p>
                <p><strong>Date départ : </strong> <?= ($woofer['date_depart']) ?></p>
                <p><strong>Adresse : </strong> <?= ($woofer['adresse']) ?></p>
                <p><strong>Competences : </strong> <?= ($woofer['competences'] ?: 'Aucune') ?></p>
                    
                <button class="btn btn-success" onclick="montrerModifWooferForm(<?= $woofer['id_woofer'] ?>)">Modifier</button>
                    <div id="modalModifWoofer<?= $woofer['id_woofer'] ?>" class="modal" style="display: none;">
                    <div class="modal-content">
                        <h4>Modifier les incompetences de <?= ($woofer['nom']) ?> <?= ($woofer['prenom']) ?></h4>
                        <form method="POST">
                            <input type="hidden" name="modif_woofer" value="1">
                            <input type="hidden" name="id_utilisateur" value="<?= $woofer['id_woofer'] ?>">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?= ($woofer['nom']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?= ($woofer['prenom']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= ($woofer['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="tel" class="form-label">Téléphone</label>
                                <input type="text" class="form-control" id="tel" name="tel" value="<?= ($woofer['tel']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="date_arrivee" class="form-label">Date d'arrivée</label>
                                <input type="date" class="form-control" id="date_arrivee" name="date_arrivee" value="<?= ($woofer['date_arrivee']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="date_depart" class="form-label">Date de départ</label>
                                <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?= ($woofer['date_depart']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" value="<?= ($woofer['adresse']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="competence" class="form-label">Compétences</label>
                                <input type="text" class="form-control" id="competences" name="competences" value="<?= ($woofer['competences']) ?>" required>
                            </div>

                                    <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <?php if (empty($woofers)) { ?>
                <p class="text-center text-danger">Aucun woofer trouvé.</p>
            <?php } ?>
        </div>
        <button class="btn btn-success mb-3" onclick="montrerAjoutWooferForm()">Ajouter un Woofer</button>

        <div id="modalAjoutWooferForm" class="modal" style="display: none;">
            <div class="modal-content">
                <h4>Ajouter un nouveau Woofer</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ajouter_woofer" value="1">
                    <div class="mb-3"><label>Nom :</label> <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-3"><label>Prénom :</label> <input type="text" name="prenom" class="form-control"
                            required></div>
                    <div class="mb-3"><label>Email :</label> <input type="email" name="email" class="form-control"
                            required></div>
                    <div class="mb-3"><label>Téléphone :</label> <input type="text" name="tel" class="form-control"
                            required></div>
                    <div class="mb-3"><label>Login :</label> <input type="text" name="login" class="form-control"
                            required></div>
                    <div class="mb-3"><label>Mot de passe :</label> <input type="text" name="mdp" class="form-control"
                            required></div>
                    <div class="mb-3"><label>Adresse :</label> <input type="text" name="adresse" class="form-control"
                            required></div>
                    <div class="mb-3"><label>Date d'arrivée :</label> <input type="date" name="date_arrivee"
                            class="form-control" required></div>
                    <div class="mb-3"><label>Date de départ :</label> <input type="date" name="date_depart"
                            class="form-control" required></div>
                    <div class="mb-3"><label>cCompétences :</label> <input type="text" name="competences"
                            class="form-control" required></div>
                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="text-center">
            <h2 class="fond">Planning des Tâches</h2>
        </div>
        <table class="table table-bordered text-center tableau">
            <thead>
                <tr>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                    <th>Samedi</th>
                    <th>Dimanche</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $jours_semaine = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"];

                $taches_par_jour = array_fill_keys($jours_semaine, []);

                foreach ($taches as $tache) {
                    $jour = ucfirst(strtolower($tache['jour']));
                    if (in_array($jour, $jours_semaine)) {
                        $taches_par_jour[$jour][] = $tache;
                    }
                }

                $max_taches = max(array_map('count', $taches_par_jour));

                for ($i = 0; $i < $max_taches; $i++) {
                    ?> <tr> <?php
                    foreach ($jours_semaine as $jour) {
                        ?><td><?php
                        if (isset($taches_par_jour[$jour][$i])) {
                            $tache = $taches_par_jour[$jour][$i];
                            ?> <strong> <?=($tache['intitule_tache'])?></strong><br>
                               Durée : <?= ($tache['duree_tache'])?><br>
                               État :  <?=($tache['etat_tache'])?><br>
                               Attribué à : <?=($tache['nom']) . " " . ($tache['prenom']) ?><br>
                            <button class="btn btn-success btn-sm" onclick="toggleModifTache(<?=($tache['id_tache'])?>)">Modifier</button>
                                <div id="modalModifTache<?=($tache['id_tache'])?>" class="modal">
                                    <div class="modal-content">
                                        <span class="close-btn" onclick="closeEditForm(<?=($tache['id_tache'])?>)">&times;</span>
                                        <h4>Modifier la tâche</h4>
                                        <form method="POST">
                                        <input type="hidden" name="id_tache" value="<?=($tache['id_tache'])?>">
                                        <div class="mb-3">
                                            <label for="intitule_tache">Intitulé de la tâche :</label>
                                            <input type="text" class="form-control" id="intitule_tache" name="intitule_tache" value="<?=($tache['intitule_tache'])?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="duree_tache">Durée :</label>
                                            <input type="text" class="form-control" id="duree_tache" name="duree_tache" value="<?=($tache['duree_tache'])?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="etat_tache">État :</label>
                                            <select class="form-control" id="etat_tache" name="etat_tache" required>
                                                <option value="A réaliser" <?=($tache['etat_tache'] == 'A réaliser' ? 'selected' : '')?>>A réaliser</option>
                                                <option value="En cours" <?=($tache['etat_tache'] == 'En cours' ? 'selected' : '')?>>En cours</option>
                                                <option value="Terminée" <?=($tache['etat_tache'] == 'Terminée' ? 'selected' : '')?>>Terminée</option>
                                            </select>
                                        </div>
                                            <div class="mb-3">
                                            <label for="jour">Jour :</label>
                                            <select class="form-control" id="jour" name="jour" required>
                                                <option value="Lundi" <?=($tache['jour'] == 'Lundi' ? 'selected' : '')?>>Lundi</option>
                                                <option value="Mardi" <?=($tache['jour'] == 'Mardi' ? 'selected' : '')?>>Mardi</option>
                                                <option value="Mercredi" <?=($tache['jour'] == 'Mercredi' ? 'selected' : '')?>>Mercredi</option>
                                                <option value="Jeudi" <?=($tache['jour'] == 'Jeudi' ? 'selected' : '')?>>Jeudi</option>
                                                <option value="Vendredi" <?=($tache['jour'] == 'Vendredi' ? 'selected' : '')?>>Vendredi</option>
                                                <option value="Samedi" <?=($tache['jour'] == 'Samedi' ? 'selected' : '')?>>Samedi</option>
                                                <option value="Dimanche" <?=($tache['jour'] == 'Dimanche' ? 'selected' : '')?>>Dimanche</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="id_woofer">Attribuer à un Woofer :</label>
                            <select class="form-control" id="id_woofer" name="id_woofer" required>
                            <?php foreach ($woofers as $woofer) {?>
                                <option value="<?=($woofer['id_woofer'])?>"
                                    <?=($tache['id_woofer'] == $woofer['id_woofer'] ? 'selected' : '')?>>
                                    <?=($woofer['nom']) . ' ' . ($woofer['prenom'])?>
                                </option>
                            <?php } ?>
                            </select>
                                            </div>
                                        <button type="submit" class="btn btn-primary" name="modif_tache">Sauvegarder les modifications</button>
                                        </form>
                                    </div>
                                </div>
                            
                            <button type="button" class="btn btn-danger btn-sm" onclick="montrerSuprTacheModal(<?=($tache['id_tache'])?>)">Supprimer</button>
                            <div id="suprTacheModal" class="modal">
                        <div class="modal-content">
                            <span class="close-btn" onclick="fermerSuprTacheModal()">&times;</span>
                            <h4>Êtes-vous sûr de vouloir supprimer cette tâche ?</h4>
                            <form method="POST">
                                <input type="hidden" name="id_tache" id="taskIdToDelete">
                                <button type="submit" class="btn btn-danger" name="supprimer_tache">Supprimer</button>
                                <button type="button" class="btn btn-secondary" onclick="fermerSuprTacheModal()">Annuler</button>
                            </form>
                        </div>
                    </div>
                        <?php } else { ?>
                            -
                        <?php } ?>
                        </td>
                    <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <button class="btn btn-success mb-3" onclick="montrerAjoutTacheForm()">Ajouter une Tâche</button>
        <div id="modalAjoutTacheForm" class="modal" style="display: none;">
            <div class="modal-content">
                <h4>Ajouter une nouvelle Tâche</h4>
                <form method="POST">
                    <input type="hidden" name="ajouter_tache" value="1">
                    <div class="mb-3">
                        <label>Intitulé de la tâche :</label>
                        <input type="text" name="intitule_tache" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Durée de la tâche :</label>
                        <input type="text" name="duree_tache" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Jour de la tâche :</label>
                        <select name="jour" class="form-control" required>
                            <option value="Lundi">Lundi</option>
                            <option value="Mardi">Mardi</option>
                            <option value="Mercredi">Mercredi</option>
                            <option value="Jeudi">Jeudi</option>
                            <option value="Vendredi">Vendredi</option>
                            <option value="Samedi">Samedi</option>
                            <option value="Dimanche">Dimanche</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Attribué à :</label>
                        <select name="id_woofer" class="form-control" required>
                            <?php foreach ($woofers as $woofer) { ?>
                                <option value="<?= $woofer['id_woofer'] ?>">
                                    <?= ($woofer['nom']) ?>     <?= ($woofer['prenom']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter la tâche</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/gestionWoofer.js"></script>
    <script src="js/onglet.js"></script>
</body>

</html>