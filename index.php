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
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100%25' height='100%25' viewBox='0 0 1600 800'%3E%3Cg stroke='%23000' stroke-width='100' stroke-opacity='0' %3E%3Ccircle fill='%23ff9d00' cx='0' cy='0' r='1800'/%3E%3Ccircle fill='%23fb8d17' cx='0' cy='0' r='1700'/%3E%3Ccircle fill='%23f47d24' cx='0' cy='0' r='1600'/%3E%3Ccircle fill='%23ed6e2d' cx='0' cy='0' r='1500'/%3E%3Ccircle fill='%23e35f34' cx='0' cy='0' r='1400'/%3E%3Ccircle fill='%23d85239' cx='0' cy='0' r='1300'/%3E%3Ccircle fill='%23cc453e' cx='0' cy='0' r='1200'/%3E%3Ccircle fill='%23be3941' cx='0' cy='0' r='1100'/%3E%3Ccircle fill='%23b02f43' cx='0' cy='0' r='1000'/%3E%3Ccircle fill='%23a02644' cx='0' cy='0' r='900'/%3E%3Ccircle fill='%23901e44' cx='0' cy='0' r='800'/%3E%3Ccircle fill='%23801843' cx='0' cy='0' r='700'/%3E%3Ccircle fill='%236f1341' cx='0' cy='0' r='600'/%3E%3Ccircle fill='%235e0f3d' cx='0' cy='0' r='500'/%3E%3Ccircle fill='%234e0c38' cx='0' cy='0' r='400'/%3E%3Ccircle fill='%233e0933' cx='0' cy='0' r='300'/%3E%3Ccircle fill='%232e062c' cx='0' cy='0' r='200'/%3E%3Ccircle fill='%23210024' cx='0' cy='0' r='100'/%3E%3C/g%3E%3C/svg%3E");
            background-attachment: fixed;
            background-size: cover;
        }

        .top-container {
            max-width: 100px; /* Adjust the width as desired */
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 25px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 25px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            padding: 20px;
        }

        .top-right {
            text-align: center;
        }
        .top-right h1 {
            margin: 0;
            font-size: 10px; /* Adjust the font size as desired */
        }

        .top-right form button[name="logout"] {
            background-color: #6f1341; /* Darkened color */
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }

        .top-right form button[name="logout"] {
            background-color: #6f1341; /* Darkened color */
            color: #fff;
            border: none;
            padding: 5px 10px;
            font-size: 14px; /* Adjust the font size as desired */
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }

        .main-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 25px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            padding: 20px;
            margin-top: 100px; /* Adjust as needed */
        }
        .song-selection-form select {
            width: 70%; /* Adjust the width as desired */
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button[type="submit"]:hover {
            color: #fb8d17;
            background:#6f1341;
            transition: all 0.2s ease;
        }
        .music-player {
            margin-top: 20px;
            text-align: center;
        }

        .music-player audio {
            width: 100%;
            max-width: 500px;
            height: 200px; /* Adjust the height as desired */
        }
        button[type="submit"] {
            color: #fff;
            background:#6f1341;
            height: 40px;
            width: 70px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
    </style>
</head>

<body>
    <body>
        <div class="top-container">
            <div class="top-right">
                <h1>Welcome, <?php echo $_SESSION["username"]; ?>!</h1>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <button type="submit" name="logout">Logout</button>
                </form>
            </div>
        </div>
    </body>

  
    <div class="main-container">
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
                        <?php
                            $songTitle = pathinfo($song, PATHINFO_FILENAME);
                        ?>
                        <option value="<?php echo $song; ?>"><?php echo $songTitle; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="song_select">Play</button>
            </form>

        <h2>Upload Song:</h2>
            <form class="song-upload-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <input type="file" name="file" id="file">
            <button type="submit" name="upload">Upload</button>
        </form>

        <div class="music-player">
            <audio id="audioPlayer" controls autoplay>
            <source id="audioSource" src="" type="audio/mpeg">
            Your browser does not support the audio element.
            </audio>
        </div>
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
