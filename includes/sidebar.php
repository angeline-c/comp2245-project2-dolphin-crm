<div class="sidebar">
    <ul>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])==='dashboard.php'?'is-active':''; ?>" href="dashboard.php"><span class="nav-ico">🏠</span>Home</a></li>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])==='new_contact.php'?'is-active':''; ?>" href="new_contact.php"><span class="nav-ico">➕</span>New Contact</a></li>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])==='users.php'?'is-active':''; ?>" href="users.php"><span class="nav-ico">👥</span>Users</a></li>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])==='logout.php'?'is-active':''; ?>" href="logout.php"><span class="nav-ico">🚪</span>Logout</a></li>
    </ul>
</div>

<div class="content">
