<?php
session_start();

// DB Connection
$servername = "localhost";
$dBusername = "cse383";
$dBpassword = "HoABBHrBfXgVwMSz";

// Create database handle and store in session variable
$_SESSION['mysqli'] = mysqli_connect($servername, $dBusername, $dBpassword, "cse383");
if (mysqli_connect_errno($_SESSION['mysqli'])) {
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
              die;
}

// Dr. Campbell's code from example:
	//returns data as json
	function retJson($data) {
	  header('content-type: application/json');
	  print json_encode($data);
	  exit;
	}

	//get request method into $path variable
	$method = strtolower($_SERVER['REQUEST_METHOD']);
	if (isset($_SERVER['PATH_INFO']))
	$path  = $_SERVER['PATH_INFO'];
	else $path = "";

	//path comes in as /a/b/c - split it apart and make sure it passes basic checks

	$pathParts = explode("/",$path);
	if (count($pathParts) <2) {
	  $ret = array('status'=>'FAIL','msg'=>'Invalid URL');
	  retJson($ret);
	}
	if ($pathParts[1] !== "v1") {
	  $ret = array('status'=>'FAIL','msg'=>'Invalid url or version');
	  retJson($ret);
	}

	//get json data if any
	$jsonData =array();
	try {
	  $rawData = file_get_contents("php://input");
	  $jsonData = json_decode($rawData,true);
	  if ($rawData !== "" && $jsonData==NULL) {
	    $ret=array("status"=>"FAIL","msg"=>"invalid json");
	    retJson($ret);
	  }
	} catch (Exception $e) {
	};

	//var_dump($pathParts);
	//var_dump($method);

