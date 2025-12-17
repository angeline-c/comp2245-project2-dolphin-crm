<?php
// require_once "../includes/auth.php";
require_once "../includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$currentUserId = $_SESSION["user_id"] ?? null;

//Filter: all|sales|support|assigned
$filter = strtolower($_GET["filter"] ?? "all");
$allowed = ["all", "sales", "support", "assigned"];
if (!in_array($filter, $allowed, true)) {
  $filter = "all";
}

$sql = "SELECT id, title, firstname, lastname, email, company, type
        FROM contacts";
$params = [];

//Apply filters
if ($filter === "sales") {
  $sql .= " WHERE type = ?";
  $params[] = "Sales Lead";
} elseif ($filter === "support") {
  $sql .= " WHERE type = ?";
  $params[] = "Support";
} elseif ($filter === "assigned") {
  $sql .= " WHERE assigned_to = ?";
  $params[] = $currentUserId;
}

$sql .= " ORDER BY lastname ASC, firstname ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function type_badge_class($type) {
  $t = strtolower(trim((string)$type));
  if ($t === "sales lead") return "badge badge-sales";
  if ($t === "support") return "badge badge-support";
  return "badge";
}

function active_tab($current, $tab) {
  return $current === $tab ? "is-active" : "";
}
?>


<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>

<main class="page">
  <div class="page-head">
    <h1 class="page-title">Dashboard</h1>

    <a class="btn btn-primary" href="new_contact.php">
      + Add Contact
    </a>
  </div>

  <section class="card">
    <div class="filters">
      <div class="filters-label">
        <span class="filter-icon">⏷</span>
        <span>Filter By:</span>
      </div>

      <nav class="filters-tabs" aria-label="Contact filters">
        <a class="tab <?php echo active_tab($filter, "all"); ?>" href="dashboard.php?filter=all">All</a>
        <a class="tab <?php echo active_tab($filter, "sales"); ?>" href="dashboard.php?filter=sales">Sales Leads</a>
        <a class="tab <?php echo active_tab($filter, "support"); ?>" href="dashboard.php?filter=support">Support</a>
        <a class="tab <?php echo active_tab($filter, "assigned"); ?>" href="dashboard.php?filter=assigned">Assigned to me</a>
      </nav>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Company</th>
            <th>Type</th>
            <th class="col-view"></th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($contacts)): ?>
            <tr>
              <td colspan="5" class="empty-state">No contacts found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($contacts as $c): ?>
              <?php
                $fullName = trim(($c["title"] ? $c["title"] . " " : "") . $c["firstname"] . " " . $c["lastname"]);
              ?>
              <tr>
                <td class="name-cell"><?php echo h($fullName); ?></td>
                <td><?php echo h($c["email"]); ?></td>
                <td><?php echo h($c["company"]); ?></td>
                <td>
                  <span class="<?php echo h(type_badge_class($c["type"])); ?>">
                    <?php echo h(strtoupper((string)$c["type"])); ?>
                  </span>
                </td>
                <td class="view-cell">
                  <a class="view-link" href="contact_view.php?id=<?php echo (int)$c["id"]; ?>">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<?php include "../includes/footer.php"; ?>
