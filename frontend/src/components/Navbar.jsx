import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const NAV_ITEMS = [
    { to: '/dashboard', icon: '🏠', label: 'Dashboard' },
    { to: '/apmc', icon: '📊', label: 'Market Prices' },
    { to: '/diary', icon: '📔', label: 'Crop Diary' },
    { to: '/ai', icon: '🔬', label: 'AI Diagnosis' },
    { to: '/community', icon: '👥', label: 'Community' },
];

export default function Navbar() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    return (
        <aside className="sidebar">
            <div className="sidebar-logo">
                <span>🌾</span>
                <div>
                    <h2>Kisan Smart</h2>
                    <p>Assistant</p>
                </div>
            </div>

            <nav className="sidebar-nav">
                {NAV_ITEMS.map(({ to, icon, label }) => (
                    <NavLink
                        key={to}
                        to={to}
                        className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}
                    >
                        <span className="nav-icon">{icon}</span>
                        {label}
                    </NavLink>
                ))}
            </nav>

            <div className="sidebar-footer">
                {user && (
                    <div className="user-chip">
                        <div className="user-avatar">{user.name?.[0]?.toUpperCase()}</div>
                        <div className="user-info">
                            <p>{user.name}</p>
                            <span>{user.role}</span>
                        </div>
                    </div>
                )}
                <button className="logout-btn" onClick={handleLogout}>🚪 Logout</button>
            </div>
        </aside>
    );
}
