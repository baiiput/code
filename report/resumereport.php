<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
// load session MikroTik
$session = $_GET['session'];


// load config
  include('../include/config.php');
  include('../include/readcfg.php');

$idbl = $_GET['idbl'];
// Fix: idbl format is MMYYYY (e.g., "122025" = December 2025)
// Extract 2 digits for month, 4 digits for year
$thisM = substr($idbl, 0, 2);  // First 2 chars = month "12"
$thisY = substr($idbl, -4);     // Last 4 chars = year "2025"

$ms = array(1 => "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
$mn = array_search($thisM, $ms);

// Month names for display
$monthNames = array("01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr", "05" => "May", "06" => "Jun",
                    "07" => "Jul", "08" => "Aug", "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec");
$thisMonthName = isset($monthNames[$thisM]) ? $monthNames[$thisM] : $thisM;

// https://secure.php.net/manual/en/function.cal-days-in-month.php#38666
function days_in_month($month, $year)
{
// calculate number of days in a month
return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}


if ($mn == date("n")){
  $totD =  (date('d') +1);
}else{
  $totD = (days_in_month($mn, $thisY)+ 1);
}

// OPTIMIZED: Data will be loaded via AJAX
$totalvrc = 0;
$totalincome = 0;
$totalreport = "Loading...";


}
?>

          <div class="card">
            <div class="card-header"><h3><i class="fa fa-area-chart"></i> Resume Report </h3></div>
          
              <div class="card-body">
                <div class="row">

                  <script src="./js/highcharts/highcharts.js"></script>
                  <script src="./js/highcharts/themes/hc.<?= $theme; ?>.js"></script>


<div class="col-12" id="container">
    <div style="text-align: center; padding: 100px 20px;">
        <div class="resume-spinner"></div>
        <div style="color: #b0b0b0; font-size: 14px; font-weight: 600; margin-top: 20px;">Loading Resume Report...</div>
        <div style="color: #808080; font-size: 12px;">Fetching sales data from Mikrotik</div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.resume-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(0,208,132,0.2);
    border-top-color: #00d084;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
var chartData = null;
var dataresume = "";
var totalresume = 0;
var totalvrc = 0;

// Function to calculate resume per day
function resumePerDay(date, dataresume) {
    // Split by pipe delimiter to get array: [date, price, date, price, ...]
    var records = dataresume.split('|');
    var count = 0;
    var total = 0;

    // Process pairs: records[0]=date, records[1]=price, records[2]=date, records[3]=price...
    for (var i = 0; i < records.length - 1; i += 2) {
        if (records[i] === date) {
            count++;
            var price = parseInt(records[i + 1], 10);
            if (!isNaN(price) && price > 0) {
                total += price;
            }
        }
    }

    return {
        count: count,
        total: total
    };
}

