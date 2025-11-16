<?php
// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_sale'])) {
    $customer_id = $_POST['customer_id'] === 'walk-in' ? null : intval($_POST['customer_id']);
    $customer_name = $_POST['customer_id'] === 'walk-in' ? 'Walk-in Customer' : 
        $db->query("SELECT name FROM customers WHERE id = " . intval($_POST['customer_id']))->fetch_assoc()['name'];
    $payment_method = sanitize_input($_POST['payment_method']);
    $amount_paid = floatval($_POST['amount_paid']);
    $seller = $_SESSION['full_name'];
    $sale_date = date('Y-m-d');
    
    // Calculate total from items
    $total = 0;
    $items = [];
    
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        for ($i = 0; $i < count($_POST['product_id']); $i++) {
            if (!empty($_POST['product_id'][$i])) {
                $product_id = intval($_POST['product_id'][$i]);
                $quantity = intval($_POST['quantity'][$i]);
                
                $product_result = $db->query("SELECT name, price, quantity as stock FROM stock WHERE id = $product_id");
                if ($product_result->num_rows > 0) {
                    $product = $product_result->fetch_assoc();
                    
                    if ($product['stock'] < $quantity) {
                        $_SESSION['notification'] = ['message' => "Insufficient stock for {$product['name']}", 'type' => 'error'];
                        header("Location: ?page=sales");
                        exit();
                    }
                    
                    $unit_price = $product['price'];
                    $item_total = $quantity * $unit_price;
                    $total += $item_total;
                    
                    $items[] = [
                        'product_id' => $product_id,
                        'product_name' => $product['name'],
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total' => $item_total
                    ];
                }
            }
        }
    }
    
    if (count($items) > 0) {
        $balance = $total - $amount_paid;
        
        // Start transaction
        $db->getConnection()->begin_transaction();
        
        try {
            // Insert sale
            $stmt = $db->prepare("INSERT INTO sales (customer_id, customer_name, total, payment_method, amount_paid, balance, seller, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsddss", $customer_id, $customer_name, $total, $payment_method, $amount_paid, $balance, $seller, $sale_date);
            $stmt->execute();
            $sale_id = $db->lastInsertId();
            
            // Insert sale items and update stock
            foreach ($items as $item) {
                $item_stmt = $db->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                $item_stmt->bind_param("iisidd", $sale_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['total']);
                $item_stmt->execute();
                
                // Update stock
                $db->query("UPDATE stock SET quantity = quantity - {$item['quantity']} WHERE id = {$item['product_id']}");
            }
            
            // Update customer
            if ($customer_id) {
                $db->query("UPDATE customers SET total_purchases = total_purchases + $total, last_purchase = '$sale_date' WHERE id = $customer_id");
            }
            
            $db->getConnection()->commit();
            
            log_audit('create', "Recorded sale: KES $total for $customer_name");
            
            // Store sale ID in session to show receipt
            $_SESSION['last_sale_id'] = $sale_id;
            $_SESSION['notification'] = ['message' => 'Sale recorded successfully!', 'type' => 'success'];
            $_SESSION['show_receipt'] = true;
            
        } catch (Exception $e) {
            $db->getConnection()->rollback();
            $_SESSION['notification'] = ['message' => 'Failed to record sale', 'type' => 'error'];
        }
    }
}

// Fetch receipt data if print requested
$show_receipt = false;
$receipt_data = null;

// Handle new sale receipt
if (isset($_SESSION['show_receipt']) && isset($_SESSION['last_sale_id'])) {
    $sale_id = intval($_SESSION['last_sale_id']);
    $sale_result = $db->query("SELECT * FROM sales WHERE id = $sale_id");
    if ($sale_result->num_rows > 0) {
        $receipt_data = $sale_result->fetch_assoc();
        $items_result = $db->query("SELECT * FROM sale_items WHERE sale_id = $sale_id");
        $receipt_data['items'] = [];
        while ($item = $items_result->fetch_assoc()) {
            $receipt_data['items'][] = $item;
        }
        $show_receipt = true;
        // Clear the session flag
        unset($_SESSION['show_receipt']);
    }
}

// Handle viewing old receipt
if (isset($_GET['view_receipt'])) {
    $sale_id = intval($_GET['view_receipt']);
    $sale_result = $db->query("SELECT * FROM sales WHERE id = $sale_id");
    if ($sale_result->num_rows > 0) {
        $receipt_data = $sale_result->fetch_assoc();
        $items_result = $db->query("SELECT * FROM sale_items WHERE sale_id = $sale_id");
        $receipt_data['items'] = [];
        while ($item = $items_result->fetch_assoc()) {
            $receipt_data['items'][] = $item;
        }
        $show_receipt = true;
    }
}

