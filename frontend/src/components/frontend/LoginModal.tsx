// src/components/frontend/LoginModal.tsx
import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';

interface LoginModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export const LoginModal: React.FC<LoginModalProps> = ({ isOpen, onClose }) => {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [twoFactorCode, setTwoFactorCode] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [showTwoFactor, setShowTwoFactor] = useState(false);

    const { login, verifyLoginTwoFactor } = useAuth();

    if (!isOpen) return null;

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            await login(email, password);
            // Ak je potrebná 2FA, zobrazí sa formulár
            // (2FA sa rieši v AuthContext)
            onClose();
        } catch (err: any) {
            setError(err.message || 'Prihlásenie zlyhalo');
        } finally {
            setLoading(false);
        }
    };

    const handleTwoFactorSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            await verifyLoginTwoFactor(twoFactorCode);
            onClose();
        } catch (err: any) {
            setError(err.message || 'Overenie TOTP zlyhalo');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div className="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md w-full">
        <h2 className="text-2xl font-bold mb-4 dark:text-white">
        {showTwoFactor ? 'Overenie TOTP' : 'Prihlásenie'}
        </h2>

        {error && (
            <div className="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 p-3 rounded mb-4">
            {error}
            </div>
        )}

        {!showTwoFactor ? (
            <form onSubmit={handleLogin}>
            <div className="mb-4">
            <label className="block text-sm font-medium mb-1 dark:text-gray-300">Email</label>
            <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            required
            disabled={loading}
            />
            </div>

            <div className="mb-4">
            <label className="block text-sm font-medium mb-1 dark:text-gray-300">Heslo</label>
            <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            required
            disabled={loading}
            />
            </div>

            <button
            type="submit"
            disabled={loading}
            className="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
            >
            {loading ? 'Prihlasovanie...' : 'Prihlásiť'}
            </button>
            </form>
        ) : (
            <form onSubmit={handleTwoFactorSubmit}>
            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Zadajte kód z Google Authenticator:
            </p>

            <div className="mb-4">
            <label className="block text-sm font-medium mb-1 dark:text-gray-300">TOTP kód</label>
            <input
            type="text"
            value={twoFactorCode}
            onChange={(e) => setTwoFactorCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
            className="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white text-center text-2xl tracking-widest"
            placeholder="000000"
            maxLength={6}
            required
            disabled={loading}
            />
            </div>

            <button
            type="submit"
            disabled={loading}
            className="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
            >
            {loading ? 'Overovanie...' : 'Overiť'}
            </button>

            <button
            type="button"
            onClick={() => {
                setShowTwoFactor(false);
                setError('');
            }}
            className="w-full mt-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
            Späť na prihlásenie
            </button>
            </form>
        )}

        <button
        onClick={onClose}
        className="absolute top-2 right-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
        ✕
        </button>
        </div>
        </div>
    );
};
