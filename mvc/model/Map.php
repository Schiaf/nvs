<?php
require_once("Model.php");
// require_once("../creation_carte/f_analyse.php");

class Carte extends Model
{
	public $x_carte;
	public $y_carte;
	public $occupee_carte;
	public $fond_carte;
	public $idPerso_carte;
	public $image_carte;
	public $vue_nord;
	public $vue_sud;
	public $coordonnees;
	protected $xMax;
	protected $yMax;
	protected $terrains = [
		// id => [nom,rouge,vert,bleu]. Propriété à tabler, un jour...
		1=>['plaine',129,156,84],
		2=>['colline',96,110,70],
		3=>['montagne',134,118,89],
		4=>['désert',215,197,101],
		5=>['neige',255,255,255],
		6=>['marécage',169,177,166],
		7=>['forêt',60,86,33],
		8=>['eau',92,191,207],
		9=>['eau_profonde',39,141,227]
	];
	protected $carteTables = ['CARTE' => 1,'CARTE2' => 2,'CARTE3' => 3];// à refactoriser, un jour...

	public function __set($name, $value) {}
	
	public function __get($name){
		return $this->$name;
	}
	
	/**
     * Vérifie que la carte existe
     *
     * @return bool
     */
	public function carteExist($id){
		$db = $this->dbConnectPDO();
		
		$carte = array_search($id,$this->carteTables);
		
		$query = "SELECT COUNT(*) FROM $carte";
		
		$request = $db->prepare($query);
		$request->execute();
		$result = (boolean) $request->fetchColumn();
		
		return $result;
	}
	
	/**
     * Vérifie que la couleur correspond à un terrain
     *
     * @return bool
     */
	public function couleurEstTerrain($colors){
		$terrains = $this->terrains;
		
		$red = $colors['red'];
		$green = $colors['green'];
		$blue = $colors['blue'];
		$alpha = $colors['alpha'];
		
		foreach($terrains as $id => $composants){
			if($composants[1]==$red && $composants[2]==$green && $composants[3]==$blue){
				return $id;
				break;
			}
		}
		
		return null;
	}
	
	/**
     * Création d'une carte vierge
     *
     * @return bool
     */
	public function createFromScratch($id,$x_max,$y_max,$fond=1,$desc=false){
		$db = $this->dbConnectPDO();
		
		$carte = array_search($id,$this->carteTables);
		
		$lines = 0;
		$result = 0;
		
		$fond = $fond.'.gif';
		
		// return [$carte,$x_max,$y_max,$fond];

		for ($x_pixel = 0; $x_pixel <= $x_max; $x_pixel++)
		{
			for ($y_pixel = 0; $y_pixel <= $y_max; $y_pixel++)
			{
				$x = $x_pixel;
				$y = $y_max-1 - $y_pixel;
				
				$coordo = $x.';'.$y;
				$query = "INSERT INTO $carte (id_carte,x_carte,y_carte,occupee_carte,fond_carte,idPerso_carte,image_carte,save_info_carte,vue_nord,vue_sud,coordonnees) VALUES (:id,:x,:y,'0',:fond,NULL,NULL,NULL,0,0,:coordo)";
				$request = $db->prepare($query);
				$request->bindParam('id', $id, PDO::PARAM_INT);
				$request->bindParam('x', $x, PDO::PARAM_INT);
				$request->bindParam('y', $y, PDO::PARAM_INT);
				$request->bindParam('fond', $fond, PDO::PARAM_STR);
				$request->bindParam('coordo', $coordo, PDO::PARAM_STR);
				$result = $request->execute();
				
				$lines++;
				$result += $result;
			}
		}

		if($lines==$result){
			return true;
		}else{
			return $lines-$result;
		}
	}
	
	/**
     * Création d'une carte à partir d'une image png
     *
	 * @param id carte, file img
     * @return bool
     */
	public function createFromPng($id,$file,$desc=false){
		$db = $this->dbConnectPDO();
		
		$x_max = 201;
		$y_max = 201;

		$origin_img = $file['tmp_name'];
		$dimensions = getimagesize($origin_img);
		$image = imagecreatefrompng($origin_img);
		
		if($dimensions[0] != 0 && $dimensions[1] != 0){
			$x_max = $dimensions[0];
			$y_max = $dimensions[1];
		}
		
		$carte = array_search($id,$this->carteTables);
		
		$lines = 0;
		$result = 0;
		
		$errors = [];

		for ($x_pixel = 0; $x_pixel < $x_max; $x_pixel++)
		{
			for ($y_pixel = 0; $y_pixel < $y_max; $y_pixel++)
			{
				$pixelrgb = imagecolorat($image, $x_pixel, $y_pixel);
				$colors = imagecolorsforindex($image, $pixelrgb);
				
				$terrain = $this->couleurEstTerrain($colors);
				
				if(isset($terrain) && !empty($terrain)){
					$fond = $terrain.'.gif';
				}else{
					$fond = '1.gif';
				}
				
				$x = $x_pixel;
				// $y = $y_max-1 - $y_pixel;
				$y = $y_pixel;
				
				$coordo = $x.';'.$y;
				$query = "INSERT INTO $carte (id_carte,x_carte,y_carte,occupee_carte,fond_carte,idPerso_carte,image_carte,save_info_carte,vue_nord,vue_sud,coordonnees) VALUES (:id,:x,:y,'0',:fond,NULL,NULL,NULL,0,0,:coordo)";
				$request = $db->prepare($query);
				$request->bindParam('id', $id, PDO::PARAM_INT);
				$request->bindParam('x', $x, PDO::PARAM_INT);
				$request->bindParam('y', $y, PDO::PARAM_INT);
				$request->bindParam('fond', $fond, PDO::PARAM_STR);
				$request->bindParam('coordo', $coordo, PDO::PARAM_STR);
				$result = $request->execute();
				
				$errors[] = $request->errorInfo();
				$lines++;
				$result += $result;
			}
		}
		
		if($lines==$result){
			return true;
		}else{
			return $errors;
		}
	}
	
