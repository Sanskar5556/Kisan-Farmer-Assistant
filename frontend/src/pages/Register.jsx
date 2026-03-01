import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api/axios';

export default function Register() {
    const [form, setForm] = useState({
        name: '', email: '', password: '', password_confirmation: '',
        phone: '', state: '', district: ''
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    const handleChange = e => setForm({ ...form, [e.target.name]: e.target.value });

    const handleSubmit = async e => {
        e.preventDefault();
        setError('');
        if (form.password !== form.password_confirmation) {
            return setError('Passwords do not match');
        }
        setLoading(true);
        try {
            const res = await api.post('/auth/register', form);
            localStorage.setItem('kisan_token', res.data.token);
            localStorage.setItem('kisan_user', JSON.stringify(res.data.user));
            setSuccess('Account created! Redirecting...');
            setTimeout(() => navigate('/dashboard'), 1200);
        } catch (err) {
            const errors = err.response?.data?.errors;
            if (errors) {
                setError(Object.values(errors).flat().join('. '));
            } else {
                setError(err.response?.data?.message || 'Registration failed');
            }
        } finally {
            setLoading(false);
        }
    };

    const STATES = ['Andhra Pradesh', 'Bihar', 'Gujarat', 'Haryana', 'Karnataka', 'Kerala', 'Madhya Pradesh',
        'Maharashtra', 'Punjab', 'Rajasthan', 'Tamil Nadu', 'Telangana', 'Uttar Pradesh', 'West Bengal'];

    return (
        <div className="auth-page">
            <div className="auth-box" style={{ maxWidth: 500 }}>
                <div className="auth-logo">
                    <span className="logo-icon">🌱</span>
                    <h1>Join Kisan Smart</h1>
                    <p>Create your farmer account</p>
                </div>

                {error && <div className="alert alert-error">{error}</div>}
                {success && <div className="alert alert-success">{success}</div>}

                <form onSubmit={handleSubmit}>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                        <div className="form-group" style={{ marginBottom: 0 }}>
                            <label>Full Name *</label>
                            <input className="form-control" name="name" placeholder="Rajesh Kumar"
                                value={form.name} onChange={handleChange} required />
                        </div>
                        <div className="form-group" style={{ marginBottom: 0 }}>
                            <label>Phone</label>
                            <input className="form-control" name="phone" placeholder="9876543210"
                                value={form.phone} onChange={handleChange} />
                        </div>
                    </div>

                    <div className="form-group" style={{ marginTop: 12 }}>
                        <label>Email Address *</label>
                        <input className="form-control" type="email" name="email"
                            placeholder="rajesh@example.com" value={form.email} onChange={handleChange} required />
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                        <div className="form-group" style={{ marginBottom: 0 }}>
                            <label>State</label>
                            <select className="form-control" name="state" value={form.state} onChange={handleChange}>
                                <option value="">Select state</option>
                                {STATES.map(s => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>
                        <div className="form-group" style={{ marginBottom: 0 }}>
                            <label>District</label>
                            <input className="form-control" name="district" placeholder="Your district"
                                value={form.district} onChange={handleChange} />
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginTop: 12 }}>
                        <div className="form-group" style={{ marginBottom: 0 }}>
                            <label>Password *</label>
                            <input className="form-control" type="password" name="password"
                                placeholder="Min 6 chars" value={form.password} onChange={handleChange} required />
                        </div>
                        <div className="form-group" style={{ marginBottom: 0 }}>
                            <label>Confirm Password *</label>
                            <input className="form-control" type="password" name="password_confirmation"
                                placeholder="Repeat password" value={form.password_confirmation} onChange={handleChange} required />
                        </div>
                    </div>

                    <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', marginTop: 20 }}
                        type="submit" disabled={loading}>
                        {loading ? 'Creating account...' : '✅ Create Account'}
                    </button>
                </form>

                <p style={{ textAlign: 'center', marginTop: 16, fontSize: '0.875rem', color: 'var(--text-light)' }}>
                    Already have an account? <Link to="/login" style={{ color: 'var(--green-main)', fontWeight: 600 }}>Login</Link>
                </p>
            </div>
        </div>
    );
}
