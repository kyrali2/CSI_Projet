<?php
$host = 'localhost';
$bdd = 'ferme';
$user = 'root';
$mdp = '';

try {

    $conn = new PDO("mysql:host=$host;dbname=$bdd;charset=utf8", $user, $mdp);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
