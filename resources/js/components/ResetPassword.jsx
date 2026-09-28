import { useState } from 'react';
import { useApi } from '../hooks/useApi';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-3 text-base focus:border-glaze-lagoon focus:outline-none';

/**
 * Where the reset email lands. The link carries only the token, so the email
 * address is typed again here.
 */
export default function ResetPassword({ token, onDone }) {
    const { post, loading, error, fieldErrors } = useApi();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const submit = async (e) => {
        e.preventDefault();
        if ((await post('/api/reset-password', { token, email, password })) !== null) onDone();
    };

    const problems = fieldErrors ? Object.values(fieldErrors).flat() : error ? [error] : [];

    return (
        <div className="flex min-h-screen items-center justify-center px-4 pt-[env(safe-area-inset-top)]">
            <form onSubmit={submit} className="w-full max-w-sm space-y-4">
                <div className="flex items-center gap-3">
                    <img src="/icons/logo.png" alt="" className="h-14 w-14 shrink-0" />
                    <h1 className="text-2xl font-black tracking-tight">
                        Fiesta<span className="text-glaze-flame"> Field Guide</span>
                    </h1>
                </div>

                <h2 className="text-lg font-black">Choose a new password</h2>

                <label className="block space-y-1">
                    <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Email</span>
                    <input type="email" autoComplete="username" value={email} onChange={(e) => setEmail(e.target.value)} required className={INPUT} />
                </label>
                <label className="block space-y-1">
                    <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">New password (8 or more characters)</span>
                    <input
                        type="password"
                        autoComplete="new-password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        required
                        minLength={8}
                        className={INPUT}
                    />
                </label>

                {problems.map((p) => (
                    <p key={p} className="text-sm font-bold text-glaze-flame">
                        {p}
                    </p>
                ))}

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full rounded-full bg-glaze-ink px-5 py-3 text-base font-bold text-glaze-cream disabled:opacity-60"
                >
                    Save the new password
                </button>
            </form>
        </div>
    );
}
