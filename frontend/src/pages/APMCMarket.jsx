import { useState } from 'react';
import api from '../api/axios';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const STATES = ['Maharashtra', 'Punjab', 'Uttar Pradesh', 'Rajasthan', 'Gujarat', 'Karnataka', 'Madhya Pradesh'];
const CROPS = ['Wheat', 'Rice', 'Tomato', 'Onion', 'Potato', 'Cotton', 'Soybean', 'Maize'];

export default function APMCMarket() {
    const [tab, setTab] = useState('district');
    const [crop, setCrop] = useState('Wheat');
    const [state, setState] = useState('Maharashtra');
    const [district, setDistrict] = useState('Pune');
    const [year, setYear] = useState(new Date().getFullYear());
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const endpoints = {
        district: `/apmc/district?crop=${crop}&district=${district}`,
        state: `/apmc/state?crop=${crop}&state=${state}`,
        national: `/apmc/national?crop=${crop}`,
        trend: `/apmc/trend?crop=${crop}&state=${state}&year=${year}`,
    };

    const fetchData = async () => {
        setLoading(true); setError('');
        try {
            const res = await api.get(endpoints[tab]);
            setData(res.data);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to fetch data. Make sure demo data is seeded.');
        } finally {
            setLoading(false);
        }
    };

    const TabBtn = ({ id, label }) => (
        <button className={`btn ${tab === id ? 'btn-primary' : 'btn-outline'} btn-sm`}
            onClick={() => { setTab(id); setData(null); }}>{label}</button>
    );

    return (
        <div>
            <div className="page-header">
                <h1>📊 APMC Market Prices</h1>
                <p>Real-time crop prices from APMC mandis across India</p>
            </div>

            {/* Tab row */}
            <div style={{ display: 'flex', gap: 8, marginBottom: 20, flexWrap: 'wrap' }}>
                <TabBtn id="district" label="🏘️ By District" />
                <TabBtn id="state" label="🗺️ State Average" />
                <TabBtn id="national" label="🇮🇳 National Average" />
                <TabBtn id="trend" label="📈 Year Trend" />
            </div>

            {/* Filter Card */}
            <div className="card" style={{ marginBottom: 20 }}>
                <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'flex-end' }}>
                    <div className="form-group" style={{ marginBottom: 0, minWidth: 140 }}>
                        <label>Crop</label>
                        <select className="form-control" value={crop} onChange={e => setCrop(e.target.value)}>
                            {CROPS.map(c => <option key={c}>{c}</option>)}
                        </select>
                    </div>

                    {(tab === 'district') && (
                        <div className="form-group" style={{ marginBottom: 0, minWidth: 150 }}>
                            <label>District</label>
                            <input className="form-control" value={district} onChange={e => setDistrict(e.target.value)} placeholder="e.g. Pune" />
                        </div>
                    )}

                    {(tab === 'state' || tab === 'trend') && (
                        <div className="form-group" style={{ marginBottom: 0, minWidth: 160 }}>
                            <label>State</label>
                            <select className="form-control" value={state} onChange={e => setState(e.target.value)}>
                                {STATES.map(s => <option key={s}>{s}</option>)}
                            </select>
                        </div>
                    )}

                    {tab === 'trend' && (
                        <div className="form-group" style={{ marginBottom: 0, minWidth: 100 }}>
                            <label>Year</label>
                            <input className="form-control" type="number" value={year} onChange={e => setYear(e.target.value)} min="2020" max="2030" />
                        </div>
                    )}

                    <button className="btn btn-primary" onClick={fetchData} disabled={loading}>
                        {loading ? '⏳ Loading...' : '🔍 Search'}
                    </button>
                </div>
            </div>

            {error && <div className="alert alert-error">{error}</div>}

            {/* Results */}
            {data && !loading && (
                <div className="card">
                    {/* District / State / National → Table */}
                    {(tab === 'district') && data.prices && (
                        <>
                            <h3 style={{ marginBottom: 16 }}>{data.crop} prices in {data.district}</h3>
                            <div style={{ overflowX: 'auto' }}>
                                <table className="price-table">
                                    <thead><tr>
                                        <th>Market</th><th>Date</th>
                                        <th>Min (₹/qtl)</th><th>Modal (₹/qtl)</th><th>Max (₹/qtl)</th>
                                    </tr></thead>
                                    <tbody>
                                        {data.prices.map((p, i) => (
                                            <tr key={i}>
                                                <td>{p.market_name}</td>
                                                <td>{new Date(p.price_date).toLocaleDateString('en-IN')}</td>
                                                <td className="price-low">₹{p.min_price}</td>
                                                <td style={{ fontWeight: 700 }}>₹{p.modal_price}</td>
                                                <td className="price-high">₹{p.max_price}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}

                    {tab === 'state' && data.by_district && (
                        <>
                            <h3 style={{ marginBottom: 4 }}>{data.crop} — {data.state}</h3>
                            <div className="alert alert-info" style={{ marginBottom: 16 }}>
                                State Average: <strong>₹{data.state_average} / quintal</strong>
                            </div>
                            <table className="price-table">
                                <thead><tr><th>District</th><th>Avg Price</th><th>Lowest</th><th>Highest</th></tr></thead>
                                <tbody>
                                    {data.by_district.map((r, i) => (
                                        <tr key={i}>
                                            <td>{r.district}</td>
                                            <td>₹{Number(r.avg_price).toFixed(0)}</td>
                                            <td className="price-low">₹{Number(r.lowest_price).toFixed(0)}</td>
                                            <td className="price-high">₹{Number(r.highest_price).toFixed(0)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </>
                    )}

                    {tab === 'national' && data.by_state && (
                        <>
                            <h3 style={{ marginBottom: 4 }}>🇮🇳 {data.crop} — National</h3>
                            <div className="alert alert-info" style={{ marginBottom: 16 }}>
                                National Average: <strong>₹{data.national_average} / quintal</strong>
                            </div>
                            <table className="price-table">
                                <thead><tr><th>State</th><th>Avg Price</th><th>Market Count</th></tr></thead>
                                <tbody>
                                    {data.by_state.map((r, i) => (
                                        <tr key={i}><td>{r.state}</td><td>₹{Number(r.avg_price).toFixed(0)}</td><td>{r.market_listings}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        </>
                    )}

                    {/* Trend → Line Chart */}
                    {tab === 'trend' && data.trend && (
                        <>
                            <h3 style={{ marginBottom: 16 }}>📈 {data.crop} Price Trend — {data.year}</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={data.trend}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month_name" tick={{ fontSize: 12 }} />
                                    <YAxis tick={{ fontSize: 12 }} />
                                    <Tooltip formatter={(v) => `₹${v}`} />
                                    <Legend />
                                    <Line type="monotone" dataKey="avg_price" stroke="#2d8a4e" strokeWidth={2} name="Avg Price (₹)" dot={{ r: 4 }} />
                                    <Line type="monotone" dataKey="min_price" stroke="#e53935" strokeWidth={1.5} name="Min Price (₹)" dot={false} strokeDasharray="4 2" />
                                    <Line type="monotone" dataKey="max_price" stroke="#f4a22d" strokeWidth={1.5} name="Max Price (₹)" dot={false} strokeDasharray="4 2" />
                                </LineChart>
                            </ResponsiveContainer>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
