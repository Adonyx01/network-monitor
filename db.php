<?php
// Start the session to store user data across requests
session_start();

// --- Database Simulation ---
// In a real application, you would connect to a database (MySQL, PostgreSQL, etc.)
// using PDO or mysqli. Passwords should ALWAYS be hashed using password_hash()
// and verified with password_verify().

// Function to hash a password
function hash_password($password) {
    // PASSWORD_BCRYPT is a strong, modern hashing algorithm
    return password_hash($password, PASSWORD_BCRYPT);
}

// Simulated user data (Email, Hashed Password, Role, Creation Date)
// Replace 'your_admin_password' and 'your_user_password' by real passwords
// and hash them before putting them here.
// Example: hash_password('secureadminpass')
$users_db = [
    "admin@example.com" => [
        "password_hash" => hash_password("motdepasseadmin123"), // Hashed password for admin
        "role" => "admin",
        "createdate" => "2023-01-01"
    ],
    "user@example.com" => [
        "password_hash" => hash_password("motdepasseuser123"),  // Hashed password for user
        "role" => "user",
        "createdate" => "2023-01-05"
    ]
];

// --- Handle Login Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $user_data = $users_db[$email] ?? null;

    if ($user_data && password_verify($password, $user_data['password_hash'])) {
        // Authentication successful
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $user_data['role'];

        if ($user_data['role'] == 'admin') {
            header("Location: admin.html"); // Redirect to admin page
            exit();
        } else {
            header("Location: user.html"); // Redirect to user page
            exit();
        }
    } else {
        // Authentication failed
        $error_message = "Email ou mot de passe incorrect.";
        // Store error message in session to display it on login page
        $_SESSION['login_error'] = $error_message;
        header("Location: index.php"); // Redirect back to login page (now index.php)
        exit();
    }
}

// --- Handle Logout Request ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();   // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page after logout (now index.php)
    exit();
}

// If accessed directly without POST or logout action, redirect to login page
header("Location: index.php");
exit();
?>
