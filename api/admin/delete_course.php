<?php
header('Content-Type: application/json');

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lcms_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Get the POST data
$data = json_decode(file_get_contents("php://input"));
$courseId = $data->id ?? null;

if (!$courseId) {
    echo json_encode(["success" => false, "message" => "Invalid course ID."]);
    exit();
}

// SQL to delete a course
$sql = "DELETE FROM courses WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $courseId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Course deleted successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Error deleting course: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>