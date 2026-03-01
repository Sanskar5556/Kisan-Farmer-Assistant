import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import Navbar from './components/Navbar';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import APMCMarket from './pages/APMCMarket';
import CropDiary from './pages/CropDiary';
import ImageUpload from './pages/ImageUpload';
import Community from './pages/Community';

/* ---- Full-page loader shown while checking auth token ---- */
function PageLoader() {
    return (
        <div style={{
            minHeight: '100vh',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            background: 'linear-gradient(135deg, #1a5c2a 0%, #2d8a4e 50%, #4caf70 100%)',
            gap: 16,
        }}>
            <div style={{ fontSize: '4rem' }}>🌾</div>
            <div style={{ color: 'white', fontWeight: 700, fontSize: '1.2rem' }}>Kisan Smart Assistant</div>
            <div style={{ color: 'rgba(255,255,255,0.7)', fontSize: '0.9rem' }}>Loading your farm dashboard...</div>
            <div className="spinner" style={{ borderColor: 'rgba(255,255,255,0.3)', borderTopColor: 'white', marginTop: 8 }} />
        </div>
    );
}

/* ---- Protected Route ---- */
/* If user is NOT logged in, redirect to /login */
function ProtectedRoute({ children }) {
    const { isAuthenticated, loading } = useAuth();
    if (loading) return <PageLoader />;
    return isAuthenticated ? children : <Navigate to="/login" replace />;
}

/* ---- Public Route ---- */
/* If user IS logged in, redirect to dashboard (no going back to login page) */
function PublicRoute({ children }) {
    const { isAuthenticated, loading } = useAuth();
    if (loading) return <PageLoader />;
    return !isAuthenticated ? children : <Navigate to="/dashboard" replace />;
}

/* ---- App Layout (Sidebar + Content) ---- */
function AppLayout({ children }) {
    return (
        <div className="app-layout">
            <Navbar />
            <main className="main-content">
                {children}
            </main>
        </div>
    );
}

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Routes>
                    {/* Public: Login / Register */}
                    <Route path="/login" element={<PublicRoute><Login /></PublicRoute>} />
                    <Route path="/register" element={<PublicRoute><Register /></PublicRoute>} />

                    {/* Protected: All app pages */}
                    <Route path="/dashboard" element={<ProtectedRoute><AppLayout><Dashboard /></AppLayout></ProtectedRoute>} />
                    <Route path="/apmc" element={<ProtectedRoute><AppLayout><APMCMarket /></AppLayout></ProtectedRoute>} />
                    <Route path="/diary" element={<ProtectedRoute><AppLayout><CropDiary /></AppLayout></ProtectedRoute>} />
                    <Route path="/ai" element={<ProtectedRoute><AppLayout><ImageUpload /></AppLayout></ProtectedRoute>} />
                    <Route path="/community" element={<ProtectedRoute><AppLayout><Community /></AppLayout></ProtectedRoute>} />

                    {/* Default redirect */}
                    <Route path="/" element={<Navigate to="/dashboard" replace />} />
                    <Route path="*" element={<Navigate to="/dashboard" replace />} />
                </Routes>
            </BrowserRouter>
        </AuthProvider>
    );
}
