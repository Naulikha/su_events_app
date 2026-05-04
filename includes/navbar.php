<?php
// Because files are in different folders ( /auth vs /dashboard), 
// we need an absolute base URL to prevent broken links.
$base_url = '/su_events_app/';
?>

<header style="background: #004aad; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <a href="<?php echo $base_url; ?>index.php" style="font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none;">🎓 SU Events</a>
    
    <nav>
        <!-- Everyone can see the public storefront -->
        <a href="<?php echo $base_url; ?>index.php" style="color: white; text-decoration: none; font-weight: bold; margin-left: 20px;">Home</a>

        <?php if(isset($_SESSION['user_id'])): ?>
            
            <?php 
                // Figure out which dashboard they own
                $dashboard = '';
                if ($_SESSION['role'] === 'Admin') $dashboard = 'dashboard/admin.php';
                if ($_SESSION['role'] === 'Organiser') $dashboard = 'dashboard/organiser.php';
                if ($_SESSION['role'] === 'Attendee') $dashboard = 'dashboard/attendee.php';
            ?>
            
            <a href="<?php echo $base_url . $dashboard; ?>" style="color: white; text-decoration: none; font-weight: bold; margin-left: 20px;">My Dashboard</a>
            
            <!-- Logged in as: Name -->
            <span style="color: #a3c2fa; margin-left: 20px; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            
            <a href="<?php echo $base_url; ?>auth/logout.php" style="color: #ff6b6b; text-decoration: none; font-weight: bold; margin-left: 20px;">Logout</a>
            
        <?php else: ?>
            <a href="<?php echo $base_url; ?>auth/login.php" style="color: white; text-decoration: none; font-weight: bold; margin-left: 20px;">Login</a>
            <a href="<?php echo $base_url; ?>auth/register.php" style="background: white; color: #004aad; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; margin-left: 20px;">Sign Up</a>
        <?php endif; ?>
    </nav>
</header>