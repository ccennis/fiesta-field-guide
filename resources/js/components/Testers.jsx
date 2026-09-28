import { useCallback, useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-2 text-sm focus:border-glaze-lagoon focus:outline-none';
const BUTTON = 'rounded-full px-4 py-2 text-sm font-bold transition';

function Section({ title, children }) {
    return (
        <section className="space-y-3 rounded-2xl border-2 border-glaze-shell bg-white p-4 shadow-sm">
            <h2 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">{title}</h2>
            {children}
        </section>
    );
}

/**
 * The owner's screen for beta testers: make an invite link to send, withdraw
 * links not yet used, and remove or restore a tester's access. Removing access
 * keeps their pieces.
 */
export default function Testers() {
    const { get: getTesters } = useApi();
    const { get: getInvites } = useApi();
    const { post, destroy, error } = useApi();

    const [testers, setTesters] = useState([]);
    const [invites, setInvites] = useState([]);
    const [note, setNote] = useState('');
    const [link, setLink] = useState(null);
    const [copied, setCopied] = useState(false);

    const load = useCallback(async () => {
        const [t, i] = await Promise.all([getTesters('/api/testers'), getInvites('/api/invitations')]);
        setTesters(t ?? []);
        setInvites(i ?? []);
    }, [getTesters, getInvites]);

    useEffect(() => {
        load();
    }, [load]);

    const createInvite = async (e) => {
        e.preventDefault();
        const created = await post('/api/invitations', { note: note || null });
        if (created) {
            setLink(created.link);
            setCopied(false);
            setNote('');
            load();
        }
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(link);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    };

    const revoke = async (invite) => {
        if (await destroy(`/api/invitations/${invite.id}`)) load();
    };

    const toggleAccess = async (tester) => {
        if (await post(`/api/testers/${tester.id}/${tester.disabled ? 'enable' : 'disable'}`)) load();
    };

    return (
        <div className="mx-auto max-w-2xl space-y-4">
            <Section title="Invite someone">
                <form onSubmit={createInvite} className="flex gap-2">
                    <input
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                        placeholder="Who it's for (just for you)"
                        maxLength={100}
                        className={INPUT}
                    />
                    <button type="submit" className={`${BUTTON} shrink-0 bg-glaze-ink text-glaze-cream`}>
                        Create link
                    </button>
                </form>

                {link && (
                    <div className="space-y-2 rounded-xl bg-glaze-sun/20 p-3">
                        <p className="text-sm font-bold">Send this link. It works once and expires in 7 days.</p>
                        <div className="flex gap-2">
                            <input readOnly value={link} onFocus={(e) => e.target.select()} className={`${INPUT} font-mono text-xs`} />
                            <button onClick={copy} className={`${BUTTON} shrink-0 bg-glaze-lagoon text-white`}>
                                {copied ? 'Copied' : 'Copy'}
                            </button>
                        </div>
                        <p className="text-xs text-glaze-slate">
                            This is the only time the link is shown. If it gets lost, withdraw it and make another.
                        </p>
                    </div>
                )}

                {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
            </Section>

            <Section title="Links not used yet">
                {invites.length === 0 ? (
                    <p className="text-sm text-glaze-slate">None waiting.</p>
                ) : (
                    <ul className="divide-y divide-glaze-shell">
                        {invites.map((invite) => (
                            <li key={invite.id} className="flex items-center gap-3 py-2 text-sm">
                                <span className="font-bold">{invite.note || 'Unnamed invite'}</span>
                                <span className="text-xs text-glaze-slate">expires {invite.expires_at}</span>
                                <button onClick={() => revoke(invite)} className="ml-auto text-xs font-bold text-glaze-slate hover:underline">
                                    Withdraw
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title="Testers">
                {testers.length === 0 ? (
                    <p className="text-sm text-glaze-slate">Nobody has joined yet.</p>
                ) : (
                    <ul className="divide-y divide-glaze-shell">
                        {testers.map((tester) => (
                            <li key={tester.id} className="flex items-center gap-3 py-2 text-sm">
                                <div className="min-w-0 flex-1">
                                    <p className={`font-bold ${tester.disabled ? 'text-glaze-slate line-through' : ''}`}>{tester.name}</p>
                                    <p className="truncate text-xs text-glaze-slate">
                                        {tester.email} · {tester.pieces} {tester.pieces === 1 ? 'piece' : 'pieces'} · joined {tester.joined}
                                    </p>
                                </div>
                                <button
                                    onClick={() => toggleAccess(tester)}
                                    className={`${BUTTON} shrink-0 ${tester.disabled ? 'bg-glaze-shell text-glaze-ink' : 'text-glaze-flame'}`}
                                >
                                    {tester.disabled ? 'Restore access' : 'Remove access'}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>
        </div>
    );
}
