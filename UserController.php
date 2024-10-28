<?php
include 'dbConnect.php';
error_reporting(0);
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // signup Add/Update API
    if ($action === 'signup') {
        if (!isset($_POST['name'], $_POST['mobile_number'], $_POST['password'], $_POST['flag'])) {
            echo json_encode(["status" => "201", "message" => "Required parameters not set."]);
            exit;
        }
        $name = $_POST['name'];
        $mobile_number = $_POST['mobile_number'];
        $password = $_POST['password'];
        $flag = $_POST['flag'];
        $id = $_POST['id'] ?? null;

        $checkQuery = "SELECT * FROM users WHERE mobile_number = :mobile_number";
        $stmt = $pdo->prepare($checkQuery);
        $stmt->execute(['mobile_number' => $mobile_number]);
        $userExists = $stmt->rowCount() > 0;

        // New User (flag = 0)
        if (!$userExists && $flag == 0) {
            $query = "INSERT INTO users (name, mobile_number, password) VALUES (:name, :mobile_number, :password)";
            $stmt = $pdo->prepare($query);

            try {
                $stmt->execute(['name' => $name, 'mobile_number' => $mobile_number, 'password' => $password]);
                echo json_encode(["status" => "200", "message" => "User registered successfully"]);
            } catch (PDOException $e) {
                echo json_encode(["status" => "201", "message" => "Error: " . $e->getMessage()]);
            }

        }
        // Update User (flag = 1)
         elseif ($userExists && $flag == 1 ) {
            if ($id === null) {
                echo json_encode(["status" => "201", "message" => "Required parameters not set."]);
                exit;
            }
            $updateQuery = "UPDATE users SET name = :name, password = :password WHERE id = :id";
            $stmt = $pdo->prepare($updateQuery);

            try {
                $stmt->execute(['name' => $name, 'password' => $password, 'id' => $id]);
                echo json_encode(["status" => "200", "message" => "User information updated successfully"]);
            } catch (PDOException $e) {
                echo json_encode(["status" => "201", "message" => "Error: " . $e->getMessage()]);
            }

        } else {
            echo json_encode(["status" => "201", "message" => "Something went wrong with the registration process"]);
        }
    }

    // Login API 
    else if ($action === 'login') {
        $mobile_number = $_POST['mobile_number'];
        $password = $_POST['password'];

        $query = "SELECT * FROM users WHERE mobile_number = :mobile_number";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['mobile_number' => $mobile_number]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($password === $user['password']) {   
                echo json_encode(["status" => "200", "message" => "Login successful"]);
            } else {
                echo json_encode(["status" => "201", "message" => "Invalid password"]);
            }
        } else {
            echo json_encode(["status" => "201", "message" => "User not found"]);
        }
    }   
} else {
    $response["message"] = "Wrong Action";
    $response["status"] = "201";
    echo json_encode($response);
} 
?>