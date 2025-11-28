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
        $branch_name = clean($_POST['branch_name'] ?? '');
        $contact_person = clean($_POST['contact_person'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $address = clean($_POST['address'] ?? '');
        
        if (empty($branch_name)) {
            $error = 'Nama cabang harus diisi';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO branches (branch_name, contact_person, phone, address) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $branch_name, $contact_person, $phone, $address);
                $success = $stmt->execute() ? 'Cabang berhasil ditambahkan' : 'Gagal menambahkan cabang';
                $stmt->close();
            } else {
                $branch_id = intval($_POST['branch_id']);
                $stmt = $conn->prepare("UPDATE branches SET branch_name=?, contact_person=?, phone=?, address=? WHERE branch_id=?");
                $stmt->bind_param("ssssi", $branch_name, $contact_person, $phone, $address, $branch_id);
                $success = $stmt->execute() ? 'Cabang berhasil diupdate' : 'Gagal mengupdate cabang';
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $branch_id = intval($_POST['branch_id']);
        $stmt = $conn->prepare("DELETE FROM branches WHERE branch_id = ?");
        $stmt->bind_param("i", $branch_id);
        $success = $stmt->execute() ? 'Cabang berhasil dihapus' : 'Gagal menghapus cabang';
        $stmt->close();
    }
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM branches WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (branch_name LIKE '%$search%' OR contact_person LIKE '%$search%')";
}
$query .= " ORDER BY branch_name ASC";

$branches = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

$page_title = 'Data Cabang';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-building"></i> Data Cabang</h1>
        <?php if (hasRole('admin')): ?>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Tambah Cabang
        </button>
        <?php endif; ?>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari nama cabang atau contact person..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 35px;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Cari
            </button>
            <?php if ($search): ?>
            <a href="branches.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="20%">Nama Cabang</th>
                    <th width="18%">Contact Person</th>
                    <th width="13%">Telepon</th>
                    <th>Alamat</th>
                    <?php if (hasRole('admin')): ?>
                    <th width="15%">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($branches)): ?>
                <tr><td colspan="6" class="text-center">Belum ada data cabang</td></tr>
                <?php else: ?>
                <?php foreach ($branches as $index => $branch): ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><strong><?php echo $branch['branch_name']; ?></strong></td>
                    <td><?php echo $branch['contact_person'] ?: '-'; ?></td>
                    <td><?php echo $branch['phone'] ?: '-'; ?></td>
                    <td><?php echo $branch['address'] ?: '-'; ?></td>
                    <?php if (hasRole('admin')): ?>
                    <td style="white-space: nowrap;">
                        <button class="btn-sm btn-warning" onclick='editBranch(<?php echo json_encode($branch); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-sm btn-danger" onclick="deleteBranch(<?php echo $branch['branch_id']; ?>, '<?php echo addslashes($branch['branch_name']); ?>')">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="branchModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-building-circle-check"></i> Tambah Cabang</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="branchForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="branch_id" id="branchId">
            
            <div class="form-group">
                <label><i class="fas fa-building"></i> Nama Cabang *</label>
                <input type="text" name="branch_name" id="branchName" required placeholder="Contoh: Cabang Jakarta">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Contact Person</label>
                <input type="text" name="contact_person" id="contactPerson" placeholder="Nama PIC (opsional)">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Telepon</label>
                <input type="text" name="phone" id="phone" placeholder="Nomor telepon (opsional)">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                <textarea name="address" id="address" rows="3" placeholder="Alamat lengkap cabang (opsional)"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="branch_id" id="deleteBranchId">
</form>

<script>
function showAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-building-circle-check"></i> Tambah Cabang';
    document.getElementById('formAction').value = 'add';
    document.getElementById('branchForm').reset();
    document.getElementById('branchModal').style.display = 'block';
}

function editBranch(branch) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Cabang';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('branchId').value = branch.branch_id;
    document.getElementById('branchName').value = branch.branch_name;
    document.getElementById('contactPerson').value = branch.contact_person || '';
    document.getElementById('phone').value = branch.phone || '';
    document.getElementById('address').value = branch.address || '';
    document.getElementById('branchModal').style.display = 'block';
}

function deleteBranch(id, name) {
    if (confirmDelete(`Hapus cabang "${name}"?`)) {
        document.getElementById('deleteBranchId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('branchModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('branchModal');
    if (event.target == modal) closeModal();
}
</script>

<?php include 'includes/footer.php'; ?>
