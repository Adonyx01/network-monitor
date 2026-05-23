<?php
session_start();

// Protection pour s'assurer que seul un admin connecté peut générer des rapports
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Inclure la bibliothèque FPDF
// Assurez-vous que le chemin est correct en fonction de l'endroit où vous avez placé le dossier fpdf
require('fpdf/fpdf.php'); // Chemin par défaut si fpdf est à la racine du projet

// Connexion à la base de données (réutiliser les mêmes informations que admin.php)
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
    // En cas d'erreur de connexion à la base de données, rediriger avec un message d'erreur
    $_SESSION['debug_error_message'] = "Erreur de connexion BDD pour rapport PDF: " . $e->getMessage();
    header("Location: admin.php?error=pdf_generation_failed");
    exit();
}

// Récupérer les données pour le rapport
$devices = [];
try {
    $stmt_devices = $pdo->query("SELECT id, name, ip_address, type, status, location, last_check FROM appareils ORDER BY name ASC");
    $devices = $stmt_devices->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur de récupération des appareils pour rapport PDF: " . $e->getMessage());
    $_SESSION['debug_error_message'] = "Erreur de récupération des appareils: " . $e->getMessage();
    header("Location: admin.php?error=pdf_generation_failed");
    exit();
}

$test_results = [];
try {
    $stmt_results = $pdo->query("SELECT tr.test_id, t.device_id, a.name AS device_name, a.ip_address,
                                 tr.result_value, tr.threshold_met, tr.notes, tr.run_at, u.email AS run_by_email
                                 FROM test_results tr
                                 JOIN tests t ON tr.test_id = t.id
                                 JOIN appareils a ON t.device_id = a.id
                                 LEFT JOIN user u ON tr.run_by = u.id
                                 WHERE t.test_type = 'connectivité'
                                 ORDER BY tr.run_at DESC
                                 LIMIT 50");
    $test_results = $stmt_results->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur de récupération des résultats de test pour rapport PDF: " . $e->getMessage());
    $_SESSION['debug_error_message'] = "Erreur de récupération des résultats de test: " . $e->getMessage();
    header("Location: admin.php?error=pdf_generation_failed");
    exit();
}

// Création du PDF
class PDF extends FPDF
{
    // En-tête du document
    function Header()
    {
        // Police Arial gras 15
        $this->SetFont('Arial', 'B', 15);
        // Titre - Utilisation de mb_convert_encoding pour la compatibilité UTF-8 vers ISO-8859-1
        $this->Cell(0, 10, mb_convert_encoding('Rapport de Monitoring des Équipements', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        // Date du rapport
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, mb_convert_encoding('Date du rapport : ' . date('d/m/Y H:i:s'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        // Saut de ligne
        $this->Ln(10);
    }

    // Pied de page du document
    function Footer()
    {
        // Positionnement à 1.5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, mb_convert_encoding('Page ' . $this->PageNo() . '/{nb}', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    }

    // Fonction pour ajouter une section avec titre et contenu
    function ChapterTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255); // Couleur de fond pour le titre de chapitre
        $this->Cell(0, 8, mb_convert_encoding($label, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L', true);
        $this->Ln(4);
    }

    // Fonction pour une table de données (Appareils)
    function LoadData($header, $data)
    {
        // Couleurs, épaisseur du trait et police par défaut
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 9);

        // En-tête
        $w = array(35, 30, 25, 20, 35, 30); // Largeurs des colonnes ajustées pour Appareils
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, mb_convert_encoding($header[$i], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $this->Ln();

        // Restauration des couleurs et police pour les données
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 8);

        // Données
        $fill = false;
        foreach ($data as $row) {
            $this->Cell($w[0], 6, mb_convert_encoding($row[0], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'L', $fill); // Nom
            $this->Cell($w[1], 6, mb_convert_encoding($row[1], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'L', $fill); // IP
            $this->Cell($w[2], 6, mb_convert_encoding($row[2], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Type
            $this->Cell($w[3], 6, mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Statut
            $this->Cell($w[4], 6, mb_convert_encoding($row[4], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'L', $fill); // Localisation
            $this->Cell($w[5], 6, mb_convert_encoding($row[5], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Dernière vérification
            $this->Ln();
            $fill = !$fill;
        }
        // Ligne de fermeture
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(10);
    }

    // Fonction pour une table de résultats de test (spécifique)
    function LoadTestResultsData($header, $data)
    {
        // Couleurs, épaisseur du trait et police par défaut
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 8);

        // En-tête
        $w = array(35, 20, 20, 25, 60, 30); // Largeurs des colonnes ajustées pour Tests (Notes plus large)
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, mb_convert_encoding($header[$i], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $this->Ln();

        // Restauration des couleurs et police pour les données
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 7);

        // Données
        $fill = false;
        foreach ($data as $row) {
            // Sauvegarde de la position X et Y
            $x = $this->GetX();
            $y = $this->GetY();

            // Calcul de la hauteur de ligne nécessaire pour la MultiCell 'Notes'
            // Ceci est une estimation, une implémentation plus sophistiquée calculerait le nombre de lignes
            $notes_text = mb_convert_encoding($row[4], 'ISO-8859-1', 'UTF-8');
            $line_height = 6;
            $nb_lines_notes = $this->GetStringWidth($notes_text) / $w[4];
            $h = max($line_height, 6 * ceil($nb_lines_notes)); // La hauteur de la ligne doit être au moins 6

            // Cellules normales
            $this->Cell($w[0], $h, mb_convert_encoding($row[0], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'L', $fill); // Appareil (Nom + IP)
            $this->Cell($w[1], $h, mb_convert_encoding($row[1], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Résultat
            $this->Cell($w[2], $h, mb_convert_encoding($row[2], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Connecté ?
            $this->Cell($w[3], $h, mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Exécuté par

            // MultiCell pour la colonne 'Notes' pour le retour à la ligne
            $this->MultiCell($w[4], $line_height, $notes_text, 'LR', 'L', $fill);

            // Retour à la position X de départ, et avance Y par la hauteur calculée
            $this->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y);

            // Dernière cellule après MultiCell
            $this->Cell($w[5], $h, mb_convert_encoding($row[5], 'ISO-8859-1', 'UTF-8'), 'LR', 0, 'C', $fill); // Date d'exécution
            $this->Ln();
            $fill = !$fill;
        }
        // Ligne de fermeture
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(10);
    }
}

// Instanciation de l'objet PDF
$pdf = new PDF();
$pdf->AliasNbPages(); // Permet d'afficher "Page X sur Y"
$pdf->AddPage(); // Ajoute une nouvelle page

// Section : Liste des appareils
$pdf->ChapterTitle('Liste des appareils');
$header_devices = array('Nom Appareil', 'Adresse IP', 'Type', 'Statut', 'Localisation', 'Dernière Vérif.');
$data_devices = [];
foreach ($devices as $device) {
    $data_devices[] = [
        $device['name'],
        $device['ip_address'],
        $device['type'],
        $device['status'],
        $device['location'] ?? 'N/A',
        $device['last_check'] ?? 'N/A'
    ];
}
$pdf->LoadData($header_devices, $data_devices);

// Section : Historique des tests de connectivité
$pdf->ChapterTitle('Historique des tests de connectivité (50 derniers)');
$header_results = array('Appareil (Nom - IP)', 'Résultat', 'Connecté ?', 'Exécuté par', 'Notes', 'Date d\'exécution');
$data_results = [];
foreach ($test_results as $result) {
    $notes_content = $result['notes'] ?? 'N/A';
    // Limiter la longueur du texte des notes pour éviter un débordement excessif
    $max_notes_length = 150; // Ajustez cette valeur si nécessaire
    if (mb_strlen($notes_content, 'UTF-8') > $max_notes_length) {
        $notes_content = mb_substr($notes_content, 0, $max_notes_length, 'UTF-8') . '...';
    }

    $data_results[] = [
        $result['device_name'] . ' - ' . $result['ip_address'],
        $result['result_value'],
        $result['threshold_met'] ? 'Oui' : 'Non',
        $result['run_by_email'] ?? 'Inconnu',
        $notes_content, // Texte des notes potentiellement tronqué
        $result['run_at']
    ];
}
$pdf->LoadTestResultsData($header_results, $data_results);


// Nom du fichier PDF à télécharger
$filename = 'rapport_monitoring_' . date('Ymd_His') . '.pdf';

// Sortie du PDF (téléchargement)
try {
    $pdf->Output('D', $filename); // 'D' pour forcer le téléchargement
} catch (Exception $e) {
    error_log("Erreur de sortie PDF: " . $e->getMessage());
    $_SESSION['debug_error_message'] = "Erreur lors de la génération du fichier PDF: " . $e->getMessage();
    header("Location: admin.php?error=pdf_generation_failed");
    exit();
}

