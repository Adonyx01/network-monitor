<?php
session_start();

// Protection de la page utilisateur - seul un utilisateur connecté peut y accéder
if (!isset($_SESSION['logged_in'])) {
    header("Location: index.php");
    exit();
}

// Inclure FPDF pour la génération de PDF
// IMPORTANT : Assurez-vous que le chemin est correct.
require('fpdf/fpdf.php');

// Inclure les classes PHPMailer
// IMPORTANT : Assurez-vous que le chemin vers PHPMailer est correct.
// Si vous avez renommé le dossier en 'phpmailer', ajustez le chemin.
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

// Fonction pour hacher le mot de passe (gardée pour consistance, même si non utilisée directement ici)
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Gestion des messages de succès/erreur
$message = '';
$message_type = '';
$detailed_error_message = ''; // Pour les messages d'erreur détaillés de PDO ou des tests

if (isset($_GET['success'])) {
    $message_type = 'success';
    switch ($_GET['success']) {
        case 'connectivity_test_run': $message = 'Test de connectivité exécuté avec succès !'; break;
        case 'pdf_generated': $message = 'Rapport PDF généré avec succès !'; break;
        case 'pdf_sent': $message = 'Rapport PDF généré et envoyé à votre adresse e-mail !'; break; // Nouveau message
    }
} elseif (isset($_GET['error'])) {
    $message_type = 'error';
    if (isset($_SESSION['debug_error_message'])) {
        $detailed_error_message = $_SESSION['debug_error_message'];
        unset($_SESSION['debug_error_message']); // Supprimer après affichage
    }
    switch ($_GET['error']) {
        case 'connectivity_test_failed': $message = 'Erreur lors de l\'exécution du test de connectivité.'; break;
        case 'invalid_data': $message = 'Données invalides fournies.'; break;
        case 'pdf_generation_failed': $message = 'Erreur lors de la génération du rapport PDF.'; break;
        case 'pdf_send_failed': $message = 'Erreur lors de l\'envoi du rapport PDF par e-mail.'; break; // Nouveau message
    }
}

// --- Logique de gestion des appareils (lecture seule pour l'utilisateur) ---
$devices = [];
try {
    // Récupérer tous les appareils (pour l'affichage général)
    $stmt = $pdo->query("SELECT a.id, a.name, a.ip_address, a.type, a.status, a.location, a.last_check, a.created_at, u.email AS created_by_email
                         FROM appareils a LEFT JOIN user u ON a.created_by = u.id ORDER BY a.id DESC");
    $devices = $stmt->fetchAll();
} catch (PDOException $e) { error_log("Erreur lors de la récupération des appareils: " . $e->getMessage()); }

// --- Logique de gestion des tests de connectivité des appareils (globaux) ---
$current_user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'run_connectivity_test') {
    if ($current_user_id === null) {
        error_log("Erreur: User ID non trouvé en session pour l'exécution du test de connectivité. Veuillez vérifier index.php et vous reconnecter.");
        $_SESSION['debug_error_message'] = "Erreur: ID utilisateur non trouvé en session pour exécuter le test. Veuillez vous déconnecter et vous reconnecter.";
        header("Location: user.php?error=connectivity_test_failed#connectivity-test");
        exit();
    }

    try {
        $stmt_devices = $pdo->query("SELECT id, name, ip_address FROM appareils");
        $all_devices = $stmt_devices->fetchAll();

        if (empty($all_devices)) {
            $_SESSION['debug_error_message'] = "Aucun appareil configuré pour le test de connectivité. Veuillez ajouter des appareils (en tant qu'administrateur).";
            header("Location: user.php?error=connectivity_test_failed#connectivity-test");
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
            $_SESSION['debug_error_message'] = "Erreur: La commande 'ping' est introuvable ou non exécutable sur le serveur PHP. Vérifiez le PATH de l'environnement du serveur et les permissions. Chemins vérifiés: " . implode(", ", $possible_ping_paths);
            error_log("CRITICAL ERROR: ping command not found or not executable. PHP_OS: " . PHP_OS . " Possible paths checked: " . implode(", ", $possible_ping_paths));
            header("Location: user.php?error=connectivity_test_failed#connectivity-test");
            exit();
        }

        foreach ($all_devices as $device) {
            $device_id = $device['id'];
            $ip_address = escapeshellarg($device['ip_address']);

            $is_connected = false;
            $result_message = 'Déconnecté';
            $threshold_met = 0;
            $ping_latency = null; // Nouvelle variable pour la latence

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
                    // Vérifications plus robustes pour succès du ping
                    if (strpos($normalized_line, 'received = 1') !== false ||
                        strpos($normalized_line, '0% packet loss') !== false ||
                        strpos($normalized_line, 'reçu = 1') !== false ||
                        strpos($normalized_line, 'reçus = 1') !== false ||
                        strpos($normalized_line, 'recus = 1') !== false ||
                        strpos(str_replace('?', '', $normalized_line), 'recus = 1') !== false ||
                        strpos($normalized_line, 'perte 0%') !== false) {
                        $is_connected = true;
                    }

                    // Tentative d'extraction de la latence (moyenne si Windows, ou single RTT)
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

            // Enregistrer l'exécution du test dans la table 'tests'
            $stmt_insert_test = $pdo->prepare(
                "INSERT INTO tests (device_id, scheduled_at, status, last_run_at, created_by, test_type)
                 VALUES (:device_id, CURRENT_TIMESTAMP(), 'terminé', CURRENT_TIMESTAMP(), :created_by, 'connectivité')"
            );
            $stmt_insert_test->bindParam(':device_id', $device_id);
            $stmt_insert_test->bindParam(':created_by', $current_user_id);
            $stmt_insert_test->execute();
            $test_id = $pdo->lastInsertId();

            // Enregistrer le résultat du test dans la table 'test_results'
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
            $stmt_insert_result->bindParam(':ping_latency_ms', $ping_latency); // Stocker la latence
            $stmt_insert_result->execute();

            // Mettre à jour le statut de l'appareil
            $new_status = $is_connected ? 'actif' : 'inactif';
            $stmt_update_device = $pdo->prepare("UPDATE appareils SET status = :status, last_check = CURRENT_TIMESTAMP() WHERE id = :id");
            $stmt_update_device->bindParam(':status', $new_status);
            $stmt_update_device->bindParam(':id', $device_id);
            $stmt_update_device->execute();
        }
        header("Location: user.php?success=connectivity_test_run#connectivity-test"); exit();
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de l'exécution du test de connectivité: " . $e->getMessage());
        $_SESSION['debug_error_message'] = "Erreur PDO: " . $e->getMessage();
        header("Location: user.php?error=connectivity_test_failed#connectivity-test");
        exit();
    } catch (Exception $e) {
        error_log("Erreur générale lors de l'exécution du test de connectivité: " . $e->getMessage());
        $_SESSION['debug_error_message'] = "Erreur générale: " . $e->getMessage();
        header("Location: user.php?error=connectivity_test_failed#connectivity-test");
        exit();
    }
}

