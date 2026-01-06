<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pageTitle = 'Materials Calculator';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>Construction Materials Calculator</h1>

    <div class="calculator-grid">
        <!-- Brick Calculator -->
        <div class="calc-card">
            <h3>Brick Calculator</h3>
            <form class="calc-form" onsubmit="calculateBricks(event)">
                <div class="form-group">
                    <label>Wall Length (m)</label>
                    <input type="number" id="brick-length" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Wall Height (m)</label>
                    <input type="number" id="brick-height" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Wall Thickness</label>
                    <select id="brick-type">
                        <option value="0.5">Half brick (4.5 in)</option>
                        <option value="1">Single brick (9 in)</option>
                        <option value="1.5">One and half brick (13.5 in)</option>
                        <option value="2">Double brick (18 in)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
                <div class="calc-result" id="brick-result"></div>
            </form>
        </div>

        <!-- Concrete Calculator -->
        <div class="calc-card">
            <h3>Concrete Calculator</h3>
            <form class="calc-form" onsubmit="calculateConcrete(event)">
                <div class="form-group">
                    <label>Length (m)</label>
                    <input type="number" id="concrete-length" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Width (m)</label>
                    <input type="number" id="concrete-width" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Depth (cm)</label>
                    <input type="number" id="concrete-depth" step="1" required>
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
                <div class="calc-result" id="concrete-result"></div>
            </form>
        </div>

        <!-- Paint Calculator -->
        <div class="calc-card">
            <h3>Paint Calculator</h3>
            <form class="calc-form" onsubmit="calculatePaint(event)">
                <div class="form-group">
                    <label>Surface Area (sq m)</label>
                    <input type="number" id="paint-area" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Number of Coats</label>
                    <select id="paint-layers">
                        <option value="1">1 coat</option>
                        <option value="2" selected>2 coats</option>
                        <option value="3">3 coats</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Coverage (L/sq m)</label>
                    <input type="number" id="paint-consumption" step="0.01" value="0.15">
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
                <div class="calc-result" id="paint-result"></div>
            </form>
        </div>

        <!-- Tile Calculator -->
        <div class="calc-card">
            <h3>Tile Calculator</h3>
            <form class="calc-form" onsubmit="calculateTiles(event)">
                <div class="form-group">
                    <label>Room Area (sq m)</label>
                    <input type="number" id="tile-area" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Tile Size</label>
                    <select id="tile-size">
                        <option value="0.09">12x12 in (30x30 cm)</option>
                        <option value="0.16">16x16 in (40x40 cm)</option>
                        <option value="0.25">20x20 in (50x50 cm)</option>
                        <option value="0.36">24x24 in (60x60 cm)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Waste Allowance (%)</label>
                    <input type="number" id="tile-reserve" value="10">
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
                <div class="calc-result" id="tile-result"></div>
            </form>
        </div>
    </div>
</div>

<script>
function calculateBricks(e) {
    e.preventDefault();
    const length = parseFloat(document.getElementById('brick-length').value);
    const height = parseFloat(document.getElementById('brick-height').value);
    const type = parseFloat(document.getElementById('brick-type').value);
    const area = length * height;
    const bricksPerSqm = type === 0.5 ? 51 : type === 1 ? 102 : type === 1.5 ? 153 : 204;
    const total = Math.ceil(area * bricksPerSqm * 1.05);
    document.getElementById('brick-result').innerHTML = `
        <strong>Result:</strong><br>
        Area: ${area.toFixed(2)} sq m<br>
        Bricks needed: <strong>${total} pcs</strong><br>
        (includes 5% waste allowance)
    `;
}

function calculateConcrete(e) {
    e.preventDefault();
    const length = parseFloat(document.getElementById('concrete-length').value);
    const width = parseFloat(document.getElementById('concrete-width').value);
    const depth = parseFloat(document.getElementById('concrete-depth').value) / 100;
    const volume = length * width * depth;
    const cement = Math.ceil(volume * 320);
    const sand = Math.ceil(volume * 0.65);
    const gravel = Math.ceil(volume * 1.2);
    document.getElementById('concrete-result').innerHTML = `
        <strong>Result:</strong><br>
        Concrete volume: ${volume.toFixed(2)} cu m<br>
        Cement: <strong>${cement} kg</strong><br>
        Sand: <strong>${sand.toFixed(2)} cu m</strong><br>
        Gravel: <strong>${gravel.toFixed(2)} cu m</strong>
    `;
}

function calculatePaint(e) {
    e.preventDefault();
    const area = parseFloat(document.getElementById('paint-area').value);
    const layers = parseInt(document.getElementById('paint-layers').value);
    const consumption = parseFloat(document.getElementById('paint-consumption').value);
    const total = Math.ceil(area * layers * consumption);
    document.getElementById('paint-result').innerHTML = `
        <strong>Result:</strong><br>
        Area: ${area} sq m x ${layers} coats<br>
        Paint needed: <strong>${total} L</strong>
    `;
}

function calculateTiles(e) {
    e.preventDefault();
    const area = parseFloat(document.getElementById('tile-area').value);
    const tileSize = parseFloat(document.getElementById('tile-size').value);
    const reserve = parseFloat(document.getElementById('tile-reserve').value) / 100;
    const tiles = Math.ceil(area / tileSize * (1 + reserve));
    document.getElementById('tile-result').innerHTML = `
        <strong>Result:</strong><br>
        Area: ${area} sq m<br>
        Tiles needed: <strong>${tiles} pcs</strong><br>
        (includes ${reserve * 100}% waste allowance)
    `;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
