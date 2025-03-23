<?php
include 'connexion_db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_session']) && isset($_POST['participant'])) {
        $id_session = $_POST['id_session'];
        $participant = json_decode($_POST['participant'], true);

        try {

            $sql = "SELECT id_participant, prenom, nom, email, tel FROM Participant WHERE email = :email OR tel = :tel";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $participant['email'], PDO::PARAM_STR);
            $stmt->bindParam(':tel', $participant['tel'], PDO::PARAM_STR);
            $stmt->execute();
            $Participantexistant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($Participantexistant) {

                if ($Participantexistant['prenom'] !== $participant['prenom'] || 
                    $Participantexistant['nom'] !== $participant['nom'] || 
                    $Participantexistant['email'] !== $participant['email'] || 
                    $Participantexistant['tel'] !== $participant['tel']) {
                    
                    http_response_code(400); 
                    echo json_encode(["message" => "Erreur : Cet email ou téléphone est déjà utilisé mais avec des informations différentes."]);
                    exit();
                }

                $id_participant = $Participantexistant['id_participant'];
            } else {

                $sql = "INSERT INTO Participant (prenom, nom, email, tel) VALUES (:prenom, :nom, :email, :tel)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':prenom', $participant['prenom'], PDO::PARAM_STR);
                $stmt->bindParam(':nom', $participant['nom'], PDO::PARAM_STR);
                $stmt->bindParam(':email', $participant['email'], PDO::PARAM_STR);
                $stmt->bindParam(':tel', $participant['tel'], PDO::PARAM_STR);
                $stmt->execute();
                $id_participant = $conn->lastInsertId();
            }


            $sql = "SELECT COUNT(*) FROM ParticipantSession WHERE id_participant = :id_participant AND id_session = :id_session";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id_participant', $id_participant, PDO::PARAM_INT);
            $stmt->bindParam(':id_session', $id_session, PDO::PARAM_INT);
            $stmt->execute();
            $inscrit = $stmt->fetchColumn();

            if ($inscrit > 0) {
                http_response_code(400); 
                echo json_encode(["message" => "Erreur : Le participant est déjà inscrit à cette session."]);
                exit();
            }


            $sql = "INSERT INTO ParticipantSession (id_participant, id_session, estValidé) VALUES (:id_participant, :id_session, TRUE)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id_participant', $id_participant, PDO::PARAM_INT);
            $stmt->bindParam(':id_session', $id_session, PDO::PARAM_INT);
            $stmt->execute();

            http_response_code(200); 
            echo json_encode(["message" => "Participant inscrit avec succès."]);

        } catch (PDOException $e) {
            http_response_code(500); 
            echo json_encode(["message" => "Erreur SQL : " . $e->getMessage()]);
        }
    } else {
        http_response_code(400); 
        echo json_encode(["message" => "Données manquantes."]);
    }
}
?>
