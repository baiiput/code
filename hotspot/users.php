<?php
// FORCE: Always load ALL users, ignore URL parameters
error_reporting(0);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M'); // Increase memory limit

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
  // OPTIMIZED: Don't load users here - will be loaded via AJAX
  // This makes the page load instantly
  $getuser = array();
  $TotalReg = 0;
  $counttuser = 0;

  // Profile list will still be needed for filter dropdown - minimal load
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $getprofile = $API->comm("/ip/hotspot/user/profile/print");
    $TotalReg2 = count($getprofile);
    $API->disconnect();
  } else {
    $getprofile = array();
    $TotalReg2 = 0;
  }

  // Force variables to ensure no backend filtering
  $prof = "all";
  $comm = "";
  $exp = "";
}
?>

<!-- ==================== OPTIMIZED CSS ==================== -->
<style>
/* ==================== SIMPLIFIED HEADER STYLING ==================== */
.card-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    border: none !important;
    border-radius: 20px 20px 0 0 !important;
    padding: 20px 25px !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.card-header h3 {
    color: #ffffff !important;
    font-weight: 700 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 15px !important;
}

.card-header h3 i {
    font-size: 24px !important;
    color: #3498db !important;
}

.card-header a {
    color: #ecf0f1 !important;
    text-decoration: none !important;
    padding: 8px 15px !important;
    border-radius: 25px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    transition: all 0.3s ease !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 0 4px !important;
}

.card-header a:hover {
    background: rgba(52, 152, 219, 0.3) !important;
    border-color: rgba(52, 152, 219, 0.5) !important;
    color: #ffffff !important;
}

/* ==================== OPTIMIZED FILTER BOX ==================== */
.filter-container {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%) !important;
    border-radius: 18px !important;
    padding: 20px !important;
    margin: 20px 0 !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.input-group {
    gap: 8px !important;
    display: flex !important;
    flex-wrap: nowrap !important;
}

.input-group-4 {
    flex: 1 !important;
    min-width: 180px !important;
}

#filterTable, #profileSelect, #commentSelect {
    background: rgba(52, 58, 70, 0.9) !important;
    border: 2px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 12px !important;
    color: #ffffff !important;
    padding: 12px 18px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    transition: border-color 0.3s ease !important;
    width: 100% !important;
}

#filterTable::placeholder {
    color: rgba(255, 255, 255, 0.6) !important;
}

#filterTable:focus, #profileSelect:focus, #commentSelect:focus {
    outline: none !important;
    border-color: #3498db !important;
}

#profileSelect, #commentSelect {
    appearance: none !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 16 16'%3e%3cpath d='m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 12px !important;
    cursor: pointer !important;
}

.btn {
    border: none !important;
    border-radius: 12px !important;
    padding: 10px 16px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 0 4px !important;
}

.btn.bg-primary {
    background: linear-gradient(135deg, #3498db, #2980b9) !important;
    color: #ffffff !important;
}

.btn.bg-red {
    background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    color: #ffffff !important;
}

.btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
}

/* ==================== TABLE STYLING ==================== */
.table-container {
    background: rgba(40, 44, 52, 0.95) !important;
    border-radius: 20px !important;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    margin-top: 20px !important;
    overflow: hidden;
}

#dataTable {
    margin: 0 !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    background: rgba(40, 44, 52, 0.95) !important;
    width: 100%;
}

#dataTable thead th {
    background: linear-gradient(135deg, #0f4c75, #3282b8) !important;
    color: white !important;
    border: none !important;
    padding: 15px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    font-size: 12px !important;
    position: sticky;
    top: 0;
    z-index: 10;
}

#dataTable tbody tr {
    background: rgba(52, 58, 70, 0.8) !important;
    color: #ffffff !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    transition: background-color 0.2s ease !important;
}

#dataTable tbody tr:nth-child(even) {
    background: rgba(45, 52, 64, 0.9) !important;
}

#dataTable tbody tr:hover {
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(41, 128, 185, 0.2)) !important;
}

#dataTable tbody td {
    padding: 12px 15px !important;
    border: none !important;
    vertical-align: middle !important;
    color: #ffffff !important;
}

/* ==================== MODERN LOADING SUITE ==================== */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(13, 17, 23, 0.95), rgba(22, 27, 34, 0.98));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    animation: fadeInModern 0.4s ease-out;
}

@keyframes fadeInModern {
    from {
        opacity: 0;
        backdrop-filter: blur(0px);
    }
    to {
        opacity: 1;
        backdrop-filter: blur(10px);
    }
}

.loading-container {
    background: linear-gradient(145deg, #1a1d23, #2d3339);
    border: 1px solid rgba(79, 172, 254, 0.2);
    border-radius: 24px;
    padding: 50px;
    text-align: center;
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
    min-width: 380px;
    max-width: 450px;
}

.loading-container::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, 
        transparent 30%, 
        rgba(79, 172, 254, 0.3) 50%, 
        transparent 70%);
    border-radius: 26px;
    z-index: -1;
    animation: borderScan 3s linear infinite;
}

@keyframes borderScan {
    0% { transform: translateX(-100%) rotate(0deg); }
    100% { transform: translateX(100%) rotate(360deg); }
}

.loading-header {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 30px;
}

.loading-icon {
    width: 60px;
    height: 60px;
    border: 3px solid rgba(79, 172, 254, 0.2);
    border-top: 3px solid #4facfe;
    border-radius: 50%;
    animation: modernSpin 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
    margin-right: 15px;
}

@keyframes modernSpin {
    0% {
        transform: rotate(0deg);
        border-top-color: #4facfe;
    }
    25% {
        border-top-color: #00f2fe;
    }
    50% {
        transform: rotate(180deg);
        border-top-color: #4facfe;
    }
    75% {
        border-top-color: #00f2fe;
    }
    100% {
        transform: rotate(360deg);
        border-top-color: #4facfe;
    }
}

.loading-status {
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    text-align: left;
}

.loading-message {
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    margin: 10px 0 25px 0;
    font-weight: 400;
    text-align: left;
    min-height: 20px;
}

.loading-progress-container {
    width: 100%;
    margin-bottom: 25px;
}

.loading-progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(79, 172, 254, 0.1);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
}

.loading-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    border-radius: 3px;
    position: relative;
    animation: progressPulse 2s ease-in-out infinite;
}

.loading-progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 255, 255, 0.4) 50%, 
        transparent 100%);
    animation: progressShine 2s ease-in-out infinite;
}

