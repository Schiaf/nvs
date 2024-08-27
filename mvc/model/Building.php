<?php
require_once("Model.php");

class Building extends Model
{
	protected $table = "instance_batiment";
	protected $primaryKey = "id_instanceBat";
	// protected $fillable = [];
	protected $guarded = [];
	
	/* fonction pour compter le nombre d'ennemis autour du bâtiment
	 * @var x (int)
	 * @var y (int)
	 * @var id_camp (int)
	 * @var distance (int)
	 * @return int
	*/
	public function enemiesArround(int $x,int $y,int $id_camp, int $distance) {
	
		$query = "SELECT COUNT(id_perso) as enemies FROM perso, carte 
				WHERE perso.id_perso = carte.idPerso_carte 
				AND x_carte <= ? + ".$distance."
				AND x_carte >= ? - ".$distance."
				AND y_carte <= ? + ".$distance."
				AND y_carte >= ? - ".$distance."
				AND perso.clan != ?";

		$values = [$x,$x,$y,$y,$id_camp];
		$request = $this->request($query,$values);
		$result = $request->fetch();

		return $result[0];
	}
	
	/* fonction pivot pour intégrer un ou plusieurs persos dans un bâtiment
	 * @var charac_ids (array) ids des persos
	 * @var building_id (int) id du bâtiment
	 * @return number of row created
	*/
	public function insertCharacters(array $charac_ids, int $building_id) {
		
		$binds = [];
		$values = [];
		
		foreach($charac_ids as $id){
			$binds[] = '(?,?)';
			$values[] = $id;
			$values[] = $building_id;
		}
		
		$binds = implode(', ',$binds);
	
		$query = 'INSERT INTO perso_in_batiment (id_perso,id_instanceBat) VALUES '.$binds;

		$request = $this->request($query,$values);
		$result = $request->rowCount();

		return $result;
	}
	
	// Obsolète. A supprimer à termes
   public function getByType(int $type,int $camp=null){
		$db = $this->dbConnectPDO();
		
		if($camp){
			$query = "SELECT id_instanceBat, id_batiment, nom_instance, x_instance, y_instance, contenance_instance FROM instance_batiment WHERE camp_instance=$camp AND id_batiment=$type ORDER BY nom_instance";
		}else{
			$query = "SELECT id_instanceBat, id_batiment, nom_instance, x_instance, y_instance, contenance_instance FROM instance_batiment WHERE id_batiment=$type ORDER BY nom_instance";
		}
		
		$request = $db->prepare($query);
		$request->execute();
		$request->setFetchMode(PDO::FETCH_ASSOC);
		
		$result = $request->fetchAll();

		return $result;
   }
   
   // Obsolète. A supprimer à termes
   public function getById(int $id){
		$db = $this->dbConnectPDO();
		
		$query = "SELECT id_instanceBat, id_batiment, nom_instance, x_instance, y_instance, contenance_instance FROM instance_batiment WHERE id_instanceBat=$id";
		
		$request = $db->prepare($query);
		$request->execute();
		
		$result = $request->fetch();

		return $result;
   }
}