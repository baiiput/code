<?php

session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $getallqueue = $API->comm("/queue/simple/print", array(
    "?dynamic" => "false",
  ));

  $getpool = $API->comm("/ip/pool/print");

  if (isset($_POST['name'])) {
    $name = (preg_replace('/\s+/', '-',$_POST['name']));
    $sharedusers = ($_POST['sharedusers']);
    $ratelimit = ($_POST['ratelimit']);
    $expmode = ($_POST['expmode']);
    $validity = ($_POST['validity']);
    $graceperiod = ($_POST['graceperiod']);
    $getprice = ($_POST['price']);
    $getsprice = ($_POST['sprice']);
    $addrpool = ($_POST['ppool']);
    if ($getprice == "") {
      $price = "0";
    } else {
      $price = $getprice;
    }
    if ($getsprice == "") {
      $sprice = "0";
    } else {
      $sprice = $getsprice;
    }
    $getlock = ($_POST['lockunlock']);
    if ($getlock == "Enable") {
      $lock = '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]';
    } else {
      $lock = "";
    }

    $randstarttime = "0".rand(1,5).":".rand(10,59).":".rand(10,59);
    $randinterval = "00:02:".rand(10,59);

    $parent = ($_POST['parent']);
    
    // === UNIVERSAL COMPATIBILITY: Deteksi RouterOS Version ===
    $rosVersion = "";
    $isROS6 = false;
    $isROS7 = false;
    
    try {
        $version = $API->comm("/system/resource/print");
        $rosVersion = isset($version[0]['version']) ? $version[0]['version'] : '';
        
        if (strpos($rosVersion, '6.') === 0) {
            $isROS6 = true;
        } elseif (strpos($rosVersion, '7.') === 0) {
            $isROS7 = true;
        } else {
            // Default to ROS 6 format for unknown versions
            $isROS6 = true;
        }
    } catch (Exception $e) {
        // Default to ROS 6 format if detection fails
        $isROS6 = true;
    }
    
    // === COMMON LOGIC - DEFINE MODE FIRST ===
    $mode = "remove"; // Default mode
    
    if ($expmode == "rem") {
      $mode = "remove";
    } elseif ($expmode == "ntf") {
      $mode = "set limit-uptime=1s";
    } elseif ($expmode == "remc") {
      $mode = "remove";
    } elseif ($expmode == "ntfc") {
      $mode = "set limit-uptime=1s";
    }
    
    // === GENERATE SCRIPTS BASED ON ROS VERSION ===
    
    if ($isROS6) {
        // ========== ROS 6.x FORMAT ==========
        $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-'.$price.'-|-$address-|-$mac-|-' . $validity . '-|-'.$name.'-|-$comment" owner="$month$year" source="$date" comment="mikhmon"';
        
        $onlogin = ':put (",'.$expmode.',' . $price . ',' . $validity . ','.$sprice.',,' . $getlock . ',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]';

        // Fixed bgservice for ROS 6.x - corrected time parsing
        $bgservice = ':local dateint do={:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );:local days [ :pick $d 4 6 ];:local month [ :pick $d 0 3 ];:local year [ :pick $d 7 11 ];:local monthint ([ :find $montharray $month]);:local month ($monthint + 1);:if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="'.$name.'" ] do={ :local comment [ /ip hotspot user get $i comment]; :local username [ /ip hotspot user get $i name]; :if ([:len $comment] > 10) do={ :local gettime [:pic $comment 12 20]; :if ([:pic $comment 3] = "/" and [:pic $comment 6] = "/") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user '.$mode.' $i ]; [ /ip hotspot active remove [find where user=$username] ];};}}}';
        
    } else {
        // ========== ROS 7.x FORMAT ==========
        $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-'.$price.'-|-$address-|-$mac-|-' . $validity . '-|-'.$name.'-|-$comment" owner="$month$year" source="$date" comment="mikhmon"';
        
        $onlogin = ':put (",'.$expmode.',' . $price . ',' . $validity . ','.$sprice.',,' . $getlock . ',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 0 4 ];:local month [ :pick $date 5 7 ]; /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]';

        // Fixed bgservice for ROS 7.x - corrected time parsing and date format
        $bgservice = ':local dateint do={:local montharray ( "01","02","03","04","05","06","07","08","09","10","11","12" );:local days [ :pick $d 8 10 ];:local month [ :pick $d 5 7 ];:local year [ :pick $d 0 4 ];:local monthint ([ :find $montharray $month]);:local monthnum ($monthint + 1);:if ( [len $monthnum] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$monthnum$days")];} else={:return [:tonum ("$year$monthnum$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="'.$name.'" ] do={ :local comment [ /ip hotspot user get $i comment]; :local username [ /ip hotspot user get $i name]; :if ([:len $comment] > 10) do={ :local gettime [:pic $comment 11 19]; :if ([:pic $comment 4] = "-" and [:pic $comment 7] = "-") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user '.$mode.' $i ]; [ /ip hotspot active remove [find where user=$username] ];};}}}';
    }
    
    // === FINALIZE ONLOGIN SCRIPT ===
    
    if ($expmode == "rem") {
      $onlogin = $onlogin . $lock . "}}";
    } elseif ($expmode == "ntf") {
      $onlogin = $onlogin . $lock . "}}";
    } elseif ($expmode == "remc") {
      $onlogin = $onlogin . $record . $lock . "}}";
    } elseif ($expmode == "ntfc") {
      $onlogin = $onlogin . $record . $lock . "}}";
    } elseif ($expmode == "0" && $price != "") {
      $onlogin = ':put (",,' . $price . ',,,noexp,' . $getlock . ',")' . $lock;
    } else {
      $onlogin = "";
    }

    // === CREATE USER PROFILE ===
    $API->comm("/ip/hotspot/user/profile/add", array(
      /*"add-mac-cookie" => "yes",*/
      "name" => "$name",
      "address-pool" => "$addrpool",
      "rate-limit" => "$ratelimit",
      "shared-users" => "$sharedusers",
      "status-autorefresh" => "1m",
      //"transparent-proxy" => "yes",
      "on-login" => "$onlogin",
      "parent-queue" => "$parent",
    ));

    // === CREATE SCHEDULER ===
    if($expmode != "0"){
      if (empty($monid)){
        $API->comm("/system/scheduler/add", array(
          "name" => "$name",
          "start-time" => "$randstarttime",
          "interval" => "$randinterval",
          "on-event" => "$bgservice",
          "disabled" => "no",
          "comment" => "Monitor Profile $name (ROS " . ($isROS6 ? "6.x" : "7.x") . " Compatible)",
          ));
      }else{
      $API->comm("/system/scheduler/set", array(
        ".id" => "$monid",
        "name" => "$name",
        "start-time" => "$randstarttime",
        "interval" => "$randinterval",
        "on-event" => "$bgservice",
        "disabled" => "no",
        "comment" => "Monitor Profile $name (ROS " . ($isROS6 ? "6.x" : "7.x") . " Compatible)",
        ));
      }}else{
        $API->comm("/system/scheduler/remove", array(
          ".id" => "$monid"));
      }

    $getprofile = $API->comm("/ip/hotspot/user/profile/print", array(
      "?name" => "$name",
    ));
    $pid = $getprofile[0]['.id'];
    echo "<script>window.location='./?user-profile=" . $pid . "&session=" . $session . "'</script>";
  }
}
?>
<div class="row">
<div class="col-8">
<div class="card box-bordered">
  <div class="card-header">
    <h3><i class="fa fa-plus"></i> <?= $_add.' '.$_user_profile ?> 
    <small style="color: #888; font-size: 12px;">
      <?php 
      // Display RouterOS version compatibility
      try {
          $version = $API->comm("/system/resource/print");
          $rosVersion = isset($version[0]['version']) ? $version[0]['version'] : 'Unknown';
          echo "🔧 Compatible with ROS " . htmlspecialchars($rosVersion);
      } catch (Exception $e) {
          echo "🔧 Universal ROS 6.x & 7.x Compatible";
      }
      ?>
    </small>
    <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> Processing... </i></small></h3>
  </div>
  <div class="card-body">
