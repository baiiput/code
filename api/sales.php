<?php
/**
 * Sales API
 */

require_once '../config/database.php';
require_once '../config/config.php';

requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = Database::getInstance()->getConnection();

    switch ($action) {
        case 'list':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;

            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            $where = ['1=1'];
            $params = [];

            if ($search) {
                $where[] = '(s.invoice_number LIKE ? OR c.name LIKE ?)';
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if ($status) {
                $where[] = 's.payment_status = ?';
                $params[] = $status;
            }

            if ($startDate && $endDate) {
                $where[] = 's.sale_date BETWEEN ? AND ?';
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $whereClause = implode(' AND ', $where);

            // Get total count
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                WHERE $whereClause
            ");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            // Get sales
            $stmt = $db->prepare("
                SELECT
                    s.*,
                    c.name as customer_name,
                    c.code as customer_code,
                    u.full_name as sales_person
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                WHERE $whereClause
                ORDER BY s.sale_date DESC, s.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $sales = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'data' => $sales,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;

            $stmt = $db->prepare("
                SELECT
                    s.*,
                    c.name as customer_name,
                    c.code as customer_code,
                    c.address as customer_address,
                    c.phone as customer_phone,
                    u.full_name as sales_person
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $sale = $stmt->fetch();

            if (!$sale) {
                throw new Exception('Penjualan tidak ditemukan');
            }

            // Get sale details
            $stmt = $db->prepare("
                SELECT
                    sd.*,
                    p.name as product_name,
                    p.sku as product_sku,
                    p.unit
                FROM sales_details sd
                JOIN products p ON sd.product_id = p.id
                WHERE sd.sale_id = ?
            ");
            $stmt->execute([$id]);
            $sale['items'] = $stmt->fetchAll();

            // Get payments
            $stmt = $db->prepare("
                SELECT * FROM payments
                WHERE sale_id = ?
                ORDER BY payment_date DESC
            ");
            $stmt->execute([$id]);
            $sale['payments'] = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'data' => $sale
            ]);
            break;

        case 'create':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['customer_id']) || empty($data['items'])) {
                throw new Exception('Customer dan item harus diisi');
            }

            $db->beginTransaction();

            try {
                // Generate invoice number
                $stmt = $db->query("SELECT MAX(id) as max_id FROM sales");
                $maxId = $stmt->fetch()['max_id'] ?? 0;
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

                // Calculate totals
                $subtotal = 0;
                foreach ($data['items'] as $item) {
                    $itemSubtotal = $item['quantity'] * $item['unit_price'];
                    $itemDiscount = $item['discount_amount'] ?? 0;
                    $subtotal += ($itemSubtotal - $itemDiscount);
                }

                $taxPercentage = $data['tax_percentage'] ?? DEFAULT_TAX_PERCENTAGE;
                $taxAmount = ($subtotal * $taxPercentage) / 100;
                $discountAmount = $data['discount_amount'] ?? 0;
                $shippingCost = $data['shipping_cost'] ?? 0;
                $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

                // Insert sale
                $stmt = $db->prepare("
                    INSERT INTO sales (
                        invoice_number, customer_id, user_id, sale_date, due_date,
                        subtotal, tax_percentage, tax_amount, discount_amount,
                        shipping_cost, total_amount, payment_method, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $invoiceNumber,
                    $data['customer_id'],
                    getCurrentUser()['id'],
                    $data['sale_date'] ?? date('Y-m-d'),
                    $data['due_date'] ?? null,
                    $subtotal,
                    $taxPercentage,
                    $taxAmount,
                    $discountAmount,
                    $shippingCost,
                    $totalAmount,
                    $data['payment_method'] ?? null,
                    $data['notes'] ?? ''
                ]);

                $saleId = $db->lastInsertId();

                // Insert sale details and update stock
                $stmtDetail = $db->prepare("
                    INSERT INTO sales_details (
                        sale_id, product_id, quantity, unit_price,
                        discount_amount, subtotal
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmtStock = $db->prepare("
                    UPDATE products SET stock = stock - ? WHERE id = ?
                ");

                foreach ($data['items'] as $item) {
                    $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);

                    $stmtDetail->execute([
                        $saleId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['discount_amount'] ?? 0,
                        $itemSubtotal
                    ]);

                    // Update stock
                    $stmtStock->execute([
                        $item['quantity'],
                        $item['product_id']
                    ]);
                }

                // Add cash flow entry
                $stmt = $db->prepare("
                    INSERT INTO cash_flow (
                        transaction_date, type, category, description,
                        amount, reference_type, reference_id
                    ) VALUES (?, 'inflow', 'Penjualan', ?, ?, 'sale', ?)
                ");
                $stmt->execute([
                    $data['sale_date'] ?? date('Y-m-d'),
                    "Penjualan - $invoiceNumber",
                    $totalAmount,
                    $saleId
                ]);

                $db->commit();

                jsonResponse([
                    'success' => true,
                    'message' => 'Penjualan berhasil dibuat',
                    'invoice_number' => $invoiceNumber,
                    'id' => $saleId
                ], 201);

            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'add_payment':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['sale_id']) || empty($data['amount'])) {
                throw new Exception('Sale ID dan amount harus diisi');
            }

            $db->beginTransaction();

            try {
                // Get sale info
                $stmt = $db->prepare("SELECT * FROM sales WHERE id = ?");
                $stmt->execute([$data['sale_id']]);
                $sale = $stmt->fetch();

                if (!$sale) {
                    throw new Exception('Penjualan tidak ditemukan');
                }

                // Generate payment number
                $stmt = $db->query("SELECT MAX(id) as max_id FROM payments");
                $maxId = $stmt->fetch()['max_id'] ?? 0;
                $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

                // Insert payment
                $stmt = $db->prepare("
                    INSERT INTO payments (
                        payment_number, sale_id, payment_date, amount,
                        payment_method, reference_number, notes, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $paymentNumber,
                    $data['sale_id'],
                    $data['payment_date'] ?? date('Y-m-d'),
                    $data['amount'],
                    $data['payment_method'] ?? 'cash',
                    $data['reference_number'] ?? '',
                    $data['notes'] ?? '',
                    getCurrentUser()['id']
                ]);

                // Update sale paid amount and payment status
                $newPaidAmount = $sale['paid_amount'] + $data['amount'];
                $paymentStatus = 'partial';

                if ($newPaidAmount >= $sale['total_amount']) {
                    $paymentStatus = 'paid';
                    $newPaidAmount = $sale['total_amount'];
                } elseif ($newPaidAmount == 0) {
                    $paymentStatus = 'unpaid';
                }

                $stmt = $db->prepare("
                    UPDATE sales
                    SET paid_amount = ?, payment_status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$newPaidAmount, $paymentStatus, $data['sale_id']]);

                $db->commit();

                jsonResponse([
                    'success' => true,
                    'message' => 'Pembayaran berhasil ditambahkan',
                    'payment_number' => $paymentNumber
                ], 201);

            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