//look for url rest.php/v1/user
if($method === "post" && count($pathParts) == 3  && $pathParts[2] === "user") {	// Authentication
	
	// Attempt a login given username and password received from login form
	function attempt_login($mysqli, $username, $password) {
        $query_result = mysqli_query($mysqli, "SELECT password FROM users WHERE user = \"$username\"");
        if (!$query_result) {
           error_log("Error on sql - $mysqli->error");
        } else {
            // If a row is returned, a valid username was entered regardless of password.  Check if password matches
            while ($row = mysqli_fetch_assoc($query_result)) {
                if (password_verify($password, $row['password'])) {
                    return true;
                }
            }

            // If code reaches this point, no rows were returned from the query and username doesn't exist
            return false;
        }
    }

    // Return a somewhat random token for an authenticated user
    function generate_token() {
        return substr(md5(rand()), 0, 40);
    }

    // Get the posted data, store in $data
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'];
    $password = $data['password'];

	// Store data to return as JSON to client
	$returnData;
	
	// Generate and return a token for a valid login, otherwise return helpful message
    if (attempt_login($_SESSION['mysqli'], $username, $password)) {
        $token = generate_token();

        $returnData['status'] = 'OK';
        $returnData['token'] = $token;  // Hand the user's token back so they can pass it in other API calls
        $_SESSION[$token] = $username;  // Store this authenticated username in a session variable by token (will be passed in) so other API pages can get the user's username
    } else {
		$returnData['status'] = 'FAIL';
		if ($username == "") $returnData['msg'] = "Invalid login - no credientials entered.";
		else $returnData['msg'] = 'Invalid login';
        $returnData['token'] = '';
    }

	// JSON-ify data and return to client
    retJson($returnData);
} else if ($method==="get" && count($pathParts) ==  3 && $pathParts[2] === "items") {	// GET the list of diary items

	$sqlItems = "SELECT pk, item FROM diaryItems";
	$res = mysqli_query($_SESSION['mysqli'], $sqlItems);
	$items = array();
	if (!$res) {
  		echo "error on sql - $mysqli->error";
	} else {
		while($row = mysqli_fetch_assoc($res)) {
			$diaryItem['pk'] = $row['pk'];
			$diaryItem['item'] = $row['item'];
			array_push($items, $diaryItem);
		}
		
		$ret = array();
		$ret['status'] = 'OK';
		$ret['msg'] = '';
		$ret['items'] = $items;
	}

	retJson($ret);
} else if ($method==="post" && count($pathParts) ==  3 && $pathParts[2] === "items") {	// POST a new item to update items consumed

	$data = json_decode(file_get_contents('php://input'), true);
	$authToken = $data['token'];
	$userPK;	// Retrieved in a query
	$itemPK = $data['itemPK'];
	$username = $_SESSION[$authToken];

	// Get the user's PK (fk in the diary table)
	$userPKQuery = mysqli_query($_SESSION['mysqli'], "SELECT pk FROM users WHERE user = \"$username\"");

	if (!$userPKQuery) {
		error_log("Error on sql - $mysqli->error");
	} else {
		$row = mysqli_fetch_assoc($userPKQuery);
		$userPK = $row['pk'];
	}
	
	// Add a new consumed item and the consumer's pk (fk in this case)
	$updateItemQuery = "INSERT INTO diary (userFK, itemFK) VALUES (\"$userPK\", \"$itemPK\")";

	$ret = array();
	if ($_SESSION['mysqli']->query($updateItemQuery)) {
		$ret['status'] = "OK";
	} else {
		$ret['status'] = "FAIL";
		$ret['msg'] = $_SESSION['mysqli']->error;
	}

	retJson($ret);
} else if($method==="get" && count($pathParts) == 4 && $pathParts[2] === "itemsSummary") {
	//rest.php/v1/itemsSummary/token
	// gets the summary of items
	$userPK = '';
	$user = '';
	$userToken = $pathParts[3];
	$itemsCounts = array();

	$user = $_SESSION[$pathParts[3]];

	// Get the user's PK (fk in the diary table)
	$userPKQuery = mysqli_query($_SESSION['mysqli'], "SELECT pk FROM users WHERE user = \"$user\"");

	if (!$userPKQuery) {
		error_log("Error on sql - $mysqli->error");
	} else {
		$row = mysqli_fetch_assoc($userPKQuery);
		$userPK = $row['pk'];
	}

	$getItemsCountsQuery = mysqli_query($_SESSION['mysqli'], 
		"SELECT diaryItems.item, COUNT(timestamp) as itemCount 
		FROM diaryItems LEFT JOIN diary ON diary.itemFK = diaryItems.pk 
		WHERE userFK = \"$userPK\"
		GROUP BY diaryItems.item");

	if (!$getItemsCountsQuery) {
		error_log("Error on sql - $mysqli->error");
	} else {
		while($row = mysqli_fetch_assoc($getItemsCountsQuery)) {
			$itemData['item'] = $row['item'];				// Get the name of the item
			$itemData['itemCount'] = $row['itemCount'];		// Get the count of that item in the diary
			array_push($itemsCounts, $itemData);			// Add it to our return data
		}
	}

	$return = array('status'=>'OK', 'msg'=>'List of items and counts', 'itemCount'=>$itemsCounts);
	retJson($return);
} else if($method === "get" && count($pathParts) == 4 && $pathParts[2] === "items") {	// GET the recent items consumed for a user
	$user = $_SESSION[$pathParts[3]];
	$returnItems = array();	// List of items and timestamps to be returned with full payload
	$returnData = array();	// Status, msg, $returnItems

	$recentConsumptionQuery = mysqli_query($_SESSION['mysqli'], 
	"SELECT diary.pk, diaryItems.item, diary.timestamp FROM diary 
	JOIN users ON diary.userFK = users.pk 
	JOIN diaryItems ON itemFK = diaryItems.pk 
	WHERE user = \"$user\" ORDER BY diary.pk DESC LIMIT 30;");

	if (!$recentConsumptionQuery) {
		error_log("Error on sql - $mysqli->error");
		$returnData['status'] = "FAIL";
		$returnData['msg'] = $_SESSION['mysqli']->error;
	} else {
		$returnData['status'] = "OK";
		$returnData['msg'] = "";

		while($row = mysqli_fetch_assoc($recentConsumptionQuery)) {
			$recentItem['item'] = $row['item'];
			$recentItem['timestamp'] = $row['timestamp'];
			array_push($returnItems, $recentItem);
		}
	}
	
	$returnData['items'] = $returnItems;
	retJson($returnData);
} else {
	$ret = array('status'=>'FAIL','msg'=>'Invalid URL');
	retJson($ret);
}



?>
