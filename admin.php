<?php
session_start();

// Protection de la page admin - seul un admin connecté peut y accéder
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Inclure FPDF pour la génération de PDF
require('fpdf/fpdf.php');

// Inclure les classes PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';


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
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour hacher le mot de passe (gardée pour consistance)
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Gestion des messages de succès/erreur
$message = '';
$message_type = '';
$detailed_error_message = '';

if (isset($_GET['success'])) {
    $message_type = 'success';
    switch ($_GET['success']) {
        case 'user_added': $message = 'Utilisateur ajouté avec succès !'; break;
        case 'user_updated': $message = 'Utilisateur mis à jour avec succès !'; break;
        case 'user_deleted': $message = 'Utilisateur supprimé avec succès !'; break;
        case 'device_added': $message = 'Appareil ajouté avec succès !'; break;
        case 'device_updated': $message = 'Appareil mis à jour avec succès !'; break;
        case 'device_deleted': $message = 'Appareil supprimé avec succès !'; break;
        case 'alert_added': $message = 'Alerte ajoutée avec succès !'; break;
        case 'alert_updated': $message = 'Alerte mise à jour avec succès !'; break;
        case 'alert_deleted': $message = 'Alerte supprimée avec succès !'; break;
        case 'alert_resolved': $message = 'Alerte résolue avec succès !'; break;
        case 'connectivity_test_run': $message = 'Test de connectivité exécuté avec succès !'; break;
        case 'pdf_generated': $message = 'Rapport PDF généré avec succès !'; break;
        case 'pdf_sent': $message = 'Rapport PDF généré et envoyé à votre adresse e-mail !'; break; // Nouveau message pour l'admin
    }
} elseif (isset($_GET['error'])) {
    $message_type = 'error';
    if (isset($_SESSION['debug_error_message'])) {
        $detailed_error_message = $_SESSION['debug_error_message'];
        unset($_SESSION['debug_error_message']); // Supprimer après affichage
    }
    switch ($_GET['error']) {
        case 'db_error': $message = 'Erreur de base de données.'; break;
        case 'invalid_data': $message = 'Données invalides fournies.'; break;
        case 'user_exists': $message = 'Un utilisateur avec cet e-mail existe déjà.'; break;
        case 'user_not_found': $message = 'Utilisateur non trouvé.'; break;
        case 'self_delete': $message = 'Vous ne pouvez pas supprimer votre propre compte.'; break;
        case 'device_not_found': $message = 'Appareil non trouvé.'; break;
        case 'alert_not_found': $message = 'Alerte non trouvée.'; break;
        case 'connectivity_test_failed': $message = 'Erreur lors de l\'exécution du test de connectivité.'; break;
        case 'pdf_generation_failed': $message = 'Erreur lors de la génération du rapport PDF.'; break;
        case 'pdf_send_failed': $message = 'Erreur lors de l\'envoi du rapport PDF par e-mail.'; break; // Nouveau message pour l'admin
    }
}


// --- Logique de gestion des utilisateurs ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add_user' || $action == 'update_user') {
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $user_id = $_POST['user_id'] ?? null;

        if (empty($email) || empty($role)) {
            header("Location: admin.php?error=invalid_data#user-management");
            exit();
        }

        // Validation simple du rôle
        if (!in_array($role, ['admin', 'user'])) {
            header("Location: admin.php?error=invalid_data#user-management");
            exit();
        }

        try {
            if ($action == 'add_user') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    header("Location: admin.php?error=user_exists#user-management");
                    exit();
                }
                $hashed_password = hash_password($password);
                $stmt = $pdo->prepare("INSERT INTO user (email, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hashed_password, $role]);
                header("Location: admin.php?success=user_added#user-management");
                exit();
            } elseif ($action == 'update_user') {
                if ($user_id === null) {
                    header("Location: admin.php?error=invalid_data#user-management");
                    exit();
                }

                $query = "UPDATE user SET email = ?, role = ? WHERE id = ?";
                $params = [$email, $role, $user_id];

                if (!empty($password)) {
                    $hashed_password = hash_password($password);
                    $query = "UPDATE user SET email = ?, password = ?, role = ? WHERE id = ?";
                    $params = [$email, $hashed_password, $role, $user_id];
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                header("Location: admin.php?success=user_updated#user-management");
                exit();
            }
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#user-management");
            exit();
        }
    } elseif ($action == 'delete_user') {
        $user_id_to_delete = $_POST['user_id'] ?? null;
        if ($user_id_to_delete === null) {
            header("Location: admin.php?error=invalid_data#user-management");
            exit();
        }
        if ($user_id_to_delete == $_SESSION['user_id']) {
            header("Location: admin.php?error=self_delete#user-management");
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
            $stmt->execute([$user_id_to_delete]);
            if ($stmt->rowCount() == 0) {
                header("Location: admin.php?error=user_not_found#user-management");
                exit();
            }
            header("Location: admin.php?success=user_deleted#user-management");
            exit();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#user-management");
            exit();
        }
    }
}

