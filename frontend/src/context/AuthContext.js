import React, { createContext, useState, useEffect, useContext } from 'react';

// Create the authentication context
const AuthContext = createContext();

// Custom hook to use the auth context
export const useAuth = () => {
  return useContext(AuthContext);
};

// Provider component that wraps the app and makes auth object available to any child component that calls useAuth()
export const AuthProvider = ({ children }) => {
  const [currentUser, setCurrentUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [addresses, setAddresses] = useState([]);
  
  // Check if user is already logged in on initial load
  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    const storedToken = localStorage.getItem('token');
    
    if (storedUser && storedToken) {
      try {
        setCurrentUser(JSON.parse(storedUser));
      } catch (e) {
        // Invalid stored user
        localStorage.removeItem('user');
        localStorage.removeItem('token');
      }
    }
    
    setLoading(false);
  }, []);
  
  // Login function
  const login = async (email, password) => {
    setLoading(true);
    setError('');
    
    try {
      // Import auth service
      const authService = await import('../services/authService').then(module => module.default);
      
      // Call the login API
      const response = await authService.login(email, password);
      
      // Check if login was successful
      if (response.status === 'success' && response.data && response.data.user) {
        // Convert snake_case to camelCase for frontend
        const user = {
          id: response.data.user.id,
          email: response.data.user.email,
          firstName: response.data.user.first_name,
          lastName: response.data.user.last_name,
          role: response.data.user.role,
          phone: response.data.user.phone,
          companyName: response.data.user.company_name,
          address: response.data.user.address,
          city: response.data.user.city,
          state: response.data.user.state,
          postalCode: response.data.user.postal_code,
          country: response.data.user.country,
          isActive: response.data.user.is_active
        };
        
        // Store user data in localStorage
        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('token', response.data.token);
        
        setCurrentUser(user);
        return user;
      } else {
        throw new Error(response.message || 'Login failed');
      }
    
    } catch (error) {
      setError('Failed to log in. Please check your credentials.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Register function
  const register = async (userData) => {
    setLoading(true);
    setError('');
    
    try {
      // Import auth service
      const authService = await import('../services/authService').then(module => module.default);
      
      // Format the user data to match the backend API expectations
      const formattedUserData = {
        email: userData.email,
        password: userData.password,
        first_name: userData.firstName,
        last_name: userData.lastName,
        role: userData.role || 'customer'
      };
      
      // Call the register API
      const response = await authService.register(formattedUserData);
      
      // Check if registration was successful
      if (response.status === 'success' && response.data && response.data.user) {
        // Convert snake_case to camelCase for frontend
        const user = {
          id: response.data.user.id,
          email: response.data.user.email,
          firstName: response.data.user.first_name,
          lastName: response.data.user.last_name,
          role: response.data.user.role,
          phone: response.data.user.phone,
          companyName: response.data.user.company_name,
          address: response.data.user.address,
          city: response.data.user.city,
          state: response.data.user.state,
          postalCode: response.data.user.postal_code,
          country: response.data.user.country,
          isActive: response.data.user.is_active
        };
        
        // Store user data in localStorage
        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('token', response.data.token);
        
        setCurrentUser(user);
        return user;
      } else {
        throw new Error(response.message || 'Registration failed');
      }
    
    } catch (error) {
      setError('Failed to register. Please try again.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Logout function
  const logout = () => {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    setCurrentUser(null);
  };
  
  // Get authentication token
  const getToken = () => {
    return localStorage.getItem('token');
  };
  
  // Check if user is authenticated
  const isAuthenticated = () => {
    return !!currentUser && !!getToken();
  };
  
  // Check if user has specific role
  const hasRole = (role) => {
    if (!currentUser) return false;
    if (Array.isArray(role)) {
      return role.includes(currentUser.role);
    }
    return currentUser.role === role;
  };
  
  // Update user profile
  const updateProfile = async (profileData) => {
    setLoading(true);
    setError('');
    
    try {
      // Import auth service
      const authService = await import('../services/authService').then(module => module.default);
      
      // Format the profile data to match the backend API expectations
      const formattedProfileData = {
        first_name: profileData.firstName,
        last_name: profileData.lastName,
        email: profileData.email,
        phone: profileData.phone || null,
        company_name: profileData.company_name || null,
        address: profileData.address || null,
        city: profileData.city || null,
        state: profileData.state || null,
        postal_code: profileData.postal_code || null,
        country: profileData.country || null
      };
      
      // Call the update profile API
      const response = await authService.updateProfile(formattedProfileData);
      
      // Check if update was successful
      if (response.status === 'success' && response.data && response.data.user) {
        // Convert snake_case to camelCase for frontend
        const updatedUser = {
          id: response.data.user.id,
          email: response.data.user.email,
          firstName: response.data.user.first_name,
          lastName: response.data.user.last_name,
          role: response.data.user.role,
          phone: response.data.user.phone,
          company_name: response.data.user.company_name,
          address: response.data.user.address,
          city: response.data.user.city,
          state: response.data.user.state,
          postal_code: response.data.user.postal_code,
          country: response.data.user.country,
          isActive: response.data.user.is_active
        };
        
        // Store updated user data in localStorage
        localStorage.setItem('user', JSON.stringify(updatedUser));
        
        setCurrentUser(updatedUser);
        return updatedUser;
      } else {
        throw new Error(response.message || 'Failed to update profile');
      }
    } catch (error) {
      setError('Failed to update profile. Please try again.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Change password
  const changePassword = async (currentPassword, newPassword) => {
    setLoading(true);
    setError('');
    
    try {
      // This will be replaced with an actual API call
      // For now, simulate API call with timeout
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // In a real implementation, this would verify the current password
      // and update to the new password on the server
      
      return true;
    } catch (error) {
      setError('Failed to change password. Please try again.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Get user addresses
  const getUserAddresses = async () => {
    setLoading(true);
    setError('');
    
    try {
      // Make sure currentUser exists
      if (!currentUser) {
        setError('User not authenticated');
        return [];
      }
      
      // This will be replaced with an actual API call
      // For now, simulate API call with timeout and mock data
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Use a default ID if currentUser.id is not available
      const userId = currentUser.id || 1;
      
      // Mock address data
      const mockAddresses = [
        {
          id: 1,
          user_id: userId,
          address_type: 'shipping',
          address_line1: '123 Main St',
          address_line2: 'Apt 4B',
          city: 'New York',
          state: 'NY',
          postal_code: '10001',
          country: 'United States',
          is_default: true
        },
        {
          id: 2,
          user_id: userId,
          address_type: 'billing',
          address_line1: '456 Market St',
          address_line2: '',
          city: 'San Francisco',
          state: 'CA',
          postal_code: '94103',
          country: 'United States',
          is_default: false
        }
      ];
      
      setAddresses(mockAddresses);
      return mockAddresses;
    } catch (error) {
      setError('Failed to fetch addresses. Please try again.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Save address (create or update)
  const saveAddress = async (addressData) => {
    setLoading(true);
    setError('');
    
    try {
      // Make sure currentUser exists
      if (!currentUser) {
        setError('User not authenticated');
        return false;
      }
      
      // This will be replaced with an actual API call
      // For now, simulate API call with timeout
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Use a default ID if currentUser.id is not available
      const userId = currentUser.id || 1;
      
      // In a real implementation, this would create or update an address on the server
      // For now, just update the local state
      if (addressData.id) {
        // Update existing address
        const updatedAddresses = addresses.map(addr => 
          addr.id === addressData.id ? { ...addr, ...addressData } : addr
        );
        setAddresses(updatedAddresses);
      } else {
        // Create new address
        const newAddress = {
          ...addressData,
          id: Math.floor(Math.random() * 1000),
          user_id: userId
        };
        setAddresses([...addresses, newAddress]);
      }
      
      return true;
    } catch (error) {
      setError('Failed to save address. Please try again.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Delete address
  const deleteAddress = async (addressId) => {
    setLoading(true);
    setError('');
    
    try {
      // Make sure currentUser exists
      if (!currentUser) {
        setError('User not authenticated');
        return false;
      }
      
      // This will be replaced with an actual API call
      // For now, simulate API call with timeout
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // In a real implementation, this would delete the address on the server
      // For now, just update the local state
      const updatedAddresses = addresses.filter(addr => addr.id !== addressId);
      setAddresses(updatedAddresses);
      
      return true;
    } catch (error) {
      setError('Failed to delete address. Please try again.');
      throw error;
    } finally {
      setLoading(false);
    }
  };
  
  // Value object that will be passed to any consuming components
  const value = {
    currentUser,
    loading,
    error,
    login,
    register,
    logout,
    getToken,
    isAuthenticated,
    hasRole,
    updateProfile,
    changePassword,
    getUserAddresses,
    saveAddress,
    deleteAddress
  };
  
  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
};

export default AuthContext;
