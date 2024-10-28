<?php
include 'dbConnect.php';
error_reporting(0);
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'bookAppointment') {
        if (!isset($_POST['user_id'], $_POST['vendor_id'], $_POST['appointment_date'], $_POST['start_time'], $_POST['end_time'], $_POST['flag'])) {
            echo json_encode(["status" => "201", "message" => "Required parameters not set."]);
            exit;
        }

        $user_id = $_POST['user_id'];
        $vendor_id = $_POST['vendor_id'];
        $appointment_date = $_POST['appointment_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $flag = $_POST['flag'];
        $appointment_id = $_POST['appointment_id'] ?? null;

        $timeSlots = [
            "morning" => ["start" => "09:00:00", "end" => "12:00:00"],
            "afternoon" => ["start" => "12:00:00", "end" => "15:00:00"],
            "evening" => ["start" => "15:00:00", "end" => "18:00:00"],
            "night" => ["start" => "18:00:00", "end" => "21:00:00"]
        ];

        $validSlot = false;
        foreach ($timeSlots as $slotName => $slot) {
            if ($start_time >= $slot['start'] && $end_time <= $slot['end']) {
                $validSlot = true;
                break;
            }
        }

        if (!$validSlot) {
            echo json_encode(["status" => "201", "message" => "Invalid time slot. Choose a valid time period."]);
            exit;
        }

        // New Appointment (flag = 0)
        if ($flag == 0) {
            $checkQuery = "SELECT * FROM appointments WHERE vendor_id = :vendor_id AND appointment_date = :appointment_date AND (
                            (start_time < :end_time AND end_time > :start_time)
                        )";
            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute([
                'vendor_id' => $vendor_id,
                'appointment_date' => $appointment_date,
                'end_time' => $end_time,
                'start_time' => $start_time
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "201", "message" => "Already booked, try another time slot."]);
            } else {
                $insertQuery = "INSERT INTO appointments (user_id, vendor_id, appointment_date, start_time, end_time) VALUES (:user_id, :vendor_id, :appointment_date, :start_time, :end_time)";
                $insertStmt = $pdo->prepare($insertQuery);

                if ($insertStmt->execute([
                    'user_id' => $user_id,
                    'vendor_id' => $vendor_id,
                    'appointment_date' => $appointment_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time
                ])) {
                    echo json_encode(["status" => "200", "message" => "Appointment booked successfully."]);
                } else {
                    echo json_encode(["status" => "201", "message" => "Error booking appointment."]);
                }
            }
        } 
        // Update Existing Appointment (flag = 1)
        else if ($flag == 1 && $appointment_id) {
            $checkQuery = "SELECT * FROM appointments WHERE vendor_id = :vendor_id AND appointment_date = :appointment_date AND 
                        (start_time < :end_time AND end_time > :start_time) AND appointment_id != :appointment_id";
            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute([
                'vendor_id' => $vendor_id,
                'appointment_date' => $appointment_date,
                'end_time' => $end_time,
                'start_time' => $start_time,
                'appointment_id' => $appointment_id
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "201", "message" => "Conflict with another booking, try another time slot."]);
            } else {
                $updateQuery = "UPDATE appointments SET vendor_id = :vendor_id, appointment_date = :appointment_date, 
                                start_time = :start_time, end_time = :end_time 
                                WHERE appointment_id = :appointment_id AND user_id = :user_id";
                $updateStmt = $pdo->prepare($updateQuery);

                if ($updateStmt->execute([
                    'vendor_id' => $vendor_id,
                    'appointment_date' => $appointment_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'appointment_id' => $appointment_id,
                    'user_id' => $user_id
                ])) {
                    echo json_encode(["status" => "200", "message" => "Appointment updated successfully."]);
                } else {
                    echo json_encode(["status" => "201", "message" => "Error updating appointment."]);
                }
            }
        }
        else {
            echo json_encode(["status" => "201", "message" => "Invalid flag or missing appointment ID for update."]);
        }
    }
    else if ($action === 'getAvailableSlots') {
        if (!isset($_POST['vendor_id'], $_POST['appointment_date'], $_POST['flag'])) {
            echo json_encode(["status" => "201", "message" => "Required parameters not set."]);
            exit;
        }

        $vendor_id = $_POST['vendor_id'];
        $appointment_date = $_POST['appointment_date'];
        $flag = $_POST['flag'];

        $timeSlots = [
            "morning" => ["start" => "09:00:00", "end" => "12:00:00"],
            "afternoon" => ["start" => "12:00:00", "end" => "15:00:00"],
            "evening" => ["start" => "15:00:00", "end" => "18:00:00"],
            "night" => ["start" => "18:00:00", "end" => "21:00:00"]
        ];

        // flag = 0 (All available slots)
        if ($flag == 0) {
            $query = "SELECT start_time, end_time FROM appointments 
                      WHERE vendor_id = :vendor_id AND appointment_date = :appointment_date";
            $params = ['vendor_id' => $vendor_id, 'appointment_date' => $appointment_date];
        } 
        // flag = 1 (specific booking slots)
        else if ($flag == 1 && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            $query = "SELECT start_time, end_time FROM appointments 
                      WHERE vendor_id = :vendor_id AND appointment_date = :appointment_date 
                      AND user_id = :user_id";
            $params = ['vendor_id' => $vendor_id, 'appointment_date' => $appointment_date, 'user_id' => $user_id];
        }
         else {
            echo json_encode(["status" => "201", "message" => "Invalid flag or missing user_id"]);
            exit;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $availableSlots = [];
        foreach ($timeSlots as $period => $slot) {
            $isAvailable = true;

            foreach ($bookedSlots as $booked) {
                if (($slot['start'] < $booked['end_time']) && ($slot['end'] > $booked['start_time'])) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable || $flag == 1) {
                $availableSlots[$period] = $isAvailable ? "Available" : "Booked";
            }
        }

        if ($availableSlots) {
            echo json_encode([
                "available_slots" => $availableSlots,
                "status" => "200",
                "message" =>  $flag == 0 ? "Available slots retrieved." : "Patient Booking done."
            ]);
        } else {
            echo json_encode(["available_slots" => [], "status" => "201", "message" => "No vendors found"]);
        }
    }
} else {
    echo json_encode(["status" => "201", "message" => "Wrong Action"]);
}
?>
