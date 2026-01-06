import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import ProfileForm from '../components/user/ProfileForm';
import './Profile.css';

function Profile() {
  const { currentUser, isAuthenticated } = useAuth();
  const [activeTab, setActiveTab] = useState('profile');

  if (!isAuthenticated()) {
    return (
      <div className="profile-container">
        <div className="profile-message">
          <h2>Access Denied</h2>
          <p>Please log in to view your profile.</p>
        </div>
      </div>
    );
  }

  return (
      <div className="profile-content">

        <div className="profile-main">
          {activeTab === 'profile' && <ProfileForm />}
          {activeTab === 'company' && (
            <div className="form-container">
              <h2>Company Profile</h2>
              <div className="company-profile-section">
                <p>Manage your company information and supplier profile here.</p>
                <button 
                  className="primary-button"
                  onClick={() => window.location.href = '/supplier'}
                >
                  Go to Supplier Dashboard
                </button>
              </div>
            </div>
          )}
          {activeTab === 'orders' && (
            <div className="form-container">
              <h2>Order History</h2>
              <p>Order history will be available soon.</p>
            </div>
          )}
        </div>
      </div>
  );
}

export default Profile;
