import { useState } from 'react';
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
 * Anyone may make an account. They are signed in straight away and asked to
 * confirm their email before they can use the app.
 */
export default function SignUp({ onSignedUp, onSignIn }) {
    const { post, loading, error, fieldErrors } = useApi();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const submit = async (e) => {
        e.preventDefault();
        const user = await post('/api/register', { name, email, password });
        if (user) onSignedUp(user);
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

                <p className="text-sm text-glaze-slate">
                    Make an account to keep track of your own Fiesta collection and wishlist.
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
                    Create account
                </button>

                <p className="text-center text-sm text-glaze-slate">
                    Already have one?{' '}
                    <button type="button" onClick={onSignIn} className="font-bold text-glaze-ink underline underline-offset-2">
                        Sign in
                    </button>
                </p>
            </form>
        </div>
    );
}
