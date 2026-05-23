<?php
// Start the session to store user data across requests
session_start();

// --- Database Connection (Real Database Example - MySQL with PDO) ---
// IMPORTANT: Replace these with your actual database credentials
$db_host = 'localhost'; // Usually 'localhost' for local development
$db_name = 'monitoring'; // Replace with your database name
$db_user = 'root'; // Replace with your database username
$db_pass = ''; // Replace with your database password

$pdo = null; // Initialize PDO object

try {
    // Attempt to establish a database connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If connection fails, display an error message and stop execution
    die("Database connection failed: " . $e->getMessage());
}

// Function to hash a password (for storing new user passwords)
function hash_password($password) {
    // PASSWORD_BCRYPT is a strong, modern hashing algorithm
    return password_hash($password, PASSWORD_BCRYPT);
}

// --- Handle Logout Request ---
// This part should be handled before any HTML is sent to the browser
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();   // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page after logout
    exit();
}

// --- Handle Login Request ---
$error_message = ''; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // --- HARDCODED ADMIN ACCESS FOR TESTING (REMOVE IN PRODUCTION) ---
    $hardcoded_admin_email = 'admin@monitoring.com';
    $hardcoded_admin_password = 'admin123'; // Use a simple password for testing

    if ($email === $hardcoded_admin_email && $password === $hardcoded_admin_password) {
        // Hardcoded admin authentication successful
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $hardcoded_admin_email;
        $_SESSION['user_role'] = 'admin'; // Assign 'admin' role
        $_SESSION['user_id'] = 1; 
        header("Location: admin.php");
        exit();
    }
    // --- END HARDCODED ADMIN ACCESS ---

    $user_data = null; // Initialize user data

    // --- Query the real database for user authentication ---
    try {
        $stmt = $pdo->prepare("SELECT id, email, password, role FROM user WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user_data = $stmt->fetch(); // Fetch the user's row
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données lors de la vérification de l'utilisateur.";
    }

    // Check if user exists and password is correct (from database)
    if ($user_data && password_verify($password, $user_data['password'])) {
        // Authentication successful
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_role'] = $user_data['role'];
        $_SESSION['user_id'] = $user_data['id'];

        if ($user_data['role'] == 'admin') {
            header("Location: admin.php");
            exit();
        } else {
            header("Location: user.php");
            exit();
        }
    } else {
        // Authentication failed
        $error_message = "Email ou mot de passe incorrect.";
    }
}

// If user is already logged in, redirect them based on their role
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: admin.php");
        exit();
    } else {
        header("Location: user.php");
        exit();
    }
}

// If there was an error message from a failed login attempt, retrieve it from session
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error message after displaying
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Vidéo en arrière-plan -->
    <video autoplay loop muted id="background-video">
        <source src="r.mp4" type="video/mp4">
        Votre navigateur ne prend pas en charge la balise vidéo.
    </video>

    <div class="login-container">
        <h2>Se connecter</h2>
        <form action="index.php" method="POST">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <?php
            if (!empty($error_message)) {
                echo '<p style="color: red; margin-bottom: 15px;">' . htmlspecialchars($error_message) . '</p>';
            }
            ?>
            <button type="submit">Connexion</button>
        </form>
            </div>
</body>
</html>
