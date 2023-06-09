<?php
session_start();

// Check if the user is already logged in, redirect to home page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
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

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Processing form data when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username");

        $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);

        $param_username = $username;

        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                if ($row = $stmt->fetch()) {
                    $id = $row["id"];
                    $username = $row["username"];
                    $hashed_password = $row["password"];
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();

                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;

                        // Redirect to home page
                        header("location: index.php");
                    } else {
                        // Password is incorrect
                        $password_err = "Invalid password.";
                    }
                }
            } else {
                // Username doesn't exist
                $username_err = "Username does not exist.";
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }

    // Close statement
    unset($stmt);
}

// Close connection
unset($pdo);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
        }

        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 5px;
            border-radius: 3px;
        }

        .form-group .help-block {
            color: #ff0000;
        }

        .form-group button[type="submit"] {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: #fff;
            text-align: center;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .form-group p {
            text-align: center;
            margin-top: 10px;
        }

        .form-group a {
            color: #333;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <button type="submit">Login</button>
            </div>
            <p>Don't have an account? <a href="registration.php">Register here</a>.</p>
        </form>
    </div>
</body>
</html>
