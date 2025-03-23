<?php
session_start();
include 'connexion_db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login']; 
    $password = $_POST['password'];

    try {

        $stmt = $conn->prepare("SELECT id_utilisateur, login, mot_de_passe FROM Utilisateur WHERE login = :login");
        $stmt->bindParam(":login", $login, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
            $_SESSION['login'] = $user['login'];


            $stmt = $conn->prepare("SELECT * FROM Responsable WHERE id_responsable = :id_utilisateur");
            $stmt->bindParam(":id_utilisateur", $user['id_utilisateur'], PDO::PARAM_INT);
            $stmt->execute();
            $responsable = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($responsable) {
                $_SESSION['role'] = 'Responsable';
                header("Location: tableauDeBord.php");
                exit();
            }


            $stmt = $conn->prepare("SELECT * FROM Woofer WHERE id_woofer = :id_utilisateur");
            $stmt->bindParam(":id_utilisateur", $user['id_utilisateur'], PDO::PARAM_INT);
            $stmt->execute();
            $woofer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($woofer) {
                $_SESSION['role'] = 'Woofer';
                header("Location: accueilWoofer.php");
                exit();
            }


            $error = "Rôle inconnu pour cet utilisateur.";
        } else {
            $error = "Identifiants incorrects.";
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion à la base de données: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="css/onglet.css">
    <link rel="stylesheet" href="css/connexion.css">
    <script src="js/onglet.js" defer></script>
</head>
<body>

    <?php include 'ongletNeutre.php'; ?>

    <div class="login-container">
        <h2>Se connecter</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post" action="">
            <label>Login</label>
            <input type="text" name="login" required>
            <br>
            <label>Mot de passe</label>
            <input type="password" name="password" required>
            <br>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>



