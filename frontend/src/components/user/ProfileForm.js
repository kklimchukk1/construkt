import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import supplierService from '../../services/supplierService';
import LoadingIndicator from '../layout/LoadingIndicator';
import ErrorMessage from '../layout/ErrorMessage';
import './UserForms.css';

const ProfileForm = () => {
  const { currentUser, updateProfile } = useAuth();
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    company_name: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: ''
  });
  
  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  // Load user data when component mounts
  useEffect(() => {
    const loadUserData = async () => {
      setIsLoading(true);
      if (currentUser) {
        // Start with basic user data
        const userData = {
          firstName: currentUser.firstName || '',
          lastName: currentUser.lastName || '',
          email: currentUser.email || '',
          phone: currentUser.phone || '',
          company_name: currentUser.companyName || '',
          address: currentUser.address || '',
          city: currentUser.city || '',
          state: currentUser.state || '',
          postal_code: currentUser.postalCode || '',
          country: currentUser.country || ''
        };

        // If user is a supplier, try to fetch supplier profile data
        if (currentUser.role === 'supplier') {
          try {
            const supplierResponse = await supplierService.getSupplierProfile();
            
            if (supplierResponse.success && supplierResponse.data) {
              // Update form data with supplier profile information
              userData.company_name = supplierResponse.data.company_name || userData.company_name;
              userData.address = supplierResponse.data.address || userData.address;
              userData.city = supplierResponse.data.city || userData.city;
              userData.state = supplierResponse.data.state || userData.state;
              userData.postal_code = supplierResponse.data.postal_code || userData.postal_code;
              userData.country = supplierResponse.data.country || userData.country;
              userData.phone = supplierResponse.data.phone || userData.phone;
            }
          } catch (error) {
            console.log('Could not fetch supplier profile, using user data only');
          }
        }

        setFormData(userData);
      }
      setIsLoading(false);
    };

    loadUserData();
  }, [currentUser]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value
    });
    
    // Clear errors when user types
    if (errors[name]) {
      setErrors({
        ...errors,
        [name]: ''
      });
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    // First name validation
    if (!formData.firstName.trim()) {
      newErrors.firstName = 'First name is required';
    }
    
    // Last name validation
    if (!formData.lastName.trim()) {
      newErrors.lastName = 'Last name is required';
    }
    
    // Email validation
    if (!formData.email) {
      newErrors.email = 'Email is required';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email address is invalid';
    }
    
    // Phone validation (optional)
    if (formData.phone && !/^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/.test(formData.phone)) {
      newErrors.phone = 'Phone number is invalid';
    }
    
    // Postal code validation (optional)
    if (formData.postal_code && !/^[0-9]{5}(-[0-9]{4})?$/.test(formData.postal_code)) {
      newErrors.postal_code = 'Postal code is invalid (e.g. 12345 or 12345-6789)';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSuccessMessage('');
    setErrorMessage('');
    
    if (validateForm()) {
      setIsSubmitting(true);
      
      try {
        // Call the updateProfile function from AuthContext
        await updateProfile(formData);
        setSuccessMessage('Profile updated successfully!');
        
        // Clear success message after 5 seconds
        setTimeout(() => {
          setSuccessMessage('');
        }, 5000);
      } catch (error) {
        setErrorMessage('Failed to update profile. Please try again.');
      } finally {
        setIsSubmitting(false);
      }
    }
  };

  // If no user is logged in, show a message
  if (!currentUser) {
    return <div className="form-container">Please log in to view your profile.</div>;
  }

  if (isLoading) {
    return (
      <div className="form-container">
        <h2>Edit Profile</h2>
        <LoadingIndicator 
          type="spinner" 
          size="medium" 
          text="Loading profile data..." 
        />
      </div>
    );
  }

  return (
    <div className="form-container">
      <h2>Edit Profile</h2>
      
      {successMessage && (
        <div className="success-message">
          <ErrorMessage 
            message={successMessage}
            type="info"
            dismissible={true}
            onDismiss={() => setSuccessMessage('')}
          />
        </div>
      )}
      
      {errorMessage && (
        <div className="error-message">
          <ErrorMessage 
            message={errorMessage}
            type="error"
            dismissible={true}
            onDismiss={() => setErrorMessage('')}
          />
        </div>
      )}
      
      <form onSubmit={handleSubmit} className="user-form">
        <div className="form-section">
          <h3>Personal Information</h3>
          
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="firstName">First Name*</label>
              <input
                type="text"
                id="firstName"
                name="firstName"
                value={formData.firstName}
                onChange={handleChange}
                className={errors.firstName ? 'error' : ''}
                disabled={isSubmitting}
              />
              {errors.firstName && <div className="error-text">{errors.firstName}</div>}
            </div>
            
            <div className="form-group">
              <label htmlFor="lastName">Last Name*</label>
              <input
                type="text"
                id="lastName"
                name="lastName"
                value={formData.lastName}
                onChange={handleChange}
                className={errors.lastName ? 'error' : ''}
                disabled={isSubmitting}
              />
              {errors.lastName && <div className="error-text">{errors.lastName}</div>}
            </div>
          </div>
          
          <div className="form-group">
            <label htmlFor="email">Email Address*</label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className={errors.email ? 'error' : ''}
              disabled={true} // Email cannot be changed
            />
            {errors.email && <div className="error-text">{errors.email}</div>}
            <div className="helper-text">Email cannot be changed. Contact support for assistance.</div>
          </div>
          
          <div className="form-group">
            <label htmlFor="phone">Phone Number</label>
            <input
              type="tel"
              id="phone"
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              className={errors.phone ? 'error' : ''}
              placeholder="(555) 123-4567"
              disabled={isSubmitting}
            />
            {errors.phone && <div className="error-text">{errors.phone}</div>}
          </div>
          
        </div>
        
        <div className="form-section">
          <h3>Address Information</h3>
          
          <div className="form-group">
            <label htmlFor="address">Street Address</label>
            <input
              type="text"
              id="address"
              name="address"
              value={formData.address}
              onChange={handleChange}
              disabled={isSubmitting}
            />
          </div>
          
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="city">City</label>
              <input
                type="text"
                id="city"
                name="city"
                value={formData.city}
                onChange={handleChange}
                disabled={isSubmitting}
              />
            </div>
            
            <div className="form-group">
              <label htmlFor="state">State/Province</label>
              <input
                type="text"
                id="state"
                name="state"
                value={formData.state}
                onChange={handleChange}
                disabled={isSubmitting}
              />
            </div>
          </div>
          
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="postal_code">Postal Code</label>
              <input
                type="text"
                id="postal_code"
                name="postal_code"
                value={formData.postal_code}
                onChange={handleChange}
                className={errors.postal_code ? 'error' : ''}
                disabled={isSubmitting}
              />
              {errors.postal_code && <div className="error-text">{errors.postal_code}</div>}
            </div>
            
            <div className="form-group">
              <label htmlFor="country">Country</label>
              <input
                type="text"
                id="country"
                name="country"
                value={formData.country}
                onChange={handleChange}
                disabled={isSubmitting}
              />
            </div>
          </div>
        </div>
        
        <div className="form-actions">
          <button 
            type="submit" 
            className="primary-button"
            disabled={isSubmitting}
          >
            {isSubmitting ? (
              <>
                <LoadingIndicator type="dots" size="small" text="" />
                <span>Saving...</span>
              </>
            ) : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ProfileForm;
