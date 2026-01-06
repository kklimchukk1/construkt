import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import './App.css';
import Products from './pages/Products';
import ProductDetail from './pages/ProductDetail';
import Cart from './pages/Cart';
import Login from './pages/Login';
import Register from './pages/Register';
import Profile from './pages/Profile';
import Calculator from './pages/Calculator';
import CustomerDashboard from './pages/CustomerDashboard';
import ManagerDashboard from './pages/ManagerDashboard';
import AdminDashboard from './pages/AdminDashboard';
import About from './pages/About';
import Layout from './components/layout/Layout';
import { AuthProvider } from './context/AuthContext';
import { ChatProvider } from './context/ChatContext';
import ProtectedRoute from './components/auth/ProtectedRoute';
import Chatbot from './components/chatbot/Chatbot';
import GlobalLoadingIndicator from './components/layout/GlobalLoadingIndicator';
import GlobalErrorHandler from './components/layout/GlobalErrorHandler';

function App() {
  return (
    <AuthProvider>
      <ChatProvider>
        <Router>
          <div className="App">
            <GlobalLoadingIndicator />
            <GlobalErrorHandler />
            <Layout>
              <Routes>
                <Route path="/" element={<Products />} />
                <Route path="/products" element={<Products />} />
                <Route path="/products/:id" element={<ProductDetail />} />
                <Route path="/cart" element={
                  <ProtectedRoute>
                    <Cart />
                  </ProtectedRoute>
                } />
                <Route path="/calculator" element={<Calculator />} />
                <Route path="/about" element={<About />} />
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />
                <Route path="/profile" element={
                  <ProtectedRoute>
                    <Profile />
                  </ProtectedRoute>
                } />
                {/* Customer Dashboard */}
                <Route path="/dashboard" element={
                  <ProtectedRoute>
                    <CustomerDashboard />
                  </ProtectedRoute>
                } />
                {/* Manager/Supplier Dashboard */}
                <Route path="/manager" element={
                  <ProtectedRoute requiredRole="supplier">
                    <ManagerDashboard />
                  </ProtectedRoute>
                } />
                <Route path="/supplier" element={
                  <ProtectedRoute requiredRole="supplier">
                    <ManagerDashboard />
                  </ProtectedRoute>
                } />
                {/* Admin Dashboard */}
                <Route path="/admin" element={
                  <ProtectedRoute requiredRole="admin">
                    <AdminDashboard />
                  </ProtectedRoute>
                } />
                <Route path="/unauthorized" element={<div className="unauthorized-page">You don't have permission to access this page</div>} />
              </Routes>
              <Chatbot />
            </Layout>
          </div>
        </Router>
      </ChatProvider>
    </AuthProvider>
  );
}

export default App;
