<?php

session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

// get MikroTik system clock
  $getclock = $API->comm("/system/clock/print");
  $clock = $getclock[0];
  $timezone = $getclock[0]['time-zone-name'];
  $_SESSION['timezone'] = $timezone;
  date_default_timezone_set($timezone);

// get system resource MikroTik
  $getresource = $API->comm("/system/resource/print");
  $resource = $getresource[0];

// get routeboard info
  $getrouterboard = $API->comm("/system/routerboard/print");
  $routerboard = $getrouterboard[0];

// get & counting hotspot users
  $countallusers = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));
  if ($countallusers < 2) {
    $uunit = "item";
  } elseif ($countallusers > 1) {
    $uunit = "items";
  }

// get & counting hotspot active
  $counthotspotactive = $API->comm("/ip/hotspot/active/print", array("count-only" => ""));
  if ($counthotspotactive < 2) {
    $hunit = "item";
  } elseif ($counthotspotactive > 1) {
    $hunit = "items";
  }

  if ($livereport == "disable") {
    $logh = "320px";
    $lreport = "style='display:none;'";
  } else {
    $logh = "280px";
    $lreport = "style='display:block;'";
  }

}
?>

<style id="compact-dashboard-styles">
:root {
  --bg-primary: #1a1a1a;
  --bg-secondary: #2d2d2d;
  --bg-tertiary: #383838;
  --text-primary: #ffffff;
  --text-secondary: #b0b0b0;
  --text-muted: #888888;
  --accent-blue: #4a9eff;
  --accent-green: #00d084;
  --accent-orange: #ff9500;
  --accent-red: #ff5757;
  --border-color: #404040;
  --shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.compact-dashboard {
  background: var(--bg-primary);
  color: var(--text-primary);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  padding: 15px;
  min-height: 100vh;
}

.compact-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  box-shadow: var(--shadow);
  margin-bottom: 15px;
  overflow: hidden;
}

.compact-header {
  background: var(--bg-tertiary);
  padding: 8px 15px;
  border-bottom: 1px solid var(--border-color);
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.compact-body {
  padding: 12px 15px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.top-row-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.middle-row-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

/* Enhanced System Status Card */
.system-status-card {
  background: linear-gradient(135deg, #2d2d2d 0%, #383838 100%);
  border: 1px solid #4a9eff;
  position: relative;
  overflow: hidden;
}

.system-status-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4a9eff 0%, #00d084 50%, #ff9500 100%);
}

.system-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #3a4149 0%, #2d2d2d 100%);
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--accent-green);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent-green);
  animation: pulse 2s infinite;
}

.status-dot.online {
  background: var(--accent-green);
  box-shadow: 0 0 0 0 rgba(0, 208, 132, 0.7);
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(0, 208, 132, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(0, 208, 132, 0); }
  100% { box-shadow: 0 0 0 0 rgba(0, 208, 132, 0); }
}

/* Primary Stats Row */
.primary-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 15px;
}

.stat-item {
  background: var(--bg-tertiary);
  border-radius: 8px;
  padding: 12px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.cpu-stat {
  background: linear-gradient(135deg, rgba(74, 158, 255, 0.1) 0%, var(--bg-tertiary) 100%);
}

.memory-stat {
  background: linear-gradient(135deg, rgba(0, 208, 132, 0.1) 0%, var(--bg-tertiary) 100%);
}

.uptime-stat {
  background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, var(--bg-tertiary) 100%);
}

.stat-icon {
  font-size: 20px;
  margin-bottom: 8px;
  color: var(--accent-blue);
}

.memory-stat .stat-icon {
  color: var(--accent-green);
}

.uptime-stat .stat-icon {
  color: var(--accent-orange);
}

.stat-content {
  text-align: center;
}

.stat-label {
  font-size: 10px;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 4px;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.stat-bar {
  width: 100%;
  height: 3px;
  background: rgba(255,255,255,0.1);
  border-radius: 2px;
  overflow: hidden;
}

.stat-progress {
  height: 100%;
  background: linear-gradient(90deg, var(--accent-blue) 0%, var(--accent-green) 100%);
  border-radius: 2px;
  transition: width 0.5s ease;
}

.memory-visual {
  display: flex;
  justify-content: center;
}

.memory-bar {
  width: 40px;
  height: 3px;
  background: linear-gradient(90deg, var(--accent-green) 0%, var(--accent-blue) 100%);
  border-radius: 2px;
  animation: memoryPulse 3s ease-in-out infinite;
}

@keyframes memoryPulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}

.uptime-pulse {
  display: flex;
  justify-content: center;
}

.pulse-dot {
  width: 6px;
  height: 6px;
  background: var(--accent-orange);
  border-radius: 50%;
  animation: uptimePulse 2s ease-in-out infinite;
}

@keyframes uptimePulse {
  0%, 100% { transform: scale(1); opacity: 0.7; }
  50% { transform: scale(1.5); opacity: 1; }
}

/* Secondary Info */
.secondary-info {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.info-pair {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.02);
  padding: 6px 8px;
  border-radius: 6px;
  border-left: 3px solid var(--accent-blue);
}

.info-icon {
  font-size: 14px;
  color: var(--accent-blue);
  min-width: 16px;
}

.info-text {
  flex: 1;
  min-width: 0;
}

.info-small {
  font-size: 9px;
  color: var(--text-muted);
  text-transform: uppercase;
  line-height: 1;
}

.info-main {
  font-size: 11px;
  color: var(--text-primary);
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Enhanced Income Card - Now Horizontal Layout */
.income-card-enhanced {
  background: linear-gradient(135deg, rgba(0, 208, 132, 0.1) 0%, var(--bg-secondary) 100%);
  border: 1px solid rgba(0, 208, 132, 0.3);
  position: relative;
  overflow: hidden;
}

.income-card-enhanced::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #00d084 0%, #4a9eff 50%, #ff9500 100%);
}

.income-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #2d4d38 0%, #2d2d2d 100%);
}

.income-icon {
  font-size: 16px;
  color: var(--accent-green);
  animation: moneyPulse 3s ease-in-out infinite;
}

@keyframes moneyPulse {
  0%, 100% { opacity: 0.7; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
}

.income-status {
  display: flex;
  align-items: center;
}

.income-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
}

.money-pulse {
  width: 8px;
  height: 8px;
  background: var(--accent-green);
  border-radius: 50%;
  animation: pulse 2s infinite;
}

/* Horizontal Income Display - STABLE LAYOUT */
.income-display-enhanced {
  padding: 12px !important;
}

.income-content {
  display: grid !important;
  grid-template-columns: 1fr 1fr !important;
  gap: 15px !important;
}

.income-stat-card {
  background: var(--bg-tertiary) !important;
  border: 1px solid var(--border-color) !important;
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
}

.income-stat-label {
  font-size: 11px !important;
  color: var(--text-muted) !important;
  text-transform: uppercase !important;
  margin-bottom: 6px !important;
  letter-spacing: 0.5px !important;
}

.income-stat-value {
  font-size: 18px !important;
  font-weight: 700 !important;
  color: var(--text-primary) !important;
  margin-bottom: 4px !important;
}

.income-stat-count {
  font-size: 10px !important;
  color: var(--text-secondary) !important;
}

.income-trend {
  position: absolute !important;
  top: 12px !important;
  right: 12px !important;
  font-size: 12px !important;
  opacity: 0.8 !important;
}

.income-trend.up {
  color: var(--accent-green) !important;
}

.income-trend.stable {
  color: var(--accent-blue) !important;
}

/* Income Loading State */
.income-loading {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 12px !important;
  padding: 20px !important;
  color: var(--text-muted) !important;
}

.loading-spinner {
  width: 24px !important;
  height: 24px !important;
  border: 2px solid var(--border-color) !important;
  border-top: 2px solid var(--accent-green) !important;
  border-radius: 50% !important;
  animation: spin 1s linear infinite !important;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  text-align: center !important;
}

