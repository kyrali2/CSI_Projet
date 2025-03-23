<?php
include 'connexion_db.php';

if (isset($_GET['id_session'])) {
    $id_session = $_GET['id_session'];

    $sql = "SELECT 
                a.nom_atelier, 
                a.prix_atelier, 
                a.id_categorie,
                s.date_session, 
                s.etat_session,
                s.numero_session, 
                s.id_session,
                w.id_woofer
            FROM Session s
            JOIN Atelier a ON s.id_atelier = a.id_atelier
            LEFT JOIN WooferAnimeSession was ON s.id_session = was.id_session
            LEFT JOIN Woofer w ON was.id_woofer = w.id_woofer
            WHERE s.id_session = :id_session";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_session', $id_session, PDO::PARAM_INT);
    $stmt->execute();
    $atelier = $stmt->fetch(PDO::FETCH_ASSOC);
    $atelier['date_session'] = date('Y-m-d\TH:i', strtotime($atelier['date_session']));

    echo json_encode($atelier);
}
?>
