<?php
session_start();

// Check if the user is not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Logout functionality
if (isset($_POST["logout"])) {
    session_destroy();
    header("location: login.php");
    exit;
}

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

// Retrieve the user's top songs from the database
$stmt = $pdo->prepare("SELECT song_title, play_count FROM user_songs WHERE user_id = :user_id ORDER BY play_count DESC LIMIT 5");
$stmt->bindParam(":user_id", $_SESSION["id"], PDO::PARAM_INT);
$stmt->execute();
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve all available songs from the user_songs table
$songStmt = $pdo->prepare("SELECT DISTINCT song_title FROM user_songs WHERE user_id = :user_id");
$songStmt->bindParam(":user_id", $_SESSION["id"], PDO::PARAM_INT);
$songStmt->execute();
$allSongs = $songStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle song selection form submission
if (isset($_POST["song_select"])) {
    $selectedSong = $_POST["song_selection"];

    // Update the play count for the selected song in the database
    $updateStmt = $pdo->prepare("UPDATE user_songs SET play_count = play_count + 1 WHERE user_id = :user_id AND song_title = :song_title");
    $updateStmt->bindParam(":user_id", $_SESSION["id"], PDO::PARAM_INT);
    $updateStmt->bindParam(":song_title", $selectedSong, PDO::PARAM_STR);
    $updateStmt->execute();

    if ($updateStmt->rowCount() > 0) {
        echo "Play count updated successfully!";
    } else {
        echo "Failed to update play count.";
    }
}

// Handle file upload form submission
if (isset($_POST["upload"])) {
    $uploadDir = "songs/"; // Directory to store uploaded files
    $uploadFileName = basename($_FILES["file"]["name"]);
    $uploadFile = $uploadDir . $uploadFileName;

    // Check if the file is an actual audio file
    $audioExtensions = array("mp3", "wav"); // Add more extensions if needed
    $fileExtension = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $audioExtensions)) {
        echo "Invalid file format. Please upload an MP3 or WAV file.";
    } else {
        // Check if the file was successfully uploaded
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $uploadFile)) {
            echo "File uploaded successfully!";
            
            // Add the uploaded song to the user_songs table
            $insertStmt = $pdo->prepare("INSERT INTO user_songs (user_id, song_title, play_count) VALUES (:user_id, :song_title, 0)");
            $insertStmt->bindParam(":user_id", $_SESSION["id"], PDO::PARAM_INT);
            $insertStmt->bindParam(":song_title", $uploadFileName, PDO::PARAM_STR);
            $insertStmt->execute();

            // Retrieve the updated list of available songs
            $songStmt = $pdo->prepare("SELECT DISTINCT song_title FROM user_songs WHERE user_id = :user_id");
            $songStmt->bindParam(":user_id", $_SESSION["id"], PDO::PARAM_INT);
            $songStmt->execute();
            $allSongs = $songStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            echo "Error uploading file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spogify - Home Page</title>
    <style>
        /* CSS styles here */
    </style>
</head>

<body>
    <div class="container">
        <h1>Welcome, <?php echo $_SESSION["username"]; ?>!</h1>

        <h2>Top Songs:</h2>
        <ul>
            <?php foreach ($songs as $song) : ?>
                <li><?php echo $song["song_title"]; ?> (Play Count: <?php echo $song["play_count"]; ?>)</li>
            <?php endforeach; ?>
        </ul>

        <h2>All Songs:</h2>
        <ul>
            <?php foreach ($allSongs as $song) : ?>
                <li><?php echo $song; ?></li>
            <?php endforeach; ?>
        </ul>

        <h2>Select Song:</h2>
        <form class="song-selection-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <select id="song_selection" name="song_selection">
                <?php foreach ($allSongs as $song) : ?>
                    <option value="<?php echo $song; ?>"><?php echo $song; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="song_select">Play</button>
        </form>

        <h2>Upload Song:</h2>
        <form class="song-upload-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <input type="file" name="file" id="file">
            <button type="submit" name="upload">Upload</button>
        </form>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="logout">Logout</button>
        </form>

        <audio id="audioPlayer" controls>
            <source id="audioSource" src="" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>
    </div>

    <script>
        // JavaScript code to handle song selection
        const songSelectionForm = document.querySelector(".song-selection-form");
        const audioPlayer = document.getElementById("audioPlayer");
        const audioSource = document.getElementById("audioSource");

        songSelectionForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const selectedSong = document.getElementById("song_selection").value;
            audioSource.src = `songs/${encodeURIComponent(selectedSong)}`;
            audioPlayer.load();
            audioPlayer.play();

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText);
                }
            };
            xhr.send(`song_select=true&song_selection=${encodeURIComponent(selectedSong)}`);
        });
    </script>
</body>

</html>
