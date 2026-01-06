<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pageTitle = 'About Us';

$stores = [
    [
        'id' => 'vilnius',
        'city' => 'Vilnius',
        'address' => 'Ukmerges g. 234, Vilnius',
        'phone' => '+370 5 123 4567',
        'email' => 'vilnius@construkt.lt',
        'hours' => [
            'weekdays' => '08:00 - 20:00',
            'saturday' => '09:00 - 18:00',
            'sunday' => '10:00 - 16:00'
        ],
        'mapUrl' => 'https://maps.google.com/?q=54.7104,25.2649'
    ],
    [
        'id' => 'kaunas',
        'city' => 'Kaunas',
        'address' => 'Savanoriu pr. 456, Kaunas',
        'phone' => '+370 37 234 567',
        'email' => 'kaunas@construkt.lt',
        'hours' => [
            'weekdays' => '08:00 - 20:00',
            'saturday' => '09:00 - 18:00',
            'sunday' => '10:00 - 16:00'
        ],
        'mapUrl' => 'https://maps.google.com/?q=54.8985,23.9036'
    ],
    [
        'id' => 'kena',
        'city' => 'Kena',
        'address' => 'Gelezinkelio g. 12, Kena',
        'phone' => '+370 5 345 6789',
        'email' => 'kena@construkt.lt',
        'hours' => [
            'weekdays' => '08:00 - 19:00',
            'saturday' => '09:00 - 17:00',
            'sunday' => 'Closed'
        ],
        'mapUrl' => 'https://maps.google.com/?q=54.6547,25.8312'
    ]
];

$deliveryZones = [
    ['zone' => 'Vilnius city', 'minOrder' => 50, 'cost' => 10, 'freeFrom' => 200, 'time' => '1-2 business days'],
    ['zone' => 'Kaunas city', 'minOrder' => 50, 'cost' => 10, 'freeFrom' => 200, 'time' => '1-2 business days'],
    ['zone' => 'Vilnius region', 'minOrder' => 100, 'cost' => 20, 'freeFrom' => 500, 'time' => '2-3 business days'],
    ['zone' => 'All Lithuania', 'minOrder' => 150, 'cost' => 35, 'freeFrom' => 1000, 'time' => '3-5 business days']
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="about-hero">
    <div class="hero-content">
        <h1>About Construkt</h1>
        <p class="hero-subtitle">Your trusted partner in construction materials since 2018</p>
    </div>
</div>

<div class="about-container">
    <!-- Company Info -->
    <section class="about-section" id="company">
        <h2>Who We Are</h2>
        <div class="company-info">
            <p>
                Construkt is a leading construction materials retailer in Lithuania, serving both
                professional builders and DIY enthusiasts. With three convenient locations across
                the country, we offer a wide range of quality building materials at competitive prices.
            </p>
            <div class="company-stats">
                <div class="stat">
                    <span class="stat-number">3</span>
                    <span class="stat-label">Stores</span>
                </div>
                <div class="stat">
                    <span class="stat-number">5000+</span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat">
                    <span class="stat-number">10000+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Store Locations -->
    <section class="about-section" id="locations">
        <h2>Our Stores</h2>
        <div class="stores-grid">
            <?php foreach ($stores as $store): ?>
            <div class="store-card" id="store-<?= $store['id'] ?>">
                <div class="store-header">
                    <h3><?= $store['city'] ?></h3>
                </div>
                <div class="store-body">
                    <div class="store-info-item">
                        <span class="info-icon">&#128205;</span>
                        <span><?= $store['address'] ?></span>
                    </div>
                    <div class="store-info-item">
                        <span class="info-icon">&#128222;</span>
                        <a href="tel:<?= $store['phone'] ?>"><?= $store['phone'] ?></a>
                    </div>
                    <div class="store-info-item">
                        <span class="info-icon">&#9993;</span>
                        <a href="mailto:<?= $store['email'] ?>"><?= $store['email'] ?></a>
                    </div>

                    <div class="store-hours" id="hours-<?= $store['id'] ?>">
                        <h4>Working Hours</h4>
                        <div class="hours-row">
                            <span>Mon - Fri:</span>
                            <span><?= $store['hours']['weekdays'] ?></span>
                        </div>
                        <div class="hours-row">
                            <span>Saturday:</span>
                            <span><?= $store['hours']['saturday'] ?></span>
                        </div>
                        <div class="hours-row">
                            <span>Sunday:</span>
                            <span><?= $store['hours']['sunday'] ?></span>
                        </div>
                    </div>

                    <a href="<?= $store['mapUrl'] ?>" target="_blank" rel="noopener noreferrer" class="map-link">
                        View on Map
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Delivery Info -->
    <section class="about-section" id="delivery">
        <h2>Delivery Information</h2>
        <div class="delivery-info">
            <div class="delivery-table-wrapper">
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Min. Order</th>
                            <th>Delivery Cost</th>
                            <th>Free Delivery From</th>
                            <th>Delivery Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveryZones as $zone): ?>
                        <tr>
                            <td><?= $zone['zone'] ?></td>
                            <td>$<?= $zone['minOrder'] ?></td>
                            <td>$<?= $zone['cost'] ?></td>
                            <td>$<?= $zone['freeFrom'] ?></td>
                            <td><?= $zone['time'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="delivery-notes">
                <h4>Important Notes:</h4>
                <ul>
                    <li>Delivery is made on business days from 9:00 to 18:00</li>
                    <li>Large items may have additional delivery charges</li>
                    <li>Exact delivery time will be confirmed by phone</li>
                    <li>Self-pickup is available at all store locations</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Contact Info -->
    <section class="about-section" id="contact">
        <h2>Contact Us</h2>
        <div class="contact-grid">
            <div class="contact-card">
                <span class="contact-icon">&#128222;</span>
                <h4>Phone</h4>
                <a href="tel:+37080012345">+370 800 12345</a>
                <p>Free hotline</p>
            </div>
            <div class="contact-card">
                <span class="contact-icon">&#9993;</span>
                <h4>Email</h4>
                <a href="mailto:info@construkt.lt">info@construkt.lt</a>
                <p>General inquiries</p>
            </div>
            <div class="contact-card">
                <span class="contact-icon">&#128172;</span>
                <h4>Support</h4>
                <a href="mailto:pagalba@construkt.lt">pagalba@construkt.lt</a>
                <p>Technical support</p>
            </div>
        </div>
    </section>

    <!-- Payment Methods -->
    <section class="about-section" id="payment">
        <h2>Payment Methods</h2>
        <div class="payment-methods">
            <div class="payment-card">
                <span class="payment-icon">&#128181;</span>
                <span>Cash</span>
            </div>
            <div class="payment-card">
                <span class="payment-icon">&#128179;</span>
                <span>Bank Card</span>
            </div>
            <div class="payment-card">
                <span class="payment-icon">&#127974;</span>
                <span>Bank Transfer</span>
            </div>
            <div class="payment-card">
                <span class="payment-icon">&#128203;</span>
                <span>Leasing</span>
            </div>
        </div>
    </section>
</div>

<style>
.about-hero {
    background: linear-gradient(135deg, rgba(30, 58, 95, 0.95) 0%, rgba(15, 23, 42, 0.95) 100%);
    padding: 80px 20px;
    text-align: center;
    color: white;
}

.about-hero .hero-content h1 {
    font-size: 3rem;
    margin-bottom: 16px;
    font-weight: 700;
}

.about-hero .hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
}