@keyframes progressPulse {
    0%, 100% { box-shadow: 0 0 5px rgba(79, 172, 254, 0.3); }
    50% { box-shadow: 0 0 20px rgba(79, 172, 254, 0.6), 0 0 30px rgba(0, 242, 254, 0.3); }
}

@keyframes progressShine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.loading-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 20px;
}

.loading-dot {
    width: 8px;
    height: 8px;
    background: #4facfe;
    border-radius: 50%;
    animation: dotPulse 1.4s ease-in-out infinite;
}

.loading-dot:nth-child(1) { animation-delay: 0s; }
.loading-dot:nth-child(2) { animation-delay: 0.2s; }
.loading-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes dotPulse {
    0%, 60%, 100% {
        transform: scale(1);
        opacity: 0.4;
    }
    30% {
        transform: scale(1.4);
        opacity: 1;
        box-shadow: 0 0 20px rgba(79, 172, 254, 0.8);
    }
}

.loading-stats {
    display: flex;
    justify-content: space-between;
    color: rgba(255, 255, 255, 0.6);
    font-size: 12px;
    margin-top: 15px;
}

.loading-time {
    color: #4facfe;
}

.loading-operation {
    color: #00f2fe;
}

/* ==================== LOADING VARIANTS ==================== */
.loading-spinner-modern {
    width: 40px;
    height: 40px;
    border: 2px solid rgba(79, 172, 254, 0.1);
    border-left: 2px solid #4facfe;
    border-radius: 50%;
    animation: modernSpinSmall 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes modernSpinSmall {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-pulse-ring {
    width: 50px;
    height: 50px;
    border: 2px solid #4facfe;
    border-radius: 50%;
    margin: 0 auto 20px;
    animation: pulseRing 2s ease-in-out infinite;
}

@keyframes pulseRing {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(79, 172, 254, 0.7);
    }
    70% {
        transform: scale(1.1);
        box-shadow: 0 0 0 20px rgba(79, 172, 254, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(79, 172, 254, 0);
    }
}

/* ==================== RESPONSIVE MODERN LOADING ==================== */
@media (max-width: 768px) {
    .loading-container {
        padding: 40px 30px;
        min-width: 320px;
        max-width: 90vw;
    }
    
    .loading-icon {
        width: 50px;
        height: 50px;
        margin-right: 12px;
    }
    
    .loading-status {
        font-size: 16px;
    }
    
    .loading-message {
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .loading-container {
        padding: 35px 25px;
        min-width: 280px;
    }
    
    .loading-header {
        flex-direction: column;
        margin-bottom: 25px;
    }
    
    .loading-icon {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .loading-status {
        font-size: 15px;
        text-align: center;
    }
    
    .loading-message {
        text-align: center;
        font-size: 12px;
    }
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: rgba(45, 52, 64, 0.9);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0 0 20px 20px;
}

.pagination-info {
    color: #ffffff;
    font-size: 14px;
}

.pagination-controls {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination-btn {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pagination-btn:disabled {
    background: rgba(255,255,255,0.1);
    cursor: not-allowed;
    opacity: 0.5;
}

.pagination-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
}

/* ==================== PERFORMANCE OPTIMIZATIONS ==================== */
.table-row-hidden {
    display: none !important;
}

.table-row-visible {
    display: table-row !important;
}

/* ==================== RESPONSIVE DESIGN ==================== */
@media (max-width: 768px) {
    .input-group {
        flex-direction: column;
    }
    
    .input-group-4 {
        min-width: 100%;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 10px;
    }
}

/* ==================== PERFORMANCE OPTIMIZATIONS ==================== */
.table-row-hidden {
    display: none !important;
}

.table-row-visible {
    display: table-row !important;
}

/* ==================== NOTIFICATION STYLING ==================== */
.filter-notification {
    background: rgba(52, 152, 219, 0.1) !important;
    border: 1px solid rgba(52, 152, 219, 0.3) !important;
    border-radius: 8px !important;
    padding: 8px 12px !important;
    margin: 5px 0 !important;
    color: #3498db !important;
    font-size: 12px !important;
    display: none;
}

.filter-notification.show {
    display: block !important;
}

/* ==================== QUICK COMMENT FILTERS ==================== */
.quick-filters-container {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    border-radius: 12px !important;
    padding: 15px !important;
    margin: 10px 0 !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.quick-filters-list {
    display: flex !important;
    flex-direction: column !important;
    gap: 4px !important;
    max-height: 180px !important;
    overflow-y: auto !important;
    padding-right: 3px !important;
}

.comment-filter-item {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    background: rgba(52, 58, 70, 0.6) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 6px !important;
    padding: 8px 12px !important;
    color: #ffffff !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    position: relative !important;
}

.comment-filter-item:hover {
    background: rgba(52, 152, 219, 0.2) !important;
    border-color: rgba(52, 152, 219, 0.4) !important;
    transform: translateX(3px) !important;
}

.comment-filter-item.active {
    background: linear-gradient(135deg, #3498db, #2980b9) !important;
    border-color: #3498db !important;
    box-shadow: 0 0 10px rgba(52, 152, 219, 0.3) !important;
}

.comment-filter-item.active::before {
    content: '●' !important;
    position: absolute !important;
    left: 6px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 6px !important;
    color: #ffffff !important;
}

.comment-filter-item.active .comment-info {
    padding-left: 12px !important;
}

.comment-info {
    display: flex !important;
    align-items: center !important;
    flex: 1 !important;
    transition: padding 0.2s ease !important;
}

.comment-name {
    font-weight: 600 !important;
    font-size: 13px !important;
    color: #ffffff !important;
}

.comment-filter-item.active .comment-name {
    color: #ffffff !important;
}

.comment-count-badge {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    padding: 2px 6px !important;
    border-radius: 10px !important;
    font-size: 10px !important;
    font-weight: 600 !important;
    min-width: 25px !important;
    text-align: center !important;
}

.comment-filter-item.active .comment-count-badge {
    background: rgba(255, 255, 255, 0.3) !important;
}

.no-comments-message {
    text-align: center !important;
    color: rgba(255, 255, 255, 0.6) !important;
    font-style: italic !important;
    padding: 20px 15px !important;
    background: rgba(52, 58, 70, 0.3) !important;
    border-radius: 6px !important;
    border: 2px dashed rgba(255, 255, 255, 0.2) !important;
    font-size: 12px !important;
}

.clear-comment-btn {
    background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    border: none !important;
    border-radius: 6px !important;
    padding: 6px 12px !important;
    color: #ffffff !important;
    cursor: pointer !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
    margin-top: 10px !important;
    width: 100% !important;
}

.clear-comment-btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3) !important;
}

/* ==================== SCROLLBAR STYLING ==================== */
.quick-filters-list::-webkit-scrollbar {
    width: 4px !important;
}

.quick-filters-list::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1) !important;
    border-radius: 2px !important;
}

.quick-filters-list::-webkit-scrollbar-thumb {
    background: rgba(52, 152, 219, 0.6) !important;
    border-radius: 2px !important;
}

.quick-filters-list::-webkit-scrollbar-thumb:hover {
    background: rgba(52, 152, 219, 0.8) !important;
}

/* ==================== RESPONSIVE FOR QUICK FILTERS ==================== */
@media (max-width: 768px) {
    .comment-filter-item {
        padding: 10px 12px !important;
    }
    
    .comment-name {
        font-size: 13px !important;
    }
    
    .comment-subtitle {
        font-size: 10px !important;
    }
    
    .comment-count-badge {
        font-size: 10px !important;
        padding: 3px 6px !important;
    }
}
</style>
<!-- ==================== END CSS ==================== -->

<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header">
    <h3><i class="fa fa-users"></i> <?= $_users ?>
      <span style="font-size: 14px">
        <?php
        if ($counttuser == 0) {
          echo "<script>window.location='./?hotspot=users&profile=all&session=" . $session . "</script>";
        } ?>
         &nbsp; | &nbsp; <a href="./?hotspot-user=add&session=<?= $session; ?>" title="Add User"><i class="fa fa-user-plus"></i> <?= $_add ?></a>
        &nbsp; | &nbsp; <a href="./?hotspot-user=generate&session=<?= $session; ?>" title="Generate User"><i class="fa fa-users"></i> <?= $_generate ?></a>
         &nbsp; | &nbsp; <a href="<?= str_replace("=users", "=export-users", $url); ?>&export=script" title="Download User List as Mikrotik Script"><i class="fa fa-download"></i> Script</a>&nbsp; | &nbsp; <a href="<?= str_replace("=users", "=export-users", $url); ?>&export=csv" title="Download User List as CSV"><i class="fa fa-download"></i> CSV</a>
        </span>  &nbsp;
        <small id="loader" style="display: none;" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></small>
    </h3>
</div>

<div class="card-body">
  <div class="row filter-container">
   <div class="col-6 pd-t-5 pd-b-5">
  <div class="input-group">
    <div class="input-group-4 col-box-4">
      <input id="filterTable" type="text" class="group-item group-item-l" placeholder="<?= $_search ?>">
    </div>
    <div class="input-group-4 col-box-4">
      <select class="group-item group-item-m" id="profileSelect" title="Filter by Profile">
        <option value="all"><?= $_show_all ?></option>
        <?php
        for ($i = 0; $i < $TotalReg2; $i++) {
          $profile = $getprofile[$i];
          echo "<option value='" . $profile['name'] . "'>" . $profile['name'] . "</option>";
        }
        ?>
      </select>
    </div>
    <div class="input-group-4 col-box-4">
      <select class="group-item group-item-r" id="commentSelect" title="Filter by Comment">
        <option value=""><?= $_comment ?></option>
      </select>
    </div>
  </div>
  <!-- Profile-Comment Filter Notification -->
  <div id="filterNotification" class="filter-notification">
    <i class="fa fa-info-circle"></i> <span id="filterNotificationText"></span>
  </div>
  </div>
 
  <div class="col-6">
    <button class="btn bg-red" id="deleteByComment" style="display: none;" title="Remove users by comment">
      <i class="fa fa-trash"></i> <?= $_by_comment ?>
    </button>
    
    <script>
      function printV(a,b){
        var comm = document.getElementById('commentSelect').value;
        var profile = document.getElementById('profileSelect').value;
        var url = "./voucher/print.php?id="+comm+"&"+a+"="+b+"&session=<?= $session; ?>";
        
        if (comm === "" ){
          <?php if ($currency == in_array($currency, $cekindo['indo'])) { ?>
          alert('Silakan pilih salah satu Comment terlebih dulu!');
          <?php } else { ?>
          alert('Please choose one of the Comments first!');
          <?php } ?>
        } else if (profile === "all") {
          <?php if ($currency == in_array($currency, $cekindo['indo'])) { ?>
          alert('Silakan pilih Profile terlebih dulu sebelum memilih Comment!');
          <?php } else { ?>
          alert('Please choose a Profile first before selecting Comment!');
          <?php } ?>
        } else {
          var win = window.open(url, '_blank');
          win.focus();
        }
      }
    </script>
    <button class="btn bg-primary" title='Print' onclick="printV('qr','no');"><i class="fa fa-print"></i> <?= $_print_default ?></button>
    <button class="btn bg-primary" title='Print QR' onclick="printV('qr','yes');"><i class="fa fa-print"></i> <?= $_print_qr ?></button>
    <button class="btn bg-primary" title='Print Small'onclick="printV('small','yes');"><i class="fa fa-print"></i> <?= $_print_small ?></button>
  </div>
</div>

<!-- Quick Comment Filters Panel (Moved below search panel) -->
<div id="quickFiltersPanel" class="quick-filters-container" style="display: none;">
  <div id="quickFiltersList" class="quick-filters-list">
    <!-- Comment items will be populated here -->
  </div>
  <button id="clearQuickFilter" class="clear-comment-btn" style="display: none;">
    <i class="fa fa-times"></i> Clear Comment Filter
  </button>
</div>

<!-- Status Display -->
<div id="statusDisplay" style="background: rgba(52, 58, 70, 0.8); border-radius: 12px; padding: 15px; margin-bottom: 20px; color: #ffffff;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Status:</strong> 
            <span id="statusText">Loading users...</span>
            <span id="filterInfo" style="margin-left: 15px; color: #3498db;"></span>
        </div>
        <div>
            <span id="userCount" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 4px 10px; border-radius: 15px; font-weight: 700;">0</span>
            <span style="margin-left: 10px; font-size: 12px; opacity: 0.8;" id="pageInfo">Page 1</span>
        </div>
    </div>
</div>

<div class="table-container">
<div style="max-height: 70vh; overflow-y: auto;" id="tableScrollContainer">
<table id="dataTable" class="table table-hover text-nowrap">
  <thead>
  <tr>
    <th style="min-width:50px;" class="align-middle text-center">#</th>
    <th style="min-width:50px;" class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Server</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_name ?></th>
    <th>Print</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_profile ?></th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> Mac Address</th>
    <th class="text-right align-middle pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_uptime_user ?></th>
    <th class="text-right align-middle pointer" title="Click to sort"><i class="fa fa-sort"></i> Bytes In</th>
    <th class="text-right align-middle pointer" title="Click to sort"><i class="fa fa-sort"></i> Bytes Out</th>
    <th class="pointer" title="Click to sort"><i class="fa fa-sort"></i> <?= $_comment ?></th>
  </tr>
  </thead>
  <tbody id="tbody">
    <!-- Users will be loaded here via JavaScript -->
  </tbody>
</table>
</div>
<div id="paginationContainer" class="pagination-container" style="display: none;">
    <div class="pagination-info">
        Showing <span id="showingStart">0</span>-<span id="showingEnd">0</span> of <span id="totalVisible">0</span> users
    </div>
    <div class="pagination-controls">
        <button class="pagination-btn" onclick="goToPage(1)" id="firstPageBtn">
            <i class="fa fa-angle-double-left"></i>
        </button>
        <button class="pagination-btn" onclick="goToPage(currentPage - 1)" id="prevPageBtn">
            <i class="fa fa-angle-left"></i>
        </button>
        <span style="color: #ffffff; margin: 0 15px; font-weight: 600;">
            Page <span id="currentPageSpan">1</span> of <span id="totalPagesSpan">1</span>
        </span>
        <button class="pagination-btn" onclick="goToPage(currentPage + 1)" id="nextPageBtn">
            <i class="fa fa-angle-right"></i>
        </button>
        <button class="pagination-btn" onclick="goToPage(totalPages)" id="lastPageBtn">
            <i class="fa fa-angle-double-right"></i>
        </button>
    </div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Modern Loading Suite Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <!-- Content will be generated by JavaScript -->
</div>

<script>
// ==================== PERFORMANCE OPTIMIZED JAVASCRIPT ====================

// Global variables
let allUsers = [];
let filteredUsers = [];
let currentPage = 1;
let usersPerPage = 100; // Increased for better performance
let totalPages = 1;
let currentProfile = 'all';
let currentComment = '';
let isLoading = false;

// Initialize loading lock state
window.loadingLocked = false;
window.deleteOperation = false;

// Performance tracking
let performanceStart = performance.now();

console.log('🚀 Starting optimized user management system');

// ==================== CHUNKED PROCESSING FUNCTIONS ====================
function processInChunks(array, chunkSize, processor, callback) {
    let index = 0;
    
    function processChunk() {
        const chunk = array.slice(index, index + chunkSize);
        if (chunk.length === 0) {
            callback();
            return;
        }
        
        chunk.forEach(processor);
        index += chunkSize;
        
        // Use requestAnimationFrame for smooth processing
        requestAnimationFrame(processChunk);
    }
    
    processChunk();
}

// ==================== MODERN LOADING SUITE FUNCTIONS ====================
function showLoading(message = 'Loading...', operation = 'default') {
    const overlay = document.getElementById('loadingOverlay');
    
    // Create modern loading structure
    overlay.innerHTML = `
        <div class="loading-container">
            <div class="loading-header">
                <div class="loading-icon"></div>
                <div>
                    <div class="loading-status">Processing</div>
                    <div class="loading-message">${message}</div>
                </div>
            </div>
            
            <div class="loading-progress-container">
                <div class="loading-progress-bar">
                    <div class="loading-progress-fill" style="width: 0%"></div>
                </div>
            </div>
            
            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
            
            <div class="loading-stats">
                <span class="loading-operation">${operation}</span>
                <span class="loading-time">Starting...</span>
            </div>
        </div>
    `;
    
    overlay.style.display = 'flex';
    isLoading = true;
    
    // Start timer for loading time display
    window.loadingStartTime = Date.now();
    startLoadingTimer();
    
    console.log(`🔄 Modern loading started: ${message}`);
}

function startLoadingTimer() {
    window.loadingTimer = setInterval(() => {
        if (window.loadingStartTime) {
            const elapsed = Math.floor((Date.now() - window.loadingStartTime) / 1000);
            const timeElement = document.querySelector('.loading-time');
            if (timeElement) {
                timeElement.textContent = `${elapsed}s elapsed`;
            }
        }
    }, 1000);
}

function updateLoadingMessage(message, progress = null) {
    const messageElement = document.querySelector('.loading-message');
    const statusElement = document.querySelector('.loading-status');
    const progressFill = document.querySelector('.loading-progress-fill');
    
    if (messageElement) {
        messageElement.textContent = message;
    }
    
    if (statusElement) {
        if (message.includes('delete') || message.includes('remove')) {
            statusElement.textContent = 'Deleting';
        } else if (message.includes('connect')) {
            statusElement.textContent = 'Connecting';
        } else if (message.includes('process')) {
            statusElement.textContent = 'Processing';
        } else {
            statusElement.textContent = 'Working';
        }
    }
    
    if (progress !== null && progressFill) {
        progressFill.style.width = `${progress}%`;
    }
}

function updateLoadingOperation(operation) {
    const operationElement = document.querySelector('.loading-operation');
    if (operationElement) {
        operationElement.textContent = operation;
    }
}

function simulateProgress(duration = 3000) {
    const progressFill = document.querySelector('.loading-progress-fill');
    if (!progressFill) return;
    
    let progress = 0;
    const interval = 50; // Update every 50ms
    const increment = (100 / (duration / interval)) * 0.8; // 80% progress simulation
    
    window.progressInterval = setInterval(() => {
        progress += increment + Math.random() * 2; // Add some randomness
        if (progress > 85) progress = 85; // Cap at 85% until real completion
        
        progressFill.style.width = `${Math.min(progress, 100)}%`;
        
        if (progress >= 85) {
            clearInterval(window.progressInterval);
        }
    }, interval);
}

function completeProgress() {
    const progressFill = document.querySelector('.loading-progress-fill');
    if (progressFill) {
        progressFill.style.width = '100%';
    }
    
    if (window.progressInterval) {
        clearInterval(window.progressInterval);
    }
}

function hideLoading() {
    // Only prevent hiding if there's an active delete operation
    if (window.loadingLocked && window.deleteOperation) {
        console.log('⚠️ Loading hide prevented - delete operation in progress');
        return;
    }
    
    // Clear timers
    if (window.loadingTimer) {
        clearInterval(window.loadingTimer);
    }
    if (window.progressInterval) {
        clearInterval(window.progressInterval);
    }
    
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = 'none';
    isLoading = false;
    console.log('✅ Modern loading hidden');
}

function forceHideLoading() {
    // Force hide loading and reset all locks
    window.loadingLocked = false;
    window.deleteOperation = false;
    
    // Clear all timers
    if (window.loadingTimer) {
        clearInterval(window.loadingTimer);
    }
    if (window.progressInterval) {
        clearInterval(window.progressInterval);
    }
    
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = 'none';
    isLoading = false;
    console.log('🔄 Modern loading force hidden - all locks reset');
}

function updateStatus(message, isError = false) {
    const statusText = document.getElementById('statusText');
    statusText.textContent = message;
    statusText.style.color = isError ? '#e74c3c' : '#2ecc71';
}

function showFilterNotification(message) {
    const notification = document.getElementById('filterNotification');
    const notificationText = document.getElementById('filterNotificationText');
    notificationText.textContent = message;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// ==================== OPTIMIZED USER DATA LOADING WITH ASYNC ====================
function loadUserData() {
    showLoading('Loading user data from Mikrotik...', 'Fetching Users');
    simulateProgress(2000);

    const session = "<?= $session ?>";
    const prof = currentProfile || 'all';
    const comm = currentComment || '';

    const url = `../dashboard/aload.php?load=hotspotusers&session=${session}&prof=${prof}&comm=${comm}`;

    console.log('🔄 Fetching users from:', url);
    updateLoadingMessage('Connecting to Mikrotik...');

    $.ajax({
        url: url,
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            console.log('📦 Received response:', response);

            if (response.success && response.data) {
                updateLoadingMessage('Processing user data...', 90);

                allUsers = response.data;
                filteredUsers = [...allUsers];

                updateStatus(`Loaded ${allUsers.length} users successfully`);
                console.log(`✅ Loaded ${allUsers.length} users`);

                completeProgress();
                updateLoadingMessage('Building interface...', 95);

                setTimeout(() => {
                    buildCommentOptions();
                    updatePagination();
                    hideLoading();
                }, 300);

            } else {
                console.error('❌ Invalid response format:', response);
                updateStatus('Failed to load users: Invalid data format', true);
                hideLoading();

                // Show error in table
                document.getElementById('tbody').innerHTML =
                    '<tr><td colspan="10" style="text-align:center; padding: 40px; color: #ff6b6b;">' +
                    '<i class="fa fa-exclamation-triangle"></i> Failed to load user data<br>' +
                    '<button class="btn btn-primary btn-sm" onclick="loadUserData()" style="margin-top: 15px;">' +
                    '<i class="fa fa-refresh"></i> Retry</button></td></tr>';
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error:', status, error);
            updateStatus(`Failed to load users: ${status}`, true);
            hideLoading();

            // Show error with retry button
            document.getElementById('tbody').innerHTML =
                '<tr><td colspan="10" style="text-align:center; padding: 40px; color: #ff6b6b;">' +
                '<i class="fa fa-exclamation-triangle"></i> Failed to load users: ' + status + '<br>' +
                '<small style="color: #999;">Error: ' + error + '</small><br>' +
                '<button class="btn btn-primary btn-sm" onclick="loadUserData()" style="margin-top: 15px;">' +
                '<i class="fa fa-refresh"></i> Retry</button></td></tr>';
        }
    });
}

// ==================== IMPROVED COMMENT OPTIONS BUILDER ====================
function buildCommentOptions(selectedProfile = 'all') {
    const commentSelect = document.getElementById('commentSelect');
    const comments = {};
    
    // Filter users based on selected profile first
    const usersToAnalyze = selectedProfile === 'all' ? 
        allUsers : 
        allUsers.filter(user => (user.profile || '') === selectedProfile);
    
    usersToAnalyze.forEach(user => {
        const comment = user.comment || '';
        if (comment && (comment.startsWith('vc-') || comment.startsWith('up-'))) {
            if (!comments[comment]) {
                comments[comment] = 0;
            }
            comments[comment]++;
        }
    });
    
    // Store current selection
    const previousSelection = commentSelect.value;
    
    // Clear existing options except first
    commentSelect.innerHTML = '<option value=""><?= $_comment ?></option>';
    
    const sortedComments = Object.keys(comments).sort();
    
    if (sortedComments.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = selectedProfile === 'all' ? 'No comments available' : `No comments for ${selectedProfile}`;
        option.disabled = true;
        commentSelect.appendChild(option);
        
        // Show notification
        if (selectedProfile !== 'all') {
            showFilterNotification(`No comments available for profile: ${selectedProfile}`);
        }
    } else {
        sortedComments.forEach(comment => {
            const option = document.createElement('option');
            option.value = comment;
            option.textContent = `${comment} [${comments[comment]}]`;
            commentSelect.appendChild(option);
        });
        
        // Show notification about available comments
        if (selectedProfile !== 'all') {
            showFilterNotification(`Found ${sortedComments.length} comment(s) for profile: ${selectedProfile}`);
        }
    }
    
    // Try to restore previous selection if it still exists
    if (previousSelection && comments[previousSelection]) {
        commentSelect.value = previousSelection;
    } else if (previousSelection && selectedProfile !== 'all') {
        // Clear selection if previous comment is not available for this profile
        commentSelect.value = '';
        currentComment = '';
        
        // Update delete button visibility
        document.getElementById('deleteByComment').style.display = 'none';
    }
    
    // Build quick comment filters
    buildQuickCommentFilters(comments, selectedProfile);
    
    // Update comment counts in the UI
    const availableComments = sortedComments.length;
    console.log(`📝 Built ${availableComments} comment options for profile: ${selectedProfile}`);
}

// ==================== QUICK COMMENT FILTERS FUNCTIONS ====================
function buildQuickCommentFilters(comments, selectedProfile) {
    const quickFiltersPanel = document.getElementById('quickFiltersPanel');
    const quickFiltersList = document.getElementById('quickFiltersList');
    
    // Show/hide panel based on profile selection
    if (selectedProfile === 'all') {
        quickFiltersPanel.style.display = 'none';
        return;
    } else {
        quickFiltersPanel.style.display = 'block';
    }
    
    // Clear existing items
    quickFiltersList.innerHTML = '';
    
    const sortedComments = Object.keys(comments).sort();
    
    if (sortedComments.length === 0) {
        const noCommentsDiv = document.createElement('div');
        noCommentsDiv.className = 'no-comments-message';
        noCommentsDiv.innerHTML = `
            <i class="fa fa-info-circle" style="margin-right: 6px;"></i>
            No comments for <strong>${selectedProfile}</strong>
        `;
        quickFiltersList.appendChild(noCommentsDiv);
        return;
    }
    
    // Create comment filter items (compact list view)
    sortedComments.forEach(comment => {
        const commentItem = document.createElement('div');
        commentItem.className = 'comment-filter-item';
        commentItem.setAttribute('data-comment', comment);
        
        // Check if this comment is currently active
        if (currentComment === comment) {
            commentItem.classList.add('active');
        }
        
        commentItem.innerHTML = `
            <div class="comment-info">
                <div class="comment-name">${comment}</div>
            </div>
            <div class="comment-count-badge">${comments[comment]}</div>
        `;
        
        // Add click handler
        commentItem.addEventListener('click', () => {
            selectQuickComment(comment);
        });
        
        quickFiltersList.appendChild(commentItem);
    });
    
    console.log(`🚀 Built ${sortedComments.length} compact comment filters`);
}

function selectQuickComment(comment) {
    // Update dropdown selection
    const commentSelect = document.getElementById('commentSelect');
    commentSelect.value = comment;
    currentComment = comment;
    
    // Update active item styling
    document.querySelectorAll('.comment-filter-item').forEach(item => {
        item.classList.remove('active');
    });
    
    document.querySelector(`[data-comment="${comment}"]`).classList.add('active');
    
    // Show clear button
    document.getElementById('clearQuickFilter').style.display = 'block';
    
    // Update delete button
    document.getElementById('deleteByComment').style.display = 'inline-block';
    document.getElementById('deleteByComment').onclick = () => {
        if (confirm('Are you sure to delete username by comment (' + comment + ')?')) {
            startDeleteByComment(comment);
        }
    };
    
    // Apply filters
    applyFilters();
    
    console.log(`⚡ Quick selected comment: ${comment}`);
}

// ==================== DELETE BY COMMENT WITH MODERN LOADING SUITE ====================
function startDeleteByComment(comment) {
    // Set delete operation flags
    window.loadingLocked = true;
    window.deleteOperation = true;
    
    // Show modern loading overlay
    showLoading(`Initializing deletion process...`, `Delete: ${comment}`);
    
    // Start progress simulation
    simulateProgress(4000);
    
    // Progressive loading states
    const loadingStates = [
        { message: "Validating permissions...", delay: 500, progress: 15 },
        { message: "Connecting to database...", delay: 1000, progress: 30 },
        { message: `Locating users with comment: ${comment}`, delay: 1500, progress: 50 },
        { message: "Preparing deletion queries...", delay: 2000, progress: 70 },
        { message: "Executing deletion process...", delay: 2500, progress: 85 }
    ];
    
    // Execute progressive states
    loadingStates.forEach(state => {
        setTimeout(() => {
            updateLoadingMessage(state.message, state.progress);
        }, state.delay);
    });
    
    // Disable delete button and show processing state
    const deleteBtn = document.getElementById('deleteByComment');
    const originalContent = deleteBtn.innerHTML;
    deleteBtn.disabled = true;
    deleteBtn.style.opacity = '0.6';
    deleteBtn.style.cursor = 'not-allowed';
    deleteBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting...';
    
    // Apply modern blur effects to disabled elements
    const elementsToBlur = document.querySelectorAll('.comment-filter-item, #profileSelect, #commentSelect, #filterTable');
    elementsToBlur.forEach(element => {
        element.style.pointerEvents = 'none';
        element.style.opacity = '0.6';
        element.style.filter = 'blur(1px)';
        element.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
    });
    
    // Update status
    updateStatus(`🗑️ Deleting users with comment: ${comment}...`, false);
    
    // Show user feedback
    showFilterNotification(`Processing deletion of users with comment: ${comment}`);
    
    console.log(`🗑️ Starting modern deletion process for comment: ${comment}`);
    
    // Set safety timeout with better UX
    window.deleteTimeout = setTimeout(() => {
        console.warn('⚠️ Delete operation timeout reached');
        updateLoadingMessage('Operation timeout - please refresh page', 100);
        updateStatus('Delete operation timed out - please refresh page', true);
        showFilterNotification('Operation timed out. Please refresh the page to see current status.');
        
        // Complete progress bar in red
        const progressFill = document.querySelector('.loading-progress-fill');
        if (progressFill) {
            progressFill.style.background = 'linear-gradient(90deg, #ff4757 0%, #ff3838 100%)';
            progressFill.style.width = '100%';
        }
    }, 45000);
    
    // Store original button state for cleanup
    window.deleteOriginalState = {
        button: deleteBtn,
        content: originalContent
    };
    
    try {
        // Execute deletion after progress simulation
        setTimeout(() => {
            updateLoadingMessage(`Finalizing deletion...`, 95);
            completeProgress();
            
            // Small delay for better UX
            setTimeout(() => {
                // Execute the actual deletion - this will navigate away from page
                loadpage('./?remove-hotspot-user-by-comment=' + comment + '&session=<?= $session; ?>');
                console.log(`🔄 Delete request sent for comment: ${comment}`);
            }, 500);
        }, 3000);
        
    } catch (error) {
        console.error('Error during deletion:', error);
        clearTimeout(window.deleteTimeout);
        
        // Show error state
        updateLoadingMessage('Error occurred during deletion', 100);
        const progressFill = document.querySelector('.loading-progress-fill');
        if (progressFill) {
            progressFill.style.background = 'linear-gradient(90deg, #ff4757 0%, #ff3838 100%)';
        }
        
        setTimeout(() => {
            resetDeleteState(deleteBtn, originalContent);
            updateStatus('Error occurred during deletion', true);
            forceHideLoading();
        }, 2000);
    }
}

function resetDeleteState(deleteBtn, originalContent) {
    // Clear timeout and timers
    if (window.deleteTimeout) {
        clearTimeout(window.deleteTimeout);
    }
    if (window.loadingTimer) {
        clearInterval(window.loadingTimer);
    }
    if (window.progressInterval) {
        clearInterval(window.progressInterval);
    }
    
    // Reset delete operation flags
    window.loadingLocked = false;
    window.deleteOperation = false;
    
    // Re-enable delete button
    if (deleteBtn) {
        deleteBtn.disabled = false;
        deleteBtn.style.opacity = '1';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.innerHTML = originalContent;
    }
    
    // Remove modern blur effects
    const elementsToUnblur = document.querySelectorAll('.comment-filter-item, #profileSelect, #commentSelect, #filterTable');
    elementsToUnblur.forEach(element => {
        element.style.pointerEvents = 'auto';
        element.style.opacity = '1';
        element.style.filter = 'none';
    });
    
    forceHideLoading();
    console.log('🔄 Delete state fully reset with modern cleanup');
}

function clearQuickComment() {
    // Clear dropdown
    document.getElementById('commentSelect').value = '';
    currentComment = '';
    
    // Remove active styling
    document.querySelectorAll('.comment-filter-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Hide clear button
    document.getElementById('clearQuickFilter').style.display = 'none';
    
    // Hide delete button
    document.getElementById('deleteByComment').style.display = 'none';
    
    // Apply filters
    applyFilters();
    
    console.log(`🧹 Cleared quick comment filter`);
}

// ==================== ENHANCED FILTERING ====================
function applyFilters() {
    if (isLoading) return;
    
    const searchTerm = document.getElementById('filterTable').value.toLowerCase();
    const profileFilter = document.getElementById('profileSelect').value;
    const commentFilter = document.getElementById('commentSelect').value;
    
    filteredUsers = allUsers.filter(user => {
        // Safe string operations with null checking
        const safeName = (user.name || '').toLowerCase();
        const safeComment = (user.comment || '').toLowerCase();
        const safeProfile = (user.profile || '').toLowerCase();
        const safeServer = (user.server || '').toLowerCase();
        
        const matchesSearch = !searchTerm || 
            safeName.includes(searchTerm) ||
            safeComment.includes(searchTerm) ||
            safeProfile.includes(searchTerm) ||
            safeServer.includes(searchTerm);
            
        const matchesProfile = profileFilter === 'all' || (user.profile || '') === profileFilter;
        const matchesComment = !commentFilter || (user.comment || '') === commentFilter;
        
        return matchesSearch && matchesProfile && matchesComment;
    });
    
    currentPage = 1;
    updatePagination();
    
    // Update filter info
    const filterInfo = document.getElementById('filterInfo');
    let filterText = '';
    if (profileFilter !== 'all') filterText += `Profile: ${profileFilter} `;
    if (commentFilter) filterText += `Comment: ${commentFilter} `;
    if (searchTerm) filterText += `Search: "${searchTerm}" `;
    
    filterInfo.textContent = filterText || 'No filters applied';
    
    // Update status based on results
    if (filteredUsers.length === 0) {
        updateStatus('No users found matching current filters', true);
    } else {
        updateStatus(`Showing ${filteredUsers.length} users`, false);
    }
}

// ==================== PROFILE CHANGE HANDLER ====================
function handleProfileChange() {
    const profileSelect = document.getElementById('profileSelect');
    const selectedProfile = profileSelect.value;
    
    // Update current profile
    currentProfile = selectedProfile;
    
    // Rebuild comment options based on selected profile
    buildCommentOptions(selectedProfile);
    
    // Apply filters with new profile
    applyFilters();
    
    console.log(`🔄 Profile changed to: ${selectedProfile}`);
}

// ==================== PAGINATION SYSTEM ====================
function updatePagination() {
    totalPages = Math.ceil(filteredUsers.length / usersPerPage);
    
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    } else if (currentPage < 1) {
        currentPage = 1;
    }
    
    renderCurrentPage();
    updatePaginationControls();
    updateCounters();
}

function renderCurrentPage() {
    const tbody = document.getElementById('tbody');
    const startIndex = (currentPage - 1) * usersPerPage;
    const endIndex = Math.min(startIndex + usersPerPage, filteredUsers.length);
    const usersToShow = filteredUsers.slice(startIndex, endIndex);
    
    // Clear table body
    tbody.innerHTML = '';
    
    // Render users in chunks to prevent blocking
    processInChunks(usersToShow, 10, (user, index) => {
        const globalIndex = startIndex + index + 1;
        const row = createUserRow(user, globalIndex);
        tbody.appendChild(row);
    }, () => {
        console.log(`📄 Rendered page ${currentPage} with ${usersToShow.length} users`);
    });
}

function createUserRow(user, index) {
    const row = document.createElement('tr');
    const isEven = index % 2 === 0;
    row.className = isEven ? 'table-row-even' : 'table-row-odd';
    
    const usermode = user.name === user.password ? "vc" : "up";
    const popup = `javascript:window.open('./voucher/print.php?user=${usermode}-${user.name}&qr=no&session=<?= $session ?>','_blank','width=320,height=550').print();`;
    const popupQR = `javascript:window.open('./voucher/print.php?user=${usermode}-${user.name}&qr=yes&session=<?= $session ?>','_blank','width=320,height=550').print();`;
    
    const disabledIcon = user.disabled === "true" 
        ? `<span class="text-warning pointer" title="Enable User ${user.name}" onclick="loadpage('./?enable-hotspot-user=${user.id}&session=<?= $session ?>')"><i class="fa fa-lock"></i></span>`
        : `<span class="pointer" style="color: #2ecc71;" title="Disable User ${user.name}" onclick="loadpage('./?disable-hotspot-user=${user.id}&session=<?= $session ?>')"><i class="fa fa-unlock"></i></span>`;
    
    // Safe display with fallbacks for null/undefined values
    const safeDisplay = (value, fallback = '') => value && value !== 'null' && value !== 'undefined' ? value : fallback;
    
    row.innerHTML = `
        <td style="text-align:center;">
            <i class="fa fa-minus-square text-danger pointer" 
               onclick="if(confirm('Are you sure to delete username (${user.name})?')){loadpage('./?remove-hotspot-user=${user.id}&session=<?= $session ?>')}else{}"
               title="Remove ${user.name}"></i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            ${disabledIcon}
        </td>
        <td>${safeDisplay(user.server, '-')}</td>
        <td>
            <a title="Open User ${user.name}" href="./?hotspot-user=${user.id}&session=<?= $session ?>" 
               style="color: #3498db; text-decoration: none;">
                <i class="fa fa-edit"></i> ${safeDisplay(user.name, 'Unknown')}
            </a>
        </td>
        <td class="text-center">
            <a title="Print ${user.name}" href="${popup}" style="color: #3498db; margin-right: 10px;">
                <i class="fa fa-print"></i>
            </a>
            <a title="Print QR ${user.name}" href="${popupQR}" style="color: #3498db;">
                <i class="fa fa-qrcode"></i>
            </a>
        </td>
        <td>${safeDisplay(user.profile, '-')}</td>
        <td>${safeDisplay(user.macaddress, '-')}</td>
        <td style="text-align:right;">${safeDisplay(user.uptime, '0s')}</td>
        <td style="text-align:right;">${safeDisplay(user.bytesin, '0 B')}</td>
        <td style="text-align:right;">${safeDisplay(user.bytesout, '0 B')}</td>
        <td>
            ${user.comment ? safeDisplay(user.comment) + safeDisplay(user.datalimit) + safeDisplay(user.timelimit) : ''}
        </td>
    `;
    
    return row;
}

function updatePaginationControls() {
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    
    document.getElementById('currentPageSpan').textContent = currentPage;
    document.getElementById('totalPagesSpan').textContent = totalPages;
    
    document.getElementById('firstPageBtn').disabled = currentPage === 1;
    document.getElementById('prevPageBtn').disabled = currentPage === 1;
    document.getElementById('nextPageBtn').disabled = currentPage === totalPages;
    document.getElementById('lastPageBtn').disabled = currentPage === totalPages;
}

function updateCounters() {
    const startUser = ((currentPage - 1) * usersPerPage) + 1;
    const endUser = Math.min(currentPage * usersPerPage, filteredUsers.length);
    
    document.getElementById('userCount').textContent = filteredUsers.length;
    document.getElementById('pageInfo').textContent = totalPages > 1 ? `Page ${currentPage}/${totalPages}` : 'Page 1';
    document.getElementById('showingStart').textContent = filteredUsers.length > 0 ? startUser : 0;
    document.getElementById('showingEnd').textContent = filteredUsers.length > 0 ? endUser : 0;
    document.getElementById('totalVisible').textContent = filteredUsers.length;
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        updatePagination();
    }
}

// ==================== DEBOUNCED SEARCH ====================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const debouncedApplyFilters = debounce(applyFilters, 300);

// ==================== EVENT LISTENERS ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Initializing optimized user management');
    
    // Load data
    setTimeout(loadUserData, 100);
    
    // Filter event listeners
    document.getElementById('filterTable').addEventListener('input', debouncedApplyFilters);
    
    // UPDATED: Profile change handler
    document.getElementById('profileSelect').addEventListener('change', handleProfileChange);
    
    // UPDATED: Comment change handler
    document.getElementById('commentSelect').addEventListener('change', function() {
        currentComment = this.value;
        
        if (this.value) {
            // Check if profile is selected
            const profileSelected = document.getElementById('profileSelect').value;
            if (profileSelected === 'all') {
                // Show warning and reset comment
                alert('Please select a Profile first before choosing a Comment!');
                this.value = '';
                currentComment = '';
                document.getElementById('deleteByComment').style.display = 'none';
                return;
            }
            
            // Update quick filter items
            document.querySelectorAll('.comment-filter-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const activeItem = document.querySelector(`[data-comment="${this.value}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
            }
            
            document.getElementById('clearQuickFilter').style.display = 'block';
            document.getElementById('deleteByComment').style.display = 'inline-block';
            document.getElementById('deleteByComment').onclick = () => {
                if (confirm('Are you sure to delete username by comment (' + this.value + ')?')) {
                    startDeleteByComment(this.value);
                }
            };
        } else {
            // Clear quick filter styling
            document.querySelectorAll('.comment-filter-item').forEach(item => {
                item.classList.remove('active');
            });
            document.getElementById('clearQuickFilter').style.display = 'none';
            document.getElementById('deleteByComment').style.display = 'none';
        }
        applyFilters();
    });
    
    // Clear quick filter button
    document.getElementById('clearQuickFilter').addEventListener('click', clearQuickComment);
    
    // Keyboard navigation for pagination
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    if (currentPage > 1) goToPage(currentPage - 1);
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    if (currentPage < totalPages) goToPage(currentPage + 1);
                    break;
            }
        }
    });
    
    // Performance monitoring
    window.addEventListener('load', function() {
        const loadTime = performance.now() - performanceStart;
        console.log(`⚡ Page loaded in ${loadTime.toFixed(2)}ms`);
        updateStatus(`Page loaded successfully in ${loadTime.toFixed(0)}ms`);
    });
    
    // Handle page refresh/reload after delete operations
    window.addEventListener('beforeunload', function() {
        // Clear any timeouts
        if (window.deleteTimeout) {
            clearTimeout(window.deleteTimeout);
        }
        // Reset flags for next page
        window.loadingLocked = false;
        window.deleteOperation = false;
    });
    
    // Reset loading state if page becomes visible again (e.g., after navigation back)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Only reset if we're not in an active delete operation
            if (!window.deleteOperation) {
                forceHideLoading();
                
                // Reset any disabled states
                const deleteBtn = document.getElementById('deleteByComment');
                if (deleteBtn && deleteBtn.disabled) {
                    const originalContent = '<i class="fa fa-trash"></i> <?= $_by_comment ?>';
                    resetDeleteState(deleteBtn, originalContent);
                }
            }
        }
    });
    
    // Ensure loading is cleared on page ready
    if (document.readyState === 'complete') {
        // Page is fully loaded, clear any stuck loading states
        setTimeout(() => {
            if (!window.deleteOperation) {
                forceHideLoading();
            }
        }, 500);
    }
});