// --- Logique de gestion des appareils ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add_device' || $action == 'update_device') {
        $name = trim($_POST['name'] ?? '');
        $ip_address = trim($_POST['ip_address'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $status = trim($_POST['status'] ?? 'inactif'); // Default status
        $location = trim($_POST['location'] ?? '');
        $device_id = $_POST['device_id'] ?? null;
        $created_by = $_SESSION['user_id']; // L'administrateur qui ajoute/modifie

        if (empty($name) || empty($ip_address) || empty($type)) {
            header("Location: admin.php?error=invalid_data#device-management");
            exit();
        }

        try {
            if ($action == 'add_device') {
                $stmt = $pdo->prepare("INSERT INTO appareils (name, ip_address, type, status, location, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $ip_address, $type, $status, $location, $created_by]);
                header("Location: admin.php?success=device_added#device-management");
                exit();
            } elseif ($action == 'update_device') {
                if ($device_id === null) {
                    header("Location: admin.php?error=invalid_data#device-management");
                    exit();
                }
                $stmt = $pdo->prepare("UPDATE appareils SET name = ?, ip_address = ?, type = ?, status = ?, location = ? WHERE id = ?");
                $stmt->execute([$name, $ip_address, $type, $status, $location, $device_id]);
                header("Location: admin.php?success=device_updated#device-management");
                exit();
            }
        } catch (PDOException $e) {
            error_log("DB Error (Appareils): " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#device-management");
            exit();
        }
    } elseif ($action == 'delete_device') {
        $device_id_to_delete = $_POST['device_id'] ?? null;
        if ($device_id_to_delete === null) {
            header("Location: admin.php?error=invalid_data#device-management");
            exit();
        }
        try {
            // Supprimer les entrées liées dans 'tests' et 'alerts' en premier pour éviter les erreurs de clé étrangère
            $pdo->beginTransaction();
            $stmt_delete_test_results = $pdo->prepare("DELETE FROM test_results WHERE test_id IN (SELECT id FROM tests WHERE device_id = ?)");
            $stmt_delete_test_results->execute([$device_id_to_delete]);
            $stmt_delete_tests = $pdo->prepare("DELETE FROM tests WHERE device_id = ?");
            $stmt_delete_tests->execute([$device_id_to_delete]);
            $stmt_delete_alerts = $pdo->prepare("DELETE FROM alerts WHERE device_id = ?");
            $stmt_delete_alerts->execute([$device_id_to_delete]);

            $stmt = $pdo->prepare("DELETE FROM appareils WHERE id = ?");
            $stmt->execute([$device_id_to_delete]);
            if ($stmt->rowCount() == 0) {
                $pdo->rollBack();
                header("Location: admin.php?error=device_not_found#device-management");
                exit();
            }
            $pdo->commit();
            header("Location: admin.php?success=device_deleted#device-management");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("DB Error (Delete Device): " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#device-management");
            exit();
        }
    }
}

// --- Logique de gestion des alertes ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add_alert' || $action == 'update_alert') {
        $device_id = $_POST['device_id'] ?? null;
        $alert_type = trim($_POST['alert_type'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $alert_id = $_POST['alert_id'] ?? null;

        if (empty($device_id) || empty($alert_type) || empty($message)) {
            header("Location: admin.php?error=invalid_data#alert-management");
            exit();
        }

        try {
            if ($action == 'add_alert') {
                $stmt = $pdo->prepare("INSERT INTO alerts (device_id, alert_type, message, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$device_id, $alert_type, $message, $is_active]);
                header("Location: admin.php?success=alert_added#alert-management");
                exit();
            } elseif ($action == 'update_alert') {
                if ($alert_id === null) {
                    header("Location: admin.php?error=invalid_data#alert-management");
                    exit();
                }
                $stmt = $pdo->prepare("UPDATE alerts SET device_id = ?, alert_type = ?, message = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$device_id, $alert_type, $message, $is_active, $alert_id]);
                header("Location: admin.php?success=alert_updated#alert-management");
                exit();
            }
        } catch (PDOException $e) {
            error_log("DB Error (Alerts): " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#alert-management");
            exit();
        }
    } elseif ($action == 'delete_alert') {
        $alert_id_to_delete = $_POST['alert_id'] ?? null;
        if ($alert_id_to_delete === null) {
            header("Location: admin.php?error=invalid_data#alert-management");
            exit();
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM alerts WHERE id = ?");
            $stmt->execute([$alert_id_to_delete]);
            if ($stmt->rowCount() == 0) {
                header("Location: admin.php?error=alert_not_found#alert-management");
                exit();
            }
            header("Location: admin.php?success=alert_deleted#alert-management");
            exit();
        } catch (PDOException $e) {
            error_log("DB Error (Delete Alert): " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#alert-management");
            exit();
        }
    } elseif ($action == 'resolve_alert') {
        $alert_id_to_resolve = $_POST['alert_id'] ?? null;
        if ($alert_id_to_resolve === null) {
            header("Location: admin.php?error=invalid_data#alert-management");
            exit();
        }
        try {
            $stmt = $pdo->prepare("UPDATE alerts SET is_active = 0 WHERE id = ?");
            $stmt->execute([$alert_id_to_resolve]);
            if ($stmt->rowCount() == 0) {
                header("Location: admin.php?error=alert_not_found#alert-management");
                exit();
            }
            header("Location: admin.php?success=alert_resolved#alert-management");
            exit();
        } catch (PDOException $e) {
            error_log("DB Error (Resolve Alert): " . $e->getMessage());
            $_SESSION['debug_error_message'] = $e->getMessage();
            header("Location: admin.php?error=db_error#alert-management");
            exit();
        }
    }
}

// --- Logique de gestion des tests de connectivité des appareils (globaux) ---
$current_user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'run_connectivity_test') {
    if ($current_user_id === null) {
        error_log("Erreur: User ID non trouvé en session pour l'exécution du test de connectivité.");
        $_SESSION['debug_error_message'] = "Erreur: ID utilisateur non trouvé en session pour exécuter le test.";
        header("Location: admin.php?error=connectivity_test_failed#connectivity-test");
        exit();
    }

    try {
        $stmt_devices = $pdo->query("SELECT id, name, ip_address FROM appareils");
        $all_devices = $stmt_devices->fetchAll();

        if (empty($all_devices)) {
            $_SESSION['debug_error_message'] = "Aucun appareil configuré pour le test de connectivité. Veuillez ajouter des appareils.";
            header("Location: admin.php?error=connectivity_test_failed#connectivity-test");
            exit();
        }

        $ping_path = '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $ping_path = 'ping';
        } else {
            $possible_ping_paths = ['/bin/ping', '/usr/bin/ping', '/sbin/ping', '/usr/sbin/ping'];
            foreach ($possible_ping_paths as $path) {
                if (is_executable($path)) {
                    $ping_path = $path;
                    break;
                }
            }
        }

        if (empty($ping_path)) {
            $_SESSION['debug_error_message'] = "Erreur: La commande 'ping' est introuvable ou non exécutable sur le serveur PHP. Vérifiez le PATH de l'environnement du serveur et les permissions.";
            error_log("CRITICAL ERROR: ping command not found or not executable. PHP_OS: " . PHP_OS . " Possible paths checked: " . implode(", ", $possible_ping_paths));
            header("Location: admin.php?error=connectivity_test_failed#connectivity-test");
            exit();
        }

        foreach ($all_devices as $device) {
            $device_id = $device['id'];
            $ip_address = escapeshellarg($device['ip_address']);

            $is_connected = false;
            $result_message = 'Déconnecté';
            $threshold_met = 0;
            $ping_latency = null;

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = "$ping_path -n 1 -w 1000 " . $ip_address;
            } else {
                $command = "$ping_path -c 1 -W 1 " . $ip_address;
            }

            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);

            if ($return_var === 0) {
                foreach ($output as $line) {
                    $normalized_line = strtolower(trim($line));
                    if (strpos($normalized_line, 'received = 1') !== false ||
                        strpos($normalized_line, '0% packet loss') !== false ||
                        strpos($normalized_line, 'reçu = 1') !== false ||
                        strpos($normalized_line, 'reçus = 1') !== false ||
                        strpos($normalized_line, 'recus = 1') !== false ||
                        strpos(str_replace('?', '', $normalized_line), 'recus = 1') !== false ||
                        strpos($normalized_line, 'perte 0%') !== false) {
                        $is_connected = true;
                    }

                    if (preg_match('/average = (\d+(\.\d+)?)ms/i', $normalized_line, $matches) ||
                        preg_match('/moyenne = (\d+(\.\d+)?)ms/i', $normalized_line, $matches)) {
                        $ping_latency = (float)$matches[1];
                    } elseif (preg_match('/time=(\d+(\.\d+)?)ms/i', $normalized_line, $matches)) {
                        $ping_latency = (float)$matches[1];
                    }
                }
            }

            if ($is_connected) {
                $result_message = 'Connecté';
                $threshold_met = 1;
            } else {
                $result_message = 'Déconnecté';
            }

            $stmt_insert_test = $pdo->prepare(
                "INSERT INTO tests (device_id, scheduled_at, status, last_run_at, created_by, test_type)
                 VALUES (:device_id, CURRENT_TIMESTAMP(), 'terminé', CURRENT_TIMESTAMP(), :created_by, 'connectivité')"
            );
            $stmt_insert_test->bindParam(':device_id', $device_id);
            $stmt_insert_test->bindParam(':created_by', $current_user_id);
            $stmt_insert_test->execute();
            $test_id = $pdo->lastInsertId();

            $notes = "Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec): " . implode(" | ", $output);
            $stmt_insert_result = $pdo->prepare(
                "INSERT INTO test_results (test_id, result_value, threshold_met, notes, run_at, run_by, ping_latency_ms)
                 VALUES (:test_id, :result_value, :threshold_met, :notes, CURRENT_TIMESTAMP(), :run_by, :ping_latency_ms)"
            );
            $stmt_insert_result->bindParam(':test_id', $test_id);
            $stmt_insert_result->bindParam(':result_value', $result_message);
            $stmt_insert_result->bindParam(':threshold_met', $threshold_met, PDO::PARAM_INT);
            $stmt_insert_result->bindParam(':notes', $notes);
            $stmt_insert_result->bindParam(':run_by', $current_user_id);
            $stmt_insert_result->bindParam(':ping_latency_ms', $ping_latency);
            $stmt_insert_result->execute();

            $new_status = $is_connected ? 'actif' : 'inactif';
            $stmt_update_device = $pdo->prepare("UPDATE appareils SET status = :status, last_check = CURRENT_TIMESTAMP() WHERE id = :id");
            $stmt_update_device->bindParam(':status', $new_status);
            $stmt_update_device->bindParam(':id', $device_id);
            $stmt_update_device->execute();
        }
        header("Location: admin.php?success=connectivity_test_run#connectivity-test"); exit();
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de l'exécution du test de connectivité: " . $e->getMessage());
        $_SESSION['debug_error_message'] = "Erreur PDO: " . $e->getMessage();
        header("Location: admin.php?error=connectivity_test_failed#connectivity-test");
        exit();
    } catch (Exception $e) {
        error_log("Erreur générale lors de l'exécution du test de connectivité: " . $e->getMessage());
        $_SESSION['debug_error_message'] = "Erreur générale: " . $e->getMessage();
        header("Location: admin.php?error=connectivity_test_failed#connectivity-test");
        exit();
    }
}

// --- Logique de génération de rapport PDF (Avec FPDF et envoi par PHPMailer) pour ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'generate_admin_pdf_report') {
    $admin_email_for_report = $_SESSION['user_email'] ?? null; // Récupérer l'email de l'administrateur connecté

    if ($admin_email_for_report === null) {
        $_SESSION['debug_error_message'] = "Impossible d'envoyer le rapport : adresse e-mail de l'administrateur non trouvée.";
        header("Location: admin.php?error=pdf_send_failed#monitoring-data");
        exit();
    }

    try {
        $pdf = new FPDF('P', 'mm', 'A3'); // Format A3 pour plus d'espace
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Rapport Complet de Monitoring du Système'), 0, 1, 'C');
        $pdf->Ln(10);

        // --- Section Statut des Appareils ---
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Statut des Appareils'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);

        $stmt_devices_pdf = $pdo->query("SELECT a.id, a.name, a.ip_address, a.type, a.status, a.location, a.last_check, a.created_at, u.email AS created_by_email
                                         FROM appareils a LEFT JOIN user u ON a.created_by = u.id ORDER BY a.id DESC");
        $devices_for_pdf = $stmt_devices_pdf->fetchAll();

        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetDrawColor(180, 180, 180);
        $header_appareils = ['ID', 'Nom', 'IP', 'Type', 'Statut', 'Localisation', 'Dernière Vérification', 'Créé par'];
        $width_appareils = [15, 40, 30, 25, 25, 35, 40, 30]; // Ajusté pour A3

        for ($i = 0; $i < count($header_appareils); $i++) {
            $pdf->Cell($width_appareils[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header_appareils[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        if (empty($devices_for_pdf)) {
            $pdf->Cell(array_sum($width_appareils), 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucun appareil à rapporter.'), 1, 1, 'C');
        } else {
            foreach ($devices_for_pdf as $device) {
                $pdf->Cell($width_appareils[0], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['id']), 1);
                $pdf->Cell($width_appareils[1], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['name']), 1);
                $pdf->Cell($width_appareils[2], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['ip_address'] ?? 'N/A'), 1);
                $pdf->Cell($width_appareils[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['type']), 1);
                $pdf->Cell($width_appareils[4], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['status']), 1);
                $pdf->Cell($width_appareils[5], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['location'] ?? 'N/A'), 1);
                $pdf->Cell($width_appareils[6], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['last_check'] ?? 'N/A'), 1);
                $pdf->Cell($width_appareils[7], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['created_by_email'] ?? 'Inconnu'), 1);
                $pdf->Ln();
            }
        }
        $pdf->Ln(10);

        // --- Section Alertes Récentes ---
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Alertes Récentes'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);

        $stmt_alerts_pdf = $pdo->query("SELECT a.id, a.device_id, a.alert_type, a.message, a.is_active, a.created_at, d.name AS device_name
                                        FROM alerts a LEFT JOIN appareils d ON a.device_id = d.id ORDER BY a.created_at DESC");
        $alerts_for_pdf = $stmt_alerts_pdf->fetchAll();

        $header_alerts = ['ID', 'Appareil', 'Type', 'Message', 'Statut', 'Date'];
        $width_alerts = [15, 45, 25, 90, 25, 30]; // Ajusté pour A3

        for ($i = 0; $i < count($header_alerts); $i++) {
            $pdf->Cell($width_alerts[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header_alerts[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        if (empty($alerts_for_pdf)) {
            $pdf->Cell(array_sum($width_alerts), 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucune alerte à rapporter.'), 1, 1, 'C');
        } else {
            foreach ($alerts_for_pdf as $alert) {
                $pdf->Cell($width_alerts[0], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['id']), 1);
                $pdf->Cell($width_alerts[1], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['device_name'] ?? 'N/A'), 1);
                $pdf->Cell($width_alerts[2], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['alert_type']), 1);
                
                $message_truncated = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['message']);
                if (strlen($message_truncated) > 80) { // Ajusté pour A3
                    $message_truncated = substr($message_truncated, 0, 77) . '...';
                }
                $pdf->Cell($width_alerts[3], 7, $message_truncated, 1);
                
                $status = ($alert['is_active'] == 1) ? 'Active' : 'Résolue';
                $pdf->Cell($width_alerts[4], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $status), 1);
                $pdf->Cell($width_alerts[5], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['created_at']), 1);
                $pdf->Ln();
            }
        }
        $pdf->Ln(10);

        // --- Section Historique des Tests de Connectivité ---
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Historique des Tests de Connectivité'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);

        $stmt_connectivity_tests_pdf = $pdo->query("SELECT tr.id, tr.test_id, t.device_id, a.name AS device_name, a.ip_address,
                                                     tr.result_value, tr.threshold_met, tr.notes, tr.run_at, u.email AS run_by_email, u.role AS run_by_role, tr.ping_latency_ms
                                                     FROM test_results tr
                                                     JOIN tests t ON tr.test_id = t.id
                                                     JOIN appareils a ON t.device_id = a.id
                                                     LEFT JOIN user u ON tr.run_by = u.id
                                                     WHERE t.test_type = 'connectivité'
                                                     ORDER BY tr.run_at DESC");
        $connectivity_tests_for_pdf = $stmt_connectivity_tests_pdf->fetchAll();

        $header_connectivity_tests = ['ID Test', 'Appareil', 'IP', 'Résultat', 'Latence (ms)', 'Exécuté par', 'Date d\'exécution'];
        $width_connectivity_tests = [20, 45, 30, 25, 30, 60, 30]; // Ajusté pour A3

        for ($i = 0; $i < count($header_connectivity_tests); $i++) {
            $pdf->Cell($width_connectivity_tests[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header_connectivity_tests[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        if (empty($connectivity_tests_for_pdf)) {
            $pdf->Cell(array_sum($width_connectivity_tests), 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucun test de connectivité à rapporter.'), 1, 1, 'C');
        } else {
            foreach ($connectivity_tests_for_pdf as $test_result) {
                $executed_by_text = '';
                if ($test_result['run_by_role'] == 'admin') {
                    $executed_by_text = 'Admin';
                } else {
                    $email_display = $test_result['run_by_email'] ?? 'Inconnu';
                    if (strlen($email_display) > 30) { // Ajusté pour A3
                        $email_display = substr($email_display, 0, 27) . '...';
                    }
                    $executed_by_text = 'Utilisateur: ' . $email_display;
                }
                
                $pdf->Cell($width_connectivity_tests[0], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $test_result['test_id']), 1);
                $pdf->Cell($width_connectivity_tests[1], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $test_result['device_name'] ?? 'N/A'), 1);
                $pdf->Cell($width_connectivity_tests[2], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $test_result['ip_address'] ?? 'N/A'), 1);
                $pdf->Cell($width_connectivity_tests[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $test_result['result_value']), 1);
                $latency_display = ($test_result['ping_latency_ms'] !== null) ? round($test_result['ping_latency_ms'], 2) . ' ms' : 'N/A';
                $pdf->Cell($width_connectivity_tests[4], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $latency_display), 1); 
                $pdf->Cell($width_connectivity_tests[5], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $executed_by_text), 1);
                $pdf->Cell($width_connectivity_tests[6], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $test_result['run_at']), 1);
                $pdf->Ln();
            }
        }


        $pdf_content = $pdf->Output('S', 'rapport_monitoring_admin_' . date('Ymd_His') . '.pdf');
        $pdf_filename = 'rapport_monitoring_admin_' . date('Ymd_His') . '.pdf';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // REMPLACEZ PAR VOTRE HÔTE SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'votre_email@gmail.com';        // REMPLACEZ PAR VOTRE ADRESSE E-MAIL D'ENVOI
            $mail->Password   = 'votre_mot_de_passe_application'; // REMPLACEZ PAR LE MOT DE PASSE D'APPLICATION GMAIL
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('votre_email@example.com', 'Systeme de Monitoring Admin');
            $mail->addAddress($admin_email_for_report, $_SESSION['user_email']);

            $mail->isHTML(false);
            $mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Votre Rapport Complet de Monitoring du Système');
            $mail->Body    = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Bonjour Administrateur,' . "\n\n" .
                               'Veuillez trouver ci-joint le rapport complet de monitoring du système, généré le ' . date('d/m/Y H:i:s') . '.' . "\n\n" .
                               'Cordialement,' . "\n" . 'Votre équipe de Monitoring');

            $mail->addStringAttachment($pdf_content, $pdf_filename, 'base64', 'application/pdf');

            $mail->send();
            header("Location: admin.php?success=pdf_sent#monitoring-data");
            exit();
        } catch (Exception $mail_exception) {
            error_log("Erreur lors de l'envoi de l'e-mail (Admin): {$mail_exception->getMessage()}");
            $_SESSION['debug_error_message'] = "Erreur d'envoi d'e-mail (Admin): {$mail_exception->getMessage()}";
            header("Location: admin.php?error=pdf_send_failed#monitoring-data");
            exit();
        }

    } catch (Exception $e) {
        error_log("Erreur lors de la génération du PDF (Admin FPDF): " . $e->getMessage());
        $_SESSION['debug_error_message'] = "Erreur de génération PDF (Admin): " . $e->getMessage();
        header("Location: admin.php?error=pdf_generation_failed#monitoring-data");
        exit();
    }
}