.about-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.about-section {
    background: rgba(255,255,255,0.95);
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.about-section h2 {
    font-size: 1.75rem;
    color: #1e3a5f;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #3b82f6;
    display: inline-block;
    text-align: left;
    text-shadow: none;
}

/* Company Info */
.company-info p {
    font-size: 1.1rem;
    color: #475569;
    line-height: 1.8;
    margin-bottom: 30px;
}

.company-stats {
    display: flex;
    gap: 40px;
    justify-content: center;
    flex-wrap: wrap;
}

.stat {
    text-align: center;
    padding: 20px 40px;
    background: #f1f5f9;
    border-radius: 12px;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: #3b82f6;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Stores Grid */
.stores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}

.store-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    background: white;
}

.store-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.store-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    padding: 20px;
    color: white;
}

.store-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.store-body {
    padding: 24px;
}

.store-info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    color: #475569;
}

.store-info-item a {
    color: #3b82f6;
    text-decoration: none;
}

.store-info-item a:hover {
    text-decoration: underline;
}

.info-icon {
    font-size: 1.2rem;
}

.store-hours {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.store-hours h4 {
    margin: 0 0 12px 0;
    color: #1e3a5f;
    font-size: 1rem;
}

.hours-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    color: #475569;
    font-size: 0.95rem;
}

.hours-row:not(:last-child) {
    border-bottom: 1px dashed #e2e8f0;
}

.map-link {
    display: inline-block;
    margin-top: 16px;
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: background 0.2s;
}

.map-link:hover {
    background: #2563eb;
}

/* Delivery Table */
.delivery-table-wrapper {
    overflow-x: auto;
}

.delivery-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
}

.delivery-table th,
.delivery-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.delivery-table th {
    background: #f8fafc;
    color: #1e3a5f;
    font-weight: 600;
    white-space: nowrap;
}

.delivery-table tr:hover {
    background: #f8fafc;
}

.delivery-notes {
    background: #fef3c7;
    padding: 20px 24px;
    border-radius: 8px;
    border-left: 4px solid #f59e0b;
}

.delivery-notes h4 {
    margin: 0 0 12px 0;
    color: #92400e;
}

.delivery-notes ul {
    margin: 0;
    padding-left: 20px;
    color: #78350f;
}

.delivery-notes li {
    margin-bottom: 8px;
}

/* Contact Grid */
.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
}

.contact-card {
    text-align: center;
    padding: 30px 20px;
    background: #f8fafc;
    border-radius: 12px;
    transition: transform 0.2s;
}

.contact-card:hover {
    transform: translateY(-4px);
}

.contact-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 12px;
}

.contact-card h4 {
    margin: 0 0 8px 0;
    color: #1e3a5f;
}

.contact-card a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.contact-card a:hover {
    text-decoration: underline;
}

.contact-card p {
    margin: 8px 0 0 0;
    color: #64748b;
    font-size: 0.9rem;
}

/* Payment Methods */
.payment-methods {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.payment-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 24px 32px;
    background: #f8fafc;
    border-radius: 12px;
    min-width: 120px;
    transition: transform 0.2s;
}

.payment-card:hover {
    transform: translateY(-4px);
}

.payment-icon {
    font-size: 2rem;
}

.payment-card span:last-child {
    color: #475569;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .about-hero .hero-content h1 {
        font-size: 2rem;
    }

    .about-section {
        padding: 24px;
    }

    .company-stats {
        gap: 20px;
    }

    .stat {
        padding: 16px 24px;
    }

    .stores-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
