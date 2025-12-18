<?php
require_once "../../includes/auth.php";
require_once "../../includes/db.php";

header("Content-Type: application/json");

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function fmt_dt($dt) {
  if (!$dt) return "";
  return date("F j, Y \\a\\t g:ia", strtotime($dt));
}

$userId = $_SESSION["user_id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["success" => false, "message" => "Method not allowed"]);
  exit;
}

$contactId  = (int)($_POST["contact_id"] ?? 0);
$comment    = trim((string)($_POST["comment"] ?? ""));
$csrfToken  = (string)($_POST["csrf_token"] ?? "");

if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "Invalid CSRF token"]);
  exit;
}

if (!$userId) {
  http_response_code(401);
  echo json_encode(["success" => false, "message" => "Not logged in"]);
  exit;
}

if ($contactId <= 0) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Invalid contact id"]);
  exit;
}

if ($comment === "") {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Note cannot be empty"]);
  exit;
}

try {
  $conn->beginTransaction();

  $ins = $conn->prepare(
    "INSERT INTO notes (contact_id, comment, created_by, created_at)
     VALUES (?, ?, ?, CURRENT_TIMESTAMP)"
  );
  $ins->execute([$contactId, $comment, (int)$userId]);

  $noteId = (int)$conn->lastInsertId();

  $upd = $conn->prepare(
    "UPDATE contacts SET updated_at = CURRENT_TIMESTAMP WHERE id = ?"
  );
  $upd->execute([$contactId]);

  $get = $conn->prepare(
    "SELECT n.comment,
            n.created_at,
            CONCAT(u.firstname, ' ', u.lastname) AS author
     FROM notes n
     JOIN users u ON n.created_by = u.id
     WHERE n.id = ?"
  );
  $get->execute([$noteId]);
  $note = $get->fetch(PDO::FETCH_ASSOC);

  if (!$note) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Note saved but could not be retrieved"]);
    exit;
  }

  $note["created_at"] = fmt_dt($note["created_at"]);

  $conn->commit();

  echo json_encode(["success" => true, "note" => $note]);
  exit;

} catch (Exception $e) {
  if ($conn->inTransaction()) {
    $conn->rollBack();
  }
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Server error"]);
  exit;
}
