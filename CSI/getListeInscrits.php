<?php 
include 'connexion_db.php';

if (isset($_GET['id_session'])) {
    $id_session = $_GET['id_session'];


    $sql = "SELECT 
                p.id_participant, 
                p.nom, 
                p.prenom, 
                p.tel, 
                p.email 
            FROM Participant p
            JOIN ParticipantSession ps ON p.id_participant = ps.id_participant
            WHERE ps.id_session = :id_session AND estValidé=TRUE";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_session', $id_session, PDO::PARAM_INT);
    $stmt->execute();
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($participants);
}
?>
