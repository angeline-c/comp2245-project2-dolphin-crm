<?php
require_once "../includes/auth.php";  // Ensures only logged-in users (admins) can access
require_once "../includes/db.php";    // Database connection

$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $email     = trim($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $role      = $_POST['role'];

    // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $error = "Email already exists. Please use a different email.";
    } else {
        // Insert new user
        try {
            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role) 
                                    VALUES (:firstname, :lastname, :email, :password, :role)");
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname'  => $lastname,
                ':email'     => $email,
                ':password'  => $password,
                ':role'      => $role
            ]);
            $success = "User added successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/sidebar.php"; ?>

<h1>Add New User</h1>

<?php if ($success): ?>
    <p style="color:green;"><?php echo $success; ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST">
    <label>First Name:</label><br>
    <input type="text" name="firstname" required><br>

    <label>Last Name:</label><br>
    <input type="text" name="lastname" required><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br>

    <label>Role:</label><br>
    <select name="role" required>
        <option value="Admin">Admin</option>
        <option value="User">User</option>
    </select><br><br>

    <button type="submit">Add User</button>
</form>

<?php include "../includes/footer.php"; ?>
