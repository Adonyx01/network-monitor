<?php
session_start();
header('Content-Type: application/json'); // Indique que la réponse est du JSON

// Protection basique : S'assurer que la requête vient d'un utilisateur connecté
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit();
}

// Connexion à la base de données
$db_host = 'localhost';
$db_name = 'monitoring';
$db_user = 'root';
$db_pass = '';

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la connexion BDD échoue, renvoyer une erreur JSON
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données.', 'notes' => $e->getMessage()]);
    exit();
}

// Récupérer l'ID de l'utilisateur connecté depuis la session
$current_user_id = $_SESSION['user_id'] ?? null;
if ($current_user_id === null) {
    echo json_encode(['success' => false, 'error' => 'ID utilisateur non trouvé en session.']);
    exit();
}

// Vérifier que les données POST nécessaires sont présentes
if (!isset($_POST['ip_address']) || !isset($_POST['device_id'])) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes (adresse IP ou ID de l\'appareil).']);
    exit();
}

$ip_address = trim($_POST['ip_address']);
$device_id = $_POST['device_id'];

// Valider l'adresse IP
if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
    echo json_encode(['success' => false, 'error' => 'Adresse IP invalide.', 'ip' => $ip_address]);
    exit();
}

// Préparer les variables de résultat
$is_connected = false;
$ping_output_raw = '';
$test_status_message = 'Déconnecté';
$device_new_status = 'inactif'; // Statut par défaut si non connecté
$last_check_time = date('Y-m-d H:i:s'); // Temps actuel, sera mis à jour après la vérification

try {
    // Déterminer le chemin de la commande ping en fonction du système d'exploitation
    $ping_path = '';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $ping_path = 'ping'; // Sur Windows, 'ping' est généralement dans le PATH
        $command = "$ping_path -n 1 -w 1000 " . escapeshellarg($ip_address);
    } else {
        // Pour Linux/Unix, chercher des chemins courants et rendre la commande exécutable
        $possible_ping_paths = ['/bin/ping', '/usr/bin/ping', '/sbin/ping', '/usr/sbin/ping'];
        foreach ($possible_ping_paths as $path) {
            if (is_executable($path)) {
                $ping_path = $path;
                break;
            }
        }
        if (empty($ping_path)) {
            throw new Exception("La commande 'ping' est introuvable ou non exécutable sur le serveur PHP. Chemins vérifiés: " . implode(", ", $possible_ping_paths));
        }
        $command = "$ping_path -c 1 -W 1 " . escapeshellarg($ip_address); // -c 1: 1 paquet, -W 1: 1 seconde de timeout
    }

    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var); // Exécuter la commande ping

    $ping_output_raw = implode("\n", $output); // Convertir le tableau de sortie en chaîne
    
    // Analyser la sortie pour déterminer la connectivité
    if ($return_var === 0) { // Code de retour 0 indique généralement un succès
        foreach ($output as $line) {
            $normalized_line = strtolower(trim($line));
            if (strpos($normalized_line, 'received = 1') !== false || // Windows
                strpos($normalized_line, '0% packet loss') !== false || // Linux/Windows
                strpos($normalized_line, 'reçu = 1') !== false || // Windows (fr)
                strpos($normalized_line, 'reçus = 1') !== false || // Linux (fr)
                strpos($normalized_line, 'recus = 1') !== false || // Linux (fr, sans accent)
                strpos(str_replace('?', '', $normalized_line), 'recus = 1') !== false || // Added for robustness with ?
                strpos($normalized_line, 'perte 0%') !== false) { // Linux (fr)
                $is_connected = true;
                break;
            }
        }
    }

    if ($is_connected) {
        $test_status_message = 'Actif';
        $threshold_met = 1;
        $device_new_status = 'actif';
    } else {
        $test_status_message = 'Inactif';
        $threshold_met = 0;
        $device_new_status = 'inactif';
    }

    // Mettre à jour le statut de l'appareil dans la BDD
    $stmt_update_device = $pdo->prepare("UPDATE appareils SET status = :status, last_check = CURRENT_TIMESTAMP() WHERE id = :id");
    $stmt_update_device->bindParam(':status', $device_new_status);
    $stmt_update_device->bindParam(':id', $device_id);
    $stmt_update_device->execute();

    // Enregistrer le résultat du test dans la table 'tests'
    $stmt_insert_test = $pdo->prepare(
        "INSERT INTO tests (device_id, scheduled_at, status, last_run_at, created_by, test_type)
         VALUES (:device_id, CURRENT_TIMESTAMP(), 'terminé', CURRENT_TIMESTAMP(), :created_by, 'connectivité')"
    );
    $stmt_insert_test->bindParam(':device_id', $device_id);
    $stmt_insert_test->bindParam(':created_by', $current_user_id);
    $stmt_insert_test->execute();
    $test_id = $pdo->lastInsertId();

    // Enregistrer le résultat détaillé dans la table 'test_results'
    $notes = "Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):\n" . $ping_output_raw;
    $stmt_insert_result = $pdo->prepare(
        "INSERT INTO test_results (test_id, result_value, threshold_met, notes, run_at, run_by)
         VALUES (:test_id, :result_value, :threshold_met, :notes, CURRENT_TIMESTAMP(), :run_by)"
    );
    $stmt_insert_result->bindParam(':test_id', $test_id);
    $stmt_insert_result->bindParam(':result_value', $test_status_message);
    $stmt_insert_result->bindParam(':threshold_met', $threshold_met, PDO::PARAM_INT);
    $stmt_insert_result->bindParam(':notes', $notes);
    $stmt_insert_result->bindParam(':run_by', $current_user_id);
    $stmt_insert_result->execute();

    // Récupérer l'heure de la dernière vérification après la mise à jour
    $stmt_get_last_check = $pdo->prepare("SELECT last_check FROM appareils WHERE id = :id");
    $stmt_get_last_check->bindParam(':id', $device_id);
    $stmt_get_last_check->execute();
    $last_check_from_db = $stmt_get_last_check->fetchColumn();
    if ($last_check_from_db) {
        $last_check_time = $last_check_from_db;
    }

    // Réponse JSON de succès
    echo json_encode([
        'success' => true,
        'test_status' => $test_status_message,
        'device_new_status' => $device_new_status,
        'last_check' => $last_check_time,
        'notes' => $notes // Inclure les notes pour le débogage et l'affichage
    ]);

} catch (Exception $e) {
    // En cas d'erreur lors de l'exécution ou de la BDD, renvoyer une erreur JSON
    error_log("Erreur dans ping_device.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue lors du test de connectivité.',
        'notes' => $e->getMessage() . "\nCommande exécutée: " . ($command ?? 'N/A') . "\nSortie brute: " . $ping_output_raw
    ]);
}
?>