<form autocomplete="off" method="post" action="">
  <div>
    <a class="btn bg-warning" href="./?hotspot=user-profiles&session=<?= $session; ?>"> <i class="fa fa-close btn-mrg"></i> <?= $_close ?></a>
    <button type="submit" name="save" class="btn bg-primary btn-mrg" ><i class="fa fa-save btn-mrg"></i> <?= $_save ?></button>
  </div>
<table class="table">
  <tr>
    <td class="align-middle"><?= $_name ?></td><td><input class="form-control" type="text" onchange="remSpace();" autocomplete="off" name="name" value="" required="1" autofocus></td>
  </tr>
  <tr>
    <td class="align-middle">Address Pool</td>
    <td>
    <select class="form-control " name="ppool">
      <option>none</option>
        <?php $TotalReg = count($getpool);
        for ($i = 0; $i < $TotalReg; $i++) {

          echo "<option>" . $getpool[$i]['name'] . "</option>";
        }
        ?>
    </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle">Shared Users</td><td><input class="form-control" type="text" size="4" autocomplete="off" name="sharedusers" value="1" required="1"></td>
  </tr>
  <tr>
    <td class="align-middle">Rate limit [up/down]</td><td><input class="form-control" type="text" name="ratelimit" autocomplete="off" value="" placeholder="Example : 512k/1M" ></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_expired_mode ?></td><td>
      <select class="form-control" onchange="RequiredV();" id="expmode" name="expmode" required="1">
        <option value="">Select...</option>
        <option value="0">None</option>
        <option value="rem">Remove</option>
        <option value="ntf">Notice</option>
        <option value="remc">Remove & Record</option>
        <option value="ntfc">Notice & Record</option>
      </select>
    </td>
  </tr>
  <tr id="validity" style="display:none;">
    <td class="align-middle"><?= $_validity ?></td><td><input class="form-control" type="text" id="validi" size="4" autocomplete="off" name="validity" value="" required="1"></td>
  </tr>
  <tr id="graceperiod" style="display:none;">
    <td class="align-middle"><?= $_grace_period ?></td><td><input class="form-control" type="text" id="gracepi" size="4" autocomplete="off" name="graceperiod" placeholder="5m" value="5m" required="1"></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_price.' '.$currency; ?></td><td><input class="form-control" type="text" size="10" min="0" name="price" value="" ></td>
  </tr>
  <tr>
    <td class="align-middle"><?= $_selling_price.' '.$currency; ?></td><td><input class="form-control" type="text" size="10" min="0" name="sprice" value="" ></td>
  </tr>
  <tr>
    <td><?= $_lock_user ?></td><td>
      <select class="form-control" id="lockunlock" name="lockunlock" required="1">
        <option value="Disable">Disable</option>
        <option value="Enable">Enable</option>
      </select>
    </td>
  </tr>
  <tr>
    <td class="align-middle">Parent Queue</td>
    <td>
    <select class="form-control " name="parent">
      <option>none</option>
        <?php $TotalReg = count($getallqueue);
        for ($i = 0; $i < $TotalReg; $i++) {

          echo "<option>" . $getallqueue[$i]['name'] . "</option>";
        }
        ?>
    </select>
  </td>
  </tr>