	/**
     * Crée un carré de terrain aux coordonnées indiquées pour la carte
     *
	 * @param id carte
     * @return bool
     */
	public function createGroundArea(int $id,int $x_min,int $x_max,int $y_min,int $y_max,int $fond=1){
		$db = $this->dbConnectPDO();
		
		$carte = array_search($id,$this->carteTables);
		$fond = $fond.'.gif';
		
		$query = "UPDATE $carte SET fond_carte=:fond WHERE x_carte>=:x_min AND x_carte<=:x_max AND y_carte>=:y_min AND y_carte<=:y_max";
		
		$request = $db->prepare($query);
		$request->bindParam('x_min', $x_min, PDO::PARAM_INT);
		$request->bindParam('x_max', $x_max, PDO::PARAM_INT);
		$request->bindParam('y_min', $y_min, PDO::PARAM_INT);
		$request->bindParam('y_max', $y_max, PDO::PARAM_INT);
		$request->bindParam('fond', $fond, PDO::PARAM_STR);
		$result = $request->execute();
		
		$errors[] = $request->errorInfo();
		
		return $result;
	}
	
	/**
     * Récupère la taille de la carte
     *
	 * @param id carte
     * @return bool
     */
	public function dimensions($id){
		$db = $this->dbConnectPDO();
		
		$carte = array_search($id,$this->carteTables);
		
		$query = "SELECT MAX(x_carte) as xMax, MAX(y_carte) as yMax FROM $carte";
		
		$request = $db->prepare($query);
		$request->execute();
		$request->setFetchMode(PDO::FETCH_OBJ);
		$result = $request->fetch();
		
		return $result;
	}
	
	/**
     * Récupère la carte en fonction d'une case et la perception définie
     *
	 * @param id carte, int x_origin, int y_origin, int perception
     * @return bool
     */
	public function getCarteWithPerc($id,$x,$y,$perc,$desc=false){
		$db = $this->dbConnectPDO();
		
		$x_min = $x - $perc;
		$x_max = $x + $perc;
		$y_min = $y - $perc;
		$y_max = $y + $perc;
		
		if($x_min<0){
			$x_min = 0;
		}
		if($y_min<0){
			$y_min = 0;
		}
		
		$carte = array_search($id,$this->carteTables);
		
		$query = "SELECT id,x_carte, y_carte, fond_carte, idPerso_carte, image_carte, occupee_carte FROM $carte WHERE (x_carte BETWEEN $x_min AND $x_max) AND (y_carte BETWEEN $y_min AND $y_max) ORDER BY y_carte, x_carte";//DESC
		
		$request = $db->prepare($query);
		$request->execute();
		$request->setFetchMode(PDO::FETCH_ASSOC);
		
		$result = $request->fetchAll();

		return $result;
	}
	
	/**
     * Récupère la carte pour affichage stratégique
     *
	 * @param id carte, int x_origin, int y_origin, int perception
     * @return bool
     */
	public function getMap($id,$desc=false){
		$db = $this->dbConnectPDO();
		
		$carte = array_search($id,$this->carteTables);
		
		$query = "SELECT id,x_carte, y_carte, fond_carte, idPerso_carte, image_carte, occupee_carte FROM $carte ORDER BY y_carte, x_carte";//DESC
		
		$request = $db->prepare($query);
		$request->execute();
		$request->setFetchMode(PDO::FETCH_ASSOC);
		
		$result = $request->fetchAll();

		return $result;
	}
	
	/**
     * Supprime et réinitialise la carte avec l'ID sélectionné
     *
     * @return bool
     */
	public function destroy($id){
		$db = $this->dbConnectPDO();
		$carte = array_search($id,$this->carteTables);
		
		$query = "TRUNCATE TABLE $carte";
		$request = $db->prepare($query);
		$request->execute();
		
		return $request;
		
		// on peut imaginer dans cette fonction qu'on réinitialise aussi les persos, bât, pnj etc.
		// Je crois que ça se fait lors du changement de carte.
	}
	
	/**
     * Mets à jour les données de la carte
     *
     * @return request
     */
	public function carteUpdate($data){
		$db = $this->dbConnectPDO();
		$query = "";
		$request = $db->prepare($query);
		$request->execute();
		return $request;
	}

	/**
     * Récupère les voisins de la cible
     *
     * @return request
     */
	public function recupereVoisins($id_cible, $x_cible, $y_cible){
		$db = $this->dbConnectPDO();
		$sql = "SELECT idPerso_carte FROM carte WHERE x_carte >= $x_cible - 1 AND x_carte <= $x_cible + 1 AND y_carte >= $y_cible - 1 AND y_carte <= $y_cible + 1 AND occupee_carte = '1' AND idPerso_carte != '$id_cible'";
		$request = $db->prepare($sql);
		$request->execute();
		return $request;
	}
}