// ==================== UTILITY FUNCTIONS ====================
function clearAllFilters() {
    document.getElementById('filterTable').value = '';
    document.getElementById('profileSelect').value = 'all';
    document.getElementById('commentSelect').value = '';
    document.getElementById('deleteByComment').style.display = 'none';
    
    currentProfile = 'all';
    currentComment = '';
    
    // Clear quick filter styling
    document.querySelectorAll('.comment-filter-item').forEach(item => {
        item.classList.remove('active');
    });
    document.getElementById('clearQuickFilter').style.display = 'none';
    document.getElementById('quickFiltersPanel').style.display = 'none';
    
    // Rebuild comment options for all profiles
    buildCommentOptions('all');
    
    applyFilters();
    
    updateStatus('All filters cleared');
}

// Export functions for global access
window.goToPage = goToPage;
window.clearAllFilters = clearAllFilters;
window.selectQuickComment = selectQuickComment;
window.clearQuickComment = clearQuickComment;
window.startDeleteByComment = startDeleteByComment;
window.resetDeleteState = resetDeleteState;
window.forceHideLoading = forceHideLoading;
window.updateLoadingMessage = updateLoadingMessage;
window.updateLoadingOperation = updateLoadingOperation;
window.simulateProgress = simulateProgress;
window.completeProgress = completeProgress;
window.startLoadingTimer = startLoadingTimer;

console.log('✅ Optimized user management system with Modern Loading Suite initialized');
</script>