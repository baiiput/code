<?php
require_once 'config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'views/navbar.php'; ?>

    <div class="main-container">
        <?php include 'views/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Manajemen Produk</h1>
                <div class="page-actions">
                    <button id="addProductBtn" class="btn btn-primary">
                        <span class="icon">➕</span> Tambah Produk
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem;">
                        <div class="form-group mb-0">
                            <input type="text" id="searchInput" class="form-control" placeholder="Cari produk (nama, SKU)...">
                        </div>
                        <div class="form-group mb-0">
                            <select id="categoryFilter" class="form-select">
                                <option value="">Semua Kategori</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <select id="stockFilter" class="form-select">
                                <option value="">Semua Stok</option>
                                <option value="low">Stok Menipis</option>
                                <option value="out">Habis</option>
                            </select>
                        </div>
                        <button id="filterBtn" class="btn btn-secondary">Filter</button>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga Beli</th>
                                    <th>Harga Jual</th>
                                    <th>Stok</th>
                                    <th>Unit</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="productsTable">
                                <tr>
                                    <td colspan="8" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div id="pagination" style="margin-top: 1.5rem; text-align: center;"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Produk</h3>
                <button class="modal-close" onclick="closeProductModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId" name="id">

                    <div class="form-group">
                        <label for="category_id">Kategori *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU *</label>
                        <input type="text" id="sku" name="sku" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Produk *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="purchase_price">Harga Beli *</label>
                            <input type="number" id="purchase_price" name="purchase_price" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="selling_price">Harga Jual *</label>
                            <input type="number" id="selling_price" name="selling_price" class="form-control" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="stock">Stok</label>
                            <input type="number" id="stock" name="stock" class="form-control" value="0">
                        </div>

                        <div class="form-group">
                            <label for="min_stock">Min. Stok</label>
                            <input type="number" id="min_stock" name="min_stock" class="form-control" value="0">
                        </div>

                        <div class="form-group">
                            <label for="unit">Unit</label>
                            <select id="unit" name="unit" class="form-control">
                                <option value="pcs">pcs</option>
                                <option value="unit">unit</option>
                                <option value="set">set</option>
                                <option value="box">box</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: 0.75rem;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .modal-body {
            padding: 1.5rem;
        }
    </style>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/products.js"></script>
</body>
</html>
