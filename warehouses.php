<?php
require_once 'config.php';
requireRole(['admin', 'manager']);

$conn = getDBConnection();
$user = getCurrentUser();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $warehouse_code = isset($_POST['warehouse_code']) ? strtoupper(clean($_POST['warehouse_code'])) : '';
        $warehouse_name = clean($_POST['warehouse_name']);
        $address = clean($_POST['address']);
        $phone = clean($_POST['phone']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'add' && empty($warehouse_code)) {
            $error = 'Kode warehouse harus diisi';
        } elseif (empty($warehouse_name)) {
            $error = 'Nama warehouse harus diisi';
        } else {
            if ($action === 'add') {
                // Check duplicate code
                $stmt = $conn->prepare("SELECT warehouse_id FROM warehouses WHERE warehouse_code = ?");
                $stmt->bind_param("s", $warehouse_code);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Kode warehouse sudah digunakan';
                } else {
                    $stmt = $conn->prepare("INSERT INTO warehouses (warehouse_code, warehouse_name, address, phone, is_active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssi", $warehouse_code, $warehouse_name, $address, $phone, $is_active);
                    if ($stmt->execute()) {
                        logActivity('CREATE', 'warehouse', "Created warehouse: $warehouse_code - $warehouse_name");
                        $success = 'Warehouse berhasil ditambahkan';
                    } else {
                        $error = 'Gagal menambahkan warehouse';
                    }
                }
                $stmt->close();
            } else {
                $warehouse_id = intval($_POST['warehouse_id']);
                $stmt = $conn->prepare("UPDATE warehouses SET warehouse_name = ?, address = ?, phone = ?, is_active = ? WHERE warehouse_id = ?");
                $stmt->bind_param("sssii", $warehouse_name, $address, $phone, $is_active, $warehouse_id);
                if ($stmt->execute()) {
                    logActivity('UPDATE', 'warehouse', "Updated warehouse: $warehouse_name");
                    $success = 'Warehouse berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate warehouse';
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $warehouse_id = intval($_POST['warehouse_id']);

        // Check if warehouse has transactions
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM stock_in WHERE warehouse_id = ?");
        $stmt->bind_param("i", $warehouse_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $error = 'Warehouse tidak dapat dihapus karena sudah memiliki transaksi';
        } else {
            // Also delete warehouse_items for this warehouse
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM warehouse_items WHERE warehouse_id = ?");
                $stmt->bind_param("i", $warehouse_id);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM warehouses WHERE warehouse_id = ?");
                $stmt->bind_param("i", $warehouse_id);
                $stmt->execute();

                $conn->commit();
                logActivity('DELETE', 'warehouse', "Deleted warehouse ID: $warehouse_id");
                $success = 'Warehouse berhasil dihapus';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Gagal menghapus warehouse: ' . $e->getMessage();
            }
        }
        $stmt->close();
    }
}

// Get warehouses list
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM warehouses WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (warehouse_code LIKE '%$search%' OR warehouse_name LIKE '%$search%')";
}

$query .= " ORDER BY warehouse_code ASC";

$warehouses = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    // Get total items in this warehouse
    $stmt = $conn->prepare("SELECT COUNT(*) as total_items, SUM(current_stock * average_cost) as total_value FROM warehouse_items WHERE warehouse_id = ?");
    $stmt->bind_param("i", $row['warehouse_id']);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $row['total_items'] = $stats['total_items'];
    $row['total_value'] = $stats['total_value'] ?? 0;
    $stmt->close();

    $warehouses[] = $row;
}

$page_title = 'Data Warehouse';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1>🏭 Data Warehouse</h1>
        <button class="btn btn-primary" onclick="showAddModal()">+ Tambah Warehouse</button>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari kode atau nama warehouse..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="warehouses.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <!-- Warehouses Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Warehouse</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th class="text-right">Total Item</th>
                    <th class="text-right">Nilai Stok</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warehouses)): ?>
                <tr>
                    <td colspan="8" class="text-center">Belum ada data warehouse</td>
                </tr>
                <?php else: ?>
                <?php foreach ($warehouses as $wh): ?>
                <tr>
                    <td><strong><?php echo $wh['warehouse_code']; ?></strong></td>
                    <td><?php echo $wh['warehouse_name']; ?></td>
                    <td><?php echo $wh['address'] ?: '-'; ?></td>
                    <td><?php echo $wh['phone'] ?: '-'; ?></td>
                    <td class="text-right"><?php echo number_format($wh['total_items']); ?></td>
                    <td class="text-right"><?php echo formatRupiah($wh['total_value']); ?></td>
                    <td class="text-center">
                        <span class="badge badge-<?php echo $wh['is_active'] ? 'success' : 'secondary'; ?>">
                            <?php echo $wh['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn-sm btn-warning" onclick='editWarehouse(<?php echo json_encode($wh); ?>)'>Edit</button>
                        <button class="btn-sm btn-danger" onclick="deleteWarehouse(<?php echo $wh['warehouse_id']; ?>, '<?php echo $wh['warehouse_code']; ?>')">Hapus</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="warehouseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Warehouse</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="warehouseForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="warehouse_id" id="warehouseId">

            <div class="form-group">
                <label>Kode Warehouse *</label>
                <input type="text" name="warehouse_code" id="warehouseCode" required placeholder="WH-001" style="text-transform: uppercase;">
            </div>

            <div class="form-group">
                <label>Nama Warehouse *</label>
                <input type="text" name="warehouse_name" id="warehouseName" required placeholder="Warehouse Jakarta Pusat">
            </div>

            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" id="address" rows="3" placeholder="Alamat lengkap warehouse"></textarea>
            </div>

            <div class="form-group">
                <label>Telepon</label>
                <input type="text" name="phone" id="phone" placeholder="021-1234567">
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="isActive" value="1" checked style="width: auto;">
                    <span>Aktif</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="warehouse_id" id="deleteWarehouseId">
</form>

<?php include 'includes/footer.php'; ?>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Warehouse';
    document.getElementById('formAction').value = 'add';
    document.getElementById('warehouseForm').reset();
    document.getElementById('warehouseCode').disabled = false;
    document.getElementById('isActive').checked = true;
    document.getElementById('warehouseModal').style.display = 'block';
}

function editWarehouse(warehouse) {
    document.getElementById('modalTitle').textContent = 'Edit Warehouse';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('warehouseId').value = warehouse.warehouse_id;
    document.getElementById('warehouseCode').value = warehouse.warehouse_code;
    document.getElementById('warehouseCode').disabled = true;
    document.getElementById('warehouseName').value = warehouse.warehouse_name;
    document.getElementById('address').value = warehouse.address || '';
    document.getElementById('phone').value = warehouse.phone || '';
    document.getElementById('isActive').checked = warehouse.is_active == 1;
    document.getElementById('warehouseModal').style.display = 'block';
}

function deleteWarehouse(warehouseId, warehouseCode) {
    if (confirmDelete(`Hapus warehouse "${warehouseCode}"?`)) {
        document.getElementById('deleteWarehouseId').value = warehouseId;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('warehouseModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('warehouseModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>
