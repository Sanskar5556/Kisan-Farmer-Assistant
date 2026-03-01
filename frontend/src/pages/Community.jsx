import { useState, useEffect } from 'react';
import api from '../api/axios';
import { useAuth } from '../context/AuthContext';

const CROP_TAGS = ['all', 'wheat', 'rice', 'tomato', 'onion', 'potato', 'cotton', 'soybean'];

export default function Community() {
    const { user } = useAuth();
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [cropTag, setCropTag] = useState('all');
    const [showForm, setShowForm] = useState(false);
    const [commentBox, setCommentBox] = useState(null);
    const [commentText, setCommentText] = useState('');
    const [form, setForm] = useState({ title: '', body: '', crop_tag: '', image: null });
    const [submitting, setSubmitting] = useState(false);
    const [msg, setMsg] = useState('');

    useEffect(() => { fetchPosts(); }, [cropTag]);

    const fetchPosts = () => {
        setLoading(true);
        const url = cropTag === 'all' ? '/community/posts' : `/community/posts?crop_tag=${cropTag}`;
        api.get(url)
            .then(r => setPosts(r.data.posts?.data || []))
            .catch(() => setPosts([]))
            .finally(() => setLoading(false));
    };

    const handlePost = async e => {
        e.preventDefault();
        setSubmitting(true); setMsg('');
        const fd = new FormData();
        fd.append('title', form.title);
        fd.append('body', form.body);
        if (form.crop_tag) fd.append('crop_tag', form.crop_tag);
        if (form.image) fd.append('image', form.image);
        try {
            await api.post('/community/posts', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            setMsg('✅ Post created!');
            setShowForm(false);
            setForm({ title: '', body: '', crop_tag: '', image: null });
            fetchPosts();
        } catch (err) {
            setMsg('❌ ' + (err.response?.data?.message || 'Failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const toggleLike = async (postId) => {
        try {
            const r = await api.post(`/community/posts/${postId}/like`);
            setPosts(posts.map(p => p.id === postId ? { ...p, likes_count: r.data.likes_count } : p));
        } catch { }
    };

    const addComment = async (postId) => {
        if (!commentText.trim()) return;
        try {
            await api.post(`/community/posts/${postId}/comments`, { body: commentText });
            setCommentText(''); setCommentBox(null);
            fetchPosts();
        } catch { }
    };

    const timeAgo = (dateStr) => {
        const diff = Date.now() - new Date(dateStr);
        const mins = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        if (days > 0) return `${days}d ago`;
        if (hours > 0) return `${hours}h ago`;
        return `${mins}m ago`;
    };

    return (
        <div>
            <div className="page-header">
                <h1>👥 Farmer Community</h1>
                <p>Share knowledge, ask questions, and learn from fellow farmers</p>
            </div>

            {/* Top bar: New Post + Crop Tags */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20, flexWrap: 'wrap', gap: 12 }}>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    {CROP_TAGS.map(tag => (
                        <button key={tag} onClick={() => setCropTag(tag)}
                            className={`btn btn-sm ${cropTag === tag ? 'btn-primary' : 'btn-outline'}`}>
                            {tag === 'all' ? '🌐 All' : `🌱 ${tag}`}
                        </button>
                    ))}
                </div>
                <button className="btn btn-accent" onClick={() => setShowForm(!showForm)}>
                    {showForm ? '✕ Cancel' : '✏️ New Post'}
                </button>
            </div>

            {msg && <div className={`alert ${msg.startsWith('✅') ? 'alert-success' : 'alert-error'}`}>{msg}</div>}

            {/* Create Post Form */}
            {showForm && (
                <div className="card" style={{ marginBottom: 24 }}>
                    <h3 style={{ marginBottom: 16, fontWeight: 700 }}>✏️ Create Post</h3>
                    <form onSubmit={handlePost}>
                        <div className="form-group">
                            <label>Title *</label>
                            <input className="form-control" value={form.title} onChange={e => setForm({ ...form, title: e.target.value })}
                                placeholder="e.g. Tips for growing wheat in dry season" required />
                        </div>
                        <div className="form-group">
                            <label>Body *</label>
                            <textarea className="form-control" rows={4} value={form.body} onChange={e => setForm({ ...form, body: e.target.value })}
                                placeholder="Share your experience, question, or advice..." required />
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label>Crop Tag</label>
                                <input className="form-control" value={form.crop_tag} onChange={e => setForm({ ...form, crop_tag: e.target.value })}
                                    placeholder="e.g. wheat, tomato" />
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label>Photo (optional)</label>
                                <input className="form-control" type="file" accept="image/*"
                                    onChange={e => setForm({ ...form, image: e.target.files[0] })} />
                            </div>
                        </div>
                        <button className="btn btn-primary" type="submit" style={{ marginTop: 16 }} disabled={submitting}>
                            {submitting ? 'Posting...' : '📤 Post'}
                        </button>
                    </form>
                </div>
            )}

            {/* Posts Feed */}
            {loading ? <div className="spinner"></div> : (
                posts.length === 0 ? (
                    <div className="card" style={{ textAlign: 'center', padding: '48px 20px' }}>
                        <div style={{ fontSize: '3rem' }}>👥</div>
                        <h3 style={{ marginTop: 12 }}>No posts yet</h3>
                        <p style={{ color: 'var(--text-light)', marginTop: 8 }}>Be the first to share in the community!</p>
                    </div>
                ) : (
                    posts.map(post => (
                        <div key={post.id} className="post-card">
                            <div className="post-header">
                                <div className="post-avatar">{post.user?.name?.[0]?.toUpperCase()}</div>
                                <div className="post-meta">
                                    <p>{post.user?.name}</p>
                                    <span>{post.user?.district && `${post.user.district} · `}{timeAgo(post.created_at)}</span>
                                </div>
                                {post.crop_tag && <span className="badge badge-green" style={{ marginLeft: 'auto' }}>🌱 {post.crop_tag}</span>}
                            </div>

                            <h4 style={{ fontWeight: 700, marginBottom: 8 }}>{post.title}</h4>
                            <p style={{ color: 'var(--text-mid)', fontSize: '0.9rem', lineHeight: 1.6 }}>{post.body}</p>

                            {post.image_path && (
                                <img className="post-image" src={`/storage/${post.image_path}`} alt="Post image" />
                            )}

                            <div className="post-actions">
                                <button className="action-btn" onClick={() => toggleLike(post.id)}>
                                    ❤️ {post.likes_count}
                                </button>
                                <button className="action-btn" onClick={() => setCommentBox(commentBox === post.id ? null : post.id)}>
                                    💬 {post.comments?.length || 0} Comments
                                </button>
                            </div>

                            {/* Comment section */}
                            {commentBox === post.id && (
                                <div style={{ marginTop: 12, paddingTop: 12, borderTop: '1px solid var(--border)' }}>
                                    {post.comments?.map(c => (
                                        <div key={c.id} style={{ display: 'flex', gap: 8, marginBottom: 10 }}>
                                            <div className="post-avatar" style={{ width: 30, height: 30, fontSize: '0.8rem' }}>
                                                {c.user?.name?.[0]?.toUpperCase()}
                                            </div>
                                            <div style={{ background: 'var(--bg)', borderRadius: 8, padding: '8px 12px', flex: 1, fontSize: '0.875rem' }}>
                                                <strong>{c.user?.name}</strong><br />
                                                <span style={{ color: 'var(--text-mid)' }}>{c.body}</span>
                                                {c.replies?.map(r => (
                                                    <div key={r.id} style={{ marginTop: 6, paddingLeft: 12, borderLeft: '2px solid var(--border)', fontSize: '0.82rem' }}>
                                                        <strong>{r.user?.name}</strong>: {r.body}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                    <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
                                        <input className="form-control" style={{ flex: 1 }} value={commentText}
                                            onChange={e => setCommentText(e.target.value)} placeholder="Write a comment..." />
                                        <button className="btn btn-sm btn-primary" onClick={() => addComment(post.id)}>Send</button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))
                )
            )}
        </div>
    );
}
