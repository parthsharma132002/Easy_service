<?php
include 'dbConnect.php';
error_reporting(0);
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'getVendorList') {
        if (!isset($_POST['type'], $_POST['location'])) {
            echo json_encode(["status" => "201", "message" => "Required parameters not set."]);
            exit;
        }
        $type = $_POST['type'];
        $location = $_POST['location'];

        $query = "SELECT 
                    company_name, 
                    vendor_name, 
                    vendor_id AS vendorId, 
                    rating, 
                    visit_charges AS visitCharges, 
                    per_hour_charges AS perHourCharges 
                FROM vendors 
                WHERE type = :type AND location = :location";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['type' => $type, 'location' => $location]);

        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($vendors) {
            echo json_encode([
                "VendorList" => $vendors,
                "status" => "200",
                "message" => "Success."
            ]);
        } else {
            echo json_encode(["VendorList" => [], "status" => "201", "message" => "No vendors found"]);
        }
    }
} else {
    echo json_encode(["status" => "201", "message" => "Wrong Action"]);
}
?>