// Récupérer les données pour l'affichage et les graphiques
$users = [];
try {
    $stmt = $pdo->query("SELECT id, email, role FROM user");
    $users = $stmt->fetchAll();
} catch (PDOException $e) { error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage()); }

$devices = [];
try {
    $stmt = $pdo->query("SELECT a.id, a.name, a.ip_address, a.type, a.status, a.location, a.last_check, a.created_at, u.email AS created_by_email
                         FROM appareils a LEFT JOIN user u ON a.created_by = u.id ORDER BY a.id DESC");
    $devices = $stmt->fetchAll();
} catch (PDOException $e) { error_log("Erreur lors de la récupération des appareils: " . $e->getMessage()); }

$alerts = [];
try {
    $stmt = $pdo->query("SELECT a.id, a.device_id, a.alert_type, a.message, a.is_active, a.created_at, d.name AS device_name
                         FROM alerts a LEFT JOIN appareils d ON a.device_id = d.id ORDER BY a.created_at DESC");
    $alerts = $stmt->fetchAll();
} catch (PDOException $e) { error_log("Erreur lors de la récupération des alertes: " . $e->getMessage()); }

$connectivity_test_results = [];
try {
    $stmt = $pdo->query("SELECT tr.id, tr.test_id, t.device_id, a.name AS device_name, a.ip_address,
                                 tr.result_value, tr.threshold_met, tr.notes, tr.run_at, u.email AS run_by_email, u.role AS run_by_role, tr.ping_latency_ms
                         FROM test_results tr
                         JOIN tests t ON tr.test_id = t.id
                         JOIN appareils a ON t.device_id = a.id
                         LEFT JOIN user u ON tr.run_by = u.id
                         WHERE t.test_type = 'connectivité'
                         ORDER BY tr.run_at DESC
                         LIMIT 50"); // Limite pour l'affichage, pas pour le PDF
    $connectivity_test_results = $stmt->fetchAll();
} catch (PDOException $e) { error_log("Erreur récupération résultats de test de connectivité: " . $e->getMessage()); }


// Préparation des données pour les graphiques (Monitoring global)
$device_status_counts = [ 'actif' => 0, 'inactif' => 0 ];
$device_type_counts = [ 'serveur' => 0, 'capteur' => 0, 'network' => 0, 'autre' => 0 ];

foreach ($devices as $device) {
    if (isset($device_status_counts[$device['status']])) { $device_status_counts[$device['status']]++; }
    else { $device_status_counts[$device['status']] = 1; }

    if (isset($device_type_counts[$device['type']])) { $device_type_counts[$device['type']]++; }
    else { $device_type_counts['autre']++; }
}

$chart_device_status_labels = json_encode(array_keys($device_status_counts));
$chart_device_status_data = json_encode(array_values($device_status_counts));
$chart_device_type_labels = json_encode(array_keys($device_type_counts));
$chart_device_type_data = json_encode(array_values($device_type_counts));

// Préparation des données pour le graphique de latence
$latency_chart_data = [];
$latency_chart_labels = [];
try {
    $stmt_latency_data = $pdo->query("SELECT tr.ping_latency_ms, tr.run_at, a.name AS device_name
                                      FROM test_results tr
                                      JOIN tests t ON tr.test_id = t.id
                                      JOIN appareils a ON t.device_id = a.id
                                      WHERE t.test_type = 'connectivité' AND tr.ping_latency_ms IS NOT NULL
                                      ORDER BY tr.run_at ASC
                                      LIMIT 20");
    $raw_latency_data = $stmt_latency_data->fetchAll();

    foreach ($raw_latency_data as $data) {
        $latency_chart_data[] = $data['ping_latency_ms'];
        $latency_chart_labels[] = date('H:i:s', strtotime($data['run_at'])) . ' (' . ($data['device_name'] ?? 'N/A') . ')';
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données de latence pour le graphique: " . $e->getMessage());
    $latency_chart_data = [];
    $latency_chart_labels = [];
}
$chart_latency_data = json_encode($latency_chart_data);
$chart_latency_labels = json_encode($latency_chart_labels);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Administrateur</title>
    <link rel="stylesheet" href="admin">
    <!-- Inclure Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Variables CSS pour faciliter les changements de thème */
        :root {
            --primary-color: #4A90E2; /* Bleu vibrant */
            --secondary-color: #50B3A2; /* Vert d'eau */
            --dark-background: #2C3E50; /* Bleu-gris foncé */
            --light-background: #F4F7F6; /* Gris clair */
            --text-color: #333;
            --light-text-color: #ECF0F1; /* Gris très clair */
            --shadow-light: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-medium: 0 6px 18px rgba(0,0,0,0.12);
            --border-color: #E0E0E0; /* Gris clair pour les bordures */
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --inactive-color: #6c757d;
        }

        /* Styles généraux du corps */
        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            background-color: var(--light-background);
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Conteneur principal du tableau de bord */
        .admin-dashboard {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* En-tête du tableau de bord */
        .dashboard-header {
            background: linear-gradient(to right, var(--dark-background), #3C576F);
            color: var(--light-text-color);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-medium);
            z-index: 10;
        }
        .dashboard-header h1 {
            margin: 0;
            font-size: 1.8em;
            letter-spacing: 0.5px;
        }
        .header-nav .logout-btn {
            background-color: var(--error-color);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .header-nav .logout-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        /* Contenu principal (sidebar + main-content) */
        .dashboard-content {
            display: flex;
            flex-grow: 1;
            padding-top: 0;
        }

        /* Barre latérale de navigation */
        .sidebar {
            width: 250px;
            background-color: #34495e;
            padding: 20px 0;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            flex-shrink: 0;
            transition: width 0.3s ease;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-nav a {
            display: block;
            color: var(--light-text-color);
            padding: 15px 25px;
            text-decoration: none;
            border-left: 5px solid transparent;
            transition: background-color 0.3s ease, color 0.3s ease, border-left-color 0.3s ease;
            font-size: 1.05em;
        }
        .sidebar-nav a:hover {
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--secondary-color);
        }
        .sidebar-nav a.active {
            background-color: var(--primary-color);
            border-left-color: var(--secondary-color);
            font-weight: bold;
            color: white;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.2);
        }

        /* Contenu principal des sections */
        .main-content {
            flex-grow: 1;
            padding: 25px;
            background-color: var(--light-background);
        }
        .main-content section {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            margin-bottom: 25px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .main-content section:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }
        .main-content section h2 {
            color: var(--dark-background);
            margin-top: 0;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
            font-size: 2em;
            font-weight: 600;
        }

        /* Section Actions (boutons au-dessus des tables/graphiques) */
        .section-actions {
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .section-actions button,
        .section-actions a.button { /* Style pour les liens qui ressemblent à des boutons */
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none; /* Pour les liens */
            display: inline-block; /* Pour les liens */
            text-align: center; /* Pour les liens */
        }
        .section-actions .add-btn,
        .section-actions .generate-pdf-btn {
            background: linear-gradient(to right, var(--primary-color), #6BB9F0);
            color: white;
        }
        .section-actions .add-btn:hover,
        .section-actions .generate-pdf-btn:hover {
            background: linear-gradient(to right, #6BB9F0, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        .section-actions .run-test-btn {
            background: linear-gradient(to right, var(--secondary-color), #6CE8D0);
            color: white;
        }
        .section-actions .run-test-btn:hover {
            background: linear-gradient(to right, #6CE8D0, var(--secondary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        .section-actions p {
            margin: 0;
            font-size: 0.95em;
            color: #666;
        }

        /* Messages de succès/erreur */
        .message-container {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            border: 1px solid;
            transition: all 0.3s ease;
        }
        .message-success {
            background-color: #e6ffed;
            color: var(--success-color);
            border-color: #a3e9c5;
        }
        .message-error {
            background-color: #ffe6e6;
            color: var(--error-color);
            border-color: #ffb3b3;
        }
        .detailed-error-message {
            background-color: #fff3e0;
            color: #ff9800;
            border: 1px solid #ffcc80;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            font-size: 0.85em;
            text-align: left;
            word-wrap: break-word;
            font-weight: normal;
        }

        /* Styles des formulaires */
        .form-section {
            background-color: #fcfcfc;
            padding: 25px;
            border-radius: 10px;
            box-shadow: inset 0 0 8px rgba(0,0,0,0.05);
            margin-top: 20px;
            border: 1px solid var(--border-color);
        }
        .form-section h3 {
            color: var(--dark-background);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: calc(100% - 24px); /* Full width minus padding */
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            outline: none;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        .form-group .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: auto;
            transform: scale(1.2);
        }
        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        .form-buttons button {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-buttons .submit-btn {
            background-color: var(--primary-color);
            color: white;
        }
        .form-buttons .submit-btn:hover {
            background-color: #3B7ADF;
            transform: translateY(-2px);
        }
        .form-buttons .cancel-btn {
            background-color: #F0F4F8;
            color: #555;
            border: 1px solid var(--border-color);
        }
        .form-buttons .cancel-btn:hover {
            background-color: #E0E7ED;
            transform: translateY(-2px);
        }


        /* Styles des tableaux de données */
        .data-table {
            background-color: white;
            border-radius: 10px;
            overflow-x: auto; /* Permet le défilement horizontal pour les petits écrans */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .data-table h3 {
            background-color: #f8f8f8;
            padding: 15px 20px;
            margin: 0;
            font-size: 1.3em;
            color: #555;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
            min-width: 700px; /* Assure que le tableau ne devient pas trop petit */
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table th {
            background-color: #F0F4F8;
            color: var(--dark-background);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .data-table tbody tr:hover {
            background-color: #eef7ff;
        }
        .data-table td {
            color: #444;
            vertical-align: middle; /* Alignement vertical pour les cellules avec des boutons */
        }

        /* Actions dans les tableaux (boutons Modifier/Supprimer/Résoudre) */
        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap; /* Permet aux boutons de passer à la ligne si l'espace est limité */
        }
        .table-actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .table-actions .edit-btn {
            background-color: var(--primary-color);
            color: white;
        }
        .table-actions .edit-btn:hover {
            background-color: #3B7ADF;
            transform: translateY(-1px);
        }
        .table-actions .delete-btn {
            background-color: var(--error-color);
            color: white;
        }
        .table-actions .delete-btn:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }
        .table-actions .resolve-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        .table-actions .resolve-btn:hover {
            background-color: #429487;
            transform: translateY(-1px);
        }

        /* Statuts spécifiques dans les tableaux */
        .status-actif { color: var(--success-color); font-weight: 600; }
        .status-inactif { color: var(--error-color); font-weight: 600; }
        .alert-critique { color: var(--error-color); font-weight: bold; }
        .alert-avertissement { color: var(--warning-color); font-weight: bold; }
        .alert-information { color: var(--info-color); font-weight: bold; }
        .alert-active { color: var(--error-color); font-weight: bold; }
        .alert-resolved { text-decoration: line-through; color: var(--inactive-color); }


        /* Styles pour les conteneurs de graphiques */
        .chart-container {
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            justify-content: center;
            align-items: start;
            margin-bottom: 30px;
        }
        .charts-grid .chart-container:last-child {
            grid-column: span 2;
            max-width: none;
            height: 420px;
            padding: 25px;
        }
        .chart-container canvas {
            max-width: 100%;
            height: auto;
        }


        /* Classe pour cacher les sections */
        .hidden-section {
            display: none;
        }
        /* Message de chargement pour le test de connectivité */
        .loading-message {
            background-color: #e7f3fe;
            color: #0056b3;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            display: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Media Queries pour la responsivité */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                padding: 15px;
            }
            .dashboard-header h1 {
                margin-bottom: 10px;
                font-size: 1.5em;
            }
            .dashboard-content {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                padding: 10px 0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding: 0 10px;
            }
            .sidebar-nav a {
                flex-shrink: 0;
                padding: 10px 15px;
                text-align: center;
                border-left: none;
                border-bottom: 3px solid transparent;
                margin-bottom: 0;
            }
            .sidebar-nav a.active {
                border-left: none;
                border-bottom-color: var(--secondary-color);
            }
            .main-content {
                padding: 15px;
            }
            .main-content section {
                padding: 20px;
                border-radius: 8px;
            }
            .main-content section h2 {
                font-size: 1.6em;
                margin-bottom: 15px;
            }
            .data-table table {
                min-width: unset; /* Remove min-width on small screens */
            }
            .data-table th, .data-table td {
                padding: 10px;
                font-size: 0.85em;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .charts-grid .chart-container:last-child {
                grid-column: span 1;
                height: 300px;
            }
            .section-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .section-actions button, .section-actions a.button {
                width: 100%;
                margin-right: 0;
            }
            .form-buttons {
                flex-direction: column;
            }
            .form-buttons button {
                width: 100%;
            }
            /* Masquer certaines colonnes pour les petits écrans */
            .user-table th:nth-child(1), .user-table td:nth-child(1), /* ID */
            .device-table th:nth-child(1), .device-table td:nth-child(1), /* ID */
            .device-table th:nth-child(6), .device-table td:nth-child(6), /* Localisation */
            .alert-table th:nth-child(1), .alert-table td:nth-child(1) /* ID */ {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .dashboard-header h1 {
                font-size: 1.2em;
            }
            .header-nav .logout-btn {
                padding: 8px 15px;
                font-size: 0.9em;
            }
            .main-content section {
                padding: 15px;
            }
            .data-table th, .data-table td {
                padding: 8px;
            }
            /* Masquer encore plus de colonnes si nécessaire */
            .device-table th:nth-child(3), .device-table td:nth-child(3) /* IP Address */ {
                display: none;
            }
            .alert-table th:nth-child(2), .alert-table td:nth-child(2) /* Device Name */ {
                display: none;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            const sections = document.querySelectorAll('.main-content section');
            const userFormSection = document.getElementById('userFormSection');
            const deviceFormSection = document.getElementById('deviceFormSection');
            const alertFormSection = document.getElementById('alertFormSection');
            const loadingMessage = document.getElementById('connectivityTestLoadingMessage');


            let deviceStatusChart;
            let deviceTypeChart;
            let pingLatencyChart;

            function showSection(id) {
                sections.forEach(section => {
                    section.classList.add('hidden-section');
                });
                const targetSection = document.getElementById(id);
                if (targetSection) {
                    targetSection.classList.remove('hidden-section');
                }

                navLinks.forEach(link => {
                    link.classList.remove('active');
                });
                const activeLink = document.querySelector(`.sidebar-nav a[href="#${id}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }

                // Cacher tous les formulaires lors du changement de section
                if (userFormSection) userFormSection.classList.add('hidden');
                if (deviceFormSection) deviceFormSection.classList.add('hidden');
                if (alertFormSection) alertFormSection.classList.add('hidden');

                // Si on va sur la section monitoring, dessiner les graphiques
                if (id === 'monitoring-data') {
                    drawCharts();
                }
                // Cacher le message de chargement quand on change de section
                if (loadingMessage) {
                    loadingMessage.style.display = 'none';
                }
            }

            // Gestion des clics sur les liens de navigation
            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const sectionId = link.getAttribute('href').substring(1);
                    showSection(sectionId);
                });
            });

            // Afficher la section par défaut au chargement de la page (ou celle de l'URL)
            const initialHash = window.location.hash.substring(1);
            if (initialHash && document.getElementById(initialHash)) {
                showSection(initialHash);
                if (initialHash === 'monitoring-data') {
                    drawCharts();
                }
            } else {
                showSection('user-management'); // Section par défaut pour l'admin
            }


            // --- User Management JS ---
            const userManagementSection = document.getElementById('user-management');
            if (userManagementSection) {
                const addUserBtn = userManagementSection.querySelector('#addUserBtn');
                const userForm = userManagementSection.querySelector('#userForm');
                const userCancelBtn = userManagementSection.querySelector('#userCancelBtn');

                addUserBtn.addEventListener('click', () => {
                    userFormSection.classList.remove('hidden');
                    userForm.reset(); // Réinitialise le formulaire
                    userForm.action.value = 'add_user';
                    userForm.user_id.value = ''; // Assurez-vous que l'ID est vide pour l'ajout
                    userFormSection.querySelector('h3').textContent = 'Ajouter un nouvel utilisateur';
                    // Effacer le mot de passe s'il était pré-rempli pour une modification
                    userForm.password.value = '';
                    userForm.password.placeholder = 'Laissez vide pour ne pas changer';
                });

                userCancelBtn.addEventListener('click', () => {
                    userFormSection.classList.add('hidden');
                });

                userManagementSection.querySelectorAll('.edit-user-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const userId = e.target.dataset.id;
                        const userEmail = e.target.dataset.email;
                        const userRole = e.target.dataset.role;

                        userFormSection.classList.remove('hidden');
                        userForm.action.value = 'update_user';
                        userForm.user_id.value = userId;
                        userForm.email.value = userEmail;
                        userForm.role.value = userRole;
                        userFormSection.querySelector('h3').textContent = 'Modifier l\'utilisateur';
                        // Laissez le champ mot de passe vide lors de l'édition et donnez une indication
                        userForm.password.value = '';
                        userForm.password.placeholder = 'Laissez vide pour ne pas changer';
                    });
                });

                userManagementSection.querySelectorAll('.delete-user-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                            const userId = e.target.dataset.id;
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'admin.php';
                            const actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'delete_user';
                            form.appendChild(actionInput);
                            const idInput = document.createElement('input');
                            idInput.type = 'hidden';
                            idInput.name = 'user_id';
                            idInput.value = userId;
                            form.appendChild(idInput);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }

            // --- Device Management JS ---
            const deviceManagementSection = document.getElementById('device-management');
            if (deviceManagementSection) {
                const addDeviceBtn = deviceManagementSection.querySelector('#addDeviceBtn');
                const deviceForm = deviceManagementSection.querySelector('#deviceForm');
                const deviceCancelBtn = deviceManagementSection.querySelector('#deviceCancelBtn');

                addDeviceBtn.addEventListener('click', () => {
                    deviceFormSection.classList.remove('hidden');
                    deviceForm.reset();
                    deviceForm.action.value = 'add_device';
                    deviceForm.device_id.value = '';
                    deviceFormSection.querySelector('h3').textContent = 'Ajouter un nouvel appareil';
                });

                deviceCancelBtn.addEventListener('click', () => {
                    deviceFormSection.classList.add('hidden');
                });

                deviceManagementSection.querySelectorAll('.edit-device-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const deviceId = e.target.dataset.id;
                        const deviceName = e.target.dataset.name;
                        const deviceIp = e.target.dataset.ip;
                        const deviceType = e.target.dataset.type;
                        const deviceStatus = e.target.dataset.status;
                        const deviceLocation = e.target.dataset.location;

                        deviceFormSection.classList.remove('hidden');
                        deviceForm.action.value = 'update_device';
                        deviceForm.device_id.value = deviceId;
                        deviceForm.name.value = deviceName;
                        deviceForm.ip_address.value = deviceIp;
                        deviceForm.type.value = deviceType;
                        deviceForm.status.value = deviceStatus;
                        deviceForm.location.value = deviceLocation;
                        deviceFormSection.querySelector('h3').textContent = 'Modifier l\'appareil';
                    });
                });

                deviceManagementSection.querySelectorAll('.delete-device-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        if (confirm('Êtes-vous sûr de vouloir supprimer cet appareil et toutes les données associées (tests, alertes) ?')) {
                            const deviceId = e.target.dataset.id;
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'admin.php';
                            const actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'delete_device';
                            form.appendChild(actionInput);
                            const idInput = document.createElement('input');
                            idInput.type = 'hidden';
                            idInput.name = 'device_id';
                            idInput.value = deviceId;
                            form.appendChild(idInput);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }

            // --- Alert Management JS ---
            const alertManagementSection = document.getElementById('alert-management');
            if (alertManagementSection) {
                const addAlertBtn = alertManagementSection.querySelector('#addAlertBtn');
                const alertForm = alertManagementSection.querySelector('#alertForm');
                const alertCancelBtn = alertManagementSection.querySelector('#alertCancelBtn');

                addAlertBtn.addEventListener('click', () => {
                    alertFormSection.classList.remove('hidden');
                    alertForm.reset();
                    alertForm.action.value = 'add_alert';
                    alertForm.alert_id.value = '';
                    alertForm.is_active.checked = true; // Par défaut, une nouvelle alerte est active
                    alertFormSection.querySelector('h3').textContent = 'Ajouter une nouvelle alerte';
                });

                alertCancelBtn.addEventListener('click', () => {
                    alertFormSection.classList.add('hidden');
                });

                alertManagementSection.querySelectorAll('.edit-alert-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const alertId = e.target.dataset.id;
                        const deviceId = e.target.dataset.device;
                        const alertType = e.target.dataset.type;
                        const message = e.target.dataset.message;
                        const isActive = e.target.dataset.active == '1';

                        alertFormSection.classList.remove('hidden');
                        alertForm.action.value = 'update_alert';
                        alertForm.alert_id.value = alertId;
                        alertForm.device_id.value = deviceId;
                        alertForm.alert_type.value = alertType;
                        alertForm.message.value = message;
                        alertForm.is_active.checked = isActive;
                        alertFormSection.querySelector('h3').textContent = 'Modifier l\'alerte';
                    });
                });

                alertManagementSection.querySelectorAll('.delete-alert-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        if (confirm('Êtes-vous sûr de vouloir supprimer cette alerte ?')) {
                            const alertId = e.target.dataset.id;
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'admin.php';
                            const actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'delete_alert';
                            form.appendChild(actionInput);
                            const idInput = document.createElement('input');
                            idInput.type = 'hidden';
                            idInput.name = 'alert_id';
                            idInput.value = alertId;
                            form.appendChild(idInput);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });

                alertManagementSection.querySelectorAll('.resolve-alert-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        if (confirm('Êtes-vous sûr de vouloir marquer cette alerte comme résolue ?')) {
                            const alertId = e.target.dataset.id;
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'admin.php';
                            const actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'resolve_alert';
                            form.appendChild(actionInput);
                            const idInput = document.createElement('input');
                            idInput.type = 'hidden';
                            idInput.name = 'alert_id';
                            idInput.value = alertId;
                            form.appendChild(idInput);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }

            // --- Connectivity Test Section JS (Global) ---
            const connectivityTestSection = document.getElementById('connectivity-test');
            if (connectivityTestSection) {
                const runConnectivityTestBtn = connectivityTestSection.querySelector('#runConnectivityTestBtn');
                if (runConnectivityTestBtn) {
                    runConnectivityTestBtn.addEventListener('click', () => {
                        if (confirm('Lancer le test de connectivité pour tous les appareils ? Cela peut prendre un certain temps.')) {
                            loadingMessage.style.display = 'block';

                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'admin.php';
                            const actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            actionInput.value = 'run_connectivity_test';
                            form.appendChild(actionInput);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                }
            }


            // --- Functions to draw charts (Global Monitoring Data) ---
            function drawCharts() {
                const ctxStatus = document.getElementById('deviceStatusChart');
                const ctxType = document.getElementById('deviceTypeChart');
                const ctxLatency = document.getElementById('pingLatencyChart');

                if (ctxStatus) {
                    const chartStatusContext = ctxStatus.getContext('2d');
                    if (deviceStatusChart) { deviceStatusChart.destroy(); }
                    deviceStatusChart = new Chart(chartStatusContext, {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo $chart_device_status_labels; ?>,
                            datasets: [{
                                data: <?php echo $chart_device_status_data; ?>,
                                backgroundColor: ['#50B3A2', '#DC3545'],
                                borderColor: ['#429487', '#B82C3A'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 14
                                        }
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Répartition des appareils par statut',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    },
                                    color: '#333'
                                }
                            }
                        }
                    });
                }

                if (ctxType) {
                    const chartTypeContext = ctxType.getContext('2d');
                    if (deviceTypeChart) { deviceTypeChart.destroy(); }
                    deviceTypeChart = new Chart(chartTypeContext, {
                        type: 'bar',
                        data: {
                            labels: <?php echo $chart_device_type_labels; ?>,
                            datasets: [{
                                label: 'Nombre d\'appareils',
                                data: <?php echo $chart_device_type_data; ?>,
                                backgroundColor: ['#4A90E2', '#FFC107', '#A0D9EF', '#8A8A8A'],
                                borderColor: ['#3C7CC9', '#CC9A00', '#85BDD3', '#6E6E6E'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Répartition des appareils par type',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    },
                                    color: '#333'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    grid: {
                                        color: '#eee'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }

                if (ctxLatency) {
                    const chartLatencyContext = ctxLatency.getContext('2d');
                    if (pingLatencyChart) { pingLatencyChart.destroy(); }
                    pingLatencyChart = new Chart(chartLatencyContext, {
                        type: 'line',
                        data: {
                            labels: <?php echo $chart_latency_labels; ?>,
                            datasets: [{
                                label: 'Latence Ping (ms)',
                                data: <?php echo $chart_latency_data; ?>,
                                borderColor: 'rgba(77, 208, 225, 1)',
                                backgroundColor: 'rgba(77, 208, 225, 0.2)',
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#4A90E2',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#4A90E2',
                                pointHoverBorderColor: '#fff',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 14
                                        }
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Latence des tests de connectivité (Ping en ms)',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    },
                                    color: '#333'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Latence (ms)',
                                        font: {
                                            size: 14
                                        }
                                    },
                                    grid: {
                                        color: '#eee'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date et Appareil',
                                        font: {
                                            size: 14
                                        }
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            }
            // --- PDF Generation Button (in Monitoring Data section) ---
            const monitoringDataSection = document.getElementById('monitoring-data');
            if (monitoringDataSection) {
                const generatePdfBtn = monitoringDataSection.querySelector('#generateAdminPdfBtn'); // Correct ID
                if (generatePdfBtn) {
                    generatePdfBtn.addEventListener('click', () => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'admin.php';
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'generate_admin_pdf_report'; // Action spécifique pour l'admin
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    });
                }
            }
        });
    </script>
</head>
<body>
    <div class="admin-dashboard">
        <header class="dashboard-header">
            <h1>Tableau de bord Administrateur</h1>
            <nav class="header-nav">
                <a href="index.php?action=logout" class="logout-btn">Déconnexion</a>
            </nav>
        </header>

        <div class="dashboard-content">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="#monitoring-data">Données de monitoring globales</a>
                    <a href="#user-management" class="active">Gestion des utilisateurs</a>
                    <a href="#device-management">Gestion des appareils</a>
                    <a href="#connectivity-test">Tests de connectivité</a>
                </nav>
            </aside>

            <main class="main-content">
                <?php if (!empty($message)): ?>
                    <div class="message-container message-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                        <?php if ($message_type == 'error' && !empty($detailed_error_message)): ?>
                            <div class="detailed-error-message">
                                Détail de l'erreur: <?php echo htmlspecialchars($detailed_error_message); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Section Gestion des utilisateurs -->
                <section id="user-management">
                    <h2>Gestion des Utilisateurs</h2>
                    <div class="section-actions">
                        <button id="addUserBtn" class="add-btn">Ajouter un nouvel utilisateur</button>
                    </div>

                    <div id="userFormSection" class="form-section hidden">
                        <h3>Ajouter un nouvel utilisateur</h3>
                        <form id="userForm" method="POST" action="admin.php">
                            <input type="hidden" name="action" value="add_user">
                            <input type="hidden" name="user_id" value="">
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Mot de passe:</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="role">Rôle:</label>
                                <select id="role" name="role" required>
                                    <option value="user">Utilisateur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="submit-btn">Enregistrer</button>
                                <button type="button" class="cancel-btn" id="userCancelBtn">Annuler</button>
                            </div>
                        </form>
                    </div>

                    <div class="data-table">
                        <h3>Liste des Utilisateurs</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="4">Aucun utilisateur trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                            <td class="table-actions">
                                                <button class="edit-btn edit-user-btn" data-id="<?php echo $user['id']; ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="<?php echo htmlspecialchars($user['role']); ?>">Modifier</button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): // Empêche l'admin de se supprimer lui-même ?>
                                                <button class="delete-btn delete-user-btn" data-id="<?php echo $user['id']; ?>">Supprimer</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Section Gestion des appareils -->
                <section id="device-management" class="hidden-section">
                    <h2>Gestion des Appareils</h2>
                    <div class="section-actions">
                        <button id="addDeviceBtn" class="add-btn">Ajouter un nouvel appareil</button>
                    </div>

                    <div id="deviceFormSection" class="form-section hidden">
                        <h3>Ajouter un nouvel appareil</h3>
                        <form id="deviceForm" method="POST" action="admin.php">
                            <input type="hidden" name="action" value="add_device">
                            <input type="hidden" name="device_id" value="">
                            <div class="form-group">
                                <label for="deviceName">Nom de l'appareil:</label>
                                <input type="text" id="deviceName" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="deviceIp">Adresse IP:</label>
                                <input type="text" id="deviceIp" name="ip_address" required>
                            </div>
                            <div class="form-group">
                                <label for="deviceType">Type:</label>
                                <select id="deviceType" name="type" required>
                                    <option value="serveur">Serveur</option>
                                    <option value="capteur">Capteur</option>
                                    <option value="network">Équipement Réseau</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                        
                            <div class="form-group">
                                <label for="deviceLocation">Localisation:</label>
                                <input type="text" id="deviceLocation" name="location">
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="submit-btn">Enregistrer</button>
                                <button type="button" class="cancel-btn" id="deviceCancelBtn">Annuler</button>
                            </div>
                        </form>
                    </div>

                    <div class="data-table">
                        <h3>Appareils configurés</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom de l'appareil</th>
                                    <th>Adresse IP</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Localisation</th>
                                    <th>Dernière Vérification</th>
                                    <th>Créé par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($devices)): ?>
                                    <tr><td colspan="9">Aucun appareil trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($devices as $device): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($device['id']); ?></td>
                                            <td><?php echo htmlspecialchars($device['name']); ?></td>
                                            <td><?php echo htmlspecialchars($device['ip_address'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($device['type']); ?></td>
                                            <td class="status-<?php echo strtolower($device['status']); ?>"><?php echo htmlspecialchars($device['status']); ?></td>
                                            <td><?php echo htmlspecialchars($device['location'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($device['last_check'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($device['created_by_email'] ?? 'Inconnu'); ?></td>
                                            <td class="table-actions">
                                                <button class="edit-btn edit-device-btn"
                                                    data-id="<?php echo $device['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($device['name']); ?>"
                                                    data-ip="<?php echo htmlspecialchars($device['ip_address'] ?? ''); ?>"
                                                    data-type="<?php echo htmlspecialchars($device['type']); ?>"
                                                    data-status="<?php echo htmlspecialchars($device['status']); ?>"
                                                    data-location="<?php echo htmlspecialchars($device['location'] ?? ''); ?>">Modifier</button>
                                                <button class="delete-btn delete-device-btn" data-id="<?php echo $device['id']; ?>">Supprimer</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                

                <!-- Section Tests de connectivité -->
                <section id="connectivity-test" class="hidden-section">
                    <h2>Tests de Connectivité des Appareils</h2>
                    <div class="section-actions">
                        <button id="runConnectivityTestBtn" class="run-test-btn">Lancer le test de connectivité pour tous les appareils</button>
                    </div>

                    <div id="connectivityTestLoadingMessage" class="loading-message">
                        Veuillez patienter pendant l'exécution des tests de connectivité...
                    </div>

                    <div class="data-table" style="margin-top: 20px;">
                        <h3>Historique des résultats de connectivité</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Test</th>
                                    <th>Appareil</th>
                                    <th>Adresse IP</th>
                                    <th>Résultat</th>
                                    <th>Latence (ms)</th>
                                    <th>Notes</th>
                                    <th>Exécuté par</th>
                                    <th>Date d'exécution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($connectivity_test_results)): ?>
                                    <tr><td colspan="8">Aucun résultat de test de connectivité trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($connectivity_test_results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['id']); ?></td>
                                            <td><?php echo htmlspecialchars($result['device_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['ip_address']); ?></td>
                                            <td style="color: <?php echo $result['threshold_met'] ? '#28a745' : '#dc3545'; ?>; font-weight: bold;"><?php echo htmlspecialchars($result['result_value']); ?></td>
                                             <td><?php echo htmlspecialchars(($result['ping_latency_ms'] !== null ? round($result['ping_latency_ms'], 2) : 'N/A') . ' ms'); ?></td>
                                            <td><?php echo htmlspecialchars($result['notes'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($result['run_by_email'] ?? 'Inconnu'); ?></td>
                                            <td><?php echo htmlspecialchars($result['run_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Section Données de monitoring (générale) -->
                <section id="monitoring-data" class="hidden-section">
                    <h2>Données de monitoring globales</h2>
                    <div class="section-actions">
                        <button id="generateAdminPdfBtn" class="generate-pdf-btn">Générer et Envoyer Rapport PDF par Email (Admin)</button>
                        <p>Cliquez sur ce bouton pour générer un rapport PDF complet des appareils, alertes et tests de connectivité, puis l'envoyer à votre adresse e-mail d'administrateur (<?php echo htmlspecialchars($_SESSION['user_email'] ?? 'N/A'); ?>).</p>
                    </div>
                    <p>Visualisation agrégée de l'état de votre parc d'appareils et des tendances.</p>
                    <div class="charts-grid">
                        <div class="chart-container">
                            <canvas id="deviceStatusChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <canvas id="deviceTypeChart"></canvas>
                        </div>
                        <div class="chart-container" style="grid-column: span 2; height: 400px;">
                            <canvas id="pingLatencyChart"></canvas>
                        </div>
                    </div>

                    <div class="data-table" style="margin-top: 30px;">
                        <h3>Statistiques des appareils</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Statut</th>
                                    <th>Nombre d'appareils</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $all_possible_statuses = ['actif', 'inactif'];
                                foreach ($all_possible_statuses as $status) {
                                    echo '<tr><td>' . ucfirst($status) . '</td><td>' . ($device_status_counts[$status] ?? 0) . '</td></tr>';
                                }
                                ?>
                                <tr>
                                    <th colspan="2">Types</th>
                                </tr>
                                <?php
                                $all_possible_types = ['serveur', 'capteur', 'network', 'autre'];
                                foreach ($all_possible_types as $type) {
                                    echo '<tr><td>' . ucfirst($type) . '</td><td>' . ($device_type_counts[$type] ?? 0) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
