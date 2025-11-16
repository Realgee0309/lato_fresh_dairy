<?php
$available_stock = $db->query("SELECT * FROM stock WHERE quantity > 0 ORDER BY name");
?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-barcode"></i> Barcode Scanner</div>
    </div>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <span>Scan product barcode or batch/lot number to view product details instantly</span>
    </div>
    
    <div class="scanner-container">
        <div class="scanner-input-group">
            <div class="form-group" style="flex: 1;">
                <label>Scan Barcode / Batch Number</label>
                <div style="position: relative;">
                    <input type="text" 
                           id="barcodeInput" 
                           placeholder="Scan or enter barcode/batch number..." 
                           autofocus 
                           autocomplete="off"
                           style="padding-left: 45px; font-size: 18px; height: 60px;">
                    <i class="fas fa-barcode" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 24px; color: var(--primary);"></i>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="clearScan()" style="margin-top: 28px; height: 60px;">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
        
        <div id="scanHistory" style="margin-top: 20px;"></div>
    </div>
    
    <div id="productResult"></div>
</div>

<!-- Quick Batch Lookup -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-search"></i> Quick Batch Lookup</div>
    </div>
    <div class="batch-search-container">
        <div class="form-group">
            <label>Search by Batch/Lot Number</label>
            <input type="text" 
                   id="batchSearch" 
                   placeholder="Enter batch number..." 
                   onkeyup="searchByBatch(this.value)">
        </div>
        <div id="batchResults"></div>
    </div>
</div>

<!-- Generate Barcode for Products -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-qrcode"></i> Generate Product Barcode</div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Select Product</label>
            <select id="productSelect" onchange="generateBarcode(this.value)">
                <option value="">-- Select Product --</option>
                <?php 
                $available_stock->data_seek(0);
                while ($product = $available_stock->fetch_assoc()): 
                ?>
                    <option value="<?php echo $product['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-batch="<?php echo htmlspecialchars($product['batch_number']); ?>"
                            data-price="<?php echo $product['price']; ?>">
                        <?php echo htmlspecialchars($product['name']); ?> 
                        <?php if ($product['batch_number']): ?>
                            - Batch: <?php echo htmlspecialchars($product['batch_number']); ?>
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    
    <div id="barcodeDisplay" style="display: none; text-align: center; padding: 30px; background: white; border-radius: 12px; margin-top: 20px;">
        <div id="barcodeImage"></div>
        <button class="btn btn-primary" onclick="printBarcode()" style="margin-top: 20px;">
            <i class="fas fa-print"></i> Print Barcode
        </button>
        <button class="btn btn-secondary" onclick="downloadBarcode()" style="margin-top: 20px;">
            <i class="fas fa-download"></i> Download
        </button>
    </div>
</div>

<!-- Available Products Table -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-list"></i> Available Products</div>
    </div>
    
    <div class="search-box" style="margin-bottom: 20px;">
        <i class="fas fa-search"></i>
        <input type="text" id="tableSearch" placeholder="Search products..." onkeyup="filterTable()">
    </div>
    
    <div class="table-container">
        <table id="productsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Batch/Lot Number</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Expiry Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $available_stock->data_seek(0);
                while ($product = $available_stock->fetch_assoc()): 
                    $days_left = get_days_until_expiry($product['expiry_date']);
                    $expiry_class = $days_left < 7 ? 'status-expiring' : 'status-ok';
                ?>
                <tr>
                    <td><strong><?php echo $product['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo $product['category']; ?></td>
                    <td>
                        <?php if ($product['batch_number']): ?>
                            <span class="status-badge status-ok">
                                <?php echo htmlspecialchars($product['batch_number']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--text-light);">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['quantity']; ?></td>
                    <td><?php echo format_currency($product['price']); ?></td>
                    <td>
                        <span class="status-badge <?php echo $expiry_class; ?>">
                            <?php echo date('M d, Y', strtotime($product['expiry_date'])); ?>
                            (<?php echo $days_left; ?> days)
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-small" onclick="scanProduct(<?php echo $product['id']; ?>)">
                            <i class="fas fa-barcode"></i> Scan
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.scanner-container {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    padding: 30px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
}

.scanner-input-group {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.batch-search-container {
    padding: 20px;
}

#productResult {
    margin-top: 20px;
}

.product-detail-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow-lg);
    border-left: 5px solid var(--primary);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-detail-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f3f7;
}

.product-detail-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid var(--primary);
}

.product-detail-info {
    flex: 1;
}

.product-detail-name {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 5px;
}

.product-detail-category {
    color: var(--text-light);
    font-size: 14px;
}

