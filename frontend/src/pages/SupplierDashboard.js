import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { Navigate } from 'react-router-dom';
import supplierService from '../services/supplierService';
import ProductManager from '../components/supplier/ProductManager';
import './SupplierDashboard.css';

const SupplierDashboard = () => {
  const { currentUser, hasRole, isAuthenticated } = useAuth();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalProducts: 0,
    pendingOrders: 0,
    recentInquiries: 0
  });

  const [needsProfile, setNeedsProfile] = useState(false);
  const [profileData, setProfileData] = useState({
    company_name: '',
    contact_name: '',
    contact_title: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'United States'
  });

  const handleProfileInputChange = (e) => {
    const { name, value } = e.target;
    setProfileData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const createSupplierProfile = async () => {
    try {
      setLoading(true);
      // Use the current user's name if contact_name is empty
      if (!profileData.contact_name && currentUser) {
        profileData.contact_name = `${currentUser.firstName} ${currentUser.lastName}`;
      }
      
      const response = await supplierService.createSupplierProfile(profileData);
      if (response.success) {
        setNeedsProfile(false);
        // Refresh the page to load the new supplier profile
        window.location.reload();
      } else {
        console.error('Failed to create supplier profile:', response.message);
      }
    } catch (error) {
      console.error('Error creating supplier profile:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const fetchSupplierData = async () => {
      try {
        // Fetch supplier profile
        try {
          const profileResponse = await supplierService.getSupplierProfile();
          
          // If we got here, the supplier profile exists
          // Attempt to fetch supplier stats if the endpoint exists
          try {
            const statsResponse = await supplierService.getSupplierStats();
            if (statsResponse.success && statsResponse.data) {
              setStats({
                totalProducts: statsResponse.data.totalProducts || 0,
                pendingOrders: statsResponse.data.pendingOrders || 0,
                recentInquiries: statsResponse.data.recentInquiries || 0
              });
            }
          } catch (statsError) {
            console.log('Stats endpoint may not be implemented yet, using default values');
            // Use default values if the stats endpoint doesn't exist yet
            setStats({
              totalProducts: profileResponse.data?.products_count || 0,
              pendingOrders: 0,
              recentInquiries: 0
            });
          }
        } catch (profileError) {
          // If we get a 404, the user doesn't have a supplier profile yet
          if (profileError.response && profileError.response.status === 404) {
            setNeedsProfile(true);
          }
          console.log('User needs to create a supplier profile');
        }
      } catch (error) {
        console.error('Error fetching supplier data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchSupplierData();
  }, [currentUser]);

  // Redirect if not authenticated or not a supplier
  if (!isAuthenticated() || !hasRole('supplier')) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="supplier-dashboard">
      <h1>Supplier Dashboard</h1>
      <p>Welcome, {currentUser?.firstName} {currentUser?.lastName}</p>
      
      {loading ? (
        <div className="loading">Loading dashboard data...</div>
      ) : needsProfile ? (
        <div className="create-profile-container">
          <h2>Create Your Supplier Profile</h2>
          <p>To get started as a supplier, please create your business profile.</p>
          
          <form className="profile-form">
            <div className="form-group">
              <label htmlFor="company_name">Company Name *</label>
              <input
                type="text"
                id="company_name"
                name="company_name"
                value={profileData.company_name}
                onChange={handleProfileInputChange}
                required
                placeholder="Your company name"
              />
            </div>
            
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="contact_name">Contact Name</label>
                <input
                  type="text"
                  id="contact_name"
                  name="contact_name"
                  value={profileData.contact_name}
                  onChange={handleProfileInputChange}
                  placeholder="Primary contact person"
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="contact_title">Contact Title</label>
                <input
                  type="text"
                  id="contact_title"
                  name="contact_title"
                  value={profileData.contact_title}
                  onChange={handleProfileInputChange}
                  placeholder="e.g. Owner, Manager"
                />
              </div>
            </div>
            
            <div className="form-group">
              <label htmlFor="phone">Phone Number *</label>
              <input
                type="tel"
                id="phone"
                name="phone"
                value={profileData.phone}
                onChange={handleProfileInputChange}
                required
                placeholder="Business phone number"
              />
            </div>
            
            <div className="form-group">
              <label htmlFor="address">Address *</label>
              <input
                type="text"
                id="address"
                name="address"
                value={profileData.address}
                onChange={handleProfileInputChange}
                required
                placeholder="Street address"
              />
            </div>
            
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="city">City *</label>
                <input
                  type="text"
                  id="city"
                  name="city"
                  value={profileData.city}
                  onChange={handleProfileInputChange}
                  required
                  placeholder="City"
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="state">State *</label>
                <input
                  type="text"
                  id="state"
                  name="state"
                  value={profileData.state}
                  onChange={handleProfileInputChange}
                  required
                  placeholder="State/Province"
                />
              </div>
            </div>
            
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="postal_code">Postal Code *</label>
                <input
                  type="text"
                  id="postal_code"
                  name="postal_code"
                  value={profileData.postal_code}
                  onChange={handleProfileInputChange}
                  required
                  placeholder="Postal/ZIP code"
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="country">Country *</label>
                <input
                  type="text"
                  id="country"
                  name="country"
                  value={profileData.country}
                  onChange={handleProfileInputChange}
                  required
                />
              </div>
            </div>
            
            <button 
              type="button" 
              className="submit-button" 
              onClick={createSupplierProfile}
              disabled={!profileData.company_name || !profileData.phone || !profileData.address || !profileData.city || !profileData.state || !profileData.postal_code}
            >
              Create Supplier Profile
            </button>
          </form>
        </div>
      ) : (
        <>
          <ProductManager />
        </>
      )}
    </div>
  );
};

export default SupplierDashboard;
