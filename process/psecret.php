<?php
/*
 * Process PPP Secret operations: enable, disable, remove
 */

if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
    exit;
}

$enablesecr = isset($_GET['enable-pppsecret']) ? $_GET['enable-pppsecret'] : '';
$disablesecr = isset($_GET['disable-pppsecret']) ? $_GET['disable-pppsecret'] : '';
$removesecr = isset($_GET['remove-pppsecret']) ? $_GET['remove-pppsecret'] : '';

if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {

    // Enable secret
    if (!empty($enablesecr)) {
        $getsecret = $API->comm("/ppp/secret/print", array("?name" => $enablesecr));

        if (!empty($getsecret)) {
            $API->comm("/ppp/secret/set", array(
                ".id" => $getsecret[0]['.id'],
                "disabled" => "no"
            ));
            echo "<script>alert('PPP Secret enabled successfully!'); window.location='./?ppp=secrets&session=$session';</script>";
        } else {
            echo "<script>alert('Secret not found!'); window.location='./?ppp=secrets&session=$session';</script>";
        }
    }

    // Disable secret
    elseif (!empty($disablesecr)) {
        $getsecret = $API->comm("/ppp/secret/print", array("?name" => $disablesecr));

        if (!empty($getsecret)) {
            $API->comm("/ppp/secret/set", array(
                ".id" => $getsecret[0]['.id'],
                "disabled" => "yes"
            ));
            echo "<script>alert('PPP Secret disabled successfully!'); window.location='./?ppp=secrets&session=$session';</script>";
        } else {
            echo "<script>alert('Secret not found!'); window.location='./?ppp=secrets&session=$session';</script>";
        }
    }

    // Remove secret
    elseif (!empty($removesecr)) {
        $getsecret = $API->comm("/ppp/secret/print", array("?name" => $removesecr));

        if (!empty($getsecret)) {
            $API->comm("/ppp/secret/remove", array(
                ".id" => $getsecret[0]['.id']
            ));
            echo "<script>alert('PPP Secret deleted successfully!'); window.location='./?ppp=secrets&session=$session';</script>";
        } else {
            echo "<script>alert('Secret not found!'); window.location='./?ppp=secrets&session=$session';</script>";
        }
    }

    $API->disconnect();

} else {
    echo "<script>alert('Failed to connect to MikroTik!'); window.location='./?ppp=secrets&session=$session';</script>";
}
?>