// Load resume data from AJAX
function loadResumeData() {
    var session = "<?= $session ?>";
    var idbl = "<?= $idbl ?>";
    var currency = "<?= $currency ?>";
    var thisM = "<?= $thisM ?>";
    var thisY = "<?= $thisY ?>";
    var totD = <?= $totD ?>;

    $.ajax({
        url: '../dashboard/aload.php?load=resumedata&session=' + session + '&idbl=' + idbl,
        dataType: 'json',
        timeout: 60000,
        success: function(response) {
            if (response.success) {
                dataresume = response.dataresume;
                totalresume = response.totalresume;
                totalvrc = response.totalvrc;

                // Debug logging
                console.log("Resume data loaded:");
                console.log("Total VCR:", totalvrc);
                console.log("Total Income:", totalresume);
                console.log("Sample dates from data:", response.sampleDates);
                console.log("Total records:", response.totalRecords);
                console.log("Dataresume length:", dataresume.length);

                // Generate chart data
                var chartSeries = [];
                console.log("Generating chart data for days...");
                console.log("Month (thisM):", thisM);
                console.log("Year (thisY):", thisY);
                console.log("Total days (totD):", totD);

                // Month name for display
                var monthName = "<?= $thisMonthName ?>";

                for (var i = 1; i < totD; i++) {
                    var thisD = i < 10 ? "0" + i : i.toString();
                    // FIX: Mikrotik uses ISO date format: YYYY-MM-DD
                    // Sample from data: "2025-12-10"
                    var idhr = thisY + '-' + thisM + '-' + thisD;

                    var dayData = resumePerDay(idhr, dataresume);
                    var total = dayData.total || 0;
                    var count = dayData.count || 0;

                    // Log first few days for debugging
                    if (i <= 3) {
                        console.log("Day " + i + " - Searching for date:", idhr);
                        console.log("  Found count:", count, "Total:", total);
                    }

                    chartSeries.push({
                        name: '<b>' + thisD + ' ' + monthName + ' ' + count + 'vcr</b>',
                        y: total
                    });
                }

                // Format total report
                var totalreport;
                <?php if ($currency == in_array($currency, $cekindo['indo'])) { ?>
                totalreport = 'Total ' + totalvrc + 'vcr : <?= $currency ?> ' + number_format(totalresume, 0, ",", ".");
                <?php } else { ?>
                totalreport = 'Total ' + totalvrc + 'vcr : <?= $currency ?> ' + number_format(totalresume, 2);
                <?php } ?>

                // Create chart
                createChart(chartSeries, totalreport, thisM, thisY);
            } else {
                $('#container').html('<div style="text-align:center; padding:40px; color:#ff6b6b;"><i class="fa fa-exclamation-triangle"></i> Failed to load data: ' + response.error + '<br><button class="btn btn-primary btn-sm" onclick="loadResumeData()" style="margin-top:15px;"><i class="fa fa-refresh"></i> Retry</button></div>');
            }
        },
        error: function(xhr, status, error) {
            $('#container').html('<div style="text-align:center; padding:40px; color:#ff6b6b;"><i class="fa fa-exclamation-triangle"></i> Failed to load data: ' + status + '<br><button class="btn btn-primary btn-sm" onclick="loadResumeData()" style="margin-top:15px;"><i class="fa fa-refresh"></i> Retry</button></div>');
        }
    });
}

// Create Highcharts chart
function createChart(seriesData, subtitle, monthName, thisY) {
    // Destroy existing chart first to prevent error #16
    if (window.resumeChart) {
        console.log("Destroying existing chart...");
        window.resumeChart.destroy();
    }

    // Create new chart
    window.resumeChart = Highcharts.chart('container', {
        chart: {
            height: 500,
            type: 'area',
        },
        title: {
            text: 'Selling Report ' + monthName + ' ' + thisY
        },

        subtitle: {
            text: subtitle
        },

        xAxis: {
            tickInterval: 1
        },

        yAxis: {
            title: {
                text: 'Total Sales'
            }
        },

        legend: {
            layout: 'horizontal',
            align: 'center',
            verticalAlign: 'bottom'
        },

        plotOptions: {
            series: {
                label: {
                    connectorAllowed: false
                },
                pointStart: 1
            }
        },

        series: [{
            name: 'Report',
            data: seriesData
        }],
        tooltip: {
            pointFormat: 'Total sales: <b>{point.y}</b>',
        },
        responsive: {
            rules: [{
                condition: {
                    maxWidth: 500
                },
                chartOptions: {
                    legend: {
                        layout: 'horizontal',
                        align: 'center',
                        verticalAlign: 'bottom'
                    }
                }
            }]
        }
    });
}

// Number format helper
function number_format(number, decimals, dec_point, thousands_sep) {
    decimals = decimals || 0;
    number = parseFloat(number);

    if (!isFinite(number)) {
        return '';
    }

    dec_point = dec_point || '.';
    thousands_sep = thousands_sep || ',';

    var parts = number.toFixed(decimals).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);

    return parts.join(dec_point);
}

// Load data on page ready
$(document).ready(function() {
    loadResumeData();
});

</script>
                </div>
              </div>  