.loading-title {
  font-size: 12px !important;
  font-weight: 600 !important;
  color: var(--text-primary) !important;
  margin-bottom: 4px !important;
}

.loading-subtitle {
  font-size: 10px !important;
  color: var(--text-muted) !important;
}

/* Enhanced Hotspot Management Card */
.hotspot-management-card {
  background: linear-gradient(135deg, #2d2d2d 0%, #383838 100%);
  border: 1px solid #4a9eff;
  position: relative;
  overflow: hidden;
}

.hotspot-management-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4a9eff 0%, #00d084 50%, #ff9500 75%, #ff5757 100%);
}

.hotspot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #3a4149 0%, #2d2d2d 100%);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-wifi-icon {
  font-size: 16px;
  color: var(--accent-blue);
  animation: wifiPulse 3s ease-in-out infinite;
}

.header-status {
  display: flex;
  align-items: center;
}

.wifi-indicator {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  height: 16px;
}

.wifi-wave {
  width: 3px;
  background: var(--accent-blue);
  border-radius: 2px;
  animation: waveAnimation 2s ease-in-out infinite;
}

.wave-1 {
  height: 6px;
  animation-delay: 0s;
}

.wave-2 {
  height: 10px;
  animation-delay: 0.2s;
}

.wave-3 {
  height: 14px;
  animation-delay: 0.4s;
}

/* Enhanced Hotspot Grid */
.hotspot-grid-enhanced {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  padding: 5px;
}

.hotspot-card {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 16px;
  text-decoration: none;
  color: inherit;
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
}

.hotspot-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.4);
  text-decoration: none;
  color: inherit;
}

.hotspot-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.03) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.hotspot-card:hover::before {
  opacity: 1;
}

/* Card Icon Container */
.card-icon-container {
  position: relative;
  flex-shrink: 0;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  position: relative;
  z-index: 2;
  transition: all 0.3s ease;
}

