<?php
session_start();
// Connect
$servername = "localhost";
$dBuser = "cse383";
$dBpassword = "HoABBHrBfXgVwMSz";

$_SESSION['mysqli'] = mysqli_connect($servername, $dBuser, $dBpassword, "cse383");
if (mysqli_connect_errno($_SESSION['mysqli'])) {
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
              die;
}
	function retJson($data) {
	  header('content-type: application/json');
	  print json_encode($data);
	  exit;
	}
	$method = strtolower($_SERVER['REQUEST_METHOD']);
	if (isset($_SERVER['PATH_INFO'])) {
		$path  = $_SERVER['PATH_INFO'];
	} else {
		$path = "";
	}
	$pathParts = explode("/",$path);
	if (count($pathParts) < 2) {
	  $ret = array('status'=>'FAIL','msg'=>'Invalid URL');
	  retJson($ret);
	}
	if ($pathParts[1] !== "v1") {
	  $ret = array('status'=>'FAIL','msg'=>'Invalid url or version');
	  retJson($ret);
	}
	$jsonData =array();
	try {
	  $rawData = file_get_contents("php://input");
	  $jsonData = json_decode($rawData,true);
	  if ($rawData !== "" && $jsonData == NULL) {
	    $ret=array("status"=>"FAIL","msg"=>"invalid json");
	    retJson($ret);
	  }
	} catch (Exception $e) {
	};
// rest.php/v1/user
if($method === "post" && count($pathParts) == 3  && $pathParts[2] === "user") {
	function attempt_login($mysqli, $user, $password) {
        $query_result = mysqli_query($mysqli, "SELECT password FROM users WHERE user = \"$user\"");
        if (!$query_result) {
           error_log("SQL Error - $mysqli->error");
        } else {
					// check password
            while ($row = mysqli_fetch_assoc($query_result)) {
                if (password_verify($password, $row['password'])) {
                    return true;
                }
            }
            return false;
        }
    }
    function generate_token() {
        return substr(md5(rand()), 0, 40);
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $user = $data['user'];
    $password = $data['password'];
	$returnData;
    if (attempt_login($_SESSION['mysqli'], $user, $password)) {
        $token = generate_token();
        $returnData['status'] = 'OK';
        $returnData['token'] = $token;
        $_SESSION[$token] = $user;
    } else {
		$returnData['status'] = 'FAIL';
		if ($user == "") {
			$returnData['msg'] = "Invalid login - no credientials entered.";
		} else {
			$returnData['msg'] = 'Invalid login';
		}
        $returnData['token'] = '';
    }
    retJson($returnData);
} else if ($method==="get" && count($pathParts) ==  3 && $pathParts[2] === "items") {
	$sqlItems = "SELECT pk, item FROM diaryItems";
	$res = mysqli_query($_SESSION['mysqli'], $sqlItems);
	$items = array();
	if (!$res) {
  		echo "SQL Error - $mysqli->error";
	} else {
		while($row = mysqli_fetch_assoc($res)) {
			$diaryItem['pk'] = $row['pk'];
			$diaryItem['item'] = $row['item'];
			array_push($items, $diaryItem);
		}
		$ret = array('status'=>'OK', 'msg'=>'');
		$ret['items'] = $items;
	}
	retJson($ret);
} else if ($method==="post" && count($pathParts) ==  3 && $pathParts[2] === "items") {
	$data = json_decode(file_get_contents('php://input'), true);
	$athTok = $data['token'];
	$userPK;
	$itemPK = $data['itemPK'];
	$user = $_SESSION[$athTok];
	$userPKQuery = mysqli_query($_SESSION['mysqli'], "SELECT pk FROM users WHERE user = \"$user\"");
	if (!$userPKQuery) {
		error_log("SQL Error - $mysqli->error");
	} else {
		$row = mysqli_fetch_assoc($userPKQuery);
		$userPK = $row['pk'];
	}
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
	$userPK = '';
	$user = '';
	$userToken = $pathParts[3];
	$itemsCounts = array();
	$user = $_SESSION[$pathParts[3]];
	$userPKQuery = mysqli_query($_SESSION['mysqli'], "SELECT pk FROM users WHERE user = \"$user\"");
	if (!$userPKQuery) {
		error_log("SQL Error - $mysqli->error");
	} else {
		$row = mysqli_fetch_assoc($userPKQuery);
		$userPK = $row['pk'];
	}
	$getItemsCountsQuery = mysqli_query($_SESSION['mysqli'], "SELECT diaryItems.item, COUNT(timestamp) as itemCount 
																			FROM diaryItems LEFT JOIN diary ON diary.itemFK = diaryItems.pk 
																			WHERE userFK = \"$userPK\" GROUP BY diaryItems.item");
	if (!$getItemsCountsQuery) {
		error_log("SQL Error - $mysqli->error");
	} else {
		while($row = mysqli_fetch_assoc($getItemsCountsQuery)) {
			$itemData['item'] = $row['item'];
			$itemData['itemCount'] = $row['itemCount'];
			array_push($itemsCounts, $itemData);
		}
	}
	$return = array('status'=>'OK', 'msg'=>'List of items and counts', 'itemCount'=>$itemsCounts);
	retJson($return);
} else if($method === "get" && count($pathParts) == 4 && $pathParts[2] === "items") {
	$user = $_SESSION[$pathParts[3]];
	$returnItems = array();
	$returnData = array();
	$recentConsumptionQuery = mysqli_query($_SESSION['mysqli'], "SELECT diary.pk, diaryItems.item, diary.timestamp 
																				FROM diary JOIN users ON diary.userFK = users.pk JOIN diaryItems ON itemFK = diaryItems.pk 
																				WHERE user = \"$user\" ORDER BY diary.pk DESC LIMIT 30;");
	if (!$recentConsumptionQuery) {
		error_log("SQL Error - $mysqli->error");
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