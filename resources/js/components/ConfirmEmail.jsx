import { useState } from 'react';
import { useApi } from '../hooks/useApi';

const BUTTON = 'w-full rounded-full px-5 py-3 text-base font-bold disabled:opacity-60';

/**
 * Shown to someone who signed up but has not clicked the link in their email
 * yet. The link can be opened on any device; "I've confirmed it" checks again.
 */
export default function ConfirmEmail({ user, onChecked, onSignOut }) {
    const { post, loading, error } = useApi();
    const { get, loading: checking } = useApi();
    const [notice, setNotice] = useState(null);

    const resend = async () => {
        if (await post('/api/email/resend')) setNotice('Sent another. It can take a minute to arrive.');
    };

    const check = async () => {
        const me = await get('/api/me');
        if (me?.email_verified) {
            onChecked(me);
            return;
        }
        setNotice('Not confirmed yet. Open the link in the email, then try again.');
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

                <h2 className="text-lg font-black">Check your email</h2>
                <p className="text-sm text-glaze-slate">
                    We sent a link to <strong className="text-glaze-ink">{user.email}</strong>. Open it to confirm your address,
                    then come back here.
                </p>

                {notice && <p className="rounded-xl bg-glaze-sun/20 px-3 py-2 text-sm font-bold">{notice}</p>}
                {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}

                <button onClick={check} disabled={checking} className={`${BUTTON} bg-glaze-ink text-glaze-cream`}>
                    I've confirmed it
                </button>
                <button onClick={resend} disabled={loading} className={`${BUTTON} bg-glaze-shell text-glaze-ink`}>
                    Send the email again
                </button>
                <button onClick={onSignOut} className="w-full text-sm font-bold text-glaze-slate underline-offset-2 hover:underline">
                    Sign out
                </button>
            </div>
        </div>
    );
}
