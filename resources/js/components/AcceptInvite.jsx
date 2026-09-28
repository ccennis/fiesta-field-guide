import { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-3 text-base focus:border-glaze-lagoon focus:outline-none';

function Field({ label, children }) {
    return (
        <label className="block space-y-1">
            <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">{label}</span>
            {children}
        </label>
    );
}

/**
 * Where an invite link lands. The link is checked first, so an expired or used
 * one says so instead of showing a form that cannot work.
 */
export default function AcceptInvite({ token, onJoined }) {
    const { get, error: checkError } = useApi();
    const { post, loading, error } = useApi();
    const [valid, setValid] = useState(null);
    const [adminName, setAdminName] = useState(null);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    useEffect(() => {
        get(`/api/invites/${token}`).then((result) => {
            setValid(result !== null);
            setAdminName(result?.admin_name ?? null);
        });
    }, [get, token]);

    const submit = async (e) => {
        e.preventDefault();
        const user = await post(`/api/invites/${token}/accept`, { name, email, password });
        if (user) onJoined(user);
    };

    return (
        <div className="flex min-h-screen items-center justify-center px-4 pt-[env(safe-area-inset-top)]">
            <div className="w-full max-w-sm space-y-4">
                <div className="flex items-center gap-3">
                    <img src="/icons/logo.png" alt="" className="h-14 w-14 shrink-0" />
                    <h1 className="text-2xl font-black tracking-tight">
                        Fiesta<span className="text-glaze-flame"> Field Guide</span>
                    </h1>
                </div>

                {valid === null && <p className="text-sm text-glaze-slate">Checking your invite...</p>}

                {valid === false && (
                    <p className="rounded-xl border-2 border-glaze-flame/30 bg-glaze-flame/10 px-3 py-2 text-sm">
                        {checkError ?? 'This invite link has expired or has already been used.'} Ask for a new one.
                    </p>
                )}

                {valid && (
                    <form onSubmit={submit} className="space-y-4">
                        <p className="text-sm text-glaze-slate">
                            You've been invited to test the field guide
                            {adminName ? ` by ${adminName}` : ''}. Set up your login to start your own collection.
                        </p>
                        <Field label="Your name">
                            <input value={name} onChange={(e) => setName(e.target.value)} required autoComplete="name" className={INPUT} />
                        </Field>
                        <Field label="Email">
                            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="username" className={INPUT} />
                        </Field>
                        <Field label="Password (8 or more characters)">
                            <input
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                minLength={8}
                                autoComplete="new-password"
                                className={INPUT}
                            />
                        </Field>
                        {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-full bg-glaze-ink px-5 py-3 text-base font-bold text-glaze-cream disabled:opacity-60"
                        >
                            {loading ? 'Joining...' : 'Join'}
                        </button>
                    </form>
                )}
            </div>
        </div>
    );
}
