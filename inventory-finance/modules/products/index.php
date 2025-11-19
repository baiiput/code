<?php
$pageTitle = 'Produk';
require_once __DIR__ . '/../../templates/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    delete('products', (int)$_GET['delete']);
    setFlash('success', 'Produk berhasil dihapus');
    header('Location: ' . BASE_URL . 'modules/products/');
    exit;
}

// Filters
$filter = $_GET['filter'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$db = getDB();
$sql = "SELECT p.*, c.name as category_name,
        (SELECT COUNT(*) FROM product_serials ps WHERE ps.product_id = p.id AND ps.status = 'available') as stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";
$params = [];

if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter == 'low_stock') {
    $sql .= " HAVING stock <= p.min_stock AND p.min_stock > 0";
}

$sql .= " ORDER BY p.name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = getAll('categories', ['is_active' => 1], 'name ASC');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Produk</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Produk' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/products/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah Produk
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-3 align-items-center">
            <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
                <input type="text" name="search" class="form-control" placeholder="Cari produk..."
                       value="<?= e($search) ?>">
            </div>
            <div class="form-group mb-0" style="min-width: 150px;">
                <select name="category" class="form-control form-select">
                    <option value="">Semua Kategori</option>
                    <?= selectOptions($categories, $category) ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <select name="filter" class="form-control form-select">
                    <option value="">Semua Status</option>
                    <option value="low_stock" <?= $filter == 'low_stock' ? 'selected' : '' ?>>Stok Menipis</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
            </button>
            <a href="<?= BASE_URL ?>modules/products/" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Harga Jual</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada produk ditemukan</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <strong><?= e($product['code']) ?></strong>
                            <?php if ($product['barcode']): ?>
                            <br><small class="text-muted"><?= e($product['barcode']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($product['name']) ?>
                            <?php if ($product['brand']): ?>
                            <br><small class="text-muted"><?= e($product['brand']) ?> <?= e($product['model']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($product['category_name']) ?></td>
                        <td class="text-center">
                            <?php
                            $stockClass = 'bg-success';
                            if ($product['stock'] == 0) $stockClass = 'bg-danger';
                            elseif ($product['stock'] <= $product['min_stock']) $stockClass = 'bg-warning';
                            ?>
                            <span class="badge <?= $stockClass ?>"><?= $product['stock'] ?></span>
                        </td>
                        <td class="text-end"><?= formatCurrency($product['default_buy_price']) ?></td>
                        <td class="text-end"><?= formatCurrency($product['default_sell_price']) ?></td>
                        <td>
                            <?php if ($product['is_active']): ?>
                            <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/products/view.php?id=<?= $product['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>modules/products/form.php?id=<?= $product['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $product['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus produk ini?"
                                   title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
