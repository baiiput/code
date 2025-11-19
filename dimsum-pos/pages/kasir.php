<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'admin_cabang', 'kasir']);

$pageTitle = 'Kasir';

// Check if user has cabang
$cabangId = Auth::cabangId();
if (!$cabangId && !Auth::isSuperAdmin()) {
    Helper::setFlash('warning', 'Anda belum ditugaskan ke cabang manapun.');
    Helper::redirect('index.php');
}

// For super admin without cabang, use first cabang
if (!$cabangId) {
    $firstCabang = $db->fetch("SELECT id FROM cabang WHERE is_active = 1 LIMIT 1");
    $cabangId = $firstCabang['id'];
}

// Get active pajak
$pajak = $db->fetch("SELECT * FROM pengaturan_pajak WHERE is_active = 1");

// Get active discounts
$diskons = $db->fetchAll("
    SELECT * FROM diskon
    WHERE is_active = 1
    AND (tanggal_mulai IS NULL OR tanggal_mulai <= CURDATE())
    AND (tanggal_selesai IS NULL OR tanggal_selesai >= CURDATE())
    ORDER BY nama ASC
");

// Get payment methods
$metodePembayaran = $db->fetchAll("SELECT * FROM metode_pembayaran WHERE is_active = 1");

// Get categories
$kategoris = $db->fetchAll("SELECT * FROM kategori WHERE is_active = 1 ORDER BY urutan ASC, nama ASC");

// Get all menu with variasi
$menus = $db->fetchAll("
    SELECT m.*, k.nama as kategori_nama,
           mv.id as variasi_id, mv.nama_variasi, mv.harga
    FROM menu m
    JOIN kategori k ON m.kategori_id = k.id
    JOIN menu_variasi mv ON m.id = mv.menu_id AND mv.is_active = 1
    WHERE m.is_active = 1 AND k.is_active = 1
    ORDER BY k.urutan ASC, m.nama ASC, mv.harga ASC
");

// Group menu by kategori
$menuByKategori = [];
foreach ($menus as $menu) {
    $katId = $menu['kategori_id'];
    if (!isset($menuByKategori[$katId])) {
        $menuByKategori[$katId] = [];
    }
    $menuByKategori[$katId][] = $menu;
}

include '../includes/header.php';
?>

<style>
.pos-wrapper { margin: -1.5rem; }
.pos-container { display: flex; height: calc(100vh - var(--header-height)); }
.pos-menu { flex: 1; overflow-y: auto; padding: 1rem; background: var(--bs-tertiary-bg); }
.pos-cart { width: 400px; display: flex; flex-direction: column; background: var(--bs-body-bg); border-left: 1px solid var(--bs-border-color); }
@media (max-width: 991px) {
    .pos-container { flex-direction: column; height: auto; }
    .pos-menu { height: 50vh; }
    .pos-cart { width: 100%; height: auto; border-left: none; border-top: 1px solid var(--bs-border-color); }
}
</style>

<div class="pos-wrapper">
    <div class="pos-container">
        <!-- Menu Section -->
        <div class="pos-menu">
            <!-- Category Filter -->
            <div class="category-pills mb-3">
                <button class="category-pill active" onclick="filterCategory(null, this)">Semua</button>
                <?php foreach ($kategoris as $kat): ?>
                <button class="category-pill" onclick="filterCategory(<?= $kat['id'] ?>, this)"><?= htmlspecialchars($kat['nama']) ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <div class="mb-3">
                <input type="text" class="form-control" id="searchMenu" placeholder="Cari menu..." onkeyup="searchMenu(this.value)">
            </div>

            <!-- Menu Grid -->
            <div class="row g-2" id="menuGrid">
                <?php foreach ($menus as $menu): ?>
                <div class="col-6 col-md-4 col-lg-3 menu-item" data-kategori="<?= $menu['kategori_id'] ?>" data-nama="<?= strtolower($menu['nama']) ?>">
                    <div class="card menu-item-card h-100" onclick="addToCart(<?= htmlspecialchars(json_encode([
                        'variasi_id' => $menu['variasi_id'],
                        'menu_id' => $menu['id'],
                        'nama' => $menu['nama'],
                        'variasi' => $menu['nama_variasi'],
                        'harga' => $menu['harga'],
                        'gambar' => $menu['gambar']
                    ])) ?>)">
                        <?php if ($menu['gambar']): ?>
                        <img src="<?= BASE_URL ?>uploads/menu/<?= $menu['gambar'] ?>" class="card-img-top" alt="">
                        <?php else: ?>
                        <div class="card-img-top bg-secondary-subtle d-flex align-items-center justify-content-center" style="height:100px">
                            <i class="bi bi-image text-muted fs-3"></i>
                        </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <div class="small fw-medium text-truncate"><?= htmlspecialchars($menu['nama']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($menu['nama_variasi']) ?></div>
                            <div class="fw-bold text-primary"><?= Helper::rupiah($menu['harga']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="pos-cart">
            <!-- Cart Header -->
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Pesanan Baru</h6>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearCart()">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </button>
                </div>
                <div class="btn-group w-100">
                    <input type="radio" class="btn-check" name="tipeOrder" id="dinein" value="dine_in" checked>
                    <label class="btn btn-outline-primary btn-sm" for="dinein">Dine In</label>
                    <input type="radio" class="btn-check" name="tipeOrder" id="takeaway" value="take_away">
                    <label class="btn btn-outline-primary btn-sm" for="takeaway">Take Away</label>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-grow-1 overflow-auto p-3" id="cartItems">
                <div class="text-center text-muted py-5" id="emptyCart">
                    <i class="bi bi-cart3 fs-1"></i>
                    <p class="mb-0 mt-2">Keranjang kosong</p>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="p-3 border-top bg-body-tertiary">
                <div class="d-flex justify-content-between mb-1">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay">Rp 0</span>
                </div>

                <!-- Diskon -->
                <div class="d-flex justify-content-between mb-1 align-items-center">
                    <span>Diskon</span>
                    <select class="form-select form-select-sm w-auto" id="diskonSelect" onchange="calculateTotal()">
                        <option value="">Tidak ada</option>
                        <?php foreach ($diskons as $d): ?>
                        <option value="<?= $d['id'] ?>" data-tipe="<?= $d['tipe'] ?>" data-nilai="<?= $d['nilai'] ?>">
                            <?= htmlspecialchars($d['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex justify-content-between mb-1 text-success">
                    <span></span>
                    <span id="diskonDisplay">- Rp 0</span>
                </div>

                <?php if ($pajak): ?>
                <div class="d-flex justify-content-between mb-1">
                    <span><?= htmlspecialchars($pajak['nama']) ?> (<?= $pajak['persentase'] ?>%)</span>
                    <span id="pajakDisplay">Rp 0</span>
                </div>
                <?php endif; ?>

                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total</span>
                    <span class="text-primary" id="totalDisplay">Rp 0</span>
                </div>
            </div>

            <!-- Payment -->
            <div class="p-3 border-top">
                <div class="mb-2">
                    <select class="form-select" id="metodePembayaran">
                        <?php foreach ($metodePembayaran as $mp): ?>
                        <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2" id="uangDiterimaGroup">
                    <input type="number" class="form-control" id="uangDiterima" placeholder="Uang diterima" onkeyup="calculateChange()">
                    <div class="d-flex justify-content-between mt-1">
                        <small>Kembalian:</small>
                        <small class="fw-bold" id="kembalianDisplay">Rp 0</small>
                    </div>
                </div>
                <button class="btn btn-primary w-100 btn-lg" onclick="processPayment()">
                    <i class="bi bi-check-circle me-2"></i>Bayar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-check-circle text-success" style="font-size:4rem"></i>
                <h4 class="mt-3">Transaksi Berhasil!</h4>
                <p class="text-muted mb-0">No. Transaksi: <strong id="noTransaksi"></strong></p>
                <p class="fs-4 fw-bold text-primary" id="totalBayar"></p>
                <p id="kembalianInfo"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-primary" onclick="newTransaction()">
                    <i class="bi bi-plus me-1"></i>Transaksi Baru
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
const CABANG_ID = ' . $cabangId . ';
const PAJAK_PERSEN = ' . ($pajak ? $pajak['persentase'] : 0) . ';

let cart = [];

function filterCategory(kategoriId, btn) {
    document.querySelectorAll(".category-pill").forEach(p => p.classList.remove("active"));
    btn.classList.add("active");

    document.querySelectorAll(".menu-item").forEach(item => {
        if (!kategoriId || item.dataset.kategori == kategoriId) {
            item.style.display = "block";
        } else {
            item.style.display = "none";
        }
    });
}

function searchMenu(query) {
    query = query.toLowerCase();
    document.querySelectorAll(".menu-item").forEach(item => {
        if (item.dataset.nama.includes(query)) {
            item.style.display = "block";
        } else {
            item.style.display = "none";
        }
    });
}

function addToCart(item) {
    const existing = cart.find(c => c.variasi_id === item.variasi_id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ ...item, qty: 1 });
    }
    renderCart();
    Toast.success("Ditambahkan ke keranjang");
}

function updateQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) {
        cart.splice(index, 1);
    }
    renderCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    if (cart.length && confirm("Hapus semua item dari keranjang?")) {
        cart = [];
        renderCart();
    }
}