// Fetch customers
$customers = $db->query("SELECT id, name FROM customers ORDER BY name");

// Fetch available stock
$available_stock = $db->query("SELECT id, name, price, quantity FROM stock WHERE quantity > 0 ORDER BY name");

// Fetch sales history
$sales_result = $db->query("SELECT s.*, GROUP_CONCAT(si.product_name SEPARATOR ', ') as products 
    FROM sales s 
    LEFT JOIN sale_items si ON s.id = si.sale_id 
    GROUP BY s.id 
    ORDER BY s.created_at DESC 
    LIMIT 20");
?>

<?php if ($show_receipt): ?>
<!-- Receipt Modal -->
<div class="modal active" id="receiptModal" style="display: flex !important;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fas fa-receipt"></i> Sales Receipt</h2>
            <span class="modal-close" onclick="closeReceipt()">&times;</span>
        </div>
        
        <div class="receipt-container" id="receipt-content">
            <div class="receipt-header" style="text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 20px; margin-bottom: 20px;">
                <h2 style="color: var(--primary); margin-bottom: 10px;">LATO FRESH DAIRY</h2>
                <p style="margin: 5px 0;">Pure Dairy, Smart Control</p>
                <p style="margin: 5px 0;">Nairobi, Kenya</p>
                <p style="margin: 5px 0;">Tel: +254 712 345 678</p>
            </div>
            
            <div class="receipt-details" style="margin-bottom: 20px;">
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td><strong>Receipt #:</strong></td>
                        <td style="text-align: right;">RCP-<?php echo str_pad($receipt_data['id'], 6, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td style="text-align: right;"><?php echo date('M d, Y H:i', strtotime($receipt_data['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cashier:</strong></td>
                        <td style="text-align: right;"><?php echo htmlspecialchars($receipt_data['seller']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Customer:</strong></td>
                        <td style="text-align: right;"><?php echo htmlspecialchars($receipt_data['customer_name']); ?></td>
                    </tr>
                </table>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="border-bottom: 2px solid #ddd;">
                        <th style="text-align: left; padding: 10px 5px;">Item</th>
                        <th style="text-align: center; padding: 10px 5px;">Qty</th>
                        <th style="text-align: right; padding: 10px 5px;">Price</th>
                        <th style="text-align: right; padding: 10px 5px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipt_data['items'] as $item): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px 5px;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="text-align: center; padding: 10px 5px;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right; padding: 10px 5px;"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right; padding: 10px 5px;"><strong><?php echo number_format($item['total'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="border-top: 2px dashed #ddd; padding-top: 20px; margin-bottom: 20px;">
                <table style="width: 100%; font-size: 15px;">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td style="text-align: right;"><strong>KES <?php echo number_format($receipt_data['total'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Tax (16%):</strong></td>
                        <td style="text-align: right;"><strong>KES <?php echo number_format($receipt_data['total'] * 0.16, 2); ?></strong></td>
                    </tr>
                    <tr style="font-size: 18px; color: var(--primary);">
                        <td><strong>TOTAL:</strong></td>
                        <td style="text-align: right;"><strong>KES <?php echo number_format($receipt_data['total'] * 1.16, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Amount Paid:</td>
                        <td style="text-align: right;">KES <?php echo number_format($receipt_data['amount_paid'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Balance:</td>
                        <td style="text-align: right;">KES <?php echo number_format($receipt_data['balance'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Payment Method:</td>
                        <td style="text-align: right;"><?php echo ucfirst($receipt_data['payment_method']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div style="text-align: center; border-top: 2px dashed #ddd; padding-top: 20px; font-size: 14px;">
                <p style="margin: 10px 0;"><strong>Thank you for your business!</strong></p>
                <p style="margin: 10px 0;">Please come again</p>
                <p style="margin: 10px 0; font-size: 12px; color: #666;">Served by: <?php echo htmlspecialchars($receipt_data['seller']); ?></p>
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-primary" onclick="printReceipt()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <button class="btn btn-secondary" onclick="closeReceipt()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-shopping-cart"></i> Record New Sale</div>
    </div>
    <form method="POST" action="" id="salesForm">
        <div class="form-row">
            <div class="form-group">
                <label>Customer</label>
                <select name="customer_id" required>
                    <option value="walk-in">Walk-in Customer</option>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="cash">Cash</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="card">Card</option>
                    <option value="credit">Credit</option>
                </select>
            </div>
        </div>
        
        <div id="saleItems">
            <div class="form-row sale-item-row">
                <div class="form-group">
                    <label>Product</label>
                    <select name="product_id[]" class="product-select" required onchange="updatePrice(this)">
                        <option value="">Select Product</option>
                        <?php 
                        $available_stock->data_seek(0);
                        while ($product = $available_stock->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['quantity']; ?> available)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity[]" class="quantity-input" placeholder="0" min="1" required onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label>Unit Price (KES)</label>
                    <input type="number" class="unit-price" placeholder="0.00" step="0.01" readonly>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger btn-small" onclick="removeItem(this)" style="margin-top: 28px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <button type="button" class="btn btn-secondary" onclick="addSaleItem()">
            <i class="fas fa-plus"></i> Add Item
        </button>
        
        <div class="form-row" style="margin-top: 20px;">
            <div class="form-group">
                <label>Grand Total (KES)</label>
                <input type="number" id="grandTotal" placeholder="0.00" step="0.01" readonly>
            </div>
            <div class="form-group">
                <label>Amount Paid (KES)</label>
                <input type="number" name="amount_paid" id="amountPaid" placeholder="0.00" step="0.01" required onchange="calculateBalance()">
            </div>
            <div class="form-group">
                <label>Balance (KES)</label>
                <input type="number" id="balance" placeholder="0.00" step="0.01" readonly>
            </div>
        </div>
        
        <button type="submit" name="record_sale" class="btn btn-primary">
            <i class="fas fa-check"></i> Complete Sale & Print Receipt
        </button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-receipt"></i> Sales History</div>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Products</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Seller</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sales_result->num_rows > 0): ?>
                    <?php while ($sale = $sales_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($sale['products']); ?></td>
                            <td><strong><?php echo format_currency($sale['total']); ?></strong></td>
                            <td><?php echo ucfirst($sale['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($sale['seller']); ?></td>
                            <td>
                                <a href="?page=sales&print_receipt=1&sale_id=<?php echo $sale['id']; ?>" 
                                   class="btn btn-secondary btn-small"
                                   onclick="printOldReceipt(<?php echo $sale['id']; ?>); return false;">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <h3>No sales recorded</h3>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function addSaleItem() {
    const container = document.getElementById('saleItems');
    const firstRow = container.querySelector('.sale-item-row');
    const newRow = firstRow.cloneNode(true);
    
    newRow.querySelectorAll('input').forEach(input => input.value = '');
    newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    
    container.appendChild(newRow);
}

function removeItem(button) {
    const rows = document.querySelectorAll('.sale-item-row');
    if (rows.length > 1) {
        button.closest('.sale-item-row').remove();
        calculateTotal();
    }
}

function updatePrice(select) {
    const row = select.closest('.sale-item-row');
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const stock = option.dataset.stock || 0;
    
    row.querySelector('.unit-price').value = price;
    row.querySelector('.quantity-input').max = stock;
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.sale-item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.unit-price').value) || 0;
        total += quantity * price;
    });
    
    document.getElementById('grandTotal').value = total.toFixed(2);
    calculateBalance();
}

function calculateBalance() {
    const total = parseFloat(document.getElementById('grandTotal').value) || 0;
    const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    document.getElementById('balance').value = (total - paid).toFixed(2);
}

function printReceipt() {
    const receiptContent = document.getElementById('receipt-content').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
    printWindow.document.write('th, td { padding: 8px; text-align: left; }');
    printWindow.document.write('.receipt-header { text-align: center; margin-bottom: 20px; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function closeReceipt() {
    window.location.href = '?page=sales';
}

function printOldReceipt(saleId) {
    // Reload page with receipt for this sale
    window.location.href = '?page=sales&view_receipt=' + saleId;
}

// Auto-print receipt if just completed a sale
<?php if ($show_receipt && isset($_GET['print_receipt'])): ?>
window.onload = function() {
    // Auto-open print dialog after a short delay
    setTimeout(function() {
        if (confirm('Print receipt now?')) {
            printReceipt();
        }
    }, 500);
};
<?php endif; ?>
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex !important;
}

.modal-content {
    background: var(--white);
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.modal-close {
    font-size: 28px;
    cursor: pointer;
    color: #999;
}

.modal-close:hover {
    color: #333;
}

.receipt-container {
    background: white;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

@media print {
    body * {
        visibility: hidden;
    }
    .receipt-container, .receipt-container * {
        visibility: visible;
    }
    .receipt-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>