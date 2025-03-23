<?php
session_start();
include 'connexion_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'supprimer' && isset($_POST['id_session'])) {
    $id_session = $_POST['id_session'];

    $conn->beginTransaction();

    $sql_delete_woofer_session = "DELETE FROM WooferAnimeSession WHERE id_session = :id_session";
    $stmt_delete_woofer_session = $conn->prepare($sql_delete_woofer_session);
    $stmt_delete_woofer_session->bindParam(':id_session', $id_session);
    $stmt_delete_woofer_session->execute();

    $sql_delete_participant_session = "DELETE FROM ParticipantSession WHERE id_session = :id_session";
    $stmt_delete_participant_session = $conn->prepare($sql_delete_participant_session);
    $stmt_delete_participant_session->bindParam(':id_session', $id_session);
    $stmt_delete_participant_session->execute();

    $sql_delete_session = "DELETE FROM Session WHERE id_session = :id_session";
    $stmt_delete_session = $conn->prepare($sql_delete_session);
    $stmt_delete_session->bindParam(':id_session', $id_session);
    $stmt_delete_session->execute();

    $sql_delete_atelier = "DELETE FROM Atelier WHERE id_atelier = (SELECT id_atelier FROM Session WHERE id_session = :id_session)";
    $stmt_delete_atelier = $conn->prepare($sql_delete_atelier);
    $stmt_delete_atelier->bindParam(':id_session', $id_session);
    $stmt_delete_atelier->execute();

    $conn->commit();

    header('Location: atelier.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_form']) && $_POST['action_form'] == 'ajouterAtelier') {
    $nom_atelier = $_POST['nom_atelier'];
    $prix_atelier = $_POST['prix_atelier'];
    $id_categorie = $_POST['id_categorie'];
    $date_session = $_POST['date_session']; 
    $etat_session = $_POST['etat_session'];
    $id_woofer = $_POST['id_woofer'];
    $numero_session = $_POST['numero_session'];


    $sql_check_exist = "SELECT s.id_session FROM Session s
                        JOIN Atelier a ON a.id_atelier = s.id_atelier
                        WHERE s.numero_session = :numero_session 
                        AND a.nom_atelier = :nom_atelier";
    $stmt_check_exist = $conn->prepare($sql_check_exist);
    $stmt_check_exist->bindParam(':numero_session', $numero_session);
    $stmt_check_exist->bindParam(':nom_atelier', $nom_atelier);
    $stmt_check_exist->execute();
    
    if ($stmt_check_exist->rowCount() > 0) {
        echo "<script>alert('Erreur : Session déjà existante');</script>";
        exit;
    }


    $current_date = date('Y-m-d'); 


    $date_only_session = substr($date_session, 0, 10); 


    if ($etat_session == 'En cours' && $date_only_session != $current_date) {
        echo "<script>alert('Erreur : La session en cours doit correspondre à la date d\'aujourd\'hui');</script>";
        exit;
    } elseif ($etat_session == 'Terminée' && $date_only_session > $current_date) {
        echo "<script>alert('Erreur : L\'atelier terminé ne peut pas avoir une date future');</script>";
        exit;
    } elseif ($etat_session == 'Programmée' && $date_only_session < $current_date) {
        echo "<script>alert('Erreur : La session programmée ne peut pas avoir lieu dans le passé');</script>";
        exit;
    }


    $conn->beginTransaction();


    $sql_check_atelier = "SELECT id_atelier FROM Atelier WHERE nom_atelier = :nom_atelier";
    $stmt_check_atelier = $conn->prepare($sql_check_atelier);
    $stmt_check_atelier->bindParam(':nom_atelier', $nom_atelier);
    $stmt_check_atelier->execute();

    if ($stmt_check_atelier->rowCount() > 0) {

        $atelier = $stmt_check_atelier->fetch();
        $id_atelier = $atelier['id_atelier'];
    } else {

        $sql_insert_atelier = "INSERT INTO Atelier (nom_atelier, prix_atelier, id_categorie)
                               VALUES (:nom_atelier, :prix_atelier, :id_categorie)";
        $stmt_insert_atelier = $conn->prepare($sql_insert_atelier);
        $stmt_insert_atelier->bindParam(':nom_atelier', $nom_atelier);
        $stmt_insert_atelier->bindParam(':prix_atelier', $prix_atelier);
        $stmt_insert_atelier->bindParam(':id_categorie', $id_categorie);
        $stmt_insert_atelier->execute();


        $id_atelier = $conn->lastInsertId();
    }


    $sql_insert_session = "INSERT INTO Session (date_session, etat_session, numero_session, id_atelier)
                           VALUES (:date_session, :etat_session, :numero_session, :id_atelier)";
    $stmt_insert_session = $conn->prepare($sql_insert_session);
    $stmt_insert_session->bindParam(':date_session', $date_session);
    $stmt_insert_session->bindParam(':etat_session', $etat_session);
    $stmt_insert_session->bindParam(':numero_session', $numero_session);
    $stmt_insert_session->bindParam(':id_atelier', $id_atelier);
    $stmt_insert_session->execute();


    $id_session = $conn->lastInsertId();
    $sql_assoc = "INSERT INTO WooferAnimeSession (id_woofer, id_session)
                  VALUES (:id_woofer, :id_session)";
    $stmt_assoc = $conn->prepare($sql_assoc);
    $stmt_assoc->bindParam(':id_woofer', $id_woofer);
    $stmt_assoc->bindParam(':id_session', $id_session);
    $stmt_assoc->execute();


    $conn->commit();


    header("Location: atelier.php");
    exit();
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_form']) && $_POST['action_form'] == 'accepterRejeter') {
    $id_participant = $_POST['id_participant'];
    $id_session = $_POST['id_session'];
    $action = $_POST['action'];

    if ($action == 'accepter') {
        $sql = "UPDATE ParticipantSession SET estValidé = TRUE WHERE id_participant = :id_participant AND id_session = :id_session";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_participant', $id_participant, PDO::PARAM_INT);
        $stmt->bindParam(':id_session', $id_session, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: atelier.php'); 
        exit;

    } elseif ($action == 'rejeter') {
        $sql = "DELETE FROM ParticipantSession WHERE id_participant = :id_participant AND id_session = :id_session";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_participant', $id_participant, PDO::PARAM_INT);
        $stmt->bindParam(':id_session', $id_session, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: atelier.php'); 
        exit;
    }
}

$sql_non_valides = "SELECT 
    p.nom, 
    p.prenom, 
    p.tel, 
    p.email, 
    a.nom_atelier, 
    s.numero_session,
    ps.id_session,
    ps.id_participant
FROM ParticipantSession ps
JOIN Participant p ON ps.id_participant = p.id_participant
JOIN Session s ON ps.id_session = s.id_session
JOIN Atelier a ON s.id_atelier = a.id_atelier
WHERE ps.estValidé = FALSE;
";

$stmt_non_valides = $conn->prepare($sql_non_valides);
$stmt_non_valides->execute();
$participants_non_valides = $stmt_non_valides->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT 
    a.id_atelier, 
    a.nom_atelier, 
    a.prix_atelier, 
    s.date_session, 
    s.etat_session, 
    u.prenom AS woofer_prenom, 
    u.nom AS woofer_nom,
    c.nom_categorie AS thematique,  
    s.numero_session,  
    s.id_session,
    COALESCE(
        GROUP_CONCAT(
            CASE WHEN ps.estValidé = 1 THEN CONCAT(p.prenom, ' ', p.nom) END
        ), 
        'Aucun participant inscrit'
    ) AS participants_inscrits  
FROM Atelier a
JOIN Session s ON a.id_atelier = s.id_atelier
LEFT JOIN WooferAnimeSession was ON s.id_session = was.id_session
LEFT JOIN Woofer w ON was.id_woofer = w.id_woofer
LEFT JOIN Utilisateur u ON w.id_woofer = u.id_utilisateur
LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie
LEFT JOIN ParticipantSession ps ON s.id_session = ps.id_session
LEFT JOIN Participant p ON ps.id_participant = p.id_participant  
GROUP BY a.id_atelier, s.id_session;
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$ateliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_users = "SELECT u.id_utilisateur, u.prenom, u.nom 
              FROM Utilisateur u
              JOIN Woofer w ON u.id_utilisateur = w.id_woofer";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

$sql_categories = "SELECT id_categorie, nom_categorie FROM Categorie";
$stmt_categories = $conn->prepare($sql_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id_session']) && !empty($_POST['id_session'])) {
        $id_session = $_POST['id_session'];
        $nom_atelier = $_POST['nom_atelier'];
        $prix_atelier = $_POST['prix_atelier'];
        $id_categorie = $_POST['id_categorie'];
        $date_session = $_POST['date_session'];
        $etat_session = $_POST['etat_session'];
        $id_woofer = $_POST['id_woofer'];
        $numero_session = $_POST['numero_session'];


        $sql_check_exist = "SELECT s.id_session FROM Session s
                            JOIN Atelier a ON a.id_atelier = s.id_atelier
                            WHERE s.numero_session = :numero_session 
                            AND a.nom_atelier = :nom_atelier 
                            AND s.id_session != :id_session"; 
        $stmt_check_exist = $conn->prepare($sql_check_exist);
        $stmt_check_exist->bindParam(':numero_session', $numero_session);
        $stmt_check_exist->bindParam(':nom_atelier', $nom_atelier);
        $stmt_check_exist->bindParam(':id_session', $id_session);
        $stmt_check_exist->execute();

        if ($stmt_check_exist->rowCount() > 0) {
            echo "<script>alert('Erreur : Session déjà existante avec ce numéro et cet atelier');</script>";
            exit;
        }


        $current_date = date('Y-m-d'); 
        $date_only_session = substr($date_session, 0, 10); 

        if ($etat_session == 'En cours' && $date_only_session != $current_date) {
            echo "<script>alert('Erreur : La session en cours doit correspondre à la date d\'aujourd\'hui');</script>";
            exit;
        } elseif ($etat_session == 'Terminée' && $date_only_session > $current_date) {
            echo "<script>alert('Erreur : L\'atelier terminé ne peut pas avoir une date future');</script>";
            exit;
        } elseif ($etat_session == 'Programmée' && $date_only_session < $current_date) {
            echo "<script>alert('Erreur : La session programmée ne peut pas avoir lieu dans le passé');</script>";
            exit;
        }

        try {
            $conn->beginTransaction();

            $sql_check_atelier = "SELECT id_atelier FROM Atelier WHERE nom_atelier = :nom_atelier";
            $stmt_check_atelier = $conn->prepare($sql_check_atelier);
            $stmt_check_atelier->bindParam(':nom_atelier', $nom_atelier);
            $stmt_check_atelier->execute();

            if ($stmt_check_atelier->rowCount() > 0) {

                $atelier = $stmt_check_atelier->fetch();
                $id_atelier = $atelier['id_atelier'];
            } else {
 
                $sql_insert_atelier = "INSERT INTO Atelier (nom_atelier, prix_atelier, id_categorie)
                                       VALUES (:nom_atelier, :prix_atelier, :id_categorie)";
                $stmt_insert_atelier = $conn->prepare($sql_insert_atelier);
                $stmt_insert_atelier->bindParam(':nom_atelier', $nom_atelier);
                $stmt_insert_atelier->bindParam(':prix_atelier', $prix_atelier);
                $stmt_insert_atelier->bindParam(':id_categorie', $id_categorie);
                $stmt_insert_atelier->execute();

  
                $id_atelier = $conn->lastInsertId();
            }


            $sql_update_session = "UPDATE Session 
                                   SET date_session = :date_session, 
                                       etat_session = :etat_session,
                                       numero_session = :numero_session,
                                       id_atelier = :id_atelier
                                   WHERE id_session = :id_session";
            $stmt_update_session = $conn->prepare($sql_update_session);
            $stmt_update_session->bindParam(':date_session', $date_session);
            $stmt_update_session->bindParam(':etat_session', $etat_session);
            $stmt_update_session->bindParam(':numero_session', $numero_session);
            $stmt_update_session->bindParam(':id_atelier', $id_atelier);
            $stmt_update_session->bindParam(':id_session', $id_session);
            $stmt_update_session->execute();


            $sql_update_woofer = "UPDATE WooferAnimeSession 
                                SET id_woofer = :id_woofer 
                                WHERE id_session = :id_session";
            $stmt_update_woofer = $conn->prepare($sql_update_woofer);
            $stmt_update_woofer->bindParam(':id_woofer', $id_woofer);
            $stmt_update_woofer->bindParam(':id_session', $id_session);
            $stmt_update_woofer->execute();

            $conn->commit();
            header("Location: atelier.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            die("Erreur : " . $e->getMessage());
        }
    } else {

        $nom_atelier = $_POST['nom_atelier'];
        $prix_atelier = $_POST['prix_atelier'];
        $id_categorie = $_POST['id_categorie'];
        $date_session = $_POST['date_session']; 
        $etat_session = $_POST['etat_session'];
        $id_woofer = $_POST['id_woofer'];
        $numero_session = $_POST['numero_session'];

        $sql_check_atelier = "SELECT id_atelier FROM Atelier WHERE nom_atelier = :nom_atelier";
        $stmt_check_atelier = $conn->prepare($sql_check_atelier);
        $stmt_check_atelier->bindParam(':nom_atelier', $nom_atelier);
        $stmt_check_atelier->execute();
    
        if ($stmt_check_atelier->rowCount() > 0) {

            $atelier = $stmt_check_atelier->fetch();
            $id_atelier = $atelier['id_atelier'];
        } else {

            $sql_insert_atelier = "INSERT INTO Atelier (nom_atelier, prix_atelier, id_categorie)
                                   VALUES (:nom_atelier, :prix_atelier, :id_categorie)";
            $stmt_insert_atelier = $conn->prepare($sql_insert_atelier);
            $stmt_insert_atelier->bindParam(':nom_atelier', $nom_atelier);
            $stmt_insert_atelier->bindParam(':prix_atelier', $prix_atelier);
            $stmt_insert_atelier->bindParam(':id_categorie', $id_categorie);
            $stmt_insert_atelier->execute();
    

            $id_atelier = $conn->lastInsertId();
        }
    

        $sql_insert_session = "INSERT INTO Session (date_session, etat_session, numero_session, id_atelier)
                               VALUES (:date_session, :etat_session, :numero_session, :id_atelier)";
        $stmt_insert_session = $conn->prepare($sql_insert_session);
        $stmt_insert_session->bindParam(':date_session', $date_session);
        $stmt_insert_session->bindParam(':etat_session', $etat_session);
        $stmt_insert_session->bindParam(':numero_session', $numero_session);
        $stmt_insert_session->bindParam(':id_atelier', $id_atelier);
        $stmt_insert_session->execute();
    

        $id_session = $conn->lastInsertId();
    

        $sql_assoc_woofer = "INSERT INTO WooferAnimeSession (id_woofer, id_session)
                             VALUES (:id_woofer, :id_session)";
        $stmt_assoc_woofer = $conn->prepare($sql_assoc_woofer);
        $stmt_assoc_woofer->bindParam(':id_woofer', $id_woofer);
        $stmt_assoc_woofer->bindParam(':id_session', $id_session);
        $stmt_assoc_woofer->execute();
    

        $conn->commit();
    

        header("Location: atelier.php");
        exit();
    }
    
}

?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ateliers</title>
    <link rel="stylesheet" href="css/onglet.css">
    <link rel="stylesheet" href="css/atelier.css">
    <script src="js/atelier.js"></script>
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
    <h1>Gestion des Ateliers</h1>
    
    <div class="container">
    <?php if (!empty($ateliers) && is_array($ateliers)): ?>
        <?php foreach ($ateliers as $atelier): ?>
            <div class="atelier">
                <h3>Nom de l'atelier: <?= $atelier['nom_atelier'] ?></h3>
                <p>
                    Prix de l'atelier: <?= $atelier['prix_atelier'] ?> €<br>
                    Date de la session: <?= $atelier['date_session'] ?><br>
                    Thématique: <?= $atelier['thematique'] ?><br>
                    Woofer: <?= $atelier['woofer_prenom'] . " " . $atelier['woofer_nom'] ?><br>
                    Numéro de session: <?= $atelier['numero_session'] ?><br>
                    État de la session: <?= $atelier['etat_session'] ?><br>
                    <strong>Liste des personnes inscrites:</strong>
                    <ul>
                        <?php 
                            $participants = explode(",", $atelier['participants_inscrits']);
                            if (!empty($participants)) {
                                foreach ($participants as $participant) {
                                    echo "<li>" . $participant . "</li>";
                                }
                            } else {
                                echo "<li>Aucun participant inscrit.</li>";
                            }
                        ?>
                    </ul>
                </p>
                <button class="modifier-btn" onclick="ouvrirModalModifier(<?= $atelier['id_session'] ?>)">Modifier</button>
                <button class="modifier-btn-inscrit" onclick="ouvrirModalModifierInscrit(<?= $atelier['id_session'] ?>)">Modifier inscrits</button>
                <button class="supprimer-btn" onclick="supprimerAtelier(<?= $atelier['id_session'] ?>)">Supprimer</button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun atelier enregistré.</p>
    <?php endif; ?>
    </div>

    <div class="button">
        <button onclick="ouvrirModal()">Ajouter Atelier</button>
    </div>
    <div id="modalAjoutAtelier" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fermerModal()">&times;</span>
            <h2>Ajouter un atelier</h2>
            <form method="POST" action="atelier.php">
                <input type="hidden" name="action_form" value="ajouterAtelier">
                <label for="numero_session">Numéro de session :</label>
                <input type="number" id="numero_session" name="numero_session" required>
                <br>
                <label for="nom_atelier">Nom de l'atelier:</label>
                <input type="text" id="nom_atelier" name="nom_atelier" required><br>

                <label for="prix_atelier">Prix de l'atelier:</label>
                <input type="number" id="prix_atelier" name="prix_atelier" min="0" required><br>

                <label for="id_categorie">Catégorie:</label>
                <select id="id_categorie" name="id_categorie" required>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= $categorie['id_categorie'] ?>">
                            <?= $categorie['nom_categorie'] ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <label for="date_session">Date de la session:</label>
                <input type="datetime-local" id="date_session" name="date_session" required><br>

                <label for="etat_session">État de la session</label>
                <select name="etat_session" id="etat_session" required>
                    <option value="Programmée">Programmée</option>
                    <option value="Reprogrammée">Reprogrammée</option>
                    <option value="Annulée">Annulée</option>
                    <option value="En cours">En cours</option>
                    <option value="Terminée">Terminée</option>
                </select><br>

                <label for="id_woofer">Choisir un Woofer:</label>
                <select id="id_woofer" name="id_woofer" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id_utilisateur'] ?>">
                            <?= $user['prenom'] . ' ' . $user['nom'] ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <button type="submit">Ajouter Atelier</button>
            </form>
        </div>
    </div>
    <div id="modalModifierAtelier" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fermerModalModifier()">&times;</span>
        <h2>Modifier un atelier</h2>
        <form id="formModifierAtelier" method="POST" action="atelier.php">
            <input type="hidden" id="id_session" name="id_session">
            <label for="numero_session">Numéro de session:</label>
            <input type="number" id="numero_session_modif" name="numero_session" required><br>

            
            <label for="nom_atelier">Nom de l'atelier:</label>
            <input type="text" id="nom_atelier_modif" name="nom_atelier" required><br>

            <label for="prix_atelier">Prix de l'atelier:</label>
            <input type="number" id="prix_atelier_modif" name="prix_atelier" min="0" required><br>

            <label for="id_categorie">Catégorie:</label>
            <select id="id_categorie_modif" name="id_categorie" required>
                <?php foreach ($categories as $categorie): ?>
                    <option value="<?= $categorie['id_categorie'] ?>">
                        <?= $categorie['nom_categorie'] ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label for="date_session">Date de la session:</label>
            <input type="datetime-local" id="date_session_modif" name="date_session" required><br>

            <label for="etat_session">État de la session</label>
            <select name="etat_session" id="etat_session_modif" required>
                <option value="Programmée">Programmée</option>
                <option value="Reprogrammée">Reprogrammée</option>
                <option value="Annulée">Annulée</option>
                <option value="En cours">En cours</option>
                <option value="Terminée">Terminée</option>
            </select><br>

            <label for="id_woofer">Choisir un Woofer:</label>
            <select id="id_woofer_modif" name="id_woofer" required>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id_utilisateur'] ?>">
                        <?= $user['prenom'] . ' ' . $user['nom'] ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <button type="submit">Modifier Atelier</button>
        </form>
    </div>
</div>

<div id="modalModifierInscrits" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fermerModalModifierInscrits()">&times;</span>
        <h2>Modifier la liste des inscrits</h2>

        <form id="formModifierInscrits" method="POST" action="atelier.php">
            <input type="hidden" id="id_session_inscrits" name="id_session">

            <h3>Participants inscrits:</h3>
            <ul id="participants_list">
                
            </ul>
        <button id="desinscrireBtn" onclick="desinscrireParticipants()">Désinscrire les participants sélectionnés</button>

            <h3>Ajouter un participant :</h3>
            <label for="participant_tel">Téléphone :</label>
            <input type="tel" id="participant_tel" name="tel" required><br>

            <label for="participant_nom">Nom :</label>
            <input type="text" id="participant_nom" name="nom" required><br>

            <label for="participant_prenom">Prénom :</label>
            <input type="text" id="participant_prenom" name="prenom" required><br>

            <label for="participant_email">Email :</label>
            <input type="email" id="participant_email" name="email" required><br>

            <button type="button" id="ajouterBtn" onclick="ajouterParticipant()">Ajouter</button>
        </form>


    </div>
</div>

<h2>Validation de participants:</h2>
<div class="validation">
    <?php if (!empty($participants_non_valides)): ?>
        <?php foreach ($participants_non_valides as $participant): ?>
            <div class="participant">
                <strong>Nom:</strong> <?= $participant['nom'] ?><br>
                <strong>Prénom:</strong> <?= $participant['prenom'] ?><br>
                <strong>Téléphone:</strong> <?= $participant['tel'] ?><br>
                <strong>Email:</strong> <?= $participant['email'] ?><br>
                <strong>Nom de l'atelier:</strong> <?= $participant['nom_atelier'] ?><br>
                <strong>Session:</strong> <?= $participant['numero_session'] ?><br>
                <div class="buttons">
                    <form method="POST" action="atelier.php">
                        <input type="hidden" name="action_form" value="accepterRejeter">
                        <input type="hidden" name="id_participant" value="<?= $participant['id_participant'] ?>">
                        <input type="hidden" name="id_session" value="<?= $participant['id_session'] ?>">
                        <button type="submit" name="action" value="accepter" class="accept">Accepter</button>
                        <button type="submit" name="action" value="rejeter" class="reject">Rejeter</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun participant en attente de validation.</p>
    <?php endif; ?>
</div>

</body>
</html>
