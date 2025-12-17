<?php
// require_once "../includes/auth.php";
require_once "../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$currentUserId = $_SESSION["user_id"] ?? null;

function h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function clean($value) {
  return trim((string)$value);
}

$errors = [];
$success = "";

try {
  $usersStmt = $conn->prepare("SELECT id, firstname, lastname FROM users ORDER BY firstname ASC, lastname ASC");
  $usersStmt->execute();
  $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $users = [];
  $errors[] = "Could not load users list.";
}

//Defaults
$form = [
  "title" => "",
  "firstname" => "",
  "lastname" => "",
  "email" => "",
  "telephone" => "",
  "company" => "",
  "type" => "Sales Lead",
  "assigned_to" => ""
];

//VALIDATION CHECKS:
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $token = $_POST["csrf_token"] ?? "";
  if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
    $errors[] = "Invalid session token. Please refresh and try again.";
  }

  $form["title"]       = clean($_POST["title"] ?? "");
  $form["firstname"]   = clean($_POST["firstname"] ?? "");
  $form["lastname"]    = clean($_POST["lastname"] ?? "");
  $form["email"]       = clean($_POST["email"] ?? "");
  $form["telephone"]   = clean($_POST["telephone"] ?? "");
  $form["company"]     = clean($_POST["company"] ?? "");
  $form["type"]        = clean($_POST["type"] ?? "");
  $form["assigned_to"] = clean($_POST["assigned_to"] ?? "");

  if ($form["firstname"] === "") $errors[] = "First Name is required.";
  if ($form["lastname"] === "")  $errors[] = "Last Name is required.";
  if ($form["company"] === "")   $errors[] = "Company is required.";
  if ($form["assigned_to"] === "") $errors[] = "Assigned To is required.";

  $allowedTitles = ["Mr", "Mrs", "Ms", "Dr", "Prof", ""];
  if (!in_array($form["title"], $allowedTitles, true)) {
    $errors[] = "Invalid title selected.";
  }

  $allowedTypes = ["Sales Lead", "Support"];
  if (!in_array($form["type"], $allowedTypes, true)) {
    $errors[] = "Invalid type selected.";
  }

  if ($form["email"] !== "" && !filter_var($form["email"], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
  }

  if ($form["assigned_to"] !== "" && !ctype_digit($form["assigned_to"])) {
    $errors[] = "Assigned To must be a valid user.";
  } elseif ($form["assigned_to"] !== "") {
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([(int)$form["assigned_to"]]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
      $errors[] = "Assigned To user does not exist.";
    }
  }

  if (!$currentUserId) {
    $errors[] = "You must be logged in to create a contact.";
  }

  //IF NO ERRORS:
  if (empty($errors)) {
    try {
      $insert = $conn->prepare(
        "INSERT INTO contacts (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
      );

      $insert->execute([
        $form["title"] === "" ? null : $form["title"],
        $form["firstname"],
        $form["lastname"],
        $form["email"] === "" ? null : $form["email"],
        $form["telephone"] === "" ? null : $form["telephone"],
        $form["company"],
        $form["type"],
        (int)$form["assigned_to"],
        (int)$currentUserId
      ]);

      $success = "Contact added successfully.";

      //RESET
      $form = [
        "title" => "",
        "firstname" => "",
        "lastname" => "",
        "email" => "",
        "telephone" => "",
        "company" => "",
        "type" => "Sales Lead",
        "assigned_to" => ""
      ];
    } catch (PDOException $e) {
      $errors[] = "Failed to add contact. Please try again.";
    }
  }
}

//Create CSRF token for the form
if (!isset($_SESSION["csrf_token"])) {
  $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>

<main class="page">
  <div class="page-head">
    <h1 class="page-title">New Contact</h1>
  </div>

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

  <section class="card">
    <form method="POST" action="new_contact.php" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">

      <div class="form-row">
        <div class="form-group">
          <label for="title">Title</label>
          <select id="title" name="title">
            <option value="" <?php echo $form["title"] === "" ? "selected" : ""; ?>>Select</option>
            <option value="Mr" <?php echo $form["title"] === "Mr" ? "selected" : ""; ?>>Mr</option>
            <option value="Mrs" <?php echo $form["title"] === "Mrs" ? "selected" : ""; ?>>Mrs</option>
            <option value="Ms" <?php echo $form["title"] === "Ms" ? "selected" : ""; ?>>Ms</option>
            <option value="Dr" <?php echo $form["title"] === "Dr" ? "selected" : ""; ?>>Dr</option>
            <option value="Prof" <?php echo $form["title"] === "Prof" ? "selected" : ""; ?>>Prof</option>
          </select>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="firstname">First Name *</label>
          <input id="firstname" name="firstname" type="text" value="<?php echo h($form["firstname"]); ?>" required>
        </div>

        <div class="form-group">
          <label for="lastname">Last Name *</label>
          <input id="lastname" name="lastname" type="text" value="<?php echo h($form["lastname"]); ?>" required>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?php echo h($form["email"]); ?>">
        </div>

        <div class="form-group">
          <label for="telephone">Telephone</label>
          <input id="telephone" name="telephone" type="text" value="<?php echo h($form["telephone"]); ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="company">Company *</label>
          <input id="company" name="company" type="text" value="<?php echo h($form["company"]); ?>" required>
        </div>

        <div class="form-group">
          <label for="type">Type *</label>
          <select id="type" name="type" required>
            <option value="Sales Lead" <?php echo $form["type"] === "Sales Lead" ? "selected" : ""; ?>>Sales Lead</option>
            <option value="Support" <?php echo $form["type"] === "Support" ? "selected" : ""; ?>>Support</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="assigned_to">Assigned To *</label>
          <select id="assigned_to" name="assigned_to" required>
            <option value="">Select a user</option>
            <?php foreach ($users as $u): ?>
              <?php
                $uid = (int)$u["id"];
                $name = trim($u["firstname"] . " " . $u["lastname"]);
                $selected = ((string)$uid === (string)$form["assigned_to"]) ? "selected" : "";
              ?>
              <option value="<?php echo $uid; ?>" <?php echo $selected; ?>>
                <?php echo h($name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </section>
</main>

<?php include "../includes/footer.php"; ?>