.product-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.product-detail-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.product-detail-label {
    font-size: 12px;
    color: var(--text-light);
    text-transform: uppercase;
    margin-bottom: 5px;
}

.product-detail-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
}

.scan-timestamp {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 15px;
    text-align: right;
}

.barcode-svg {
    margin: 20px auto;
    border: 2px solid #ddd;
    padding: 20px;
    border-radius: 8px;
    background: white;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
let scanHistory = [];

// Barcode scanner functionality
document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const input = this.value.trim();
        if (input) {
            searchProduct(input);
            addToHistory(input);
        }
    }
});

function searchProduct(searchTerm) {
    // Show loading
    document.getElementById('productResult').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary);"></i>
            <p style="margin-top: 20px; color: var(--text-light);">Searching...</p>
        </div>
    `;
    
    // Try to find by ID first, then by batch number
    fetch(`api/stock_api.php?action=get&id=${searchTerm}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayProductDetails(data.data);
            } else {
                // Try searching by batch number
                searchByBatch(searchTerm);
            }
        })
        .catch(err => {
            showNotification('Error searching product', 'error');
            document.getElementById('productResult').innerHTML = '';
        });
}

function searchByBatch(batchNumber) {
    if (!batchNumber) {
        document.getElementById('batchResults').innerHTML = '';
        return;
    }
    
    fetch(`api/stock_api.php?action=list&search=${encodeURIComponent(batchNumber)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const filtered = data.data.filter(p => 
                    p.batch_number && p.batch_number.toLowerCase().includes(batchNumber.toLowerCase())
                );
                
                if (filtered.length > 0) {
                    if (filtered.length === 1) {
                        displayProductDetails(filtered[0]);
                    } else {
                        displayBatchResults(filtered);
                    }
                } else {
                    showNotFound();
                }
            } else {
                showNotFound();
            }
        });
}

function displayProductDetails(product) {
    const defaultImages = {
        'Milk': 'https://picsum.photos/seed/milk/100/100.jpg',
        'Yogurt': 'https://picsum.photos/seed/yogurt/100/100.jpg',
        'Cheese': 'https://picsum.photos/seed/cheese/100/100.jpg',
        'Butter': 'https://picsum.photos/seed/butter/100/100.jpg',
        'Cream': 'https://picsum.photos/seed/cream/100/100.jpg'
    };
    
    const imageSrc = product.image_path || defaultImages[product.category] || 'https://picsum.photos/seed/product/100/100.jpg';
    const daysLeft = Math.floor((new Date(product.expiry_date) - new Date()) / (1000 * 60 * 60 * 24));
    const stockStatus = product.quantity < product.alert_level ? 'Low Stock' : 'In Stock';
    const statusClass = product.quantity < product.alert_level ? 'status-low' : 'status-ok';
    
    document.getElementById('productResult').innerHTML = `
        <div class="product-detail-card">
            <div class="product-detail-header">
                <img src="${imageSrc}" class="product-detail-image" alt="${product.name}">
                <div class="product-detail-info">
                    <div class="product-detail-name">${product.name}</div>
                    <div class="product-detail-category">
                        <i class="fas fa-tag"></i> ${product.category}
                    </div>
                </div>
                <span class="status-badge ${statusClass}" style="font-size: 14px; padding: 10px 20px;">
                    ${stockStatus}
                </span>
            </div>
            
            <div class="product-detail-grid">
                <div class="product-detail-item">
                    <div class="product-detail-label">Product ID</div>
                    <div class="product-detail-value">#${product.id}</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Batch/Lot Number</div>
                    <div class="product-detail-value">${product.batch_number || 'N/A'}</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Available Quantity</div>
                    <div class="product-detail-value">${product.quantity} units</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Unit Price</div>
                    <div class="product-detail-value">KES ${parseFloat(product.price).toFixed(2)}</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Expiry Date</div>
                    <div class="product-detail-value">${new Date(product.expiry_date).toLocaleDateString()} (${daysLeft} days)</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Location</div>
                    <div class="product-detail-value">${product.location}</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Supplier</div>
                    <div class="product-detail-value">${product.supplier || 'N/A'}</div>
                </div>
                <div class="product-detail-item">
                    <div class="product-detail-label">Alert Level</div>
                    <div class="product-detail-value">${product.alert_level} units</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="?page=sales" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Record Sale
                </a>
                <button class="btn btn-secondary" onclick="printProductLabel(${product.id})">
                    <i class="fas fa-print"></i> Print Label
                </button>
                <button class="btn btn-secondary" onclick="clearScan()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
            
            <div class="scan-timestamp">
                <i class="fas fa-clock"></i> Scanned at ${new Date().toLocaleTimeString()}
            </div>
        </div>
    `;
    
    // Play success sound (optional)
    playBeep();
    showNotification('Product found successfully!', 'success');
}

function displayBatchResults(products) {
    let html = '<div class="panel"><div class="panel-header"><div class="panel-title">Multiple Products Found</div></div>';
    
    products.forEach(product => {
        html += `
            <div class="customer-card" onclick="scanProduct(${product.id})" style="cursor: pointer;">
                <div class="customer-info">
                    <div class="customer-name">${product.name}</div>
                    <div class="customer-contact">
                        Batch: ${product.batch_number} | Qty: ${product.quantity} | Price: KES ${product.price}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    document.getElementById('productResult').innerHTML = html;
}

function showNotFound() {
    document.getElementById('productResult').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-search" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
            <h3>Product Not Found</h3>
            <p>No product found with this barcode or batch number</p>
            <button class="btn btn-secondary" onclick="clearScan()">
                <i class="fas fa-times"></i> Clear Search
            </button>
        </div>
    `;
    showNotification('Product not found', 'error');
}

function scanProduct(productId) {
    document.getElementById('barcodeInput').value = productId;
    searchProduct(productId);
}

function clearScan() {
    document.getElementById('barcodeInput').value = '';
    document.getElementById('productResult').innerHTML = '';
    document.getElementById('barcodeInput').focus();
}

function addToHistory(searchTerm) {
    scanHistory.unshift({
        term: searchTerm,
        timestamp: new Date().toLocaleTimeString()
    });
    
    if (scanHistory.length > 5) scanHistory.pop();
    
    updateHistoryDisplay();
}

function updateHistoryDisplay() {
    const historyDiv = document.getElementById('scanHistory');
    if (scanHistory.length === 0) {
        historyDiv.innerHTML = '';
        return;
    }
    
    let html = '<div style="margin-top: 15px;"><strong>Recent Scans:</strong><div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">';
    
    scanHistory.forEach(item => {
        html += `
            <span class="status-badge status-ok" style="cursor: pointer;" onclick="searchProduct('${item.term}')">
                ${item.term} <small>(${item.timestamp})</small>
            </span>
        `;
    });
    
    html += '</div></div>';
    historyDiv.innerHTML = html;
}

// Barcode generation
function generateBarcode(productId) {
    if (!productId) {
        document.getElementById('barcodeDisplay').style.display = 'none';
        return;
    }
    
    const select = document.getElementById('productSelect');
    const option = select.options[select.selectedIndex];
    const productName = option.dataset.name;
    const batchNumber = option.dataset.batch;
    const price = option.dataset.price;
    
    const barcodeValue = batchNumber || `PRD${String(productId).padStart(8, '0')}`;
    
    document.getElementById('barcodeDisplay').style.display = 'block';
    document.getElementById('barcodeImage').innerHTML = `
        <h3 style="margin-bottom: 20px;">${productName}</h3>
        <svg id="barcode"></svg>
        <div style="margin-top: 15px;">
            <p><strong>Barcode:</strong> ${barcodeValue}</p>
            ${batchNumber ? `<p><strong>Batch:</strong> ${batchNumber}</p>` : ''}
            <p><strong>Price:</strong> KES ${parseFloat(price).toFixed(2)}</p>
        </div>
    `;
    
    JsBarcode("#barcode", barcodeValue, {
        format: "CODE128",
        width: 2,
        height: 100,
        displayValue: true,
        fontSize: 18,
        margin: 10
    });
}

function printBarcode() {
    const printContent = document.getElementById('barcodeImage').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Barcode</title>');
    printWindow.document.write('<style>body{text-align:center;padding:20px;font-family:Arial;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function downloadBarcode() {
    const svg = document.getElementById('barcode');
    const serializer = new XMLSerializer();
    const svgString = serializer.serializeToString(svg);
    const blob = new Blob([svgString], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'barcode.svg';
    a.click();
    URL.revokeObjectURL(url);
    showNotification('Barcode downloaded!', 'success');
}

function printProductLabel(productId) {
    window.open(`print_label.php?id=${productId}`, '_blank');
}

function filterTable() {
    const input = document.getElementById('tableSearch');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('productsTable');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const tds = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < tds.length; j++) {
            if (tds[j].textContent.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

function playBeep() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 800;
    oscillator.type = 'sine';
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.1);
}

// Auto-focus on input when page loads
window.addEventListener('load', function() {
    document.getElementById('barcodeInput').focus();
});
</script>