<?php
/**
 * Developer Database Schema Viewer
 * Standalone page for developers only - NOT linked from admin panel
 * URL: http://localhost/store/dev/schema.php
 */

require_once __DIR__ . '/../config/config.php';

// Developer access check - only allow local development access
$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost']);
$hasDevKey = isset($_GET['key']) && $_GET['key'] === 'dev2024';

if (!$isLocal && !$hasDevKey) {
    http_response_code(403);
    die('Access denied. Developer only.');
}

// Get database schema
function getDatabaseSchema() {
    $conn = connectDB();
    $dbName = DB_NAME;
    
    $schema = ['tables' => [], 'relationships' => []];
    
    // Get tables
    $stmt = $conn->prepare("
        SELECT TABLE_NAME, TABLE_COMMENT, TABLE_ROWS 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $schema['tables'][$row['TABLE_NAME']] = [
            'name' => $row['TABLE_NAME'],
            'comment' => $row['TABLE_COMMENT'],
            'row_count' => $row['TABLE_ROWS'],
            'columns' => []
        ];
    }
    $stmt->close();
    
    // Get columns
    $stmt = $conn->prepare("
        SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, 
               COLUMN_DEFAULT, COLUMN_COMMENT, COLUMN_KEY, EXTRA,
               CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = ? 
        ORDER BY TABLE_NAME, ORDINAL_POSITION
    ");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($col = $result->fetch_assoc()) {
        $table = $col['TABLE_NAME'];
        if (isset($schema['tables'][$table])) {
            $schema['tables'][$table]['columns'][] = [
                'name' => $col['COLUMN_NAME'],
                'type' => $col['DATA_TYPE'],
                'nullable' => $col['IS_NULLABLE'],
                'default' => $col['COLUMN_DEFAULT'],
                'comment' => $col['COLUMN_COMMENT'],
                'key' => $col['COLUMN_KEY'],
                'extra' => $col['EXTRA'],
                'max_length' => $col['CHARACTER_MAXIMUM_LENGTH'],
                'precision' => $col['NUMERIC_PRECISION'],
                'scale' => $col['NUMERIC_SCALE']
            ];
        }
    }
    $stmt->close();
    
    // Get primary keys
    $stmt = $conn->prepare("
        SELECT TABLE_NAME, COLUMN_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = ? AND CONSTRAINT_NAME = 'PRIMARY'
        ORDER BY TABLE_NAME, ORDINAL_POSITION
    ");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $result = $stmt->get_result();
    $pks = [];
    while ($pk = $result->fetch_assoc()) {
        $pks[$pk['TABLE_NAME']][] = $pk['COLUMN_NAME'];
    }
    $stmt->close();
    
    // Get foreign keys
    $stmt = $conn->prepare("
        SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.CONSTRAINT_NAME,
               kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
        WHERE kcu.TABLE_SCHEMA = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($fk = $result->fetch_assoc()) {
        $table = $fk['TABLE_NAME'];
        foreach ($schema['tables'][$table]['columns'] as &$col) {
            if ($col['name'] === $fk['COLUMN_NAME']) {
                $col['is_foreign_key'] = true;
                $col['references_table'] = $fk['REFERENCED_TABLE_NAME'];
                $col['references_column'] = $fk['REFERENCED_COLUMN_NAME'];
            }
        }
        $schema['relationships'][] = [
            'from_table' => $table,
            'from_column' => $fk['COLUMN_NAME'],
            'to_table' => $fk['REFERENCED_TABLE_NAME'],
            'to_column' => $fk['REFERENCED_COLUMN_NAME']
        ];
    }
    $stmt->close();
    
    // Mark PKs
    foreach ($pks as $table => $cols) {
        foreach ($schema['tables'][$table]['columns'] as &$col) {
            if (in_array($col['name'], $cols)) {
                $col['is_primary_key'] = true;
            }
        }
    }
    
    // Get actual row counts
    foreach ($schema['tables'] as $name => &$table) {
        $count = $conn->query("SELECT COUNT(*) as c FROM `$name`");
        if ($count) {
            $table['row_count'] = $count->fetch_assoc()['c'];
        }
    }
    
    $conn->close();
    return $schema;
}

$schema = getDatabaseSchema();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev | Database Schema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        
        .dev-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 25px 30px;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .dev-header h1 {
            font-size: 1.5rem;
            color: #60a5fa;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .dev-header h1 i {
            color: #f59e0b;
        }
        
        .dev-badge {
            background: #dc2626;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            width: 280px;
            font-size: 14px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #60a5fa;
        }
        
        .filter-btn {
            background: #1e293b;
            border: 1px solid #334155;
            color: #94a3b8;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #64748b;
        }
        
        .stats span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .stats i {
            color: #60a5fa;
        }
        
        .schema-container {
            padding: 30px;
            position: relative;
            min-height: calc(100vh - 100px);
        }
        
        .svg-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .svg-layer svg {
            width: 100%;
            height: 100%;
        }
        
        .line {
            fill: none;
            stroke: #475569;
            stroke-width: 1.5;
            opacity: 0.5;
            transition: all 0.3s;
        }
        
        .line.highlight {
            stroke: #60a5fa;
            stroke-width: 2.5;
            opacity: 1;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            position: relative;
            z-index: 2;
        }
        
        .table-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .table-card:hover {
            border-color: #60a5fa;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .table-card.highlight {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px #60a5fa, 0 10px 30px rgba(96,165,250,0.2);
        }
        
        .table-card.dimmed {
            opacity: 0.3;
        }
        
        .table-card.hidden {
            display: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .row-count {
            background: rgba(255,255,255,0.15);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            color: white;
        }
        
        .card-body {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .card-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .card-body::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 3px;
        }
        
        .column {
            padding: 12px 20px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .column:last-child {
            border-bottom: none;
        }
        
        .column:hover {
            background: #252f47;
        }
        
        .col-info {
            flex: 1;
        }
        
        .col-name {
            color: #e2e8f0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
        }
        
        .col-type {
            color: #64748b;
            font-size: 11px;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-pk {
            background: #059669;
            color: white;
        }
        
        .badge-fk {
            background: #d97706;
            color: white;
            cursor: help;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .legend {
            position: fixed;
            bottom: 25px;
            left: 25px;
            background: #1e293b;
            border: 1px solid #334155;
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .legend-title {
            color: #94a3b8;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .dev-header {
                padding: 20px;
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .legend {
                position: relative;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dev-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <h1><i class="fas fa-code"></i> Database Schema Viewer</h1>
            <span class="dev-badge">Developer Only</span>
        </div>
        
        <div class="controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search tables...">
            </div>
            
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="relations">With Relations</button>
            
            <div class="stats">
                <span><i class="fas fa-table"></i> <span id="tableStats">0</span> tables</span>
                <span><i class="fas fa-link"></i> <span id="relationStats">0</span> relations</span>
            </div>
        </div>
    </div>
    
    <div class="schema-container">
        <svg class="svg-layer" id="svgLayer">
            <!-- Lines drawn here -->
        </svg>
        
        <div class="tables-grid" id="tablesGrid"></div>
    </div>
    
    <div class="legend">
        <div class="legend-title">Legend</div>
        <div class="legend-item">
            <span class="badge badge-pk">PK</span>
            Primary Key
        </div>
        <div class="legend-item">
            <span class="badge badge-fk">FK</span>
            Foreign Key
        </div>
    </div>
    
    <script>
        const schemaData = <?php echo json_encode($schema); ?>;
        let selectedTable = null;
        
        function render() {
            const grid = document.getElementById('tablesGrid');
            grid.innerHTML = '';
            
            Object.values(schemaData.tables).forEach(table => {
                const hasRelations = schemaData.relationships.some(
                    r => r.from_table === table.name || r.to_table === table.name
                );
                
                const card = document.createElement('div');
                card.className = 'table-card';
                card.dataset.table = table.name;
                card.dataset.hasRelations = hasRelations;
                
                const header = document.createElement('div');
                header.className = 'card-header';
                header.innerHTML = `
                    <h3><i class="fas fa-table"></i> ${table.name}</h3>
                    <span class="row-count">${table.row_count.toLocaleString()} rows</span>
                `;
                
                const body = document.createElement('div');
                body.className = 'card-body';
                
                table.columns.forEach(col => {
                    const row = document.createElement('div');
                    row.className = 'column';
                    
                    let badges = '';
                    if (col.is_primary_key) badges += '<span class="badge badge-pk">PK</span>';
                    if (col.is_foreign_key) {
                        badges += `<span class="badge badge-fk" title="References ${col.references_table}.${col.references_column}">FK</span>`;
                    }
                    
                    let typeStr = col.type;
                    if (col.max_length) typeStr += `(${col.max_length})`;
                    else if (col.precision) typeStr += `(${col.precision},${col.scale||0})`;
                    
                    row.innerHTML = `
                        <div class="col-info">
                            <div class="col-name">${col.name} ${badges}</div>
                            <div class="col-type">${typeStr}${col.nullable==='NO'?' NOT NULL':''}</div>
                        </div>
                    `;
                    
                    body.appendChild(row);
                });
                
                card.appendChild(header);
                card.appendChild(body);
                card.addEventListener('click', () => selectTable(table.name));
                grid.appendChild(card);
            });
            
            updateStats();
            setTimeout(drawLines, 100);
        }
        
        function drawLines() {
            const svg = document.getElementById('svgLayer');
            const container = document.querySelector('.schema-container');
            svg.innerHTML = '';
            
            const containerRect = container.getBoundingClientRect();
            
            schemaData.relationships.forEach(rel => {
                const from = document.querySelector(`.table-card[data-table="${rel.from_table}"]`);
                const to = document.querySelector(`.table-card[data-table="${rel.to_table}"]`);
                if (!from || !to) return;
                
                const fromRect = from.getBoundingClientRect();
                const toRect = to.getBoundingClientRect();
                
                const x1 = fromRect.left + fromRect.width/2 - containerRect.left;
                const y1 = fromRect.top + fromRect.height/2 - containerRect.top;
                const x2 = toRect.left + toRect.width/2 - containerRect.left;
                const y2 = toRect.top + toRect.height/2 - containerRect.top;
                
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                const mx = (x1+x2)/2;
                
                path.setAttribute('d', `M ${x1} ${y1} Q ${mx} ${y1} ${mx} ${(y1+y2)/2} T ${x2} ${y2}`);
                path.setAttribute('class', 'line');
                path.dataset.from = rel.from_table;
                path.dataset.to = rel.to_table;
                
                svg.appendChild(path);
            });
        }
        
        function selectTable(name) {
            const cards = document.querySelectorAll('.table-card');
            const lines = document.querySelectorAll('.line');
            
            if (selectedTable === name) {
                cards.forEach(c => c.classList.remove('highlight', 'dimmed'));
                lines.forEach(l => l.classList.remove('highlight'));
                selectedTable = null;
                return;
            }
            
            selectedTable = name;
            const related = new Set([name]);
            
            schemaData.relationships.forEach(r => {
                if (r.from_table === name) related.add(r.to_table);
                if (r.to_table === name) related.add(r.from_table);
            });
            
            cards.forEach(c => {
                c.classList.remove('highlight', 'dimmed');
                if (related.has(c.dataset.table)) {
                    c.classList.add('highlight');
                } else {
                    c.classList.add('dimmed');
                }
            });
            
            lines.forEach(l => {
                l.classList.remove('highlight');
                if (l.dataset.from === name || l.dataset.to === name) {
                    l.classList.add('highlight');
                }
            });
        }
        
        function updateStats() {
            document.getElementById('tableStats').textContent = Object.keys(schemaData.tables).length;
            document.getElementById('relationStats').textContent = schemaData.relationships.length;
        }
        
        // Event listeners
        document.getElementById('searchInput').addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.table-card').forEach(card => {
                const match = card.dataset.table.toLowerCase().includes(term);
                card.classList.toggle('hidden', !match);
            });
            setTimeout(drawLines, 100);
        });
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.table-card').forEach(card => {
                    if (filter === 'all') {
                        card.classList.remove('hidden');
                    } else {
                        const hasRel = card.dataset.hasRelations === 'true';
                        card.classList.toggle('hidden', !hasRel);
                    }
                });
                setTimeout(drawLines, 100);
            });
        });
        
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(drawLines, 150);
        });
        
        render();
    </script>
</body>
</html>
