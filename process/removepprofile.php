<?php
/*
 * Process PPP Profile operations: remove
 */

if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
    exit;
}

$removepprofile = isset($_GET['remove-pprofile']) ? $_GET['remove-pprofile'] : '';

if (!empty($removepprofile)) {
    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {

        $getprofile = $API->comm("/ppp/profile/print", array("?name" => $removepprofile));

        if (!empty($getprofile)) {
            // Check if it's default profile
            if ($removepprofile === 'default' || $removepprofile === 'default-encryption') {
                echo "<script>alert('Cannot delete default profile!'); window.location='./?ppp=profiles&session=$session';</script>";
            } else {
                $API->comm("/ppp/profile/remove", array(
                    ".id" => $getprofile[0]['.id']
                ));
                echo "<script>alert('Profile deleted successfully!'); window.location='./?ppp=profiles&session=$session';</script>";
            }
        } else {
            echo "<script>alert('Profile not found!'); window.location='./?ppp=profiles&session=$session';</script>";
        }

        $API->disconnect();

    } else {
        echo "<script>alert('Failed to connect to MikroTik!'); window.location='./?ppp=profiles&session=$session';</script>";
    }
}
?>
