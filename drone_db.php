<?php // Starts PHP code. This ONE file is the whole database part.

header("Content-Type: application/json"); // Tell the browser we are sending back JSON (data), not a web page
mysqli_report(MYSQLI_REPORT_OFF); // We handle errors ourselves instead of showing ugly PHP errors

function out($ok, $message) { // Small helper: sends the answer back to the page and stops
  echo json_encode(["ok" => $ok, "message" => $message]); // Send true/false and a message
  exit; // Stop running the file
} // Ends out

// ---------- 1. CONNECT TO MYSQL AND SET UP kaso_db ----------
$conn = new mysqli("localhost", "root", ""); // Connect to MySQL (XAMPP default: user root, empty password)
if ($conn->connect_error) out(false, "Cannot connect to MySQL."); // If MySQL is not running, send an error

$conn->query("CREATE DATABASE IF NOT EXISTS kaso_db"); // Create the database kaso_db if it does not exist yet
$conn->select_db("kaso_db"); // Use the kaso_db database

$conn->query("CREATE TABLE IF NOT EXISTS drone_users (   -- Create the accounts table if it does not exist yet
  id INT AUTO_INCREMENT PRIMARY KEY,                     -- Unique number for each account (1, 2, 3...)
  username VARCHAR(50) NOT NULL UNIQUE,                  -- Username (cannot be empty, no two the same)
  password VARCHAR(255) NOT NULL,                        -- Password saved scrambled (hashed), not plain text
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP         -- Date and time the account was created
)"); // Ends the CREATE TABLE

// ---------- 2. READ WHAT THE PAGE SENT ----------
$action   = $_POST["action"] ?? "";           // What the page wants: signup, login, verify or reset
$username = trim($_POST["username"] ?? "");   // The username (trim removes extra spaces)
$password = $_POST["password"] ?? "";         // The password
$code     = $_POST["code"] ?? "";             // The verification code (demo code is 123456)

// ---------- 3. SIGN UP ----------
if ($action === "signup") { // If the page wants to create an account
  if ($username === "" || $password === "") out(false, "Fill in all fields."); // A field is empty? Error
  if (strlen($password) < 6) out(false, "Minimum 6 characters."); // Password too short? Error

  $check = $conn->prepare("SELECT id FROM drone_users WHERE username = ?"); // Safe query: look for this username
  $check->bind_param("s", $username); // Put the username into the ?
  $check->execute(); // Run the query
  $check->store_result(); // Keep the result so we can count rows
  if ($check->num_rows > 0) out(false, "Username already taken."); // Found one? Then it is taken

  $hash = password_hash($password, PASSWORD_DEFAULT); // Scramble the password
  $add = $conn->prepare("INSERT INTO drone_users (username, password) VALUES (?, ?)"); // Safe query: add the account
  $add->bind_param("ss", $username, $hash); // Put the username and scrambled password into the two ?
  $add->execute(); // Run the query (saves the account)
  out(true, "Account created! Please log in."); // Tell the page it worked
} // Ends signup

// ---------- 4. LOGIN ----------
if ($action === "login") { // If the page wants to log in
  $stmt = $conn->prepare("SELECT password FROM drone_users WHERE username = ?"); // Safe query: get the saved password
  $stmt->bind_param("s", $username); // Put the username into the ?
  $stmt->execute(); // Run the query
  $stmt->bind_result($hash); // Say where to put the saved (scrambled) password
  $found = $stmt->fetch(); // Get the row (true if the username exists)
  if ($found && password_verify($password, $hash)) out(true, "Login successful! Welcome, $username."); // Match? Welcome
  out(false, "Wrong username or password."); // No match? Error
} // Ends login

// ---------- 5. FORGOT PASSWORD: VERIFY ----------
if ($action === "verify" || $action === "reset") { // Both verify and reset start with the same checks
  if ($code !== "123456") out(false, "Wrong username or code."); // Wrong code? Error
  $stmt = $conn->prepare("SELECT id FROM drone_users WHERE username = ?"); // Safe query: does this username exist?
  $stmt->bind_param("s", $username); // Put the username into the ?
  $stmt->execute(); // Run the query
  $stmt->store_result(); // Keep the result so we can count rows
  if ($stmt->num_rows === 0) out(false, "Wrong username or code."); // No such user? Error

  if ($action === "verify") out(true, ""); // Verify only: all good, the page shows the Change Password section

  // ---------- 6. FORGOT PASSWORD: CHANGE PASSWORD ----------
  if (strlen($password) < 6) out(false, "Minimum 6 characters."); // New password too short? Error
  $hash = password_hash($password, PASSWORD_DEFAULT); // Scramble the new password
  $upd = $conn->prepare("UPDATE drone_users SET password = ? WHERE username = ?"); // Safe query: change the password
  $upd->bind_param("ss", $hash, $username); // Put the scrambled password and username into the two ?
  $upd->execute(); // Run the query (updates the account)
  out(true, "Password changed. Please log in."); // Tell the page it worked
} // Ends verify/reset

out(false, "Unknown request."); // Anything else: error