// --- Logique de génération de rapport PDF (Avec FPDF et envoi par PHPMailer) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'generate_pdf_report') {
    $user_email_for_report = $_SESSION['user_email'] ?? null; // Récupérer l'email de l'utilisateur connecté

    if ($user_email_for_report === null) {
        $_SESSION['debug_error_message'] = "Impossible d'envoyer le rapport : adresse e-mail de l'utilisateur non trouvée.";
        header("Location: user.php?error=pdf_send_failed#monitoring-data");
        exit();
    }

    try {
        // CHANGEMENT ICI : DÉFINITION DU FORMAT A3 POUR LE PDF
        $pdf = new FPDF('P', 'mm', 'A3'); // 'P' pour Portrait, 'mm' pour Millimètres, 'A3' pour le format de page
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Rapport de Monitoring des Appareils'), 0, 1, 'C');
        $pdf->Ln(10); // Saut de ligne

        // Section Statut des Appareils
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Statut des Appareils'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);

        // En-têtes du tableau Appareils
        $pdf->SetFillColor(230, 230, 230); // Gris clair pour l'en-tête
        $pdf->SetDrawColor(180, 180, 180); // Bordure plus foncée
        $header_appareils = ['ID', 'Nom', 'IP', 'Statut', 'Dernière Vérification', 'Localisation', 'Créé par'];
        // Les largeurs doivent être ajustées pour le format A3
        $width_appareils = [20, 50, 40, 30, 50, 40, 40]; // Largeurs des colonnes ajustées pour A3

        for ($i = 0; $i < count($header_appareils); $i++) {
            $pdf->Cell($width_appareils[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header_appareils[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln(); // Nouvelle ligne après l'en-tête

        // Données du tableau Appareils
        if (empty($devices)) {
            $pdf->Cell(array_sum($width_appareils), 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucun appareil à rapporter.'), 1, 1, 'C');
        } else {
            foreach ($devices as $device) {
                $pdf->Cell($width_appareils[0], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['id']), 1);
                $pdf->Cell($width_appareils[1], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['name']), 1);
                $pdf->Cell($width_appareils[2], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['ip_address'] ?? 'N/A'), 1);
                $pdf->Cell($width_appareils[3], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['status']), 1);
                $pdf->Cell($width_appareils[4], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['last_check'] ?? 'N/A'), 1);
                $pdf->Cell($width_appareils[5], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['location'] ?? 'N/A'), 1);
                $pdf->Cell($width_appareils[6], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $device['created_by_email'] ?? 'Inconnu'), 1);
                $pdf->Ln();
            }
        }
        $pdf->Ln(10);

        // Section Alertes Récentes (Pour l'utilisateur, on affiche aussi les alertes)
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Alertes Récentes'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);

        $alerts_for_pdf = [];
        try {
            $stmt = $pdo->query("SELECT a.id, a.device_id, a.alert_type, a.message, a.is_active, a.created_at, d.name AS device_name
                                 FROM alerts a LEFT JOIN appareils d ON a.device_id = d.id ORDER BY a.created_at DESC LIMIT 50");
            $alerts_for_pdf = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des alertes pour le PDF: " . $e->getMessage());
        }

        // En-têtes du tableau Alertes
        $header_alerts = ['ID', 'Appareil', 'Type', 'Message', 'Statut', 'Date'];
        $width_alerts = [20, 50, 30, 80, 30, 30]; // Largeurs des colonnes ajustées pour A3

        for ($i = 0; $i < count($header_alerts); $i++) {
            $pdf->Cell($width_alerts[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header_alerts[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Données du tableau Alertes
        if (empty($alerts_for_pdf)) {
            $pdf->Cell(array_sum($width_alerts), 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucune alerte à rapporter.'), 1, 1, 'C');
        } else {
            foreach ($alerts_for_pdf as $alert) {
                $pdf->Cell($width_alerts[0], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['id']), 1);
                $pdf->Cell($width_alerts[1], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['device_name'] ?? 'N/A'), 1);
                $pdf->Cell($width_alerts[2], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['alert_type']), 1);
                
                $message_truncated = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['message']);
                // Ajuster la longueur de troncature pour le format A3
                if (strlen($message_truncated) > 70) { 
                    $message_truncated = substr($message_truncated, 0, 67) . '...';
                }
                $pdf->Cell($width_alerts[3], 7, $message_truncated, 1);
                
                $status = ($alert['is_active'] == 1) ? 'Active' : 'Résolue';
                $pdf->Cell($width_alerts[4], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $status), 1);
                $pdf->Cell($width_alerts[5], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $alert['created_at']), 1);
                $pdf->Ln();
            }
        }

        $pdf->Ln(10); // Saut de ligne

        // Section Historique des Tests de Connectivité
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Historique des Tests de Connectivité'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);

        $connectivity_tests_for_pdf = [];
        try {
            $stmt = $pdo->query("SELECT tr.id, tr.test_id, t.device_id, a.name AS device_name, a.ip_address,
                                 tr.result_value, tr.threshold_met, tr.notes, tr.run_at, u.email AS run_by_email, u.role AS run_by_role, tr.ping_latency_ms
                                 FROM test_results tr
                                 JOIN tests t ON tr.test_id = t.id
                                 JOIN appareils a ON t.device_id = a.id
                                 LEFT JOIN user u ON tr.run_by = u.id
                                 WHERE t.test_type = 'connectivité'
                                 ORDER BY tr.run_at DESC
                                 LIMIT 50");
            $connectivity_tests_for_pdf = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des tests de connectivité pour le PDF: " . $e->getMessage());
        }

        // En-têtes du tableau Tests de Connectivité
        $header_connectivity_tests = ['ID Test', 'Appareil', 'IP', 'Résultat', 'Latence (ms)', 'Exécuté par', 'Date d\'exécution'];
        // Ajustement des largeurs ici pour "Exécuté par" et "Date d'exécution" pour A3
        $width_connectivity_tests = [25, 45, 35, 25, 30, 60, 70];

        for ($i = 0; $i < count($header_connectivity_tests); $i++) {
            $pdf->Cell($width_connectivity_tests[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header_connectivity_tests[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Données du tableau Tests de Connectivité
        if (empty($connectivity_tests_for_pdf)) {
            $pdf->Cell(array_sum($width_connectivity_tests), 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucun test de connectivité à rapporter.'), 1, 1, 'C');
        } else {
            foreach ($connectivity_tests_for_pdf as $test_result) {
                $executed_by_text = '';
                if ($test_result['run_by_role'] == 'admin') {
                    $executed_by_text = 'Admin';
                } else {
                    // Truncate email if too long to fit in cell for display, but full email in data
                    $email_display = $test_result['run_by_email'] ?? 'Inconnu';
                    // Ajuster la longueur de troncature pour le format A3
                    if (strlen($email_display) > 25) { 
                        $email_display = substr($email_display, 0, 22) . '...';
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

        // Capture le PDF en tant que chaîne de caractères
        $pdf_content = $pdf->Output('S', 'rapport_monitoring_utilisateur_' . date('Ymd_His') . '.pdf');
        $pdf_filename = 'rapport_monitoring_utilisateur_' . date('Ymd_His') . '.pdf';

        // Initialisation de PHPMailer
        $mail = new PHPMailer(true); // Passer 'true' active les exceptions

        try {
            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // REMPLACEZ PAR VOTRE HÔTE SMTP (ex: smtp.gmail.com, mail.votre_domaine.com)
            $mail->SMTPAuth   = true;
            $mail->Username   = 'votre_email@gmail.com';        // REMPLACEZ PAR VOTRE ADRESSE E-MAIL D'ENVOI
            $mail->Password   = 'votre_mot_de_passe_application'; // REMPLACEZ PAR LE MOT DE PASSE D'APPLICATION GMAIL
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Utilisez ENCRYPTION_SMTPS pour le port 465, ENCRYPTION_STARTTLS pour 587
            $mail->Port       = 587; // Port SMTP (ex: 587 pour TLS, 465 pour SSL)

            // Destinataires
            $mail->setFrom('votre_email@example.com', 'Systeme de Monitoring'); // De qui vient l'email
            $mail->addAddress($user_email_for_report, $_SESSION['user_email']);     // Envoyer à l'utilisateur qui a généré le rapport

            // Contenu de l'email
            $mail->isHTML(false); // Définir le format de l'e-mail en texte brut (pas HTML)
            $mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Votre Rapport de Monitoring des Appareils');
            $mail->Body    = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Bonjour,' . "\n\n" .
                               'Veuillez trouver ci-joint votre rapport de monitoring des appareils, généré le ' . date('d/m/Y H:i:s') . '.' . "\n\n" .
                               'Cordialement,' . "\n" . 'Votre équipe de Monitoring');

            // Pièce jointe
            $mail->addStringAttachment($pdf_content, $pdf_filename, 'base64', 'application/pdf');

            $mail->send();
            header("Location: user.php?success=pdf_sent#monitoring-data");
            exit();
        } catch (Exception $mail_exception) {
            error_log("Erreur lors de l'envoi de l'e-mail: {$mail_exception->getMessage()}");
            $_SESSION['debug_error_message'] = "Erreur d'envoi d'e-mail: {$mail_exception->getMessage()}";
            header("Location: user.php?error=pdf_send_failed#monitoring-data");
            exit();
        }

    } catch (Exception $e) {
        error_log("Erreur lors de la génération du PDF (FPDF): " . $e->getMessage());
        $_SESSION['debug_error_message'] = "Erreur de génération PDF: " . $e->getMessage();
        header("Location: user.php?error=pdf_generation_failed#monitoring-data");
        exit();
    }
}


// Récupérer les résultats de test de connectivité (limitons aux 50 derniers pour la lisibilité)
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
                         LIMIT 50");
    $connectivity_test_results = $stmt->fetchAll();
} catch (PDOException $e) { error_log("Erreur récupération résultats de test de connectivité: " . $e->getMessage()); }

// --- Préparation des données pour les graphiques (Monitoring global) ---
$device_status_counts = [ 'actif' => 0, 'inactif' => 0 ];
$device_type_counts = [ 'serveur' => 0, 'capteur' => 0, 'network' => 0, 'autre' => 0 ];

foreach ($devices as $device) {
    if (isset($device_status_counts[$device['status']])) { $device_status_counts[$device['status']]++; }
    else { $device_status_counts[$device['status']] = 1; } // Gérer les statuts inattendus

    if (isset($device_type_counts[$device['type']])) { $device_type_counts[$device['type']]++; }
    else { $device_type_counts['autre']++; } // Si le type n'est pas dans la liste, le classer comme 'autre'
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
                                      LIMIT 20"); // Limite pour la clarté du graphique
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
    <title>Tableau de bord Utilisateur</title>
    <!-- Le fichier admin.css est utilisé comme base, mais les styles sont majoritairement dans le <style> pour la démonstration -->
    <link rel="stylesheet" href="user-style.css"> 
    <!-- Inclure Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            const sections = document.querySelectorAll('.main-content section');
            const loadingMessage = document.getElementById('connectivityTestLoadingMessage');

            let deviceStatusChart;
            let deviceTypeChart;
            let pingLatencyChart; // Nouveau graphique

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

                if (id === 'monitoring-data') {
                    drawCharts();
                }
                // Cacher le message de chargement quand on change de section
                if (loadingMessage) {
                    loadingMessage.style.display = 'none';
                }
            }

            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const sectionId = link.getAttribute('href').substring(1);
                    showSection(sectionId);
                });
            });

            const initialHash = window.location.hash.substring(1);
            if (initialHash && document.getElementById(initialHash)) {
                showSection(initialHash);
                if (initialHash === 'monitoring-data') {
                    drawCharts();
                }
            } else {
                // Set 'monitoring-data' as the default section for the user dashboard
                showSection('monitoring-data');
                drawCharts(); // Ensure charts are drawn if it's the default
            }


            // --- Device Management JS (Adjusted for read-only) ---
            const deviceManagementSection = document.getElementById('device-management');
            if (deviceManagementSection) {
                // No add/edit/delete buttons or forms for users, so no JS handlers for them
                // The form-section and related buttons for add/edit are removed from HTML.
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
                            form.action = 'user.php'; // Changed action to user.php
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
                const ctxLatency = document.getElementById('pingLatencyChart'); // Canvas pour la latence

                if (ctxStatus) {
                    const chartStatusContext = ctxStatus.getContext('2d');
                    if (deviceStatusChart) { deviceStatusChart.destroy(); }
                    deviceStatusChart = new Chart(chartStatusContext, {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo $chart_device_status_labels; ?>,
                            datasets: [{
                                data: <?php echo $chart_device_status_data; ?>,
                                backgroundColor: ['#50B3A2', '#DC3545'], /* Vert d'eau, Rouge */
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
                                backgroundColor: ['#4A90E2', '#FFC107', '#A0D9EF', '#8A8A8A'], /* Bleu, Jaune, Bleu clair, Gris */
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

                // Nouveau: Graphique de latence
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
                const generatePdfBtn = monitoringDataSection.querySelector('#generatePdfBtn');
                if (generatePdfBtn) {
                    generatePdfBtn.addEventListener('click', () => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'user.php'; // Changed action to user.php
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'generate_pdf_report';
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
            <h1>Tableau de bord Utilisateur</h1>
            <nav class="header-nav">
                <a href="index.php?action=logout" class="logout-btn">Déconnexion</a>
            </nav>
        </header>

        <div class="dashboard-content">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="#monitoring-data">Données de monitoring</a>
                    <a href="#device-management" class="active">Liste des appareils</a>
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

                <!-- Section Gestion des appareils (lecture seule) -->
                <section id="device-management">
                    <h2>Liste des appareils</h2>
                    <!-- Le bouton "Ajouter un nouvel appareil" est supprimé pour l'utilisateur -->

                    <div class="data-table">
                        <h3>Appareils configurés</h3>
                        <table>
                            <thead>
                                <tr>
                                    
                                    <th>Nom de l'appareil</th>
                                    <th>Adresse IP</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Localisation</th>
                                    <th>Dernière Vérification</th>
                                    <th>Créé par</th>
                                    <!-- La colonne Actions est supprimée pour l'utilisateur -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($devices)): ?>
                                    <tr><td colspan="8">Aucun appareil trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($devices as $device): ?>
                                        <tr>
                                            
                                            <td><?php echo htmlspecialchars($device['name']); ?></td>
                                            <td><?php echo htmlspecialchars($device['ip_address'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($device['type']); ?></td>
                                            <td class="status-<?php echo strtolower($device['status']); ?>"><?php echo htmlspecialchars($device['status']); ?></td>
                                            <td><?php echo htmlspecialchars($device['location'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($device['last_check'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($device['created_by_email'] ?? 'Inconnu'); ?></td>
                                            <!-- Les boutons Modifier et Supprimer sont supprimés pour l'utilisateur -->
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- La section de formulaire pour ajouter/modifier des appareils est supprimée pour l'utilisateur -->
                    <!-- <div class="form-section hidden">...</div> -->
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
                        <button id="generatePdfBtn" class="generate-pdf-btn">Générer et Envoyer Rapport PDF par Email</button>
                        <p>Cliquez sur ce bouton pour générer un rapport PDF des appareils et des tests de connectivité, puis l'envoyer à votre adresse e-mail (<?php echo htmlspecialchars($_SESSION['user_email'] ?? 'N/A'); ?>).</p>
                    </div>
                    <p>Visualisation agrégée de l'état de votre parc d'appareils et des tendances.</p>
                    <div class="charts-grid">
                        <div class="chart-container">
                            <canvas id="deviceStatusChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <canvas id="deviceTypeChart"></canvas>
                        </div>
                        <!-- Conteneur pour le graphique de latence agrandi -->
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
