<?php
/*
 * Process PPP Active connection: disconnect
 */

if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
    exit;
}

$removepactive = isset($_GET['remove-pactive']) ? $_GET['remove-pactive'] : '';

if (!empty($removepactive)) {
    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {

        // Remove/disconnect active PPP connection
        $API->comm("/ppp/active/remove", array(
            ".id" => $removepactive
        ));

        $API->disconnect();

        echo "<script>alert('PPP connection disconnected successfully!'); window.location='./?ppp=active&session=$session';</script>";

    } else {
        echo "<script>alert('Failed to connect to MikroTik!'); window.location='./?ppp=active&session=$session';</script>";
    }
}
?>
