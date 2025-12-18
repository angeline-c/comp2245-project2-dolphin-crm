<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$currentUserId = $_SESSION["user_id"] ?? null;

function h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function fmt_dt($dt) {
  if (!$dt) return "";
  return date("F j, Y \a\\t g:ia", strtotime($dt));
}

$errors = [];
$success = "";

//CSRF token
if (!isset($_SESSION["csrf_token"])) {
  $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$contactId = $_GET["id"] ?? "";
if (!ctype_digit($contactId)) {
  $errors[] = "Invalid contact id.";
  $contactId = null;
} else {
  $contactId = (int)$contactId;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $token = $_POST["csrf_token"] ?? "";
  if (!hash_equals($_SESSION["csrf_token"], $token)) {
    $errors[] = "Invalid session token. Please refresh and try again.";
  } elseif (!$currentUserId) {
    $errors[] = "You must be logged in.";
  } elseif (!$contactId) {
    $errors[] = "Invalid contact id.";
  } else {
    $action = $_POST["action"] ?? "";

    try {
      if ($action === "assign_to_me") {
        $stmt = $conn->prepare("UPDATE contacts SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([(int)$currentUserId, $contactId]);
        $success = "Contact assigned to you.";
      }

      if ($action === "switch_type") {
        $tstmt = $conn->prepare("SELECT type FROM contacts WHERE id = ?");
        $tstmt->execute([$contactId]);
        $row = $tstmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
          $errors[] = "Contact not found.";
        } else {
          $currentType = (string)$row["type"];
          $newType = (strtolower($currentType) === "support") ? "Sales Lead" : "Support";

          $ustmt = $conn->prepare("UPDATE contacts SET type = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
          $ustmt->execute([$newType, $contactId]);
          $success = "Contact type updated.";
        }
      }

      if ($action === "add_note") {
        $comment = trim((string)($_POST["comment"] ?? ""));
        if ($comment === "") {
          $errors[] = "Note cannot be empty.";
        } else {
          $nstmt = $conn->prepare("INSERT INTO notes (contact_id, comment, created_by) VALUES (?, ?, ?)");
          $nstmt->execute([$contactId, $comment, (int)$currentUserId]);
          $success = "Note added.";
        }
      }
    } catch (PDOException $e) {
      $errors[] = "Something went wrong. Please try again.";
    }
  }
}

//GetDEETS
$contact = null;
if ($contactId) {
  $cstmt = $conn->prepare(
    "SELECT
        c.*,
        CONCAT(cb.firstname, ' ', cb.lastname) AS created_by_name,
        CONCAT(ab.firstname, ' ', ab.lastname) AS assigned_to_name
     FROM contacts c
     LEFT JOIN users cb ON c.created_by = cb.id
     LEFT JOIN users ab ON c.assigned_to = ab.id
     WHERE c.id = ?"
  );
  $cstmt->execute([$contactId]);
  $contact = $cstmt->fetch(PDO::FETCH_ASSOC);

  if (!$contact) {
    $errors[] = "Contact not found.";
  }
}

//GETNOTES
$notes = [];
if ($contact) {
  $nst = $conn->prepare(
    "SELECT n.comment, n.created_at, CONCAT(u.firstname, ' ', u.lastname) AS author
     FROM notes n
     JOIN users u ON n.created_by = u.id
     WHERE n.contact_id = ?
     ORDER BY n.created_at DESC"
  );
  $nst->execute([$contactId]);
  $notes = $nst->fetchAll(PDO::FETCH_ASSOC);
}

$fullName = "";
$headerTypeBtnText = "";
if ($contact) {
  $fullName = trim(($contact["title"] ? $contact["title"] . " " : "") . $contact["firstname"] . " " . $contact["lastname"]);
  $isSupport = (strtolower((string)$contact["type"]) === "support");
  $headerTypeBtnText = $isSupport ? "Switch to Sales Lead" : "Switch to Support";
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>

<main class="page">
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?php echo h($err); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($contact): ?>
    <div class="contact-head">
      <div class="contact-title">
        <div class="avatar" aria-hidden="true"></div>
        <div>
          <h1 class="page-title"><?php echo h($fullName); ?></h1>
          <div class="subtext">
            Created on <?php echo h(fmt_dt($contact["created_at"])); ?>
            by <?php echo h($contact["created_by_name"] ?? ""); ?><br>
            Updated on <?php echo h(fmt_dt($contact["updated_at"])); ?>
          </div>
        </div>
      </div>

      <div class="contact-actions">
        <form method="POST" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
          <input type="hidden" name="action" value="assign_to_me">
          <button class="btn btn-success" type="submit">Assign to me</button>
        </form>

        <form method="POST" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
          <input type="hidden" name="action" value="switch_type">
          <button class="btn btn-warn" type="submit"><?php echo h($headerTypeBtnText); ?></button>
        </form>
      </div>
    </div>

    <section class="card contact-info">
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Email</div>
          <div class="info-value"><?php echo h($contact["email"]); ?></div>
        </div>

        <div class="info-item">
          <div class="info-label">Telephone</div>
          <div class="info-value"><?php echo h($contact["telephone"]); ?></div>
        </div>

        <div class="info-item">
          <div class="info-label">Company</div>
          <div class="info-value"><?php echo h($contact["company"]); ?></div>
        </div>

        <div class="info-item">
          <div class="info-label">Assigned To</div>
          <div class="info-value"><?php echo h($contact["assigned_to_name"] ?? ""); ?></div>
        </div>
      </div>
    </section>

    <section class="card notes-card">
      <div class="notes-head">
        <h2 class="notes-title">Notes</h2>
      </div>

      <?php if (empty($notes)): ?>
        <p class="empty-state">No notes yet.</p>
      <?php else: ?>
        <div class="notes-list">
          <?php foreach ($notes as $n): ?>
            <div class="note">
              <div class="note-author"><?php echo h($n["author"]); ?></div>
              <div class="note-body"><?php echo nl2br(h($n["comment"])); ?></div>
              <div class="note-date"><?php echo h(fmt_dt($n["created_at"])); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="note-form">
        <div class="note-form-title">Add a note</div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
          <input type="hidden" name="action" value="add_note">
          <textarea name="comment" rows="4" placeholder="Enter details here..." required></textarea>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Add Note</button>
          </div>
        </form>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php include "../includes/footer.php"; ?>
