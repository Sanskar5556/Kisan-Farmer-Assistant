import { createContext, useContext, useState, useEffect } from 'react';
import api from '../api/axios';

/*
 * AuthContext — manages login state across the entire app
 *
 * On startup: validates stored token with /auth/me.
 * If token is stale/expired → clears localStorage and shows login.
 * This prevents the blank page caused by an old invalid token.
 */
const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [token, setToken] = useState(null);
    const [loading, setLoading] = useState(true);

    // On app start, validate any existing stored token
    useEffect(() => {
        const storedToken = localStorage.getItem('kisan_token');
        const storedUser = localStorage.getItem('kisan_user');

        if (storedToken && storedUser) {
            // Optimistically set state so UI doesn't flash 
            setToken(storedToken);
            setUser(JSON.parse(storedUser));

            // Verify the token is still valid with the backend
            api.get('/auth/me')
                .then(res => {
                    // Token is valid — update user data with latest from server
                    setUser(res.data.user || JSON.parse(storedUser));
                })
                .catch(() => {
                    // Token is invalid/expired — clear everything and go to login
                    localStorage.removeItem('kisan_token');
                    localStorage.removeItem('kisan_user');
                    setToken(null);
                    setUser(null);
                })
                .finally(() => {
                    setLoading(false);
                });
        } else {
            // No stored token — not logged in
            setLoading(false);
        }
    }, []);

    const login = async (email, password) => {
        const response = await api.post('/auth/login', { email, password });
        const { token, user } = response.data;
        localStorage.setItem('kisan_token', token);
        localStorage.setItem('kisan_user', JSON.stringify(user));
        setToken(token);
        setUser(user);
        return user;
    };

    const logout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (_) { /* ignore if token already expired */ }
        localStorage.removeItem('kisan_token');
        localStorage.removeItem('kisan_user');
        setToken(null);
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, token, login, logout, isAuthenticated: !!token, loading }}>
            {children}
        </AuthContext.Provider>
    );
}

export const useAuth = () => useContext(AuthContext);
