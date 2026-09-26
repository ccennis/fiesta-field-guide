import { useState } from 'react';
import { useApi } from '../hooks/useApi';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-3 text-base focus:border-glaze-lagoon focus:outline-none';

/**
 * The autocomplete hints let the phone's password manager fill both fields.
 */
export default function Login({ onSignedIn }) {
    const { post, loading, error } = useApi();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const submit = async (e) => {
        e.preventDefault();
        const user = await post('/api/login', { email, password });
        if (user) onSignedIn(user);
    };

    return (
        <div className="flex min-h-screen items-center justify-center px-4 pt-[env(safe-area-inset-top)]">
            <form onSubmit={submit} className="w-full max-w-sm space-y-4">
                <div className="flex items-center gap-3">
                    <img src="/icons/logo.png" alt="" className="h-14 w-14 shrink-0" />
                    <h1 className="text-2xl font-black tracking-tight">
                        Fiesta<span className="text-glaze-flame"> Field Guide</span>
                    </h1>
                </div>

                <label className="block space-y-1">
                    <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Email</span>
                    <input
                        type="email"
                        autoComplete="username"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                        className={INPUT}
                    />
                </label>

                <label className="block space-y-1">
                    <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Password</span>
                    <input
                        type="password"
                        autoComplete="current-password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        required
                        className={INPUT}
                    />
                </label>

                {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full rounded-full bg-glaze-ink px-5 py-3 text-base font-bold text-glaze-cream disabled:opacity-60"
                >
                    {loading ? 'Signing in...' : 'Sign in'}
                </button>
            </form>
        </div>
    );
}