</table>
</form>
</div>
</div>
</div>
<div class="col-4">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-book"></i> <?= $_readme ?></h3>
    </div>
    <div class="card-body">
<table class="table">
    <tr>
    <td colspan="2">
      <p style="padding:0px 5px;">
        <?= $_details_user_profile ?>
      </p>
      <p style="padding:0px 5px;">
        <?= $_format_validity ?>
      </p>
      
      <!-- Compatibility Info -->
      <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 5px; padding: 10px; margin-top: 15px;">
        <h6 style="color: #28a745; margin: 0 0 8px 0;"><i class="fa fa-check-circle"></i> Universal Compatibility</h6>
        <p style="margin: 0; font-size: 12px; color: #6c757d;">
          🔧 <strong>Auto-detects RouterOS version</strong><br>
          ✅ Supports ROS 6.49 (jun/22/2025 format)<br>
          ✅ Supports ROS 7.19 (2025-06-22 format)<br>
          🎯 No need for separate files!
        </p>
        
        <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px;">
          <strong style="color: #856404;">🧪 Testing Checklist:</strong>
          <ol style="margin: 5px 0; padding-left: 20px; font-size: 11px; color: #856404;">
            <li>Create profile dengan "Remove & Record"</li>
            <li>Generate user dengan profile tersebut</li>
            <li>Login sebagai user (comment akan ter-set)</li>
            <li>Tunggu expired time atau ubah waktu router</li>
            <li>Check scheduler: /system scheduler print</li>
            <li>User harus terhapus otomatis saat expired</li>
          </ol>
        </div>
      </div>
    </td>
  </tr>
</table>
</div>
</div>
</div>
</div>
<script type="text/javascript">
function remSpace() {
  var upName = document.getElementsByName("name")[0];
  var newUpName = upName.value.replace(/\s/g, "-");
  //alert("<?php if ($currency == in_array($currency, $cekindo['indo'])) {
            echo "Nama Profile tidak boleh berisi spasi";
          } else {
            echo "Profile name can't containing white space!";
          } ?>");
  upName.value = newUpName;
  upName.focus();
}
</script>