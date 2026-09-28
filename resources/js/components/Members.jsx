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
 * The admin's screen for everyone else: make an invite link for a friend,
 * withdraw links not yet used, and remove or restore anyone's access.
 * Removing access keeps their pieces.
 */
export default function Members() {
    const { get: getMembers } = useApi();
    const { get: getInvites } = useApi();
    const { post, destroy, error } = useApi();

    const [members, setMembers] = useState([]);
    const [invites, setInvites] = useState([]);
    const [note, setNote] = useState('');
    const [link, setLink] = useState(null);
    const [copied, setCopied] = useState(false);

    const load = useCallback(async () => {
        const [m, i] = await Promise.all([getMembers('/api/members'), getInvites('/api/invitations')]);
        setMembers(m ?? []);
        setInvites(i ?? []);
    }, [getMembers, getInvites]);

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

    const toggleAccess = async (member) => {
        if (await post(`/api/members/${member.id}/${member.disabled ? 'enable' : 'disable'}`)) load();
    };

    return (
        <div className="mx-auto max-w-2xl space-y-4">
            <Section title="Invite a friend">
                <p className="text-xs text-glaze-slate">
                    Anyone can sign up on their own. A friend who joins with a link can also look at your collection.
                </p>
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

            <Section title={`Members · ${members.length}`}>
                {members.length === 0 ? (
                    <p className="text-sm text-glaze-slate">Nobody has joined yet.</p>
                ) : (
                    <ul className="divide-y divide-glaze-shell">
                        {members.map((member) => (
                            <li key={member.id} className="flex items-center gap-3 py-2 text-sm">
                                <div className="min-w-0 flex-1">
                                    <p className={`font-bold ${member.disabled ? 'text-glaze-slate line-through' : ''}`}>
                                        {member.name}
                                        {member.invited && (
                                            <span className="ml-2 rounded-full bg-glaze-sun/40 px-2 py-0.5 text-xs font-bold text-glaze-ink">friend</span>
                                        )}
                                        {!member.email_verified && (
                                            <span className="ml-2 rounded-full bg-glaze-shell px-2 py-0.5 text-xs font-bold text-glaze-slate">email not confirmed</span>
                                        )}
                                    </p>
                                    <p className="truncate text-xs text-glaze-slate">
                                        {member.email} · {member.pieces} {member.pieces === 1 ? 'piece' : 'pieces'} · joined {member.joined}
                                    </p>
                                </div>
                                <button
                                    onClick={() => toggleAccess(member)}
                                    className={`${BUTTON} shrink-0 ${member.disabled ? 'bg-glaze-shell text-glaze-ink' : 'text-glaze-flame'}`}
                                >
                                    {member.disabled ? 'Restore access' : 'Remove access'}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>
        </div>
    );
}
