<?php
    session_start();

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

    // Return a somewhat random token
    function generate_token() {
        return substr(md5(rand()), 0, 16);
    }

    // Database connection
    $mysqli = mysqli_connect("localhost", "cse383", "HoABBHrBfXgVwMSz", "cse383");
    if (mysqli_connect_errno($mysqli)) {
        error_log("Failed to connect to MySQL: " . mysqli_connect_error());
        die;
    }

    // Get the posted data, store in $data
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'];
    $password = $data['password'];

    $returnData;
    if (attempt_login($mysqli, $username, $password)) {
        $token = generate_token();

        $returnData['status'] = 'OK';
        $returnData['token'] = $token;  // Hand the user's token back so they can pass it in other API calls
        $_SESSION[$token] = $username;  // Store this authenticated username in a session variable by token (will be passed in) so other API pages can get the user's username
    } else {
        $returnData['status'] = 'FAIL';
        $returnData['msg'] = 'Invalid login';
        $returnData['token'] = '';
    }

    // Response JSON
    header('Content-type: application/json');
    
    echo json_encode($returnData);
?>
