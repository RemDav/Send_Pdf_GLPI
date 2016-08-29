<?php
try
{
	// On se connecte � MySQL
	$base_glpi = new PDO('mysql:host=localhost;dbname=name;charset=utf8', 'root', '');

}
catch(Exception $e)
{
	// En cas d'erreur, on affiche un message et on arr�te tout
        die('Erreur : '.$e->getMessage());
}
?>
