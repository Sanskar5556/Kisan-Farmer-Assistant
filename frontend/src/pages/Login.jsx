import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Login() {
    const [form, setForm] = useState({ email: '', password: '' });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const { login } = useAuth();
    const navigate = useNavigate();

    const handleChange = e => setForm({ ...form, [e.target.name]: e.target.value });

    const handleSubmit = async e => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            await login(form.email, form.password);
            navigate('/dashboard');
        } catch (err) {
            setError(err.response?.data?.message || 'Login failed. Check your credentials.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-page">
            <div className="auth-box">
                <div className="auth-logo">
                    <span className="logo-icon">🌾</span>
                    <h1>Kisan Smart Assistant</h1>
                    <p>Your agricultural intelligence platform</p>
                </div>

                {error && <div className="alert alert-error">{error}</div>}

                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label>Email Address</label>
                        <input className="form-control" type="email" name="email"
                            placeholder="farmer@example.com" value={form.email}
                            onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label>Password</label>
                        <input className="form-control" type="password" name="password"
                            placeholder="Your password" value={form.password}
                            onChange={handleChange} required />
                    </div>
                    <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center' }}
                        type="submit" disabled={loading}>
                        {loading ? 'Logging in...' : '🔑 Login'}
                    </button>
                </form>

                <p style={{ textAlign: 'center', marginTop: '20px', fontSize: '0.875rem', color: 'var(--text-light)' }}>
                    New farmer? <Link to="/register" style={{ color: 'var(--green-main)', fontWeight: 600 }}>Create account</Link>
                </p>
            </div>
        </div>
    );
}
