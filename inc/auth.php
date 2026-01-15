<?php
// ...existing code...
function is_admin() {
    session_start();
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['is_admin']);
}
// ...existing code...
?>