function renderCart() {
    const container = document.getElementById("cartItems");
    const empty = document.getElementById("emptyCart");

    if (cart.length === 0) {
        container.innerHTML = "";
        container.appendChild(empty);
        empty.style.display = "block";
    } else {
        empty.style.display = "none";
        container.innerHTML = cart.map((item, i) => `
            <div class="cart-item d-flex align-items-center py-2 border-bottom">
                <div class="flex-grow-1">
                    <div class="fw-medium small">${item.nama}</div>
                    <div class="text-muted small">${item.variasi} - ${Format.rupiah(item.harga)}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${i}, -1)">-</button>
                    <span class="fw-bold">${item.qty}</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${i}, 1)">+</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${i})">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        `).join("");
    }

    calculateTotal();
}

function calculateTotal() {
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);

    // Calculate discount
    let diskon = 0;
    const diskonSelect = document.getElementById("diskonSelect");
    const selected = diskonSelect.options[diskonSelect.selectedIndex];
    if (selected.value) {
        const tipe = selected.dataset.tipe;
        const nilai = parseFloat(selected.dataset.nilai);
        if (tipe === "persen" || tipe === "happy_hour" || tipe === "member") {
            diskon = subtotal * (nilai / 100);
        } else if (tipe === "nominal") {
            diskon = nilai;
        }
    }

    // Calculate tax
    const afterDiskon = subtotal - diskon;
    const pajak = afterDiskon * (PAJAK_PERSEN / 100);
    const total = afterDiskon + pajak;

    document.getElementById("subtotalDisplay").textContent = Format.rupiah(subtotal);
    document.getElementById("diskonDisplay").textContent = "- " + Format.rupiah(diskon);
    document.getElementById("totalDisplay").textContent = Format.rupiah(total);

    if (document.getElementById("pajakDisplay")) {
        document.getElementById("pajakDisplay").textContent = Format.rupiah(pajak);
    }

    calculateChange();
}

