import React from 'react';
import './About.css';

function About() {
  const stores = [
    {
      id: 'vilnius',
      city: 'Vilnius',
      address: 'Ukmerges g. 234, Vilnius',
      phone: '+370 5 123 4567',
      email: 'vilnius@construkt.lt',
      hours: {
        weekdays: '08:00 - 20:00',
        saturday: '09:00 - 18:00',
        sunday: '10:00 - 16:00'
      },
      mapUrl: 'https://maps.google.com/?q=54.7104,25.2649'
    },
    {
      id: 'kaunas',
      city: 'Kaunas',
      address: 'Savanoriu pr. 456, Kaunas',
      phone: '+370 37 234 567',
      email: 'kaunas@construkt.lt',
      hours: {
        weekdays: '08:00 - 20:00',
        saturday: '09:00 - 18:00',
        sunday: '10:00 - 16:00'
      },
      mapUrl: 'https://maps.google.com/?q=54.8985,23.9036'
    },
    {
      id: 'kena',
      city: 'Kena',
      address: 'Gelezinkelio g. 12, Kena',
      phone: '+370 5 345 6789',
      email: 'kena@construkt.lt',
      hours: {
        weekdays: '08:00 - 19:00',
        saturday: '09:00 - 17:00',
        sunday: 'Closed'
      },
      mapUrl: 'https://maps.google.com/?q=54.6547,25.8312'
    }
  ];

  const deliveryZones = [
    { zone: 'Vilnius city', minOrder: 50, cost: 10, freeFrom: 200, time: '1-2 business days' },
    { zone: 'Kaunas city', minOrder: 50, cost: 10, freeFrom: 200, time: '1-2 business days' },
    { zone: 'Vilnius region', minOrder: 100, cost: 20, freeFrom: 500, time: '2-3 business days' },
    { zone: 'All Lithuania', minOrder: 150, cost: 35, freeFrom: 1000, time: '3-5 business days' }
  ];

  return (
    <div className="about-page">
      <div className="about-hero">
        <div className="hero-content">
          <h1>About Construkt</h1>
          <p className="hero-subtitle">Your trusted partner in construction materials since 2018</p>
        </div>
      </div>

      <div className="about-container">
        {/* Company Info */}
        <section className="about-section" id="company">
          <h2>Who We Are</h2>
          <div className="company-info">
            <p>
              Construkt is a leading construction materials retailer in Lithuania, serving both
              professional builders and DIY enthusiasts. With three convenient locations across
              the country, we offer a wide range of quality building materials at competitive prices.
            </p>
            <div className="company-stats">
              <div className="stat">
                <span className="stat-number">3</span>
                <span className="stat-label">Stores</span>
              </div>
              <div className="stat">
                <span className="stat-number">5000+</span>
                <span className="stat-label">Products</span>
              </div>
              <div className="stat">
                <span className="stat-number">10000+</span>
                <span className="stat-label">Happy Customers</span>
              </div>
            </div>
          </div>
        </section>

        {/* Store Locations */}
        <section className="about-section" id="locations">
          <h2>Our Stores</h2>
          <div className="stores-grid">
            {stores.map(store => (
              <div key={store.id} className="store-card" id={`store-${store.id}`}>
                <div className="store-header">
                  <h3>{store.city}</h3>
                </div>
                <div className="store-body">
                  <div className="store-info-item">
                    <span className="info-icon">📍</span>
                    <span>{store.address}</span>
                  </div>
                  <div className="store-info-item">
                    <span className="info-icon">📞</span>
                    <a href={`tel:${store.phone}`}>{store.phone}</a>
                  </div>
                  <div className="store-info-item">
                    <span className="info-icon">✉️</span>
                    <a href={`mailto:${store.email}`}>{store.email}</a>
                  </div>

                  <div className="store-hours" id={`hours-${store.id}`}>
                    <h4>Working Hours</h4>
                    <div className="hours-row">
                      <span>Mon - Fri:</span>
                      <span>{store.hours.weekdays}</span>
                    </div>
                    <div className="hours-row">
                      <span>Saturday:</span>
                      <span>{store.hours.saturday}</span>
                    </div>
                    <div className="hours-row">
                      <span>Sunday:</span>
                      <span>{store.hours.sunday}</span>
                    </div>
                  </div>

                  <a href={store.mapUrl} target="_blank" rel="noopener noreferrer" className="map-link">
                    View on Map
                  </a>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Delivery Info */}
        <section className="about-section" id="delivery">
          <h2>Delivery Information</h2>
          <div className="delivery-info">
            <div className="delivery-table-wrapper">
              <table className="delivery-table">
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
                  {deliveryZones.map((zone, index) => (
                    <tr key={index}>
                      <td>{zone.zone}</td>
                      <td>${zone.minOrder}</td>
                      <td>${zone.cost}</td>
                      <td>${zone.freeFrom}</td>
                      <td>{zone.time}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="delivery-notes">
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

        {/* Contact Info */}
        <section className="about-section" id="contact">
          <h2>Contact Us</h2>
          <div className="contact-grid">
            <div className="contact-card">
              <span className="contact-icon">📞</span>
              <h4>Phone</h4>
              <a href="tel:+37080012345">+370 800 12345</a>
              <p>Free hotline</p>
            </div>
            <div className="contact-card">
              <span className="contact-icon">✉️</span>
              <h4>Email</h4>
              <a href="mailto:info@construkt.lt">info@construkt.lt</a>
              <p>General inquiries</p>
            </div>
            <div className="contact-card">
              <span className="contact-icon">💬</span>
              <h4>Support</h4>
              <a href="mailto:pagalba@construkt.lt">pagalba@construkt.lt</a>
              <p>Technical support</p>
            </div>
          </div>
        </section>

        {/* Payment Methods */}
        <section className="about-section" id="payment">
          <h2>Payment Methods</h2>
          <div className="payment-methods">
            <div className="payment-card">
              <span className="payment-icon">💵</span>
              <span>Cash</span>
            </div>
            <div className="payment-card">
              <span className="payment-icon">💳</span>
              <span>Bank Card</span>
            </div>
            <div className="payment-card">
              <span className="payment-icon">🏦</span>
              <span>Bank Transfer</span>
            </div>
            <div className="payment-card">
              <span className="payment-icon">📋</span>
              <span>Leasing</span>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}

export default About;
