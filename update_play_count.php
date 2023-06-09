<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  exit("Access denied.");
}

if (!isset($_POST["selected_song"])) {
  exit("Invalid request.");
}

$selectedSong = $_POST["selected_song"];
$userID = $_SESSION["id"];

// Database connection
$servername = "";
$username = "root";
$password = "";
$dbname = "spogify";

try {
  $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}

// Update the play count for the selected song in the database
$updateStmt = $pdo->prepare("UPDATE user_songs SET play_count = play_count + 1 WHERE user_id = :user_id AND song_title = :song_title");
$updateStmt->bindParam(":user_id", $userID, PDO::PARAM_INT);
$updateStmt->bindParam(":song_title", $selectedSong, PDO::PARAM_STR);
$updateStmt->execute();

if ($updateStmt->rowCount() > 0) {
  echo "Play count updated successfully!";
} else {
  echo "Failed to update play count.";
}
?>