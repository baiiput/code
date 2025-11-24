<?php
require_once 'config.php';
requireRole(['admin', 'manager', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();
$success = $error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $supplier_name = clean($_POST['supplier_name'] ?? '');
        $contact_person = clean($_POST['contact_person'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $address = clean($_POST['address'] ?? '');
        
        if (empty($supplier_name)) {
            $error = 'Nama supplier harus diisi';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $supplier_name, $contact_person, $phone, $email, $address);
                $success = $stmt->execute() ? 'Supplier berhasil ditambahkan' : 'Gagal menambahkan supplier';
                $stmt->close();
            } else {
                $supplier_id = intval($_POST['supplier_id']);
                $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, contact_person=?, phone=?, email=?, address=? WHERE supplier_id=?");
                $stmt->bind_param("sssssi", $supplier_name, $contact_person, $phone, $email, $address, $supplier_id);
                $success = $stmt->execute() ? 'Supplier berhasil diupdate' : 'Gagal mengupdate supplier';
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $supplier_id = intval($_POST['supplier_id']);
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
        $success = $stmt->execute() ? 'Supplier berhasil dihapus' : 'Gagal menghapus supplier';
        $stmt->close();
    }
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM suppliers WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (supplier_name LIKE '%$search%' OR contact_person LIKE '%$search%')";
}
$query .= " ORDER BY supplier_name ASC";

$suppliers = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}

$page_title = 'Data Supplier';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1>🏪 Data Supplier</h1>
        <?php if (hasRole('admin')): ?>
        <button class="btn btn-primary" onclick="showAddModal()">+ Tambah Supplier</button>
        <?php endif; ?>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Cari nama supplier atau contact person..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="suppliers.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Supplier</th>
                    <th>Contact Person</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <?php if (hasRole('admin')): ?>
                    <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                <tr><td colspan="6" class="text-center">Belum ada data supplier</td></tr>
                <?php else: ?>
                <?php foreach ($suppliers as $sup): ?>
                <tr>
                    <td><strong><?php echo $sup['supplier_name']; ?></strong></td>
                    <td><?php echo $sup['contact_person'] ?: '-'; ?></td>
                    <td><?php echo $sup['phone'] ?: '-'; ?></td>
                    <td><?php echo $sup['email'] ?: '-'; ?></td>
                    <td><?php echo $sup['address'] ?: '-'; ?></td>
                    <?php if (hasRole('admin')): ?>
                    <td>
                        <button class="btn-sm btn-warning" onclick='editSupplier(<?php echo json_encode($sup); ?>)'>Edit</button>
                        <button class="btn-sm btn-danger" onclick="deleteSupplier(<?php echo $sup['supplier_id']; ?>, '<?php echo addslashes($sup['supplier_name']); ?>')">Hapus</button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="supplierModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Supplier</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="supplierForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="supplier_id" id="supplierId">
            
            <div class="form-group">
                <label>Nama Supplier *</label>
                <input type="text" name="supplier_name" id="supplierName" required>
            </div>
            
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" id="contactPerson">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" name="phone" id="phone">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
            </div>
            
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" id="address" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="supplier_id" id="deleteSupplierId">
</form>

<link rel="stylesheet" href="styles/common.css">
<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Supplier';
    document.getElementById('formAction').value = 'add';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierModal').style.display = 'block';
}

function editSupplier(supplier) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('supplierId').value = supplier.supplier_id;
    document.getElementById('supplierName').value = supplier.supplier_name;
    document.getElementById('contactPerson').value = supplier.contact_person || '';
    document.getElementById('phone').value = supplier.phone || '';
    document.getElementById('email').value = supplier.email || '';
    document.getElementById('address').value = supplier.address || '';
    document.getElementById('supplierModal').style.display = 'block';
}

function deleteSupplier(id, name) {
    if (confirmDelete(`Hapus supplier "${name}"?`)) {
        document.getElementById('deleteSupplierId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('supplierModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('supplierModal');
    if (event.target == modal) closeModal();
}
</script>

<?php include 'includes/footer.php'; ?>
