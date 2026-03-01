import { useState, useRef } from 'react';
import api from '../api/axios';

export default function ImageUpload() {
    const [image, setImage] = useState(null);
    const [preview, setPreview] = useState(null);
    const [cropName, setCropName] = useState('Wheat');
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [history, setHistory] = useState([]);
    const [showHistory, setShowHistory] = useState(false);
    const [dragging, setDragging] = useState(false);
    const fileRef = useRef();

    const CROPS = ['Wheat', 'Rice', 'Tomato', 'Onion', 'Potato', 'Cotton', 'Soybean', 'Maize', 'Other'];

    const handleFile = (file) => {
        if (!file || !file.type.startsWith('image/')) {
            setError('Please select a valid image file (JPEG, PNG, WebP)');
            return;
        }
        setImage(file);
        setPreview(URL.createObjectURL(file));
        setResult(null);
        setError('');
    };

    const handleDrop = e => {
        e.preventDefault(); setDragging(false);
        handleFile(e.dataTransfer.files[0]);
    };

    const analyze = async () => {
        if (!image) return;
        setLoading(true); setError('');
        const formData = new FormData();
        formData.append('image', image);
        formData.append('crop_name', cropName);
        try {
            const res = await api.post('/image/analyze', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            setResult(res.data);
        } catch (err) {
            setError(err.response?.data?.message || 'Analysis failed. Make sure the AI service is running.');
        } finally {
            setLoading(false);
        }
    };

    const loadHistory = async () => {
        try {
            const r = await api.get('/image/history');
            setHistory(r.data.detections?.data || []);
            setShowHistory(true);
        } catch { setHistory([]); setShowHistory(true); }
    };

    const isHealthy = result?.disease === 'Healthy';

    return (
        <div>
            <div className="page-header">
                <h1>🔬 AI Crop Diagnosis</h1>
                <p>Upload a photo of your crop leaf to detect diseases instantly</p>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 24 }}>
                {/* Upload Panel */}
                <div>
                    <div className="card" style={{ marginBottom: 16 }}>
                        <div className="form-group">
                            <label>Select Crop Type</label>
                            <select className="form-control" value={cropName} onChange={e => setCropName(e.target.value)}>
                                {CROPS.map(c => <option key={c}>{c}</option>)}
                            </select>
                        </div>

                        {/* Drop zone */}
                        <div
                            className={`upload-area ${dragging ? 'dragging' : ''}`}
                            onClick={() => fileRef.current.click()}
                            onDragOver={e => { e.preventDefault(); setDragging(true); }}
                            onDragLeave={() => setDragging(false)}
                            onDrop={handleDrop}
                        >
                            {preview ? (
                                <img src={preview} alt="Preview" style={{ maxHeight: 180, borderRadius: 8, marginBottom: 12 }} />
                            ) : (
                                <>
                                    <div style={{ fontSize: '3rem', marginBottom: 12 }}>📸</div>
                                    <p style={{ fontWeight: 600, color: 'var(--green-dark)' }}>Click or drag & drop a crop image</p>
                                    <p style={{ fontSize: '0.8rem', color: 'var(--text-light)', marginTop: 4 }}>JPEG, PNG, WebP · Max 10MB</p>
                                </>
                            )}
                            <input ref={fileRef} type="file" accept="image/*" style={{ display: 'none' }}
                                onChange={e => handleFile(e.target.files[0])} />
                        </div>
                    </div>

                    {error && <div className="alert alert-error">{error}</div>}

                    <div style={{ display: 'flex', gap: 10 }}>
                        <button className="btn btn-primary" onClick={analyze} disabled={!image || loading} style={{ flex: 1, justifyContent: 'center' }}>
                            {loading ? '🔄 Analyzing...' : '🔬 Analyze Image'}
                        </button>
                        <button className="btn btn-outline" onClick={loadHistory}>📋 History</button>
                    </div>
                </div>

                {/* Result Panel */}
                <div>
                    {loading && (
                        <div className="card" style={{ textAlign: 'center', padding: '60px 20px' }}>
                            <div className="spinner"></div>
                            <p style={{ color: 'var(--text-mid)', marginTop: 16 }}>AI is analyzing your crop image…</p>
                            <p style={{ fontSize: '0.8rem', color: 'var(--text-light)' }}>This may take a few seconds</p>
                        </div>
                    )}

                    {result && !loading && (
                        <div className={`disease-card ${isHealthy ? 'disease-healthy' : 'disease-sick'}`}>
                            <div style={{ fontSize: '2.5rem', marginBottom: 12 }}>{isHealthy ? '✅' : '⚠️'}</div>
                            <div className="disease-title">{result.disease}</div>
                            <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 8 }}>
                                <span style={{ fontSize: '0.85rem', color: 'var(--text-mid)' }}>Confidence:</span>
                                <strong>{result.confidence}%</strong>
                            </div>
                            <div className="confidence-bar">
                                <div className="confidence-fill" style={{ width: `${result.confidence}%`, background: isHealthy ? 'var(--green-main)' : 'var(--accent)' }} />
                            </div>
                            <div style={{ marginTop: 16 }}>
                                <h4 style={{ fontWeight: 700, marginBottom: 8 }}>📋 Recommendation</h4>
                                <p style={{ fontSize: '0.875rem', lineHeight: 1.7, whiteSpace: 'pre-line', color: 'var(--text-mid)' }}>
                                    {result.recommendation}
                                </p>
                            </div>
                        </div>
                    )}

                    {!result && !loading && (
                        <div className="card" style={{ textAlign: 'center', padding: '60px 20px', border: '1.5px dashed var(--border)' }}>
                            <div style={{ fontSize: '3rem' }}>🌿</div>
                            <p style={{ marginTop: 12, color: 'var(--text-light)' }}>Results will appear here after analysis</p>
                        </div>
                    )}
                </div>
            </div>

            {/* History Table */}
            {showHistory && (
                <div className="card" style={{ marginTop: 24 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                        <h3>📋 Past Detections</h3>
                        <button onClick={() => setShowHistory(false)} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.1rem' }}>✕</button>
                    </div>
                    {history.length === 0 ? (
                        <p style={{ color: 'var(--text-light)' }}>No past detections yet.</p>
                    ) : (
                        <table className="price-table">
                            <thead><tr><th>Crop</th><th>Disease</th><th>Confidence</th><th>Date</th></tr></thead>
                            <tbody>
                                {history.map(h => (
                                    <tr key={h.id}>
                                        <td>{h.crop_name || '–'}</td>
                                        <td><span className={`badge ${h.disease_name === 'Healthy' ? 'badge-green' : 'badge-amber'}`}>{h.disease_name}</span></td>
                                        <td>{h.confidence}%</td>
                                        <td>{new Date(h.analyzed_at).toLocaleDateString('en-IN')}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            )}
        </div>
    );
}
