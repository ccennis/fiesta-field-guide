import { useState } from 'react';
import { useApi } from '../hooks/useApi';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-3 text-base focus:border-glaze-lagoon focus:outline-none';

/**
 * Asks for a reset link. The answer is the same whether or not the address has
 * an account.
 */
export default function ForgotPassword({ onBack }) {
    const { post, loading, error } = useApi();
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        if ((await post('/api/forgot-password', { email })) !== null) setSent(true);
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

                <h2 className="text-lg font-black">Reset your password</h2>

                {sent ? (
                    <p className="rounded-xl bg-glaze-sun/20 px-3 py-2 text-sm font-bold">
                        If {email} has an account, a link to reset the password is on its way. It works for an hour.
                    </p>
                ) : (
                    <>
                        <p className="text-sm text-glaze-slate">Enter your email and we will send you a link to choose a new password.</p>
                        <label className="block space-y-1">
                            <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Email</span>
                            <input type="email" autoComplete="username" value={email} onChange={(e) => setEmail(e.target.value)} required className={INPUT} />
                        </label>
                        {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-full bg-glaze-ink px-5 py-3 text-base font-bold text-glaze-cream disabled:opacity-60"
                        >
                            Send the link
                        </button>
                    </>
                )}

                <button type="button" onClick={onBack} className="w-full text-sm font-bold text-glaze-slate underline-offset-2 hover:underline">
                    Back to sign in
                </button>
            </form>
        </div>
    );
}
