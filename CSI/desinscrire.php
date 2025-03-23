<?php
include 'connexion_db.php'; 
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_session']) && isset($_POST['participants'])) {
        $id_session = $_POST['id_session'];
        $participants = json_decode($_POST['participants']); 

        echo "<script>console.log('ID Session: " . $id_session . "');</script>";
        echo "<script>console.log('Participants: " . json_encode($participants) . "');</script>";

        $placeholders = implode(',', array_fill(0, count($participants), '?'));
        $sql = "DELETE FROM ParticipantSession WHERE id_session = ? AND id_participant IN ($placeholders)";

        $stmt = $conn->prepare($sql);

        $stmt->bindValue(1, $id_session, PDO::PARAM_INT); 
        foreach ($participants as $key => $participant) {
            $stmt->bindValue($key + 2, $participant, PDO::PARAM_INT); 
        }

        try {
            $stmt->execute();

            $rowsDeleted = $stmt->rowCount();
            echo "<script>console.log('Rows Deleted: " . $rowsDeleted . "');</script>";

            if ($rowsDeleted > 0) {
                echo "success"; 
            } else {
                echo "Aucune donnée supprimée"; 
            }
        } catch (PDOException $e) {
            echo "<script>console.log('Error: " . $e->getMessage() . "');</script>";
            echo "error: " . $e->getMessage();
        }
    } else {
        echo "error: Données manquantes";
    }
}
?>
