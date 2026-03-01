import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import api from '../api/axios';
import DiscoveryPopup from '../components/DiscoveryPopup';

export default function Dashboard() {
    const { user } = useAuth();
    const [stats, setStats] = useState({ diaries: 0, detections: 0, posts: 0 });
    const [popup, setPopup] = useState(null);
    const [diaries, setDiaries] = useState([]);

    useEffect(() => {
        // Load diary count
        api.get('/diary').then(r => {
            const d = r.data.diaries || [];
            setDiaries(d.slice(0, 3));
            setStats(prev => ({ ...prev, diaries: d.length }));
        }).catch(() => { });

        // Load discovery popup
        api.get('/discovery/popup').then(r => {
            if (r.data.show_popup) setPopup(r.data.popup);
        }).catch(() => { });
    }, []);

    const CROP_EMOJI = { Wheat: '🌾', Rice: '🌾', Tomato: '🍅', Onion: '🧅', Potato: '🥔' };

    return (
        <div>
            {popup && <DiscoveryPopup popup={popup} onClose={() => setPopup(null)} />}

            <div className="page-header">
                <h1>Welcome back, {user?.name?.split(' ')[0]}! 👋</h1>
                <p>Here's your farm overview for today — {new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
            </div>

            {/* Stats Row */}
            <div className="card-grid" style={{ marginBottom: 28 }}>
                <div className="stat-card">
                    <div className="stat-icon green">🌱</div>
                    <div>
                        <div className="stat-val">{stats.diaries}</div>
                        <div className="stat-label">Active Crops</div>
                    </div>
                </div>
                <div className="stat-card">
                    <div className="stat-icon amber">📊</div>
                    <div>
                        <div className="stat-val">Live</div>
                        <div className="stat-label">APMC Prices</div>
                    </div>
                </div>
                <div className="stat-card">
                    <div className="stat-icon blue">🔬</div>
                    <div>
                        <div className="stat-val">AI</div>
                        <div className="stat-label">Disease Detection</div>
                    </div>
                </div>
                <div className="stat-card">
                    <div className="stat-icon purple">👥</div>
                    <div>
                        <div className="stat-val">Community</div>
                        <div className="stat-label">Farmer Network</div>
                    </div>
                </div>
            </div>

            {/* Quick Actions */}
            <div className="card" style={{ marginBottom: 24 }}>
                <h3 style={{ marginBottom: 16, fontWeight: 700 }}>⚡ Quick Actions</h3>
                <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                    <Link to="/diary" className="btn btn-primary">📔 Add Crop Entry</Link>
                    <Link to="/ai" className="btn btn-accent">🔬 Diagnose Disease</Link>
                    <Link to="/apmc" className="btn btn-outline">📊 Check Prices</Link>
                    <Link to="/community" className="btn btn-outline">💬 Community</Link>
                </div>
            </div>

            {/* Recent Crop Diaries */}
            {diaries.length > 0 && (
                <div className="card">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <h3 style={{ fontWeight: 700 }}>🌱 Recent Crops</h3>
                        <Link to="/diary" style={{ color: 'var(--green-main)', fontSize: '0.85rem', fontWeight: 600 }}>View all →</Link>
                    </div>
                    {diaries.map(d => (
                        <div key={d.id} className="diary-card">
                            <div className="crop-icon">{CROP_EMOJI[d.crop_name] || '🌿'}</div>
                            <div>
                                <strong>{d.crop_name}</strong>
                                <div style={{ fontSize: '0.82rem', color: 'var(--text-light)', marginTop: 2 }}>
                                    Sown: {new Date(d.sowing_date).toLocaleDateString('en-IN')} · {d.field_area} acres
                                </div>
                                <span className="crop-stage-badge" style={{ marginTop: 6, display: 'inline-block' }}>
                                    {d.crop_stage} · Day {d.crop_age_days}
                                </span>
                            </div>
                            <Link to="/diary" className="btn btn-sm btn-outline">Advisory →</Link>
                        </div>
                    ))}
                </div>
            )}

            {/* Empty state */}
            {diaries.length === 0 && (
                <div className="card" style={{ textAlign: 'center', padding: '48px 20px' }}>
                    <div style={{ fontSize: '4rem', marginBottom: 16 }}>🌱</div>
                    <h3>Start your crop diary</h3>
                    <p style={{ color: 'var(--text-light)', marginTop: 8, marginBottom: 20 }}>
                        Track your crops and get personalized advisory
                    </p>
                    <Link to="/diary" className="btn btn-primary">Add First Crop</Link>
                </div>
            )}
        </div>
    );
}
