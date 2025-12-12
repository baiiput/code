<?php

session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
// load session MikroTik
  $session = $_GET['session'];
// set  timezone
date_default_timezone_set($_SESSION['timezone']);

// lang
include('../include/lang.php');
include('../lang/'.$langid.'.php');

// load config
  include('../include/config.php');
  include('../include/readcfg.php');

// routeros api
  include_once('../lib/routeros_api.class.php');
  include_once('../lib/formatbytesbites.php');
  $API = new RouterosAPI();
  $API->debug = false;
  $API->connect($iphost, $userhost, decrypt($passwdhost));

  if ($livereport == "disable") {
    $logh = "457px";
    $lreport = "style='display:none;'";
  } else {
    $logh = "350px";
    $lreport = "style='display:block;'";
// get selling report
    $thisD = date("d");
    $thisM = date("m");
    $thisY = date("Y");

    if (strlen($thisD) == 1) {
      $thisD = "0" . $thisD;
    } else {
      $thisD = $thisD;
    }

    $idhr = $thisY . "-" . $thisM . "-" . $thisD;
    $idbl = $thisM . $thisY;

    $_SESSION[$session.'idhr'] = $idhr;

    $getSRBl = $API->comm("/system/script/print", array(
      "?owner" => "$idbl",
    ));
    $TotalRBl = count($getSRBl);
    $_SESSION[$session.'totalBl'] = $TotalRBl;

    foreach($getSRBl as $row){
    
      if((explode("-|-", $row['name'])[0]) == $idhr){
         $tHr += explode("-|-", $row['name'])[3];
         $TotalRHr += count((array)$row['source']); /*Modif line add (array) by github https://github.com/MasKawer*/
 
       }
       $tBl += explode("-|-", $row['name'])[3];

      if($TotalRHr == ""){
        $TotalRHr = "0";
        $_SESSION[$session.'totalHr'] = "0";
      }else{
        $_SESSION[$session.'totalHr'] = $TotalRHr;
      }
      
    }
  }
}
?>

