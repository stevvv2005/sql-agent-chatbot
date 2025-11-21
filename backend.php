<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Récupération question utilisateur
$input = json_decode(file_get_contents("php://input"), true);
$user_question = trim($input['message'] ?? '');

if ($user_question === "") {
    echo json_encode(["reply" => "⚠️ Message vide."]);
    exit;
}

// ❗ IMPORTANT : Plus aucun prompt et plus d'appel API.
// Le message utilisateur sera traité directement comme une requête SQL fournie par l'utilisateur.

$sql = $user_question;

/* ======================================================
   SÉCURITÉ SQL (IMPORTANT)
====================================================== */

// Bloquer les requêtes dangereuses
if (preg_match('/\b(DELETE|UPDATE|INSERT|DROP|ALTER|TRUNCATE)\b/i', $sql)) {
    echo json_encode(["reply" => "❌ Requête refusée pour raisons de sécurité.", "sql" => $sql]);
    exit;
}

// Vérifier que la requête commence par SELECT
if (!preg_match('/^SELECT/i', $sql)) {
    echo json_encode(["reply" => "⚠️ Seulement les requêtes SELECT sont autorisées.", "sql" => $sql]);
    exit;
}

/* ======================================================
   EXECUTION MYSQL
====================================================== */

$conn = new mysqli("localhost", "root", "", "newbase");

if ($conn->connect_error) {
    echo json_encode(["reply" => "❌ MySQL erreur : " . $conn->connect_error]);
    exit;
}

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "reply" => "⚠️ Erreur SQL : " . $conn->error,
        "sql"   => $sql
    ]);
    $conn->close();
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = implode(" – ", $row);
}

$conn->close();

$reply = count($rows) ? implode("\n", $rows) : "Aucun résultat trouvé.";

echo json_encode([
    "reply" => $reply,
    "sql"   => $sql
]);

?>
