<?php
// This file assumes session_start() has already been called on the main page.

// Check for a Success message
if (isset($_SESSION['flash_success'])) {
    echo '<div id="flash-msg" style="background:#d4edda; color:#155724; padding:15px; text-align:center; position:fixed; top:0; left:0; width:100%; z-index:9999; box-shadow:0 4px 6px rgba(0,0,0,0.1); font-weight:bold; transition: top 0.5s ease-in-out;">' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
    unset($_SESSION['flash_success']); // Delete it so it only shows once
}

// Check for an Error message
if (isset($_SESSION['flash_error'])) {
    echo '<div id="flash-msg" style="background:#f8d7da; color:#721c24; padding:15px; text-align:center; position:fixed; top:0; left:0; width:100%; z-index:9999; box-shadow:0 4px 6px rgba(0,0,0,0.1); font-weight:bold; transition: top 0.5s ease-in-out;">' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
    unset($_SESSION['flash_error']); // Delete it so it only shows once
}
?>

<script>
    // UX Magic: Make the banner automatically slide up and disappear after 3 seconds
    setTimeout(function() {
        var flash = document.getElementById('flash-msg');
        if (flash) {
            flash.style.top = '-100px'; // Slide it off screen
            setTimeout(function() { flash.remove(); }, 500); // Remove from HTML
        }
    }, 3000);
</script>