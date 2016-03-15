<?php
$servername = "localhost";
$ini_array = parse_ini_file("db_conf.ini");

$username = $ini_array['username'];
$password = $ini_array['password'];
if(isset($_POST['user'])) {
	if(!empty($_POST['user']['dbuser']) && !empty($_POST['user']['dbpass'])) {
		$username = $_POST['user']['dbuser'];
		$password = $_POST['user']['dbpass'];
	
	}
}

$dbname = $ini_array['dbname'];

$action = $_POST['action'];
try {

	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$response_array['status'] = "Connected successfully";




	switch($action) {
				
		case 'insert': 
			createIfNotExists($conn);
			$link_name = $_POST['linkName'];
			$link_url = $_POST['linkURL'];
			if(empty($link_name) || empty($link_url)) {
				$response_array['status'] = "Cannot have empty fields";
			} else {
				try {
					

					$link_url = makeHttpLink($link_url);
					$sql = $conn->prepare("INSERT INTO links (linkname, linkurl) 
					VALUES (:linkname, :linkurl)");
					$sql->bindParam(':linkname', $link_name);
					$sql->bindParam(':linkurl', $link_url);
					$sql->execute();
					$response_array['status'] = "New record created successfully";
				} catch (PDOException $e) {
					$response_array['status'] =  $e->getMessage();
				}			
			}
			break;
		case 'delete': 
			$ID = $_POST['id'];
			if(empty($ID)) {
				$response_array['status'] = "No ID given";
			} else {
				try {
					$stmt = $conn->prepare("DELETE FROM links WHERE ID = :ID");
					$stmt->bindParam(':ID', $ID);
					$stmt->execute();
					$response_array['status'] = "Record removed";
				} catch (PDOException $e) {
					$response_array['status'] = $e->getMessage();
				}
			}
			break;
		case 'query':
			try {
				$stmt = $conn->prepare('SELECT * FROM links');
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$response_array['data'] =  json_encode($results);
			} catch (PDOException $e) {
				$response_array['status'] =  $e->getMessage();
			}
			break;
		case 'update':
			$orderingPairs = json_decode($_POST['orderingPairs'], true);

			try {
				$stmt = $conn->prepare("UPDATE links SET position=:pos WHERE ID=:id");
				$stmt->bindParam(':pos', $pos);
				$stmt->bindParam(':id', $id);

				foreach ($orderingPairs as $key => $value) {
					$pos = $value;
					$id = $key;
					$stmt->execute();
					
				}
				$response_array['status'] = "Ordering updated";
			} catch(PDOException $e) {
				$response_array['status'] = $e->getMessage();
			}


			break;
	}

} catch (PDOException $e) {
	$response_array['status'] = $e->getMessage();
}

function makeHttpLink($link) {
	$http_str = 'http';
	$pos = strpos($link, $http_str);

	if($pos === false) {
		return "http://" . $link;
	} else {
		return $link;
	}
}

function createIfNotExists($conn) {
	$stmt = $conn->prepare("CREATE TABLE IF NOT EXISTS links (
			ID INT AUTO_INCREMENT NOT NULL,
			linkname TEXT,
			linkurl TEXT,
			position INT,
			PRIMARY KEY (ID))");
	$stmt->execute();	
}

echo json_encode($response_array);







?>