<!-- Enhanced Income Display with HORIZONTAL Layout - FIXED -->
<div id="r_4" class="income-display-enhanced">
    <div id="reloadLreport" class="income-content">
        <?php if ($livereport != "disable") { ?>
            <?php 
            if ($currency == in_array($currency, $cekindo['indo'])) {
                $dincome = number_format((float)$tHr, 0, ",", ".");
                $mincome = number_format((float)$tBl, 0, ",", ".");
                $_SESSION[$session.'dincome'] = $dincome;
                $_SESSION[$session.'mincome'] = $mincome;
            }else{
                $dincome = number_format((float)$tHr, 2);
                $mincome = number_format((float)$tBl, 2);
                $_SESSION[$session.'dincome'] = $dincome;
                $_SESSION[$session.'mincome'] = $mincome;
            }
            ?>
            
            <!-- FIXED: HORIZONTAL Layout CSS yang konsisten dengan home.php -->
            <style id="income-report-horizontal-styles">
            /* HORIZONTAL Layout - Konsisten dengan home.php */
            .income-display-enhanced {
                padding: 12px !important;
                position: relative !important;
            }
            
            /* KUNCI: Layout HORIZONTAL - Grid 2 kolom */
            .income-content {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 15px !important;
                position: relative !important;
                z-index: 1 !important;
            }
            
            .income-stat-card {
                background: #383838 !important;
                border: 1px solid #404040 !important;
                border-radius: 10px !important;
                padding: 16px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                position: relative !important;
                overflow: hidden !important;
                transition: all 0.3s ease !important;
                min-height: 100px !important;
                box-sizing: border-box !important;
            }
            
            .income-stat-card:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 8px 20px rgba(0,0,0,0.3) !important;
            }
            
            .income-stat-card::before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 3px !important;
            }
            
            .today-card::before {
                background: linear-gradient(90deg, #00d084 0%, #4a9eff 100%) !important;
            }
            
            .month-card::before {
                background: linear-gradient(90deg, #4a9eff 0%, #ff9500 100%) !important;
            }
            
            .income-stat-icon {
                width: 40px !important;
                height: 40px !important;
                border-radius: 10px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 18px !important;
                margin-bottom: 12px !important;
                flex-shrink: 0 !important;
            }
            
            .today-card .income-stat-icon {
                background: linear-gradient(135deg, #00d084 0%, #00b574 100%) !important;
                color: white !important;
            }
            
            .month-card .income-stat-icon {
                background: linear-gradient(135deg, #4a9eff 0%, #3a8ae8 100%) !important;
                color: white !important;
            }
            
            .income-stat-content {
                flex: 1 !important;
                width: 100% !important;
                text-align: center !important;
            }
            
            .income-stat-label {
                font-size: 11px !important;
                color: #888888 !important;
                text-transform: uppercase !important;
                margin-bottom: 6px !important;
                letter-spacing: 0.5px !important;
                font-weight: 600 !important;
            }
            
            .income-stat-value {
                font-size: 18px !important;
                font-weight: 700 !important;
                color: #ffffff !important;
                margin-bottom: 4px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            
            .income-stat-count {
                font-size: 10px !important;
                color: #b0b0b0 !important;
            }
            
            .income-trend {
                position: absolute !important;
                top: 12px !important;
                right: 12px !important;
                font-size: 12px !important;
                opacity: 0.8 !important;
            }
            
            .income-trend.up {
                color: #00d084 !important;
            }
            
            .income-trend.stable {
                color: #4a9eff !important;
            }
            
            /* Loading state styles */
            .income-loading {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 12px !important;
                padding: 20px !important;
                color: #888888 !important;
                grid-column: 1 / -1 !important;
            }
            
            .loading-spinner {
                width: 24px !important;
                height: 24px !important;
                border: 2px solid #404040 !important;
                border-top: 2px solid #00d084 !important;
                border-radius: 50% !important;
                animation: incomeSpinner 1s linear infinite !important;
            }
            
            @keyframes incomeSpinner {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .loading-text {
                text-align: center !important;
            }
            
            .loading-title {
                font-size: 12px !important;
                font-weight: 600 !important;
                color: #ffffff !important;
                margin-bottom: 4px !important;
            }
            
            .loading-subtitle {
                font-size: 10px !important;
                color: #888888 !important;
            }
            
            /* Responsive - KONSISTEN dengan home.php */
            @media (max-width: 1400px) and (min-width: 1200px) {
                .income-stat-card {
                    padding: 6px !important;
                    gap: 6px !important;
                    min-height: 45px !important;
                }
                
                .income-stat-icon {
                    width: 22px !important;
                    height: 22px !important;
                    font-size: 9px !important;
                    margin-bottom: 4px !important;
                }
                
                .income-stat-value {
                    font-size: 11px !important;
                }
                
                .income-stat-label {
                    font-size: 7px !important;
                    margin-bottom: 2px !important;
                }
                
                .income-stat-count {
                    font-size: 6px !important;
                }
                
                .income-trend {
                    font-size: 8px !important;
                }
            }
            
            @media (min-width: 1401px) and (max-width: 2000px) {
                .income-stat-card {
                    padding: 14px !important;
                    gap: 14px !important;
                    min-height: 70px !important;
                }
                
                .income-stat-icon {
                    width: 32px !important;
                    height: 32px !important;
                    font-size: 14px !important;
                }
                
                .income-stat-value {
                    font-size: 16px !important;
                }
            }
            
            @media (min-width: 2001px), (min-width: 1400px) and (-webkit-min-device-pixel-ratio: 2) {
                .income-stat-card {
                    padding: 16px !important;
                    gap: 16px !important;
                    min-height: 80px !important;
                }
                
                .income-stat-icon {
                    width: 36px !important;
                    height: 36px !important;
                    font-size: 16px !important;
                }
                
                .income-stat-value {
                    font-size: 18px !important;
                }
                
                .income-stat-label {
                    font-size: 11px !important;
                }
            }
            
            @media (max-width: 768px) {
                .income-content {
                    grid-template-columns: 1fr !important;
                    gap: 10px !important;
                }
                
                .income-stat-card {
                    padding: 12px !important;
                    min-height: 60px !important;
                }
                
                .income-stat-icon {
                    width: 28px !important;
                    height: 28px !important;
                    font-size: 12px !important;
                }
                
                .income-stat-value {
                    font-size: 14px !important;
                }
            }
            </style>
            
            <?php
            // STRUKTUR HTML yang konsisten dengan home.php
            echo '<div class="income-stat-card today-card">
                    <div class="income-stat-icon">
                        <i class="fa fa-calendar-o"></i>
                    </div>
                    <div class="income-stat-content">
                        <div class="income-stat-label">' . $_today . '</div>
                        <div class="income-stat-value">' . $currency . ' ' . $dincome . '</div>
                        <div class="income-stat-count">' . $TotalRHr . ' vouchers sold</div>
                    </div>
                    <div class="income-trend up">
                        <i class="fa fa-arrow-up"></i>
                    </div>
                  </div>
                  <div class="income-stat-card month-card">
                    <div class="income-stat-icon">
                        <i class="fa fa-bar-chart"></i>
                    </div>
                    <div class="income-stat-content">
                        <div class="income-stat-label">' . $_this_month . '</div>
                        <div class="income-stat-value">' . $currency . ' ' . $mincome . '</div>
                        <div class="income-stat-count">' . $TotalRBl . ' total vouchers</div>
                    </div>
                    <div class="income-trend stable">
                        <i class="fa fa-line-chart"></i>
                    </div>
                  </div>';
            ?>
            
        <?php } else { ?>
            <div class="income-loading">
                <div class="loading-spinner"></div>
                <div class="loading-text">
                    <div class="loading-title">Live report is disabled</div>
                    <div class="loading-subtitle">Please enable live reporting</div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>