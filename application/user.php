<?php
    session_start();
    function attempt_login($mysqli, $user, $password) {
        $query_result = mysqli_query($mysqli, "SELECT password FROM users WHERE user = \"$user\"");
        if (!$query_result) {
           error_log("SQL Error - $mysqli->error");
        } else {
            while ($row = mysqli_fetch_assoc($query_result)) {
                if (password_verify($password, $row['password'])) {
                    return true;
                }
            }
            return false;
        }
    }
    function generate_token() {
        return substr(md5(rand()), 0, 16);
    }
    $mysqli = mysqli_connect("localhost", "cse383", "HoABBHrBfXgVwMSz", "cse383");
    if (mysqli_connect_errno($mysqli)) {
        error_log("Failed to connect to MySQL: " . mysqli_connect_error());
        die;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $user = $data['user'];
    $password = $data['password'];
    $returnData;
    if (attempt_login($mysqli, $user, $password)) {
        $token = generate_token();
        $returnData['status'] = 'OK';
        $returnData['token'] = $token;
        $_SESSION[$token] = $user;
    } else {
        $returnData['status'] = 'FAIL';
        $returnData['msg'] = 'Invalid login';
        $returnData['token'] = '';
    }
    header('Content-type: application/json');
    echo json_encode($returnData);
?>