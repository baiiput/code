																								<?php
// Logic untuk format validity (sama seperti original)
if(substr($validity,-1) == "d"){
  $validity = "Masa Aktif : ".substr($validity,0,-1)." Hari";
}else if(substr($validity,-1) == "h"){
  $validity = "Masa Aktif : ".substr($validity,0,-1)." Jam";
}

// Logic untuk format timelimit (sama seperti original)
if(substr($timelimit,-1) == "d" & strlen($timelimit) >3){
  $timelimit = "Durasi :".((substr($timelimit,0,-1)*7) +  substr($timelimit, 2,1))." Hari";
}else if(substr($timelimit,-1) == "d"){
  $timelimit = "Durasi :".substr($timelimit,0,-1)." Hari";
}else if(substr($timelimit,-1) == "h"){
  $timelimit = "Durasi :".substr($timelimit,0,-1)." Jam";
}else if(substr($timelimit,-1) == "w"){
  $timelimit = "Durasi :".(substr($timelimit,0,-1)*7)." Hari";
}

// Color mapping berdasarkan price dengan warna yang berbeda untuk pencetakan
if($getprice == "1000"){ $color = "#752CEB";}          // Ungu tua
elseif($getprice == "2000"){ $color = "#13C013";}      // Hijau
elseif($getprice == "3000"){ $color = "#E50877";}      // Pink magenta
elseif($getprice == "4000"){ $color = "#F75418";}      // Orange
elseif($getprice == "5000"){ $color = "#FF69B4";}      // Pink terang
elseif($getprice == "8000"){ $color = "#663399";}      // Ungu sedang
elseif($getprice == "10000"){ $color = "#8B4513";}     // Coklat sedang (ganti dari #663399)
elseif($getprice == "13000"){ $color = "#2E8B57";}     // Hijau laut
elseif($getprice == "15000"){ $color = "#228B22";}     // Hijau hutan (ganti dari #2E8B57)
elseif($getprice == "17000"){ $color = "#0000FF";}     // Biru
elseif($getprice == "20000"){ $color = "#4169E1";}     // Biru royal (ganti dari #0000FF)
elseif($getprice == "25000"){ $color = "#DC143C";}     // Merah crimson
elseif($getprice == "30000"){ $color = "#6495ED";}     // Biru cornflower
elseif($getprice == "40000"){ $color = "#1E90FF";}     // Biru dodger (ganti dari #6495ED)
elseif($getprice == "50000"){ $color = "#FF8C00";}     // Orange tua
elseif($getprice == "80000"){ $color = "#B8860B";}     // Emas tua (ganti dari #FF8C00)
elseif($getprice == "125000"){ $color = "#8B0000";}    // Maroon (ganti dari #DC143C)
elseif($getprice == "150000"){ $color = "#9932CC";}    // Ungu gelap (ganti dari #FF69B4)
elseif($getprice == "175000"){ $color = "#CD853F";}    // Tan (ganti dari #FF69B4)
elseif($getprice == "200000"){ $color = "#2F4F4F";}    // Abu gelap (ganti dari #FF69B4)
elseif($getprice == "250000"){ $color = "#483D8B";}    // Slate biru (ganti dari #FF69B4)
elseif($getprice == "300000"){ $color = "#800080";}    // Purple (ganti dari #FF69B4)
else{ $color = "#000000";}                             // Hitam untuk default (ganti dari #FF69B4)



// Check data kosong untuk responsive layout
$validity_empty = empty($validity) || $validity == "Masa Aktif : ";
$timelimit_empty = empty($timelimit) || $timelimit == "Durasi :";
$datalimit_empty = empty($datalimit);

// Hitung berapa kolom yang aktif
$active_columns = 0;
if (!$validity_empty) $active_columns++;
if (!$timelimit_empty) $active_columns++;
if (!$datalimit_empty) $active_columns++;

// Set grid columns berdasarkan data yang ada
if ($active_columns == 1) {
    $grid_columns = "1fr";
} elseif ($active_columns == 2) {
    $grid_columns = "1fr 1fr";
} else {
    $grid_columns = "1fr 1fr 1fr";
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 10px;
            background: #f5f7fa;
        }
        
.voucher-container {
    display: inline-block;
    width: 140px;
    height: 160px;                  // ← 170px → 160px (KURANGI 10px)
    margin: 2px;
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: white;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    border: 2px solid #d0d0d0;
    transition: all 0.3s ease;
}
        
        .voucher-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.18);
        }
        
        @media print {
            .voucher-container {
                border: 3px solid <?php echo $color; ?> !important;
                box-shadow: none !important;
                margin: 3px !important;
                transform: none !important;
            }
        }
        
        /* Header dengan price dan logo */
        .voucher-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 4px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 20px;
            position: relative;
            border-bottom: 3px solid <?php echo $color; ?>;
        }
        
        .voucher-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, <?php echo $color; ?>40, transparent);
        }
        
        .logo {
            height: 16px;
            width: auto;
            max-width: 60px;
            object-fit: contain;
            font-weight: 700;
            font-size: 8px;
            color: <?php echo $color; ?>;
            text-shadow: none;
        }
        
        .price-display {
            font-size: 8px;
            font-weight: 800;
            background: <?php echo $color; ?>;
            color: white;
            padding: 3px 6px;
            border-radius: 10px;
            box-shadow: 0 1px 3px <?php echo $color; ?>40;
        }
        
