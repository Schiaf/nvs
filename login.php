<?php
session_start();
session_unset();

require_once("fonctions.php");

$mysqli = db_connexion();

if(isSet ($_POST['pseudo']) && isSet ($_POST['password']))
{
	// recuperation des variables post
	$pseudo = $_POST['pseudo'];
	$mdp 	= $_POST['password'];

	// test champs vide
	if ( $pseudo == "" || $mdp == "") { 
		echo "<div class=\"erreur\" align=\"center\">Merci de remplir tous les champs</div><br>";
	}
	else {
		// passage du mdp en md5
		$mdp = md5($mdp); 
		
		// recuperation de l'id du joueur et log du joueur
		$sql = "SELECT id_joueur, mdp_joueur, id_perso FROM joueur,perso WHERE joueur.id_joueur=perso.idJoueur_perso and nom_perso='$pseudo'";
		$res = $mysqli->query($sql);
		$t_user = $res->fetch_assoc();
		$mdp_j = $t_user["mdp_joueur"];
		
		if($mdp == $mdp_j){
			
			$id_joueur = $_SESSION["ID_joueur"] = $t_user["id_joueur"];
			
			$_SESSION["id_perso"] = $t_user["id_perso"];
			
			// recuperation de l'ip du joueur
			$ip_joueur = realip();
			$sql = "INSERT INTO joueur_as_ip VALUES ('$id_joueur','$ip_joueur')";
			$mysqli->query($sql);
			
			header("location:jeu/jouer.php");
		}
		else {
			echo "mot de passe incorrect<br>";
			echo "<a href=\"index.php\"><font color=\"#000000\" size=\"1\" face=\"Verdana, Arial, Helvetica, sans-serif\">[ retour ]</font></a>";
		}
	}
}
?>