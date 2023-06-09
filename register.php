<?php
require_once "config.php";

// Define variables and initialize with empty values
$email = $username = $password = $confirmPassword = "";
$email_err = $username_err = $password_err = $confirmPassword_err = "";

// Process form data on submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Validate email
  if (empty(trim($_POST["email"]))) {
    $email_err = "Email is required";
  } else {
    $email = trim($_POST["email"]);
    // Check if the email is already registered
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
      $email_err = "Email is already registered";
    }
    mysqli_stmt_close($stmt);
  }

  // Validate username
  if (empty(trim($_POST["username"]))) {
    $username_err = "Username is required";
  } else {
    $username = trim($_POST["username"]);
    // Check if the username is already taken
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
      $username_err = "Username is already taken";
    }
    mysqli_stmt_close($stmt);
  }

  // Validate password
  if (empty(trim($_POST["password"]))) {
    $password_err = "Password is required";
  } elseif (strlen(trim($_POST["password"])) < 6) {
    $password_err = "Password must be at least 6 characters";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate confirm password
  if (empty(trim($_POST["confirm-password"]))) {
    $confirmPassword_err = "Please confirm password";
  } else {
    $confirmPassword = trim($_POST["confirm-password"]);
    if (empty($password_err) && ($password != $confirmPassword)) {
      $confirmPassword_err = "Passwords do not match";
    }
  }

  // Check input errors before inserting into database
  if (empty($email_err) && empty($username_err) && empty($password_err) && empty($confirmPassword_err)) {

    // Prepare an INSERT statement
    $sql = "INSERT INTO users (email, username, password) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $email, $username, password_hash($password, PASSWORD_DEFAULT));
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirect to login page after successful registration
    header("location: login.php");
    exit();
  }
  mysqli_close($conn);
}
?>