/* Active Card Styling */
.active-card .card-icon {
  background: linear-gradient(135deg, var(--accent-blue) 0%, #3a8ae8 100%);
  color: white;
}

.status-ring {
  position: absolute;
  top: -3px;
  left: -3px;
  right: -3px;
  bottom: -3px;
  border-radius: 13px;
  z-index: 1;
}

.active-ring {
  background: linear-gradient(45deg, var(--accent-blue), transparent, var(--accent-blue));
  animation: ringRotate 3s linear infinite;
}

/* Users Card Styling */
.users-card .card-icon {
  background: linear-gradient(135deg, var(--accent-green) 0%, #00b574 100%);
  color: white;
}

.users-ring {
  background: linear-gradient(45deg, var(--accent-green), transparent, var(--accent-green));
  animation: ringRotate 4s linear infinite;
}

/* Add Card Styling */
.add-card .card-icon {
  background: linear-gradient(135deg, var(--accent-orange) 0%, #e6850e 100%);
  color: white;
}

.add-icon-bg {
  position: absolute;
  top: -5px;
  left: -5px;
  right: -5px;
  bottom: -5px;
  background: radial-gradient(circle, rgba(255,149,0,0.2) 0%, transparent 70%);
  border-radius: 15px;
  animation: addPulse 2s ease-in-out infinite;
}

/* Generate Card Styling */
.generate-card .card-icon {
  background: linear-gradient(135deg, var(--accent-red) 0%, #e04848 100%);
  color: white;
}

.magic-sparkles {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.sparkle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #ffd700;
  border-radius: 50%;
  animation: sparkleAnimation 2s ease-in-out infinite;
}

.sparkle-1 {
  top: 5px;
  right: 5px;
  animation-delay: 0s;
}

.sparkle-2 {
  bottom: 5px;
  left: 5px;
  animation-delay: 0.7s;
}

.sparkle-3 {
  top: 50%;
  right: 2px;
  animation-delay: 1.4s;
}

/* Card Content */
.card-content {
  flex: 1;
  min-width: 0;
}

.card-number {
  display: flex;
  align-items: baseline;
  gap: 4px;
  margin-bottom: 4px;
}

.number-display {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.number-label {
  font-size: 10px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.add-symbol, .generate-symbol {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
}

.card-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 2px;
  line-height: 1.2;
}

.card-subtitle {
  font-size: 10px;
  color: var(--text-muted);
  line-height: 1.2;
}

/* Card Arrow */
.card-arrow {
  opacity: 0;
  transform: translateX(-5px);
  transition: all 0.3s ease;
  color: var(--text-muted);
  font-size: 12px;
}

.hotspot-card:hover .card-arrow {
  opacity: 1;
  transform: translateX(0);
}

.hotspot-card:hover .card-icon {
  transform: scale(1.1);
}

/* Traffic Card Enhanced */
.traffic-card-enhanced {
  background: linear-gradient(135deg, #2d2d2d 0%, #383838 100%);
  border: 1px solid #ff9500;
  position: relative;
  overflow: hidden;
  min-height: 350px;
}

.traffic-card-enhanced::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #ff9500 0%, #00d084 50%, #4a9eff 100%);
}

.traffic-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #4d3a1f 0%, #2d2d2d 100%);
}

.traffic-icon {
  font-size: 16px;
  color: var(--accent-orange);
  animation: chartPulse 3s ease-in-out infinite;
}

@keyframes chartPulse {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

.interface-badge {
  background: rgba(255, 149, 0, 0.2);
  color: var(--accent-orange);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  margin-left: 8px;
}

.traffic-status {
  display: flex;
  align-items: center;
}

.traffic-indicator {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  height: 16px;
}

.signal-bar {
  width: 3px;
  background: var(--accent-orange);
  border-radius: 2px;
  animation: signalAnimation 2s ease-in-out infinite;
}

.bar-1 {
  height: 4px;
  animation-delay: 0s;
}

.bar-2 {
  height: 8px;
  animation-delay: 0.2s;
}

.bar-3 {
  height: 12px;
  animation-delay: 0.4s;
}

.bar-4 {
  height: 16px;
  animation-delay: 0.6s;
}

@keyframes signalAnimation {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 1; }
}

.traffic-body {
  padding: 12px 15px;
}

/* FIXED: Traffic Stats in One Row */
.traffic-stats {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
  padding: 8px;
  background: rgba(255,255,255,0.02);
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.traffic-stat {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: var(--bg-tertiary);
  border-radius: 6px;
  transition: all 0.3s ease;
}

.traffic-stat:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.upload-stat {
  border-left: 3px solid var(--accent-orange);
}

.download-stat {
  border-left: 3px solid var(--accent-green);
}

.traffic-stat .stat-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.upload-stat .stat-icon {
  background: linear-gradient(135deg, var(--accent-orange) 0%, #e6850e 100%);
  color: white;
}

.download-stat .stat-icon {
  background: linear-gradient(135deg, var(--accent-green) 0%, #00b574 100%);
  color: white;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.traffic-stat .stat-label {
  font-size: 10px;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 4px;
  letter-spacing: 0.5px;
}

.traffic-stat .stat-value {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chart-container {
  position: relative;
  height: 240px;
  background: rgba(255,255,255,0.02);
  border-radius: 8px;
  border: 1px solid var(--border-color);
  overflow: hidden;
}

#trafficMonitor {
  width: 100%;
  height: 100%;
}

/* Enhanced Logs Card CSS - Always Applied */
.logs-card-enhanced {
  background: linear-gradient(135deg, #2d2d2d 0%, #383838 100%);
  border: 1px solid #4a9eff;
  position: relative;
  overflow: hidden;
}

.logs-card-enhanced::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4a9eff 0%, #00d084 50%, #ff9500 100%);
}

.logs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #1f3a4d 0%, #2d2d2d 100%);
}

.logs-icon {
  font-size: 16px;
  color: var(--accent-blue);
  animation: logsPulse 3s ease-in-out infinite;
}

@keyframes logsPulse {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

.logs-status {
  display: flex;
  align-items: center;
}

.logs-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
}

.activity-dot {
  width: 8px;
  height: 8px;
  background: var(--accent-green);
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.activity-text {
  font-size: 10px;
  color: var(--accent-green);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.logs-body {
  padding: 8px 12px;
}

.logs-container {
  max-height: 280px;
  overflow-y: auto;
  background: rgba(255,255,255,0.02);
  border-radius: 6px;
  border: 1px solid var(--border-color);
}

.logs-table-enhanced {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}

.logs-table-enhanced th {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 9px;
  padding: 8px 6px;
  border-bottom: 1px solid var(--border-color);
  text-align: left;
  position: sticky;
  top: 0;
  z-index: 1;
}

.logs-table-enhanced th i {
  margin-right: 4px;
  opacity: 0.7;
}

.logs-table-enhanced td {
  padding: 6px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  color: var(--text-primary);
  font-size: 10px;
}

.logs-table-enhanced tbody tr:hover {
  background: rgba(255,255,255,0.02);
}

.log-entry-time {
  color: var(--text-secondary);
  font-family: 'Courier New', monospace;
  font-size: 9px;
}

.log-entry-user {
  color: var(--accent-blue);
  font-weight: 600;
}

.log-entry-message {
  color: var(--text-primary);
}

.log-entry-message.login {
  color: var(--accent-green);
}

.log-entry-message.logout {
  color: var(--accent-orange);
}

.log-entry-message.error {
  color: var(--accent-red);
}

/* Loading states for logs */
.logs-loading {
  text-align: center;
  padding: 20px;
  color: var(--text-muted);
  background: rgba(255,255,255,0.02);
}

.logs-loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.logs-empty-icon {
  font-size: 18px;
  color: var(--accent-blue);
  opacity: 0.7;
  margin-bottom: 8px;
}

.loading-dots {
  display: flex;
  gap: 4px;
}

.loading-dots .dot {
  width: 6px;
  height: 6px;
  background: var(--accent-blue);
  border-radius: 50%;
  animation: dotPulse 1.4s ease-in-out infinite both;
}

.loading-dots .dot:nth-child(1) { animation-delay: -0.32s; }
.loading-dots .dot:nth-child(2) { animation-delay: -0.16s; }
.loading-dots .dot:nth-child(3) { animation-delay: 0s; }

@keyframes dotPulse {
  0%, 80%, 100% { transform: scale(0); opacity: 0.5; }
  40% { transform: scale(1); opacity: 1; }
}

/* Animations */
@keyframes wifiPulse {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

@keyframes waveAnimation {
  0%, 100% { opacity: 0.4; transform: scaleY(0.5); }
  50% { opacity: 1; transform: scaleY(1); }
}

@keyframes ringRotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes addPulse {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.1); }
}

@keyframes sparkleAnimation {
  0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
  50% { opacity: 1; transform: scale(1) rotate(180deg); }
}

.main-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 15px;
}

.sidebar-stack {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.info-item {
  background: var(--bg-tertiary);
  padding: 8px 12px;
  border-radius: 6px;
  text-align: center;
  border: 1px solid var(--border-color);
}

.info-label {
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 2px;
}

.info-value {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}

.loading-indicator {
  text-align: center;
  padding: 20px;
  color: var(--text-muted);
}

.loading-indicator i {
  margin-right: 8px;
}

/* Responsive - FIXED for 1366x768 with better text fitting */
@media (max-width: 1400px) and (min-width: 1200px) {
  /* Optimized for 1366x768 resolution */
  .compact-dashboard {
    padding: 8px;
  }
  
  .top-row-grid {
    grid-template-columns: 1.6fr 0.8fr 0.8fr;
    gap: 8px;
    margin-bottom: 8px;
  }
  
  .compact-card {
    margin-bottom: 8px;
  }
  
  .compact-header {
    padding: 4px 8px;
    font-size: 11px;
  }
  
  .compact-body {
    padding: 6px 8px;
  }
  
  /* System Status - Optimized */
  .primary-stats {
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    margin-bottom: 8px;
  }
  
  .stat-item {
    padding: 6px 4px;
  }
  
  .stat-icon {
    font-size: 14px;
    margin-bottom: 4px;
  }
  
  .stat-label {
    font-size: 8px;
    margin-bottom: 2px;
  }
  
  .stat-value {
    font-size: 12px;
    margin-bottom: 3px;
  }
  
  .secondary-info {
    grid-template-columns: repeat(2, 1fr);
    gap: 3px;
  }
  
  .info-pair {
    padding: 3px 4px;
  }
  
  .info-icon {
    font-size: 10px;
  }
  
  .info-small {
    font-size: 7px;
  }
  
  .info-main {
    font-size: 9px;
  }
  
  /* Hotspot Management - FIXED for 1366x768 */
  .hotspot-grid-enhanced {
    grid-template-columns: repeat(2, 1fr);
    gap: 4px;
    padding: 2px;
  }
  
  .hotspot-card {
    padding: 6px 8px;
    gap: 6px;
    min-height: 50px;
    border-radius: 8px;
  }
  
  .card-icon {
    width: 28px;
    height: 28px;
    font-size: 12px;
  }
  
  .card-content {
    min-width: 0;
    flex: 1;
  }
  
  .card-number {
    gap: 2px;
    margin-bottom: 2px;
  }
  
  .number-display {
    font-size: 16px;
    line-height: 1;
  }
  
  .number-label {
    font-size: 7px;
  }
  
  .card-title {
    font-size: 9px;
    line-height: 1;
    margin-bottom: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .card-subtitle {
    font-size: 7px;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .add-symbol, .generate-symbol {
    font-size: 14px;
  }
  
  .card-arrow {
    font-size: 10px;
  }
  
  /* Income Report - FIXED for 1366x768 */
  .income-stat-card {
    padding: 6px !important;
    gap: 6px !important;
    min-height: 45px !important;
  }
  
  .income-stat-icon {
    width: 22px !important;
    height: 22px !important;
    font-size: 9px !important;
  }
  
  .income-stat-content {
    min-width: 0 !important;
  }
  
  .income-stat-label {
    font-size: 7px !important;
    margin-bottom: 2px !important;
  }
  
  .income-stat-value {
    font-size: 11px !important;
    margin-bottom: 1px !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }
  
  .income-stat-count {
    font-size: 6px !important;
  }
  
  .income-trend {
    font-size: 8px !important;
  }
  
  /* Main Grid - Optimized */
  .main-grid {
    gap: 8px;
  }
  
  /* Traffic Stats - FIXED */
  .traffic-stats {
    flex-direction: row;
    gap: 6px;
    padding: 4px;
    margin-bottom: 8px;
  }
  
  .traffic-stat {
    padding: 4px 6px;
    gap: 6px;
  }
  
  .traffic-stat .stat-icon {
    width: 24px;
    height: 24px;
    font-size: 10px;
  }
  
  .traffic-stat .stat-label {
    font-size: 7px;
    margin-bottom: 2px;
  }
  
  .traffic-stat .stat-value {
    font-size: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .chart-container {
    height: 200px;
  }
  
  /* Logs - FIXED */
  .logs-container {
    max-height: 220px;
  }
  
  .logs-table-enhanced th {
    padding: 4px 3px;
    font-size: 7px;
  }
  
  .logs-table-enhanced td {
    padding: 3px 2px;
    font-size: 8px;
  }
  
  .logs-body {
    padding: 4px 6px;
  }
  
  /* Status indicators - smaller */
  .status-indicator {
    font-size: 9px;
  }
  
  .status-dot {
    width: 6px;
    height: 6px;
  }
  
  .header-wifi-icon, .traffic-icon, .logs-icon, .income-icon {
    font-size: 12px;
  }
  
  .wifi-wave {
    width: 2px;
  }
  
  .wave-1 { height: 4px; }
  .wave-2 { height: 6px; }
  .wave-3 { height: 8px; }
  
  .signal-bar {
    width: 2px;
  }
  
  .bar-1 { height: 3px; }
  .bar-2 { height: 5px; }
  .bar-3 { height: 7px; }
  .bar-4 { height: 9px; }
  
  .interface-badge {
    font-size: 8px;
    padding: 1px 4px;
  }
  
  .activity-text {
    font-size: 8px;
  }
  
  .activity-dot {
    width: 6px;
    height: 6px;
  }
}

/* High Resolution Support - 1920x1080 and 2880x1800 with 200% scale */
@media (min-width: 1401px) and (max-width: 2000px) {
  /* Subtle optimizations for 1920x1080 - don't change layout drastically */
  .compact-dashboard {
    padding: 16px;
  }
  
  .top-row-grid {
    gap: 16px;
    margin-bottom: 16px;
  }
  
  .compact-header {
    padding: 10px 16px;
    font-size: 15px;
  }
  
  .compact-body {
    padding: 14px 16px;
  }
  
  /* Hotspot cards - moderate increase */
  .hotspot-card {
    padding: 18px 14px;
    gap: 14px;
    min-height: 80px;
  }
  
  .card-icon {
    width: 44px;
    height: 44px;
    font-size: 20px;
  }
  
  .number-display {
    font-size: 26px;
  }
  
  .card-title {
    font-size: 14px;
  }
  
  .card-subtitle {
    font-size: 10px;
  }
  
  /* Income cards - moderate increase */
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
  
  /* Traffic stats - moderate increase */
  .traffic-stats {
    gap: 18px;
    padding: 10px;
  }
  
  .traffic-stat {
    padding: 14px 16px;
    gap: 14px;
  }
  
  .traffic-stat .stat-icon {
    width: 36px;
    height: 36px;
    font-size: 16px;
  }
  
  .traffic-stat .stat-value {
    font-size: 15px;
  }
}

/* Ultra High Resolution Support - 2880x1800 with 200% scale */
@media (min-width: 2001px), (min-width: 1400px) and (-webkit-min-device-pixel-ratio: 2) {
  /* More conservative scaling for ultra high res */
  .compact-dashboard {
    padding: 20px;
  }
  
  .top-row-grid {
    gap: 20px;
    margin-bottom: 20px;
  }
  
  .compact-header {
    padding: 12px 20px;
    font-size: 16px;
  }
  
  .compact-body {
    padding: 16px 20px;
  }
  
  /* Hotspot cards - reasonable scaling */
  .hotspot-card {
    padding: 20px 16px;
    gap: 16px;
    min-height: 90px;
  }
  
  .card-icon {
    width: 48px;
    height: 48px;
    font-size: 22px;
  }
  
  .number-display {
    font-size: 28px;
  }
  
  .card-title {
    font-size: 15px;
  }
  
  .card-subtitle {
    font-size: 11px;
  }
  
  /* Income cards - reasonable scaling */
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
  
  /* Traffic stats - reasonable scaling */
  .traffic-stats {
    gap: 20px;
    padding: 12px;
  }
  
  .traffic-stat {
    padding: 16px 18px;
    gap: 16px;
  }
  
  .traffic-stat .stat-icon {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
  
  .traffic-stat .stat-value {
    font-size: 16px;
  }
  
  .traffic-stat .stat-label {
    font-size: 11px;
  }
  
  /* System status - reasonable scaling */
  .stat-item {
    padding: 16px 12px;
  }
  
  .stat-icon {
    font-size: 22px;
  }
  
  .stat-value {
    font-size: 18px;
  }
  
  .info-main {
    font-size: 13px;
  }
}

@media (max-width: 1200px) {
  .top-row-grid {
    grid-template-columns: 1.5fr 1fr 1fr;
  }
  
  .primary-stats {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .stat-item {
    padding: 8px;
  }
  
  .secondary-info {
    grid-template-columns: 1fr;
    gap: 4px;
  }
  
  .traffic-stats {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 968px) {
  .top-row-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .main-grid {
    grid-template-columns: 1fr;
  }
  
  .primary-stats {
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }
  
  .secondary-info {
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
  }
  
  .system-status-card {
    order: 1;
  }
  
  .income-card-enhanced {
    order: 2;
  }
  
  .compact-card:has(.hotspot-grid-enhanced) {
    order: 3;
  }
  
  .traffic-stats {
    flex-direction: row;
    gap: 10px;
  }
}

@media (max-width: 768px) {
  .compact-dashboard {
    padding: 10px;
  }
  
  .top-row-grid {
    gap: 10px;
  }
  
  .main-grid {
    gap: 10px;
  }
  
  .primary-stats {
    grid-template-columns: 1fr;
    gap: 6px;
  }
  
  .stat-item {
    padding: 10px;
  }
  
  .stat-icon {
    font-size: 18px;
  }
  
  .stat-value {
    font-size: 14px;
  }
  
  .secondary-info {
    grid-template-columns: 1fr;
  }
  
  .hotspot-grid-enhanced {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 3px;
  }
  
  .hotspot-card {
    padding: 12px;
    gap: 8px;
  }
  
  .card-icon {
    width: 35px;
    height: 35px;
    font-size: 16px;
  }
  
  .number-display {
    font-size: 20px;
  }
  
  .card-title {
    font-size: 12px;
  }
  
  .card-subtitle {
    font-size: 9px;
  }
  
  .header-wifi-icon {
    font-size: 14px;
  }
  
  .wifi-wave {
    width: 2px;
  }
  
  .wave-1 { height: 5px; }
  .wave-2 { height: 8px; }
  .wave-3 { height: 11px; }
  
  .traffic-card-enhanced {
    min-height: 300px;
  }
  
  .traffic-stats {
    flex-direction: column;
    gap: 6px;
  }
  
  .chart-container {
    height: 200px;
  }
}

@media (max-width: 480px) {
  .compact-dashboard {
    padding: 8px;
  }
  
  .hotspot-grid-enhanced {
    grid-template-columns: 1fr;
    gap: 6px;
    padding: 2px;
  }
  
  .hotspot-card {
    padding: 10px;
    gap: 10px;
  }
  
  .card-icon {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }
  
  .number-display {
    font-size: 18px;
  }
  
  .add-symbol, .generate-symbol {
    font-size: 16px;
  }
  
  .primary-stats {
    gap: 4px;
  }
  
  .stat-item {
    padding: 8px;
  }
  
  .info-pair {
    padding: 4px 6px;
  }
  
  .info-main {
    font-size: 10px;
  }
  
  .traffic-card-enhanced {
    min-height: 250px;
  }
  
  .chart-container {
    height: 160px;
  }
}

/* Dark scrollbar */
.logs-container::-webkit-scrollbar,
.log-container::-webkit-scrollbar {
  width: 6px;
}

.logs-container::-webkit-scrollbar-track,
.log-container::-webkit-scrollbar-track {
  background: var(--bg-tertiary);
}

.logs-container::-webkit-scrollbar-thumb,
.log-container::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 3px;
}

.logs-container::-webkit-scrollbar-thumb:hover,
.log-container::-webkit-scrollbar-thumb:hover {
  background: var(--text-muted);
}

/* Force styles to persist */
.income-display-enhanced * {
  transition: none !important;
}

.income-content * {
  transition: none !important;
}
</style>
    
<div id="reloadHome" class="compact-dashboard">

  <!-- Top Row: System Status & Income Report -->
  <div class="top-row-grid">
    
    <!-- System Status Card - Enhanced -->
    <div id="r_1" class="compact-card system-status-card">
      <div class="compact-header system-header">
        <i class="fa fa-server"></i>
        System Status
        <div class="status-indicator">
          <div class="status-dot online"></div>
          <span>Online</span>
        </div>
      </div>
      <div class="compact-body">
        
        <!-- Primary Stats Row -->
        <div class="primary-stats">
          <div class="stat-item cpu-stat">
            <div class="stat-icon">
              <i class="fa fa-microchip"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">CPU Load</div>
              <div class="stat-value"><?= $resource['cpu-load'] ?>%</div>
              <div class="stat-bar">
                <div class="stat-progress" style="width: <?= $resource['cpu-load'] ?>%"></div>
              </div>
            </div>
          </div>
          
          <div class="stat-item memory-stat">
            <div class="stat-icon">
              <i class="fa fa-memory"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">Free Memory</div>
              <div class="stat-value"><?= formatBytes($resource['free-memory'], 1) ?></div>
              <div class="memory-visual">
                <div class="memory-bar"></div>
              </div>
            </div>
          </div>
          
          <div class="stat-item uptime-stat">
            <div class="stat-icon">
              <i class="fa fa-clock-o"></i>
            </div>
            <div class="stat-content">
              <div class="stat-label">Uptime</div>
              <div class="stat-value"><?= formatDTM($resource['uptime']) ?></div>
              <div class="uptime-pulse">
                <div class="pulse-dot"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Secondary Info Grid -->
        <div class="secondary-info">
          <div class="info-pair">
            <div class="info-icon"><i class="fa fa-calendar"></i></div>
            <div class="info-text">
              <div class="info-small">Date & Time</div>
              <div class="info-main"><?= ucfirst($clock['date']) . " " . $clock['time'] ?></div>
            </div>
          </div>
          
          <div class="info-pair">
            <div class="info-icon"><i class="fa fa-info-circle"></i></div>
            <div class="info-text">
              <div class="info-small">Board</div>
              <div class="info-main"><?= $resource['board-name'] ?></div>
            </div>
          </div>
          
          <div class="info-pair">
            <div class="info-icon"><i class="fa fa-cog"></i></div>
            <div class="info-text">
              <div class="info-small">RouterOS</div>
              <div class="info-main"><?= $resource['version'] ?></div>
            </div>
          </div>
          
          <div class="info-pair">
            <div class="info-icon"><i class="fa fa-hdd-o"></i></div>
            <div class="info-text">
              <div class="info-small">Free HDD</div>
              <div class="info-main"><?= formatBytes($resource['free-hdd-space'], 1) ?></div>
            </div>
          </div>
        </div>
        
      </div>
    </div>
    
    <!-- Income Report Card - Now Horizontal -->
    <div <?= $lreport; ?> class="compact-card income-card-enhanced">
      <div class="compact-header income-header">
        <div class="header-left">
          <i class="fa fa-money income-icon"></i>
          <span>Income Report</span>
        </div>
        <div class="income-status">
          <div class="income-indicator">
            <div class="money-pulse"></div>
          </div>
        </div>
      </div>
      <div class="compact-body">
        <div id="r_4" class="income-display-enhanced">
          <div id="reloadLreport" class="income-content">
            <?php 
            if ($_SESSION[$session.'sdate'] == $_SESSION[$session.'idhr']){
              echo '<div class="income-stat-card today-card">
                      <div class="income-stat-icon">
                        <i class="fa fa-calendar-o"></i>
                      </div>
                      <div class="income-stat-content">
                        <div class="income-stat-label">Today\'s Revenue</div>
                        <div class="income-stat-value">' . $currency . ' ' . $_SESSION[$session.'dincome'] . '</div>
                        <div class="income-stat-count">' . $_SESSION[$session.'totalHr'] . ' vouchers sold</div>
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
                        <div class="income-stat-label">This Month</div>
                        <div class="income-stat-value">' . $currency . ' ' . $_SESSION[$session.'mincome'] . '</div>
                        <div class="income-stat-count">' . $_SESSION[$session.'totalBl'] . ' total vouchers</div>
                      </div>
                      <div class="income-trend stable">
                        <i class="fa fa-line-chart"></i>
                      </div>
                    </div>';
            } else {
              echo '<div class="income-loading">
                      <div class="loading-spinner"></div>
                      <div class="loading-text">
                        <div class="loading-title">Processing Income Data</div>
                        <div class="loading-subtitle">Calculating revenue metrics...</div>
                      </div>
                    </div>';
            }
            ?>
          </div>                       
        </div>
      </div>
    </div>
    
  </div>

  <!-- Middle Row: Hotspot Management -->
  <div class="middle-row-grid">
    
    <!-- Hotspot Management Card - Now Full Width Horizontal -->
    <div id="r_2" class="compact-card hotspot-management-card">
      <div class="compact-header hotspot-header">
        <div class="header-left">
          <i class="fa fa-wifi header-wifi-icon"></i>
          <span>Hotspot Management</span>
        </div>
        <div class="header-status">
          <div class="wifi-indicator">
            <div class="wifi-wave wave-1"></div>
            <div class="wifi-wave wave-2"></div>
            <div class="wifi-wave wave-3"></div>
          </div>
        </div>
      </div>
      <div class="compact-body">
        <div class="hotspot-grid-enhanced">
          
          <!-- Active Users -->
          <a class="hotspot-card active-card" onclick="cancelPage()" href="./?hotspot=active&session=<?= $session; ?>">
            <div class="card-icon-container">
              <div class="card-icon">
                <i class="fa fa-laptop"></i>
              </div>
              <div class="status-ring active-ring"></div>
            </div>
            <div class="card-content">
              <div class="card-number" data-count="<?= $counthotspotactive; ?>">
                <span class="number-display"><?= $counthotspotactive; ?></span>
                <span class="number-label">active</span>
              </div>
              <div class="card-title">
                Active Users
              </div>
              <div class="card-subtitle">
                Currently online
              </div>
            </div>
          </a>
          
          <!-- Total Users -->
          <a class="hotspot-card users-card" onclick="cancelPage()" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
            <div class="card-icon-container">
              <div class="card-icon">
                <i class="fa fa-users"></i>
              </div>
              <div class="status-ring users-ring"></div>
            </div>
            <div class="card-content">
              <div class="card-number" data-count="<?= $countallusers; ?>">
                <span class="number-display"><?= $countallusers; ?></span>
                <span class="number-label">total</span>
              </div>
              <div class="card-title">
                Total Users
              </div>
              <div class="card-subtitle">
                All registered
              </div>
            </div>
          </a>
          
          <!-- Add User -->
          <a class="hotspot-card add-card" onclick="cancelPage()" href="./?hotspot-user=add&session=<?= $session; ?>">
            <div class="card-icon-container">
              <div class="card-icon">
                <i class="fa fa-user-plus"></i>
              </div>
              <div class="add-icon-bg"></div>
            </div>
            <div class="card-content">
              <div class="card-number">
                <span class="add-symbol">+</span>
              </div>
              <div class="card-title">
                Add User
              </div>
              <div class="card-subtitle">
                Create new user
              </div>
            </div>
          </a>
          
          <!-- Generate Users -->
          <a class="hotspot-card generate-card" onclick="cancelPage()" href="./?hotspot-user=generate&session=<?= $session; ?>">
            <div class="card-icon-container">
              <div class="card-icon">
                <i class="fa fa-magic"></i>
              </div>
              <div class="magic-sparkles">
                <div class="sparkle sparkle-1"></div>
                <div class="sparkle sparkle-2"></div>
                <div class="sparkle sparkle-3"></div>
              </div>
            </div>
            <div class="card-content">
              <div class="card-number">
                <span class="generate-symbol">⚡</span>
              </div>
              <div class="card-title">
                Generate
              </div>
              <div class="card-subtitle">
                Bulk user creation
              </div>
            </div>
          </a>
          
        </div>
      </div>
    </div>
    
  </div>

  <!-- Main Content Grid -->
  <div class="main-grid">
    
    <!-- Traffic Monitor - Fixed: Upload & Download in One Row -->
    <div class="compact-card traffic-card-enhanced">
      <div class="compact-header traffic-header">
        <div class="header-left">
          <i class="fa fa-area-chart traffic-icon"></i>
          <span>Network Traffic Monitor</span>
          <?php 
          $getinterface = $API->comm("/interface/print");
          $interface = $getinterface[$iface - 1]['name']; 
          ?>
          <span class="interface-badge"><?= $interface ?></span>
        </div>
        <div class="traffic-status">
          <div class="traffic-indicator">
            <div class="signal-bar bar-1"></div>
            <div class="signal-bar bar-2"></div>
            <div class="signal-bar bar-3"></div>
            <div class="signal-bar bar-4"></div>
          </div>
        </div>
      </div>
      <div class="compact-body traffic-body">
        <!-- FIXED: Traffic stats in one row -->
        <div class="traffic-stats">
          <div class="traffic-stat upload-stat">
            <div class="stat-icon">
              <i class="fa fa-arrow-up"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Upload</div>
              <div class="stat-value" id="uploadValue">0 bps</div>
            </div>
          </div>
          <div class="traffic-stat download-stat">
            <div class="stat-icon">
              <i class="fa fa-arrow-down"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Download</div>
              <div class="stat-value" id="downloadValue">0 bps</div>
            </div>
          </div>
        </div>
        <div class="chart-container">
          <div id="trafficMonitor"></div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar-stack">
      
      <!-- Hotspot Logs - Enhanced -->
      <div id="r_3" class="compact-card logs-card-enhanced">
        <div class="compact-header logs-header">
          <div class="header-left">
            <a onclick="cancelPage()" href="./?hotspot=log&session=<?= $session; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px;">
              <i class="fa fa-align-justify logs-icon"></i>
              <span>Hotspot Logs</span>
            </a>
          </div>
          <div class="logs-status">
            <div class="logs-indicator">
              <div class="activity-dot"></div>
              <span class="activity-text">Live</span>
            </div>
          </div>
        </div>
        <div class="compact-body logs-body">
          <div class="logs-container">
            <table class="logs-table-enhanced">
              <thead>
                <tr>
                  <th><i class="fa fa-clock-o"></i> Time</th>
                  <th><i class="fa fa-user"></i> User</th>
                  <th><i class="fa fa-comment"></i> Activity</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="3" class="logs-loading">
                    <div class="logs-loading-content">
                      <div class="loading-dots">
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                      </div>
                      <span>Loading recent activity...</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</div>

<!-- Enhanced JavaScript for Better AJAX Handling -->
<script type="text/javascript">
// Improved CSS persistence and income report stability
function ensureCompactStyling() {
  if (!document.getElementById('compact-dashboard-styles')) {
    var existingStyles = document.head.querySelector('style[id*="compact"]');
    if (existingStyles) {
      existingStyles.remove();
    }
    
    var style = document.createElement('style');
    style.id = 'compact-dashboard-styles';
    style.innerHTML = `
      /* Force income report styling stability */
      .income-display-enhanced { padding: 8px !important; }
      .income-content { display: flex !important; flex-direction: column !important; gap: 10px !important; }
      .income-stat-card { 
        background: #383838 !important; 
        border: 1px solid #404040 !important; 
        border-radius: 8px !important; 
        padding: 10px !important; 
        display: flex !important; 
        align-items: center !important; 
        gap: 10px !important; 
        transition: all 0.3s ease !important; 
        position: relative !important; 
        overflow: hidden !important; 
      }
      .income-stat-card::before { 
        content: '' !important; 
        position: absolute !important; 
        top: 0 !important; 
        left: 0 !important; 
        bottom: 0 !important; 
        width: 3px !important; 
      }
      .today-card::before { background: linear-gradient(180deg, #00d084 0%, #4a9eff 100%) !important; }
      .month-card::before { background: linear-gradient(180deg, #4a9eff 0%, #ff9500 100%) !important; }
      .income-stat-icon { 
        width: 28px !important; 
        height: 28px !important; 
        border-radius: 6px !important; 
        display: flex !important; 
        align-items: center !important; 
        justify-content: center !important; 
        font-size: 12px !important; 
        flex-shrink: 0 !important; 
      }
      .today-card .income-stat-icon { background: linear-gradient(135deg, #00d084 0%, #00b574 100%) !important; color: white !important; }
      .month-card .income-stat-icon { background: linear-gradient(135deg, #4a9eff 0%, #3a8ae8 100%) !important; color: white !important; }
      .income-stat-content { flex: 1 !important; min-width: 0 !important; }
      .income-stat-label { font-size: 9px !important; color: #888888 !important; text-transform: uppercase !important; margin-bottom: 3px !important; }
      .income-stat-value { font-size: 14px !important; font-weight: 700 !important; color: #ffffff !important; margin-bottom: 2px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
      .income-stat-count { font-size: 8px !important; color: #b0b0b0 !important; }
      .income-trend { font-size: 10px !important; opacity: 0.7 !important; flex-shrink: 0 !important; }
      .income-trend.up { color: #00d084 !important; }
      .income-trend.stable { color: #4a9eff !important; }
      
      /* Traffic stats in one row */
      .traffic-stats { 
        display: flex !important; 
        gap: 15px !important; 
        margin-bottom: 15px !important; 
        padding: 8px !important; 
        background: rgba(255,255,255,0.02) !important; 
        border-radius: 8px !important; 
        border: 1px solid #404040 !important; 
      }
      .traffic-stat { 
        flex: 1 !important; 
        display: flex !important; 
        align-items: center !important; 
        gap: 10px !important; 
        padding: 8px 12px !important; 
        background: #383838 !important; 
        border-radius: 6px !important; 
      }
      .upload-stat { border-left: 3px solid #ff9500 !important; }
      .download-stat { border-left: 3px solid #00d084 !important; }
      .traffic-stat .stat-icon { 
        width: 32px !important; 
        height: 32px !important; 
        border-radius: 6px !important; 
        display: flex !important; 
        align-items: center !important; 
        justify-content: center !important; 
        font-size: 14px !important; 
        flex-shrink: 0 !important; 
      }
      .upload-stat .stat-icon { background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%) !important; color: white !important; }
      .download-stat .stat-icon { background: linear-gradient(135deg, #00d084 0%, #00b574 100%) !important; color: white !important; }
      .stat-info { flex: 1 !important; min-width: 0 !important; }
      .traffic-stat .stat-label { font-size: 10px !important; color: #888888 !important; text-transform: uppercase !important; margin-bottom: 4px !important; }
      .traffic-stat .stat-value { font-size: 14px !important; font-weight: 700 !important; color: #ffffff !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
      
      /* Enhanced logs loading styles for better UX */
      .logs-loading { text-align: center !important; padding: 20px !important; color: #888888 !important; background: rgba(255,255,255,0.02) !important; }
      .logs-loading-content { display: flex !important; flex-direction: column !important; align-items: center !important; gap: 10px !important; }
      .logs-empty-icon { font-size: 18px !important; color: #4a9eff !important; opacity: 0.7 !important; margin-bottom: 8px !important; }
      .loading-dots { display: flex !important; gap: 4px !important; }
      .loading-dots .dot { width: 6px !important; height: 6px !important; background: #4a9eff !important; border-radius: 50% !important; animation: dotPulse 1.4s ease-in-out infinite both !important; }
      .loading-dots .dot:nth-child(1) { animation-delay: -0.32s !important; }
      .loading-dots .dot:nth-child(2) { animation-delay: -0.16s !important; }
      .loading-dots .dot:nth-child(3) { animation-delay: 0s !important; }
      @keyframes dotPulse { 0%, 80%, 100% { transform: scale(0); opacity: 0.5; } 40% { transform: scale(1); opacity: 1; } }
      
      /* Optimized spacing for 1366x768 */
      .compact-dashboard { padding: 12px; }
      .top-row-grid { gap: 10px; margin-bottom: 12px; }
      .main-grid { gap: 12px; }
      .compact-card { margin-bottom: 12px; }
      .compact-header { padding: 6px 12px; font-size: 13px; }
      .compact-body { padding: 10px 12px; }
      
      /* FIXED 1366x768 specific optimizations */
      @media (max-width: 1400px) and (min-width: 1200px) {
        .compact-dashboard { padding: 8px !important; }
        .top-row-grid { grid-template-columns: 1.6fr 0.8fr 0.8fr !important; gap: 8px !important; }
        .compact-header { padding: 4px 8px !important; font-size: 11px !important; }
        .compact-body { padding: 6px 8px !important; }
        .hotspot-card { padding: 6px 8px !important; min-height: 50px !important; }
        .card-title { font-size: 9px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
        .card-subtitle { font-size: 7px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
        .number-display { font-size: 16px !important; }
        .card-icon { width: 28px !important; height: 28px !important; font-size: 12px !important; }
        .income-stat-card { padding: 6px !important; gap: 6px !important; min-height: 45px !important; }
        .income-stat-value { font-size: 11px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
        .traffic-stats { gap: 6px !important; padding: 4px !important; }
        .traffic-stat { padding: 4px 6px !important; }
        .traffic-stat .stat-value { font-size: 10px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
      }
      
      /* High Resolution Support - 1920x1080 (Conservative scaling) */
      @media (min-width: 1401px) and (max-width: 2000px) {
        .compact-dashboard { padding: 16px !important; }
        .top-row-grid { gap: 16px !important; margin-bottom: 16px !important; }
        .compact-header { padding: 10px 16px !important; font-size: 15px !important; }
        .compact-body { padding: 14px 16px !important; }
        .hotspot-card { padding: 18px 14px !important; gap: 14px !important; min-height: 80px !important; }
        .card-icon { width: 44px !important; height: 44px !important; font-size: 20px !important; }
        .number-display { font-size: 26px !important; }
        .card-title { font-size: 14px !important; }
        .card-subtitle { font-size: 10px !important; }
        .income-stat-card { padding: 14px !important; gap: 14px !important; min-height: 70px !important; }
        .income-stat-icon { width: 32px !important; height: 32px !important; font-size: 14px !important; }
        .income-stat-value { font-size: 16px !important; }
        .traffic-stats { gap: 18px !important; padding: 10px !important; }
        .traffic-stat { padding: 14px 16px !important; gap: 14px !important; }
        .traffic-stat .stat-icon { width: 36px !important; height: 36px !important; font-size: 16px !important; }
        .traffic-stat .stat-value { font-size: 15px !important; }
      }
      
      /* Ultra High Resolution Support - 2880x1800 with 200% scale (Conservative) */
      @media (min-width: 2001px), (min-width: 1400px) and (-webkit-min-device-pixel-ratio: 2) {
        .compact-dashboard { padding: 20px !important; }
        .top-row-grid { gap: 20px !important; margin-bottom: 20px !important; }
        .main-grid { gap: 20px !important; }
        .compact-header { padding: 12px 20px !important; font-size: 16px !important; }
        .compact-body { padding: 16px 20px !important; }
        .hotspot-card { padding: 20px 16px !important; gap: 16px !important; min-height: 90px !important; }
        .card-icon { width: 48px !important; height: 48px !important; font-size: 22px !important; }
        .number-display { font-size: 28px !important; }
        .card-title { font-size: 15px !important; }
        .card-subtitle { font-size: 11px !important; }
        .income-stat-card { padding: 16px !important; gap: 16px !important; min-height: 80px !important; }
        .income-stat-icon { width: 36px !important; height: 36px !important; font-size: 16px !important; }
        .income-stat-value { font-size: 18px !important; }
        .income-stat-label { font-size: 11px !important; }
        .traffic-stats { gap: 20px !important; padding: 12px !important; }
        .traffic-stat { padding: 16px 18px !important; gap: 16px !important; }
        .traffic-stat .stat-icon { width: 40px !important; height: 40px !important; font-size: 18px !important; }
        .traffic-stat .stat-value { font-size: 16px !important; }
        .traffic-stat .stat-label { font-size: 11px !important; }
      }
      
      @media (max-width: 768px) {
        .traffic-stats { flex-direction: column !important; gap: 6px !important; }
      }
    `;
    document.head.appendChild(style);
  }
}

// SIMPLIFIED AJAX handling for income report - No more forced styling
function reloadIncomeReport() {
  var incomeContainer = document.getElementById('reloadLreport');
  if (incomeContainer && typeof $ !== 'undefined') {
    $.ajax({
      url: './include/livereport.php?session=<?= $session ?>',
      type: 'GET',
      cache: false,
      timeout: 10000,
      success: function(data) {
        try {
          var tempDiv = document.createElement('div');
          tempDiv.innerHTML = data;
          var newContent = tempDiv.querySelector('.income-content');
          if (newContent && incomeContainer) {
            // Simply replace content - livereport.php now has consistent styling
            incomeContainer.innerHTML = newContent.innerHTML;
          }
        } catch (e) {
          console.log('Error parsing income data:', e);
        }
      },
      error: function() {
        console.log('Income report reload failed, retrying...');
        setTimeout(reloadIncomeReport, 5000);
      }
    });
  }
}

// Enhanced logs loading with much better error handling
var logsLoading = false;
var logsRetryCount = 0;
var maxRetries = 5;
var lastSuccessTime = Date.now();
var connectionStable = true;

function loadLogsContent() {
  if (logsLoading) return;
  
  var logsContainer = document.querySelector('#r_3 .logs-container');
  if (!logsContainer || typeof $ === 'undefined') return;
  
  var timeSinceLastSuccess = Date.now() - lastSuccessTime;
  var shouldShowLoading = timeSinceLastSuccess > 30000;
  
  if (shouldShowLoading && logsRetryCount === 0) {
    var currentTable = logsContainer.querySelector('.logs-table-enhanced tbody');
    if (currentTable && currentTable.children.length === 0) {
      currentTable.innerHTML = `
        <tr>
          <td colspan="3" class="logs-loading">
            <div class="logs-loading-content">
              <div class="loading-dots">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
              </div>
              <span>Refreshing activity logs...</span>
            </div>
          </td>
        </tr>
      `;
    }
  }
  
  logsLoading = true;
  
  $.ajax({
    url: './include/aload.php?load=logs&session=<?= $session ?>',
    type: 'GET',
    timeout: 15000,
    cache: false,
    dataType: 'html',
    success: function(data) {
      try {
        if (data && data.trim().length > 0) {
          var tempDiv = document.createElement('div');
          tempDiv.innerHTML = data;
          
          var newLogsContainer = tempDiv.querySelector('.logs-container');
          if (newLogsContainer && newLogsContainer.innerHTML.trim()) {
            var newTable = newLogsContainer.querySelector('.logs-table-enhanced');
            var currentTable = logsContainer.querySelector('.logs-table-enhanced');
            
            if (newTable && currentTable) {
              var scrollTop = logsContainer.scrollTop;
              currentTable.innerHTML = newTable.innerHTML;
              logsContainer.scrollTop = scrollTop;
            } else if (newLogsContainer.innerHTML.trim()) {
              logsContainer.innerHTML = newLogsContainer.innerHTML;
            }
            
            logsRetryCount = 0;
            lastSuccessTime = Date.now();
            connectionStable = true;
          } else {
            throw new Error('Empty response');
          }
        } else {
          throw new Error('No data received');
        }
      } catch (e) {
        console.log('Error parsing logs data:', e);
        handleLogsError('parsing');
      }
      logsLoading = false;
    },
    error: function(xhr, status, error) {
      console.log('Logs AJAX error:', status, error);
      handleLogsError(status);
      logsLoading = false;
    }
  });
}

function handleLogsError(errorType) {
  logsRetryCount++;
  
  if (logsRetryCount <= maxRetries) {
    var delay = Math.min(2000 + (logsRetryCount * 1000), 8000);
    setTimeout(function() {
      loadLogsContent();
    }, delay);
    return;
  }
  
  if (logsRetryCount > maxRetries && connectionStable) {
    var logsContainer = document.querySelector('#r_3 .logs-container');
    if (logsContainer) {
      var errorMsg = '';
      var retryTime = '';
      
      switch(errorType) {
        case 'timeout':
          errorMsg = 'Connection timeout';
          retryTime = 'Retrying in 30 seconds...';
          break;
        case 'parsererror':
          errorMsg = 'Data format error';
          retryTime = 'Retrying in 15 seconds...';
          break;
        case 'parsing':
          errorMsg = 'Processing error';
          retryTime = 'Retrying in 10 seconds...';
          break;
        default:
          errorMsg = 'Network unavailable';
          retryTime = 'Retrying in 30 seconds...';
      }
      
      logsContainer.innerHTML = `
        <table class="logs-table-enhanced">
          <thead>
            <tr>
              <th><i class="fa fa-clock-o"></i> Time</th>
              <th><i class="fa fa-user"></i> User</th>
              <th><i class="fa fa-comment"></i> Activity</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="3" class="logs-loading">
                <div class="logs-loading-content">
                  <div class="logs-empty-icon">
                    <i class="fa fa-wifi" style="color: #ff9500;"></i>
                  </div>
                  <span style="color: #ff9500;">${errorMsg}</span>
                  <small style="color: #888888; font-size: 8px;">${retryTime}</small>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      `;
      
      connectionStable = false;
    }
    
    setTimeout(function() {
      logsRetryCount = 0;
      connectionStable = true;
    }, errorType === 'timeout' ? 30000 : 15000);
  }
}

// SIMPLIFIED initialization
$(document).ready(function() {
  // Load logs with delay
  setTimeout(function() {
    loadLogsContent();
  }, 3000);
  
  // SIMPLIFIED intervals - no more forced styling
  setInterval(reloadIncomeReport, 25000); // Every 25 seconds 
  setInterval(loadLogsContent, 35000); // Every 35 seconds
});

// Force styles on any AJAX completion, but heavily debounced
var styleTimeout;
$(document).ajaxComplete(function() {
  clearTimeout(styleTimeout);
  styleTimeout = setTimeout(ensureCompactStyling, 500); // Longer delay
});
</script>

<!-- Traffic Monitor Script - Enhanced -->
<script type="text/javascript"> 
  var chart;
  var sessiondata = "<?= $session ?>";
  var interface = "<?= $interface ?>";
  var n = 3000;
  
  // Format bytes function
  function formatTrafficBytes(bytes) {
    var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
    if (bytes == 0) return '0 bps';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
  }
  
  // Update real-time stats
  function updateTrafficStats(uploadValue, downloadValue) {
    var uploadEl = document.getElementById('uploadValue');
    var downloadEl = document.getElementById('downloadValue');
    if (uploadEl) uploadEl.textContent = formatTrafficBytes(uploadValue);
    if (downloadEl) downloadEl.textContent = formatTrafficBytes(downloadValue);
  }
  
  function requestDatta(session,iface) {
    $.ajax({
      url: './traffic/traffic.php?session='+session+'&iface='+iface,
      datatype: "json",
      success: function(data) {
        var midata = JSON.parse(data);
        if( midata.length > 0 ) {
          var TX=parseInt(midata[0].data);
          var RX=parseInt(midata[1].data);
          var x = (new Date()).getTime(); 
          shift=chart.series[0].data.length > 19;
          chart.series[0].addPoint([x, TX], true, shift);
          chart.series[1].addPoint([x, RX], true, shift);
          
          // Update real-time stats
          updateTrafficStats(TX, RX);
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) { 
        console.error("Traffic data error: " + textStatus); 
      }       
    });
  }	

  $(document).ready(function() {
    Highcharts.setOptions({
      global: {
        useUTC: false
      }
    });

    chart = new Highcharts.Chart({
      chart: {
        renderTo: 'trafficMonitor',
        animation: Highcharts.svg,
        type: 'areaspline',
        backgroundColor: 'transparent',
        height: 240,
        spacing: [10, 10, 10, 10],
        events: {
          load: function () {
            setInterval(function () {
              requestDatta(sessiondata,interface);
            }, 8000);
          }				
        }
      },
      title: {
        text: null
      },
      
      credits: {
        enabled: false
      },
      
      xAxis: {
        type: 'datetime',
        tickPixelInterval: 150,
        maxZoom: 20 * 1000,
        gridLineColor: 'rgba(255,255,255,0.1)',
        lineColor: 'rgba(255,255,255,0.2)',
        tickColor: 'rgba(255,255,255,0.2)',
        labels: {
          style: {
            color: '#b0b0b0',
            fontSize: '10px'
          }
        }
      },
      yAxis: {
        minPadding: 0.2,
        maxPadding: 0.2,
        title: {
          text: null
        },
        gridLineColor: 'rgba(255,255,255,0.1)',
        labels: {
          formatter: function () {      
            var bytes = this.value;                          
            var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
            if (bytes == 0) return '0 bps';
            var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];                    
          },
          style: {
            color: '#b0b0b0',
            fontSize: '10px'
          }
        }      
      },
      
      legend: {
        align: 'center',
        verticalAlign: 'top',
        floating: true,
        y: 10,
        itemStyle: {
          color: '#b0b0b0',
          fontSize: '11px'
        },
        itemHoverStyle: {
          color: '#ffffff'
        }
      },
      
      plotOptions: {
        areaspline: {
          fillOpacity: 0.3,
          marker: {
            enabled: false,
            states: {
              hover: {
                enabled: true,
                radius: 4
              }
            }
          },
          lineWidth: 2
        }
      },
      
      series: [{
        name: '↑ Upload',
        data: [],
        color: '#ff9500',
        fillColor: {
          linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
          stops: [
            [0, 'rgba(255, 149, 0, 0.4)'],
            [1, 'rgba(255, 149, 0, 0.1)']
          ]
        }
      }, {
        name: '↓ Download',
        data: [],
        color: '#00d084',
        fillColor: {
          linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
          stops: [
            [0, 'rgba(0, 208, 132, 0.4)'],
            [1, 'rgba(0, 208, 132, 0.1)']
          ]
        }
      }],

      tooltip: {
        backgroundColor: 'rgba(45, 45, 45, 0.95)',
        borderColor: '#404040',
        borderRadius: 8,
        style: {
          color: '#ffffff',
          fontSize: '11px'
        },
        formatter: function () { 
          var s = [];
          $.each(this.points, function(i, point) {
            var bytes = point.y;
            var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
            if(bytes == 0) {
              s.push('<span style="color:' + this.series.color + '; font-size: 1.2em;">●</span> <b>' + this.series.name + ':</b> 0 bps');
            } else {
              var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
              s.push('<span style="color:' + this.series.color + '; font-size: 1.2em;">●</span> <b>' + this.series.name + ':</b> ' + parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i]);
            }
          });
          return '<div style="text-align: center;"><b>Network Traffic</b><br/><small>' + Highcharts.dateFormat('%H:%M:%S', new Date(this.x)) + '</small><br/>' + s.join('<br/>') + '</div>';
        },
        shared: true,
        useHTML: true                                                   
      }
    });
  });
</script>

<?php 
$_SESSION[$session.'sdate'] = $clock['date'];
?>