<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function clean($value) {
  return trim((string)$value);
}

/**
 * Password rules:
 * - at least 8 characters
 * - at least 1 uppercase
 * - at least 1 lowercase
 * - at least 1 number
 */
function isStrongPassword($password) {
  return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password) === 1;
}

$errors = [];
$success = "";

// CSRF
if (!isset($_SESSION["csrf_token"])) {
  $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// ✅ Optional: enforce admin-only (depends on how your auth.php stores role)
// If your session has role, uncomment and adjust key name.
// $role = $_SESSION["role"] ?? "";
// if ($role !== "Admin") { header("Location: dashboard.php"); exit; }

$form = [
  "firstname" => "",
  "lastname"  => "",
  "email"     => "",
  "role"      => "Member"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // CSRF check
  $token = $_POST["csrf_token"] ?? "";
  if (!hash_equals($_SESSION["csrf_token"], $token)) {
    $errors[] = "Invalid session token. Please refresh and try again.";
  }

  $form["firstname"] = clean($_POST["firstname"] ?? "");
  $form["lastname"]  = clean($_POST["lastname"] ?? "");
  $form["email"]     = clean($_POST["email"] ?? "");
  $passwordRaw       = (string)($_POST["password"] ?? "");
  $form["role"]      = clean($_POST["role"] ?? "Member");

  // Validate role strictly
  $allowedRoles = ["Admin", "Member"];
  if (!in_array($form["role"], $allowedRoles, true)) {
    $errors[] = "Invalid role selected.";
  }

  // Validate first/last names (letters, spaces, hyphens, apostrophes)
  if ($form["firstname"] === "") {
    $errors[] = "First name is required.";
  } elseif (!preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/", $form["firstname"])) {
    $errors[] = "First name contains invalid characters.";
  } elseif (strlen($form["firstname"]) > 50) {
    $errors[] = "First name must be 50 characters or less.";
  }

  if ($form["lastname"] === "") {
    $errors[] = "Last name is required.";
  } elseif (!preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/", $form["lastname"])) {
    $errors[] = "Last name contains invalid characters.";
  } elseif (strlen($form["lastname"]) > 50) {
    $errors[] = "Last name must be 50 characters or less.";
  }

  // Validate email
  if ($form["email"] === "") {
    $errors[] = "Email is required.";
  } elseif (!filter_var($form["email"], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
  } elseif (strlen($form["email"]) > 100) {
    $errors[] = "Email must be 100 characters or less.";
  }

  // Validate password strength
  if ($passwordRaw === "") {
    $errors[] = "Password is required.";
  } elseif (!isStrongPassword($passwordRaw)) {
    $errors[] = "Password must be at least 8 characters and include one uppercase letter, one lowercase letter, and one number.";
  }

  // Check duplicate email
  if (empty($errors)) {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->execute([$form["email"]]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
      $errors[] = "Email already exists. Please use a different email.";
    }
  }

  // Insert
  if (empty($errors)) {
    try {
      $hashed = password_hash($passwordRaw, PASSWORD_DEFAULT);

      $ins = $conn->prepare(
        "INSERT INTO users (firstname, lastname, email, password, role)
         VALUES (?, ?, ?, ?, ?)"
      );
      $ins->execute([
        $form["firstname"],
        $form["lastname"],
        $form["email"],
        $hashed,
        $form["role"]
      ]);

      $success = "User added successfully.";
      // reset form
      $form = ["firstname" => "", "lastname" => "", "email" => "", "role" => "Member"];

    } catch (PDOException $e) {
      $errors[] = "Failed to add user. Please try again.";
    }
  }
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>

<main class="page">
  <div class="page-head">
    <h1 class="page-title">New User</h1>
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

  <section class="card form-card">
    <form method="POST" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION["csrf_token"]); ?>">

      <div class="form-grid-2">
        <div class="form-group">
          <label for="firstname">First Name</label>
          <input id="firstname" name="firstname" type="text" value="<?php echo h($form["firstname"]); ?>" required>
        </div>

        <div class="form-group">
          <label for="lastname">Last Name</label>
          <input id="lastname" name="lastname" type="text" value="<?php echo h($form["lastname"]); ?>" required>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?php echo h($form["email"]); ?>" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required>
          <small class="help-text">Must be 8+ chars with 1 uppercase, 1 lowercase, and 1 number.</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="Member" <?php echo $form["role"] === "Member" ? "selected" : ""; ?>>Member</option>
            <option value="Admin" <?php echo $form["role"] === "Admin" ? "selected" : ""; ?>>Admin</option>
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