function calculateChange() {
    const total = parseFloat(document.getElementById("totalDisplay").textContent.replace(/[^0-9]/g, "")) || 0;
    const uang = parseFloat(document.getElementById("uangDiterima").value) || 0;
    const kembalian = Math.max(0, uang - total);
    document.getElementById("kembalianDisplay").textContent = Format.rupiah(kembalian);
}

async function processPayment() {
    if (cart.length === 0) {
        Toast.error("Keranjang masih kosong!");
        return;
    }

    const total = parseFloat(document.getElementById("totalDisplay").textContent.replace(/[^0-9]/g, ""));
    const uangDiterima = parseFloat(document.getElementById("uangDiterima").value) || 0;

    const metodePembayaran = document.getElementById("metodePembayaran");
    const isNonCash = metodePembayaran.options[metodePembayaran.selectedIndex].text !== "Tunai";

    if (!isNonCash && uangDiterima < total) {
        Toast.error("Uang yang diterima kurang!");
        return;
    }

    const data = {
        cabang_id: CABANG_ID,
        tipe_order: document.querySelector("input[name=tipeOrder]:checked").value,
        items: cart,
        diskon_id: document.getElementById("diskonSelect").value || null,
        metode_pembayaran_id: metodePembayaran.value,
        uang_diterima: isNonCash ? total : uangDiterima
    };

    try {
        const response = await fetch("' . BASE_URL . 'pages/api/transaksi.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById("noTransaksi").textContent = result.no_transaksi;
            document.getElementById("totalBayar").textContent = Format.rupiah(result.total);

            if (!isNonCash || uangDiterima > total) {
                document.getElementById("kembalianInfo").innerHTML =
                    `Kembalian: <strong>${Format.rupiah(result.kembalian)}</strong>`;
            } else {
                document.getElementById("kembalianInfo").innerHTML = "";
            }

            new bootstrap.Modal(document.getElementById("successModal")).show();
            cart = [];
            renderCart();
            document.getElementById("uangDiterima").value = "";
            document.getElementById("diskonSelect").value = "";
        } else {
            Toast.error(result.message || "Gagal memproses transaksi");
        }
    } catch (error) {
        Toast.error("Terjadi kesalahan: " + error.message);
    }
}

function newTransaction() {
    bootstrap.Modal.getInstance(document.getElementById("successModal")).hide();
    cart = [];
    renderCart();
    document.getElementById("uangDiterima").value = "";
    document.getElementById("diskonSelect").value = "";
}

// Initialize
renderCart();
</script>';

include '../includes/footer.php';
?>
