import { useState, useEffect } from 'react';
import api from '../api/axios';

const CROP_EMOJI = { Wheat: '🌾', Rice: '🌾', Tomato: '🍅', Onion: '🧅', Potato: '🥔', Cotton: '🌸', Soybean: '🫘', Maize: '🌽', Other: '🌿' };
const STAGES = { germination: '🌱', seedling: '🌿', vegetative: '🍃', flowering: '🌸', grain_filling: '🌾', maturity: '✅' };

export default function CropDiary() {
    const [diaries, setDiaries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [advisory, setAdvisory] = useState(null);
    const [advLoading, setAdvLoading] = useState(false);
    const [form, setForm] = useState({
        crop_name: 'Wheat', sowing_date: '', field_area: '',
        field_location: '', irrigation_type: 'rain-fed', notes: ''
    });
    const [submitting, setSubmitting] = useState(false);
    const [msg, setMsg] = useState('');

    useEffect(() => { fetchDiaries(); }, []);

    const fetchDiaries = () => {
        setLoading(true);
        api.get('/diary')
            .then(r => setDiaries(r.data.diaries || []))
            .catch(() => { })
            .finally(() => setLoading(false));
    };

    const handleSubmit = async e => {
        e.preventDefault();
        setSubmitting(true); setMsg('');
        try {
            await api.post('/diary', form);
            setMsg('✅ Crop entry added!');
            setShowForm(false);
            setForm({ crop_name: 'Wheat', sowing_date: '', field_area: '', field_location: '', irrigation_type: 'rain-fed', notes: '' });
            fetchDiaries();
        } catch (err) {
            setMsg('❌ ' + (err.response?.data?.message || 'Failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const getAdvisory = async (id) => {
        setAdvLoading(id); setAdvisory(null);
        try {
            const r = await api.get(`/diary/${id}/advisory`);
            setAdvisory(r.data);
        } catch { setAdvisory({ error: 'Could not load advisory' }); }
        finally { setAdvLoading(false); }
    };

    const deleteDiary = async (id) => {
        if (!window.confirm('Delete this crop entry?')) return;
        await api.delete(`/diary/${id}`);
        fetchDiaries();
    };

    return (
        <div>
            <div className="page-header">
                <h1>📔 Crop Diary</h1>
                <p>Track your crops and get personalized daily advisory</p>
            </div>

            <button className="btn btn-primary" style={{ marginBottom: 20 }} onClick={() => setShowForm(!showForm)}>
                {showForm ? '✕ Cancel' : '+ Add Crop Entry'}
            </button>

            {msg && <div className={`alert ${msg.startsWith('✅') ? 'alert-success' : 'alert-error'}`}>{msg}</div>}

            {/* Add Crop Form */}
            {showForm && (
                <div className="card" style={{ marginBottom: 24 }}>
                    <h3 style={{ marginBottom: 16, fontWeight: 700 }}>🌱 New Crop Entry</h3>
                    <form onSubmit={handleSubmit}>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label>Crop Name *</label>
                                <select className="form-control" value={form.crop_name} onChange={e => setForm({ ...form, crop_name: e.target.value })}>
                                    {Object.keys(CROP_EMOJI).filter(c => c !== 'Other').map(c => <option key={c}>{c}</option>)}
                                </select>
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label>Sowing Date *</label>
                                <input className="form-control" type="date" value={form.sowing_date}
                                    onChange={e => setForm({ ...form, sowing_date: e.target.value })}
                                    max={new Date().toISOString().split('T')[0]} required />
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label>Field Area (acres) *</label>
                                <input className="form-control" type="number" step="0.01" min="0.01" value={form.field_area}
                                    onChange={e => setForm({ ...form, field_area: e.target.value })} placeholder="e.g. 2.5" required />
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label>Irrigation Type</label>
                                <select className="form-control" value={form.irrigation_type} onChange={e => setForm({ ...form, irrigation_type: e.target.value })}>
                                    {['rain-fed', 'canal', 'drip', 'sprinkler'].map(t => <option key={t}>{t}</option>)}
                                </select>
                            </div>
                            <div className="form-group" style={{ marginBottom: 0, gridColumn: '1/-1' }}>
                                <label>Field Location</label>
                                <input className="form-control" value={form.field_location}
                                    onChange={e => setForm({ ...form, field_location: e.target.value })} placeholder="e.g. Village Rampur, Block North" />
                            </div>
                            <div className="form-group" style={{ marginBottom: 0, gridColumn: '1/-1' }}>
                                <label>Notes</label>
                                <textarea className="form-control" rows={2} value={form.notes}
                                    onChange={e => setForm({ ...form, notes: e.target.value })} placeholder="Any observations..." />
                            </div>
                        </div>
                        <button className="btn btn-primary" type="submit" style={{ marginTop: 16 }} disabled={submitting}>
                            {submitting ? 'Saving...' : '💾 Save Entry'}
                        </button>
                    </form>
                </div>
            )}

            {/* Advisory Popup */}
            {advisory && (
                <div className="card advisory-box" style={{ marginBottom: 24, border: '1.5px solid var(--green-light)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 12 }}>
                        <h3>🌤️ Daily Advisory — Day {advisory.age_days}</h3>
                        <button onClick={() => setAdvisory(null)} style={{ background: 'none', border: 'none', fontSize: '1.2rem', cursor: 'pointer' }}>✕</button>
                    </div>
                    {advisory.error ? (
                        <div className="alert alert-error">{advisory.error}</div>
                    ) : (
                        <>
                            <div style={{ display: 'flex', gap: 12, marginBottom: 16, flexWrap: 'wrap' }}>
                                <span className="badge badge-green">Stage: {advisory.stage}</span>
                                <span className="badge badge-blue">🌡️ {advisory.weather?.temperature}°C</span>
                                <span className="badge badge-amber">💧 {advisory.weather?.humidity}% humidity</span>
                            </div>
                            {advisory.advisory?.weather_tips?.map((tip, i) => (
                                <div key={i} className="advisory-tip">
                                    <span>💡</span><span>{tip}</span>
                                </div>
                            ))}
                        </>
                    )}
                </div>
            )}

            {/* Crop List */}
            {loading ? <div className="spinner"></div> : (
                diaries.length === 0 ? (
                    <div className="card" style={{ textAlign: 'center', padding: '48px 20px' }}>
                        <div style={{ fontSize: '3rem' }}>📔</div>
                        <h3 style={{ marginTop: 12 }}>No crops added yet</h3>
                        <p style={{ color: 'var(--text-light)', marginTop: 8 }}>Click "Add Crop Entry" to get started</p>
                    </div>
                ) : (
                    diaries.map(d => (
                        <div key={d.id} className="diary-card">
                            <div className="crop-icon">{CROP_EMOJI[d.crop_name] || '🌿'}</div>
                            <div>
                                <strong style={{ fontSize: '1rem' }}>{d.crop_name}</strong>
                                <div style={{ fontSize: '0.82rem', color: 'var(--text-light)', marginTop: 2 }}>
                                    Sown: {new Date(d.sowing_date).toLocaleDateString('en-IN')} · {d.field_area} acres · {d.irrigation_type}
                                </div>
                                <div style={{ marginTop: 6, display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
                                    <span className="crop-stage-badge">{STAGES[d.crop_stage] || ''} {d.crop_stage} — Day {d.crop_age_days}</span>
                                    {d.notes && <span style={{ fontSize: '0.78rem', color: 'var(--text-light)' }}>📝 {d.notes}</span>}
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: 8 }}>
                                <button className="btn btn-sm btn-outline" onClick={() => getAdvisory(d.id)} disabled={advLoading === d.id}>
                                    {advLoading === d.id ? '...' : '🌤️ Advisory'}
                                </button>
                                <button className="btn btn-sm btn-danger" onClick={() => deleteDiary(d.id)}>🗑️</button>
                            </div>
                        </div>
                    ))
                )
            )}
        </div>
    );
}
