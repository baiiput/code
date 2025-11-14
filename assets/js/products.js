/**
 * Products Page JavaScript
 */

let currentPage = 1;
let currentCategory = '';
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadProducts();

    // Event listeners
    document.getElementById('addProductBtn').addEventListener('click', openAddModal);
    document.getElementById('productForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('filterBtn').addEventListener('click', applyFilters);

    // Search with debounce
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', debounce(function() {
        currentSearch = this.value;
        currentPage = 1;
        loadProducts();
    }, 500));
});

async function loadCategories() {
    try {
        const data = await apiRequest('api/products.php?action=categories');

        const categoryFilter = document.getElementById('categoryFilter');
        const categorySelect = document.getElementById('category_id');

        data.data.forEach(category => {
            const option1 = document.createElement('option');
            option1.value = category.id;
            option1.textContent = category.name;
            categoryFilter.appendChild(option1);

            const option2 = document.createElement('option');
            option2.value = category.id;
            option2.textContent = category.name;
            categorySelect.appendChild(option2);
        });
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

async function loadProducts(page = 1) {
    try {
        currentPage = page;

        let url = `api/products.php?action=list&page=${page}`;
        if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
        if (currentCategory) url += `&category=${currentCategory}`;

        const data = await apiRequest(url);

        renderProductsTable(data.data);
        renderPagination(data.pagination);
    } catch (error) {
        console.error('Error loading products:', error);
        document.getElementById('productsTable').innerHTML =
            '<tr><td colspan="8" class="text-center">Error loading products</td></tr>';
    }
}

function renderProductsTable(products) {
    const tbody = document.getElementById('productsTable');

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = products.map(product => {
        const stockClass = product.stock <= product.min_stock ? 'text-danger' : '';
        const profit = product.selling_price - product.purchase_price;
        const margin = ((profit / product.purchase_price) * 100).toFixed(1);

        return `
            <tr>
                <td><strong>${product.sku}</strong></td>
                <td>
                    ${product.name}
                    ${product.description ? `<br><small class="text-muted">${product.description.substring(0, 50)}...</small>` : ''}
                </td>
                <td>${product.category_name}</td>
                <td>${formatCurrency(product.purchase_price)}</td>
                <td>
                    ${formatCurrency(product.selling_price)}
                    <br><small class="text-success">+${margin}%</small>
                </td>
                <td class="${stockClass}"><strong>${product.stock}</strong> / ${product.min_stock}</td>
                <td>${product.unit}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="editProduct(${product.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id}, '${product.name}')">Hapus</button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination');

    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">';

    // Previous button
    if (pagination.page > 1) {
        html += `<button class="btn btn-secondary" onclick="loadProducts(${pagination.page - 1})">‹ Prev</button>`;
    }

    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="btn btn-primary">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.page) <= 2) {
            html += `<button class="btn btn-secondary" onclick="loadProducts(${i})">${i}</button>`;
        } else if (Math.abs(i - pagination.page) === 3) {
            html += '<span>...</span>';
        }
    }

    // Next button
    if (pagination.page < pagination.total_pages) {
        html += `<button class="btn btn-secondary" onclick="loadProducts(${pagination.page + 1})">Next ›</button>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

function applyFilters() {
    currentCategory = document.getElementById('categoryFilter').value;
    currentPage = 1;
    loadProducts();
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Produk';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productModal').style.display = 'flex';
}

async function editProduct(id) {
    try {
        const data = await apiRequest(`api/products.php?action=get&id=${id}`);
        const product = data.data;

        document.getElementById('modalTitle').textContent = 'Edit Produk';
        document.getElementById('productId').value = product.id;
        document.getElementById('category_id').value = product.category_id;
        document.getElementById('sku').value = product.sku;
        document.getElementById('name').value = product.name;
        document.getElementById('description').value = product.description || '';
        document.getElementById('purchase_price').value = product.purchase_price;
        document.getElementById('selling_price').value = product.selling_price;
        document.getElementById('stock').value = product.stock;
        document.getElementById('min_stock').value = product.min_stock;
        document.getElementById('unit').value = product.unit;

        document.getElementById('productModal').style.display = 'flex';
    } catch (error) {
        alert('Error loading product: ' + error.message);
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    const id = document.getElementById('productId').value;
    const action = id ? 'update' : 'create';

    try {
        const response = await apiRequest(`api/products.php?action=${action}`, {
            method: 'POST',
            body: JSON.stringify(data)
        });

        alert(response.message);
        closeProductModal();
        loadProducts(currentPage);
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deleteProduct(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus produk "${name}"?`)) {
        return;
    }

    try {
        const response = await apiRequest('api/products.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id })
        });

        alert(response.message);
        loadProducts(currentPage);
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('productModal');
    if (e.target === modal) {
        closeProductModal();
    }
});
