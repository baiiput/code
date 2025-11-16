<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();
$success = $error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $category_name = clean($_POST['category_name']);
        $description = clean($_POST['description']);
        
        if (empty($category_name)) {
            $error = 'Nama kategori harus diisi';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $category_name, $description);
                $success = $stmt->execute() ? 'Kategori berhasil ditambahkan' : 'Gagal menambahkan kategori';
                $stmt->close();
            } else {
                $category_id = intval($_POST['category_id']);
                $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
                $stmt->bind_param("ssi", $category_name, $description, $category_id);
                $success = $stmt->execute() ? 'Kategori berhasil diupdate' : 'Gagal mengupdate kategori';
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $category_id = intval($_POST['category_id']);
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $success = $stmt->execute() ? 'Kategori berhasil dihapus' : 'Gagal menghapus kategori';
        $stmt->close();
    }
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM categories WHERE 1=1";
if (!empty($search)) {
    $query .= " AND category_name LIKE '%$search%'";
}
$query .= " ORDER BY category_name ASC";

$categories = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$page_title = 'Kategori Barang';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-folder"></i> Kategori Barang</h1>
        <?php if (hasRole('admin')): ?>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Tambah Kategori
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
                <input type="text" name="search" placeholder="Cari nama kategori..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 35px;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Cari
            </button>
            <?php if ($search): ?>
            <a href="categories.php" class="btn btn-secondary">
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
                    <th width="25%">Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th width="12%">Dibuat</th>
                    <?php if (hasRole('admin')): ?>
                    <th width="15%">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr><td colspan="5" class="text-center">Belum ada data kategori</td></tr>
                <?php else: ?>
                <?php foreach ($categories as $index => $cat): ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><strong><?php echo $cat['category_name']; ?></strong></td>
                    <td><?php echo $cat['description'] ?: '-'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($cat['created_at'])); ?></td>
                    <?php if (hasRole('admin')): ?>
                    <td style="white-space: nowrap;">
                        <button class="btn-sm btn-warning" onclick='editCategory(<?php echo json_encode($cat); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-sm btn-danger" onclick="deleteCategory(<?php echo $cat['category_id']; ?>, '<?php echo addslashes($cat['category_name']); ?>')">
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

<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-folder-plus"></i> Tambah Kategori</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="categoryForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="category_id" id="categoryId">
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Nama Kategori *</label>
                <input type="text" name="category_name" id="categoryName" required placeholder="Contoh: Alat, Bahan Baku, dll">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Deskripsi</label>
                <textarea name="description" id="description" rows="3" placeholder="Deskripsi singkat kategori (optional)"></textarea>
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
    <input type="hidden" name="category_id" id="deleteCategoryId">
</form>

<script>
function showAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-folder-plus"></i> Tambah Kategori';
    document.getElementById('formAction').value = 'add';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(category) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Kategori';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('categoryId').value = category.category_id;
    document.getElementById('categoryName').value = category.category_name;
    document.getElementById('description').value = category.description || '';
    document.getElementById('categoryModal').style.display = 'block';
}

function deleteCategory(id, name) {
    if (confirmDelete(`Hapus kategori "${name}"?`)) {
        document.getElementById('deleteCategoryId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('categoryModal');
    if (event.target == modal) closeModal();
}
</script>

<?php include 'includes/footer.php'; ?>
