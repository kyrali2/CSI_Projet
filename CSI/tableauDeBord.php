<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Responsable') {
    header("Location: connexion.php");
    exit();
}

include('connexion_db.php');

$requete_stock = "SELECT intitule_produit, quantite_stock FROM Produit";
$statement_stock = $conn->prepare($requete_stock);
$statement_stock->execute();
$stocks = $statement_stock->fetchAll(PDO::FETCH_ASSOC);

$requete_atelier = "SELECT nom_atelier, prix_atelier, DATE_FORMAT(date_session, '%Hh%i') AS heure, 
                        COUNT(id_participant) AS participants,
                        CONCAT(u.prenom, ' ', u.nom) AS nom_woofer
                  FROM Atelier 
                  JOIN Session ON Atelier.id_atelier = Session.id_atelier
                  LEFT JOIN ParticipantSession ON Session.id_session = ParticipantSession.id_session
                  LEFT JOIN WooferAnimeSession ON Session.id_session = WooferAnimeSession.id_session
                  LEFT JOIN Woofer w ON WooferAnimeSession.id_woofer = w.id_woofer
                  LEFT JOIN Utilisateur u ON w.id_woofer = u.id_utilisateur
                  WHERE date_session >= CURDATE()
                  GROUP BY nom_atelier, prix_atelier, date_session, nom_woofer
                  ORDER BY date_session
                  LIMIT 2";
$statement_atelier = $conn->prepare($requete_atelier);
$statement_atelier->execute();
$ateliers = $statement_atelier->fetchAll(PDO::FETCH_ASSOC);

$requete_vente = "SELECT v.date_vente, p.intitule_produit, vp.quantite_vendue, u.prenom, u.nom 
                FROM Vente v
                JOIN Vente_Produit vp ON v.id_vente = vp.id_vente
                JOIN Produit p ON vp.id_produit = p.id_produit
                JOIN Utilisateur u ON v.id_utilisateur = u.id_utilisateur
                ORDER BY v.date_vente DESC LIMIT 5";
$statement_vente = $conn->prepare($requete_vente);
$statement_vente->execute();
$ventes = $statement_vente->fetchAll(PDO::FETCH_ASSOC);

$requete_woofer = "SELECT prenom, nom 
                 FROM Woofer 
                 JOIN Utilisateur ON Woofer.id_woofer = Utilisateur.id_utilisateur 
                 WHERE date_depart IS NULL OR date_depart > CURDATE()";
$statement_woofer = $conn->prepare($requete_woofer);
$statement_woofer->execute();
$woofers = $statement_woofer->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="css/onglet.css">
    <link rel="stylesheet" href="css/tableauDeBord.css">
</head>
<body>

    <?php include 'ongletNeutre.php'; ?>

    <h1>Tableau de Bord</h1>

    <div class="container">
        <div class="box">
            <h2>Stock</h2>
            <?php foreach ($stocks as $stock): ?>
                <p><?php echo $stock['intitule_produit']; ?> : <?php echo $stock['quantite_stock']; ?></p>
            <?php endforeach; ?>
            <a href="stock.php" class="voir-tout">Voir tout</a>
        </div>

        <div class="box">
            <h2>Ateliers à venir</h2>
            <?php foreach ($ateliers as $atelier): ?>
                <p><?php echo $atelier['heure']; ?> - "<?php echo $atelier['nom_atelier']; ?>" par <?php echo $atelier['nom_woofer']; ?>, <?php echo $atelier['participants']; ?> participants</p>
            <?php endforeach; ?>
            <a href="atelier.php" class="voir-tout">Voir tout</a>
        </div>

        <div class="box">
            <h2>Ventes récentes</h2>
            <?php foreach ($ventes as $vente): ?>
                <p>Vente le <?php echo date('d/m/Y', strtotime($vente['date_vente'])); ?> : <?php echo $vente['quantite_vendue']; ?> unités de <?php echo $vente['intitule_produit']; ?>, par <?php echo $vente['prenom'] . ' ' . $vente['nom']; ?></p>
            <?php endforeach; ?>
            <a href="vente.php" class="voir-tout">Voir tout</a>
        </div>

        <div class="box">
            <h2>Woofers présents</h2>
            <?php if (!empty($woofers)): ?>
                <?php foreach ($woofers as $woofer): ?>
                    <p><?php echo $woofer['prenom']; ?> <?php echo $woofer['nom']; ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun Woofer actuellement présent.</p>
            <?php endif; ?>
            <a href="gestionWoofer.php" class="voir-tout">Voir tout</a>
        </div>
    </div>

    <script src="js/onglet.js"></script>
</body>
</html>