.voucher-content {
    padding: 4px 8px;
    height: calc(100% - 48px);
    display: flex;
    flex-direction: column;
    gap: 3px;                       // ← KEMBALIKAN KE 3px
}
        
        /* Credentials - lebih compact */
        .credentials {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 6px 5px;
            text-align: center;
            border-left: 3px solid <?php echo $color; ?>;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .cred-label {
            font-size: 6px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }
        
        .cred-value {
            font-size: 15px;
            font-weight: 700;
            color: #212529;
            line-height: 1.2;
            word-break: break-all;
        }
        
        /* Info grid - 2 kolom untuk efficiency */
.info-grid {
    display: grid;
    grid-template-columns: <?php echo $grid_columns; ?>;  // ← DYNAMIC COLUMNS
    gap: 3px;
    align-content: start;
}
        
.info-item {
    background: #f1f3f4;
    border-radius: 4px;
    padding: 2px 1px;              // ← 3px 2px → 2px 1px
    text-align: center;
    border: 1px solid #e0e0e0;
}
        
.info-label {
    font-size: 5px;
    color: #5f6368;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 1px;
    letter-spacing: 0.2px;
    text-align: center;              // ← TAMBAHKAN INI
    display: block;                  // ← TAMBAHKAN INI
    width: 100%;                     // ← TAMBAHKAN INI
}

.info-value {
    font-size: 5px;
    color: #202124;
    font-weight: 700;
    line-height: 1.0;
    text-align: center;              // ← TAMBAHKAN INI
    display: block;                  // ← TAMBAHKAN INI
    width: 100%;                     // ← TAMBAHKAN INI
}
        

        /* Connection info */
.connection-info {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0fff4 100%);
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    padding: 3px 5px;              // ← 4px 6px → 3px 5px
    text-align: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
        
.connection-text {
    font-size: 6px;
    color: #155724;
    font-weight: 600;
    text-align: center;              // ← TAMBAHKAN INI
    display: block;                  // ← TAMBAHKAN INI
    width: 100%;                     // ← TAMBAHKAN INI
}
        
        /* Comment */
.comment {
    font-size: 6px;
    color: #000000;
    text-align: center;
    font-style: bold;
    padding: 3px 4px;
    background: #fafbfc;
    border-radius: 3px;
    border: 1px solid #f1f3f4;
    margin-top: 1px;               // ← 2px → 1px
}
        
        /* Footer */
        .voucher-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, <?php echo $color; ?>dd 0%, <?php echo $color; ?> 100%);
            color: white;
            text-align: center;
            padding: 4px 6px;
            font-size: 7px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        
        .voucher-footer a {
            color: white;
            text-decoration: none;
        }
        
        /* Decorative corner accent */
        .voucher-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 20px 20px 0;
            border-color: transparent <?php echo $color; ?>25 transparent transparent;
        }
    </style>
</head>
<body>

<!--mks-mulai-->
<div class="voucher-container">
    <!-- Header dengan accent bar berwarna -->
    <div class="voucher-header">
        <img src="<?php echo $logo; ?>" alt="Logo" class="logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
        <span style="display:none; font-size:8px; font-weight:700; color:<?php echo $color; ?>;"><?php echo $logo ?: 'WiFi'; ?></span>
        <div class="price-display"><?= $price; ?></div>
    </div>
    
    <div class="voucher-content">
        <!-- Credentials Section -->
        <div class="credentials">
            <?php if($usermode == "vc"){ ?>
                <div class="cred-label">Kode Voucher</div>
                <div class="cred-value"><?php echo $username; ?></div>
            <?php } elseif($usermode == "up"){ ?>
                <div class="cred-label">Login Account</div>
                <div class="cred-value">
                    <?php echo $username; ?> / <?php echo $password; ?>
                </div>
            <?php } ?>
        </div>
        
        <!-- Connection Info -->
        <div class="connection-info">
            <div class="connection-text">🌐 Masuk ke http://log.in</div>
        </div>
        
<!-- Info Grid -->
<div class="info-grid">
    <?php if (!$validity_empty) { ?>
    <div class="info-item">
        <div class="info-label">⏰ Berlaku</div>
        <div class="info-value"><?php echo str_replace('Masa Aktif : ', '', $validity); ?></div>
    </div>
    <?php } ?>
    
    <?php if (!$timelimit_empty) { ?>
    <div class="info-item">
        <div class="info-label">⏱️ Durasi</div>
        <div class="info-value"><?php echo str_replace('Durasi :', '', $timelimit); ?></div>
    </div>
    <?php } ?>
    
    <?php if (!$datalimit_empty) { ?>
    <div class="info-item">
        <div class="info-label">📊 Kuota</div>
        <div class="info-value"><?php echo $datalimit; ?></div>
    </div>
    <?php } ?>
</div>


        
        <!-- Comment jika ada -->
        <?php if(!empty($comment)){ ?>
        <div class="comment">💬 <?= $comment; ?></div>
        <?php } ?>
    </div>
    
    <!-- Footer -->
    <div class="voucher-footer">
        🔒 Voucher Jangan Hilang • [<?php echo $num; ?>]
    </div>
</div>
<!--mks-akhir-->

</body>
</html>	        	        	        	        