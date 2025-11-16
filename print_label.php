<?php
require_once 'config.php';
require_login();

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    die('Invalid product ID');
}

// Fetch product details
$result = $db->query("SELECT * FROM stock WHERE id = $product_id");

if ($result->num_rows === 0) {
    die('Product not found');
}

$product = $result->fetch_assoc();

// Default images
$default_images = [
    'Milk' => 'https://picsum.photos/seed/milk/150/150.jpg',
    'Yogurt' => 'https://picsum.photos/seed/yogurt/150/150.jpg',
    'Cheese' => 'https://picsum.photos/seed/cheese/150/150.jpg',
    'Butter' => 'https://picsum.photos/seed/butter/150/150.jpg',
    'Cream' => 'https://picsum.photos/seed/cream/150/150.jpg'
];

$image_src = ($product['image_path'] && file_exists($product['image_path'])) 
    ? $product['image_path'] 
    : ($default_images[$product['category']] ?? 'https://picsum.photos/seed/product/150/150.jpg');

// Generate barcode value
$barcode_value = $product['batch_number'] ?: 'PRD' . str_pad($product_id, 8, '0', STR_PAD_LEFT);

// Calculate expiry status
$days_left = get_days_until_expiry($product['expiry_date']);
$expiry_status = $days_left < 7 ? 'URGENT' : ($days_left < 30 ? 'SOON' : 'OK');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label - <?php echo htmlspecialchars($product['name']); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .print-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Control Panel */
        .control-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        .control-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .control-header h2 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .control-header p {
            color: #666;
            font-size: 14px;
        }

        .label-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .selector-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .selector-card:hover {
            border-color: #1976d2;
            background: #e3f2fd;
            transform: translateY(-2px);
        }

        .selector-card.selected {
            border-color: #1976d2;
            background: #e3f2fd;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }

        .selector-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }

        .selector-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .selector-info {
            flex: 1;
        }

        .selector-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .selector-desc {
            font-size: 12px;
            color: #666;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .quantity-selector label {
            font-size: 12px;
            color: #666;
        }

        .quantity-selector input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .control-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .control-buttons button {
            padding: 12px 30px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-print {
            background: #1976d2;
            color: white;
        }

        .btn-print:hover:not(:disabled) {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
        }

        .btn-print:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-select-all {
            background: #00897b;
            color: white;
        }

        .btn-select-all:hover {
            background: #00796b;
        }

        .btn-close {
            background: #666;
            color: white;
        }

        .btn-close:hover {
            background: #444;
        }

        .selection-summary {
            text-align: center;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .selection-summary strong {
            color: #1976d2;
            font-size: 18px;
        }

        /* Labels Grid */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .label {
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 20px;
            page-break-inside: avoid;
            position: relative;
            overflow: hidden;
        }

        .label.hidden {
            display: none !important;
        }

        .label.small {
            height: 300px;
        }

        .label.large {
            height: 400px;
            grid-column: span 2;
        }

        .label.shelf {
            height: 200px;
        }

        .label-header {
            text-align: center;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 12px;
            color: #666;
        }

        .label-body {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .product-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-details {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .product-details strong {
            color: #333;
        }

        .price-section {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }

        .price-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .price-value {
            font-size: 36px;
            font-weight: bold;
        }

        .barcode-section {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .barcode-section svg {
            max-width: 100%;
            height: auto;
        }

        .batch-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .expiry-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .expiry-warning.urgent {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .expired-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            font-weight: bold;
            color: rgba(220, 53, 69, 0.2);
            z-index: 1;
            pointer-events: none;
        }

        .label.large .product-image {
            width: 150px;
            height: 150px;
        }

        .label.large .product-name {
            font-size: 28px;
        }

        .label.large .price-value {
            font-size: 48px;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .control-panel {
                display: none !important;
            }

            .labels-grid {
                gap: 10mm;
            }

            .label {
                border: 2px solid #000;
                box-shadow: none;
                page-break-inside: avoid;
                margin-bottom: 10mm;
            }

            .label.hidden {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="control-header">
                <h2>🏷️ Label Printing Manager</h2>
                <p>Select the label formats you want to print for <strong><?php echo htmlspecialchars($product['name']); ?></strong></p>
            </div>

            <div class="label-selector">
                <div class="selector-card selected" id="card-large">
                    <label class="selector-label">
                        <input type="checkbox" id="check-large" checked>
                        <div class="selector-info">
                            <div class="selector-title">📋 Large Product Label</div>
                            <div class="selector-desc">Full details with image and complete info</div>
                        </div>
                    </label>
                    <div class="quantity-selector">
                        <label>Copies:</label>
                        <input type="number" id="qty-large" value="1" min="1" max="10">
                    </div>
                </div>

                <div class="selector-card selected" id="card-standard">
                    <label class="selector-label">
                        <input type="checkbox" id="check-standard" checked>
                        <div class="selector-info">
                            <div class="selector-title">📦 Standard Labels</div>
                            <div class="selector-desc">Medium-sized labels for packages</div>
                        </div>
                    </label>
                    <div class="quantity-selector">
                        <label>Copies:</label>
                        <input type="number" id="qty-standard" value="2" min="1" max="10">
                    </div>
                </div>

                <div class="selector-card selected" id="card-shelf">
                    <label class="selector-label">
                        <input type="checkbox" id="check-shelf" checked>
                        <div class="selector-info">
                            <div class="selector-title">🏪 Shelf Labels</div>
                            <div class="selector-desc">Horizontal format for shelf display</div>
                        </div>
                    </label>
                    <div class="quantity-selector">
                        <label>Copies:</label>
                        <input type="number" id="qty-shelf" value="2" min="1" max="10">
                    </div>
                </div>
            </div>

            <div class="selection-summary" id="selectionSummary">
                <strong>5 labels</strong> selected for printing
            </div>

            <div class="control-buttons">
                <button class="btn-select-all" onclick="selectAll()">✓ Select All</button>
                <button class="btn-select-all" onclick="deselectAll()" style="background: #ef5350;">✗ Deselect All</button>
                <button class="btn-print" id="printBtn" onclick="doPrint()">🖨️ Print Selected Labels</button>
                <button class="btn-close" onclick="window.close()">❌ Close</button>
            </div>
        </div>

        <!-- Labels Container -->
        <div class="labels-grid" id="labelsContainer">
            <!-- Labels will be generated here by JavaScript -->
        </div>
    </div>

    <script>
        const productData = {
            name: <?php echo json_encode($product['name']); ?>,
            category: <?php echo json_encode($product['category']); ?>,
            price: <?php echo json_encode($product['price']); ?>,
            quantity: <?php echo json_encode($product['quantity']); ?>,
            location: <?php echo json_encode($product['location']); ?>,
            expiry_date: <?php echo json_encode($product['expiry_date']); ?>,
            batch_number: <?php echo json_encode($product['batch_number']); ?>,
            supplier: <?php echo json_encode($product['supplier']); ?>,
            date_added: <?php echo json_encode($product['date_added']); ?>,
            id: <?php echo json_encode($product['id']); ?>,
            image_src: <?php echo json_encode($image_src); ?>,
            barcode_value: <?php echo json_encode($barcode_value); ?>,
            days_left: <?php echo json_encode($days_left); ?>,
            expiry_status: <?php echo json_encode($expiry_status); ?>
        };

        let labelElements = [];

        // Initialize on page load
        window.addEventListener('load', function() {
            generateLabels();
            updateSummary();
            setupEventListeners();
        });

        function generateLabels() {
            const container = document.getElementById('labelsContainer');
            container.innerHTML = '';
            labelElements = [];

            // Generate Large Label
            const qtyLarge = parseInt(document.getElementById('qty-large').value) || 1;
            for (let i = 0; i < qtyLarge; i++) {
                const label = createLargeLabel();
                container.appendChild(label);
                labelElements.push({ element: label, type: 'large' });
            }

            // Generate Standard Labels
            const qtyStandard = parseInt(document.getElementById('qty-standard').value) || 2;
            for (let i = 0; i < qtyStandard; i++) {
                const label = createStandardLabel();
                container.appendChild(label);
                labelElements.push({ element: label, type: 'standard' });
            }

            // Generate Shelf Labels
            const qtyShelf = parseInt(document.getElementById('qty-shelf').value) || 2;
            for (let i = 0; i < qtyShelf; i++) {
                const label = createShelfLabel();
                container.appendChild(label);
                labelElements.push({ element: label, type: 'shelf' });
            }

            // Generate all barcodes
            setTimeout(() => {
                document.querySelectorAll('.barcode').forEach(barcode => {
                    const height = barcode.dataset.height || 70;
                    const width = barcode.dataset.width || 2;
                    JsBarcode(barcode, productData.barcode_value, {
                        format: "CODE128",
                        width: parseFloat(width),
                        height: parseInt(height),
                        displayValue: false,
                        margin: 5
                    });
                });
            }, 100);

            updateVisibility();
        }

        function createLargeLabel() {
            const div = document.createElement('div');
            div.className = 'label large';
            div.innerHTML = `
                ${productData.days_left < 0 ? '<div class="expired-watermark">EXPIRED</div>' : ''}
                <div class="label-header">
                    <div class="company-name"><?php echo APP_NAME; ?></div>
                    <div class="company-tagline"><?php echo APP_TAGLINE; ?></div>
                </div>
                ${productData.expiry_status !== 'OK' ? `
                    <div class="expiry-warning ${productData.expiry_status === 'URGENT' ? 'urgent' : ''}">
                        ⚠️ ${productData.days_left < 0 ? 'EXPIRED!' : 'EXPIRES IN ' + productData.days_left + ' DAYS'}
                    </div>
                ` : ''}
                <div class="label-body">
                    <img src="${productData.image_src}" alt="${productData.name}" class="product-image">
                    <div class="product-info">
                        <div class="product-name">${productData.name}</div>
                        <span class="product-category">${productData.category}</span>
                        <div class="product-details">
                            <strong>Quantity:</strong> ${productData.quantity} units<br>
                            <strong>Location:</strong> ${productData.location}<br>
                            <strong>Expiry:</strong> ${formatDate(productData.expiry_date)}<br>
                            ${productData.supplier ? `<strong>Supplier:</strong> ${productData.supplier}` : ''}
                        </div>
                    </div>
                </div>
                <div class="price-section">
                    <div class="price-label">RETAIL PRICE</div>
                    <div class="price-value">KES ${parseFloat(productData.price).toFixed(2)}</div>
                </div>
                <div class="barcode-section">
                    <svg class="barcode" data-width="2" data-height="70"></svg>
                    <div style="margin-top: 10px; font-size: 14px; font-weight: bold; font-family: monospace;">
                        ${productData.barcode_value}
                    </div>
                </div>
                <div class="batch-info">
                    <span><strong>Batch:</strong> ${productData.batch_number || 'N/A'}</span>
                    <span><strong>ID:</strong> #${productData.id}</span>
                    <span><strong>Added:</strong> ${formatDate(productData.date_added)}</span>
                </div>
            `;
            return div;
        }

        function createStandardLabel() {
            const div = document.createElement('div');
            div.className = 'label small';
            div.innerHTML = `
                <div class="label-header">
                    <div class="company-name" style="font-size: 18px;"><?php echo APP_NAME; ?></div>
                </div>
                <div class="product-name" style="font-size: 16px; margin-bottom: 10px;">
                    ${productData.name}
                </div>
                <span class="product-category">${productData.category}</span>
                <div class="price-section" style="margin: 15px 0;">
                    <div class="price-label">PRICE</div>
                    <div class="price-value" style="font-size: 28px;">
                        KES ${parseFloat(productData.price).toFixed(2)}
                    </div>
                </div>
                <div class="barcode-section" style="padding: 10px;">
                    <svg class="barcode" data-width="1.5" data-height="50"></svg>
                </div>
                <div style="font-size: 10px; text-align: center; color: #666;">
                    Batch: ${productData.batch_number || 'N/A'}<br>
                    Exp: ${formatDate(productData.expiry_date)}
                </div>
            `;
            return div;
        }

        function createShelfLabel() {
            const div = document.createElement('div');
            div.className = 'label shelf';
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <div class="product-name" style="font-size: 18px;">
                            ${productData.name}
                        </div>
                        <span class="product-category">${productData.category}</span>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 14px; color: #666;">PRICE</div>
                        <div style="font-size: 42px; font-weight: bold; color: #1976d2;">
                            ${parseFloat(productData.price).toFixed(2)}
                        </div>
                        <div style="font-size: 12px; color: #666;">KES</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <svg class="barcode" data-width="1.5" data-height="40" style="max-width: 200px;"></svg>
                    </div>
                    <div style="font-size: 11px; color: #666; text-align: right;">
                        <strong>Batch:</strong> ${(productData.batch_number || 'N/A').substring(0, 15)}<br>
                        <strong>Exp:</strong> ${formatDateShort(productData.expiry_date)}
                    </div>
                </div>
            `;
            return div;
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function formatDateShort(dateStr) {
            const date = new Date(dateStr);
            return (date.getMonth() + 1) + '/' + date.getDate() + '/' + date.getFullYear();
        }

        function updateVisibility() {
            const checkLarge = document.getElementById('check-large').checked;
            const checkStandard = document.getElementById('check-standard').checked;
            const checkShelf = document.getElementById('check-shelf').checked;

            labelElements.forEach(item => {
                if (item.type === 'large' && !checkLarge) {
                    item.element.classList.add('hidden');
                } else if (item.type === 'standard' && !checkStandard) {
                    item.element.classList.add('hidden');
                } else if (item.type === 'shelf' && !checkShelf) {
                    item.element.classList.add('hidden');
                } else {
                    item.element.classList.remove('hidden');
                }
            });

            updateSummary();
        }

        function updateSummary() {
            let total = 0;
            if (document.getElementById('check-large').checked) {
                total += parseInt(document.getElementById('qty-large').value) || 0;
            }
            if (document.getElementById('check-standard').checked) {
                total += parseInt(document.getElementById('qty-standard').value) || 0;
            }
            if (document.getElementById('check-shelf').checked) {
                total += parseInt(document.getElementById('qty-shelf').value) || 0;
            }

            document.getElementById('selectionSummary').innerHTML = 
                `<strong>${total} label${total !== 1 ? 's' : ''}</strong> selected for printing`;
            
            document.getElementById('printBtn').disabled = total === 0;
        }

        function setupEventListeners() {
            // Checkbox change
            ['check-large', 'check-standard', 'check-shelf'].forEach(id => {
                document.getElementById(id).addEventListener('change', function() {
                    const card = document.getElementById('card-' + id.replace('check-', ''));
                    if (this.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                    updateVisibility();
                });
            });

            // Quantity change
            ['qty-large', 'qty-standard', 'qty-shelf'].forEach(id => {
                document.getElementById(id).addEventListener('change', function() {
                    generateLabels();
                });
            });

            // Card click (toggle checkbox)
            ['card-large', 'card-standard', 'card-shelf'].forEach(id => {
                document.getElementById(id).addEventListener('click', function(e) {
                    if (e.target.type !== 'checkbox' && e.target.type !== 'number') {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
            });
        }

        function selectAll() {
            ['check-large', 'check-standard', 'check-shelf'].forEach(id => {
                const checkbox = document.getElementById(id);
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            });
        }

        function deselectAll() {
            ['check-large', 'check-standard', 'check-shelf'].forEach(id => {
                const checkbox = document.getElementById(id);
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            });
        }

        function doPrint() {
            const visibleLabels = document.querySelectorAll('.label:not(.hidden)');
            if (visibleLabels.length === 0) {
                alert('Please select at least one label type to print!');
                return;
            }
            
            window.print();
        }
    </script>
</body>
</html>