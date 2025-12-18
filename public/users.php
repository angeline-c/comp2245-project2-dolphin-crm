<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

if (session_status() === PHP_SESSION_NONE) session_start();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function fmt_dt($dt){ return $dt ? date("F j, Y \\a\\t g:ia", strtotime($dt)) : ""; }

try {
  $stmt = $conn->query("SELECT firstname, lastname, email, role, created_at FROM users ORDER BY created_at DESC");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $users = [];
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>

<main class="page">
  <div class="users-header">
    <div>
      <h1 class="users-title">Users</h1>
      <p class="users-subtitle">Manage system users and roles.</p>
    </div>

    <a class="btn btn-primary btn-pill" href="new_user.php">
      <span class="btn-icon">+</span> New User
    </a>
  </div>

  <section class="users-card">
    <?php if (empty($users)): ?>
      <div class="empty-state">
        <div class="empty-emoji">🫧</div>
        <div>
          <div class="empty-title">No users found</div>
          <div class="empty-text">Add your first user to get started.</div>
        </div>
        <a class="btn btn-primary btn-pill" href="new_user.php">+ New User</a>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <?php
                $full = trim($u["firstname"]." ".$u["lastname"]);
                $role = strtolower((string)$u["role"]);
                $pillClass = ($role === "admin") ? "chip chip-admin" : "chip chip-member";
              ?>
              <tr>
                <td class="name-cell">
                  <div class="avatar-circle"><?php echo h(strtoupper(substr($u["firstname"],0,1).substr($u["lastname"],0,1))); ?></div>
                  <div class="name-stack">
                    <div class="name"><?php echo h($full); ?></div>
                    <div class="muted">User</div>
                  </div>
                </td>
                <td class="email"><?php echo h($u["email"]); ?></td>
                <td><span class="<?php echo $pillClass; ?>"><?php echo h(ucfirst($role)); ?></span></td>
                <td class="muted"><?php echo h(fmt_dt($u["created_at"])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php include "../includes/footer.php"; ?>
