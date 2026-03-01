import { useNavigate } from 'react-router-dom';
import api from '../api/axios';

/*
 * DiscoveryPopup — shown on Dashboard when a new crop suggestion is available
 * Props:
 *   popup  = { title, message, crop }
 *   onClose = function to hide the popup
 */
export default function DiscoveryPopup({ popup, onClose }) {
    const navigate = useNavigate();

    if (!popup) return null;

    const handleExplore = async () => {
        // Track that user clicked this crop
        try { await api.post('/discovery/track', { crop_name: popup.crop }); } catch { }
        // Navigate to APMC page to see prices for this crop
        onClose();
        navigate(`/apmc`);
    };

    return (
        <div className="popup-overlay" onClick={onClose}>
            <div className="popup-box" onClick={e => e.stopPropagation()}>
                <span className="popup-emoji">🌟</span>
                <h3>{popup.title}</h3>
                <p>{popup.message}</p>
                <div style={{ display: 'flex', gap: 10, justifyContent: 'center' }}>
                    <button className="btn btn-primary" onClick={handleExplore}>
                        📊 See Market Prices
                    </button>
                    <button className="btn btn-outline" onClick={onClose}>
                        Not now
                    </button>
                </div>
            </div>
        </div>
    );
}
