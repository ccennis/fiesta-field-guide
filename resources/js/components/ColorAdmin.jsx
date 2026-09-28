import { useCallback, useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-2 text-sm focus:border-glaze-lagoon focus:outline-none';
const BUTTON = 'rounded-full px-4 py-2 text-sm font-bold transition';

function Years({ color, onSaved }) {
    const { patch, loading, error } = useApi();
    const [from, setFrom] = useState(color.produced_from ?? '');
    const [to, setTo] = useState(color.produced_to ?? '');
    const [saved, setSaved] = useState(false);

    useEffect(() => {
        setFrom(color.produced_from ?? '');
        setTo(color.produced_to ?? '');
        setSaved(false);
    }, [color]);

    const save = async (e) => {
        e.preventDefault();
        const updated = await patch(`/api/colors/${color.id}`, {
            produced_from: from === '' ? null : Number(from),
            produced_to: to === '' ? null : Number(to),
        });
        if (updated) {
            setSaved(true);
            onSaved(updated);
        }
    };

    return (
        <form onSubmit={save} className="space-y-2">
            <h3 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Years made</h3>
            <div className="flex gap-2">
                <input value={from} onChange={(e) => setFrom(e.target.value)} inputMode="numeric" placeholder="First year" className={INPUT} />
                <input value={to} onChange={(e) => setTo(e.target.value)} inputMode="numeric" placeholder="Last year, blank if still made" className={INPUT} />
                <button type="submit" disabled={loading} className={`${BUTTON} shrink-0 bg-glaze-ink text-glaze-cream`}>
                    Save
                </button>
            </div>
            {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
            {saved && !error && <p className="text-sm font-bold text-glaze-fern">Saved. The era follows the first year.</p>}
            <p className="text-xs text-glaze-slate">
                Swatches and years are saved to the database only. Put them in the seed data as well to survive a catalog re-import.
            </p>
        </form>
    );
}

/**
 * The swatch is a literal #rrggbb. It applies to this color everywhere.
 */
function SwatchField({ color, onSaved }) {
    const { patch, loading, error } = useApi();
    const [hex, setHex] = useState(color.hex ?? '');
    const [saved, setSaved] = useState(null);

    useEffect(() => {
        setHex(color.hex ?? '');
        setSaved(null);
    }, [color]);

    const save = async (value) => {
        const updated = await patch(`/api/colors/${color.id}`, { hex: value });
        if (updated) {
            setSaved(value ? 'Swatch saved.' : 'Swatch cleared.');
            onSaved(updated);
        }
    };

    const valid = /^#[0-9a-fA-F]{6}$/.test(hex);

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                save(hex === '' ? null : hex);
            }}
            className="space-y-2"
        >
            <h3 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Swatch</h3>
            <div className="flex gap-2">
                <input
                    type="color"
                    value={valid ? hex : '#cccccc'}
                    onChange={(e) => setHex(e.target.value)}
                    aria-label="Pick a swatch"
                    className="h-10 w-14 shrink-0 cursor-pointer rounded-lg border-2 border-glaze-shell bg-white"
                />
                <input value={hex} onChange={(e) => setHex(e.target.value)} placeholder="#rrggbb" className={`${INPUT} font-mono`} />
                <button type="submit" disabled={loading} className={`${BUTTON} shrink-0 bg-glaze-ink text-glaze-cream`}>
                    Save
                </button>
            </div>
            {color.hex && (
                <button type="button" onClick={() => save(null)} className="text-xs font-bold text-glaze-slate hover:underline">
                    Clear the swatch
                </button>
            )}
            {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
            {saved && !error && <p className="text-sm font-bold text-glaze-fern">{saved}</p>}
        </form>
    );
}

function Checklist({ color }) {
    const { get, loading } = useApi();
    const { post, error } = useApi();
    const [entries, setEntries] = useState([]);

    useEffect(() => {
        setEntries([]);
        get(`/api/colors/${color.id}/checklist`).then((result) => setEntries(result ?? []));
    }, [get, color.id]);

    const toggle = async (entry) => {
        const updated = await post(`/api/colors/${color.id}/made`, { product_id: entry.product.id, made: !entry.made });
        if (updated) setEntries((current) => current.map((e) => (e.variant_id === updated.variant_id ? updated : e)));
    };

    const made = entries.filter((e) => e.made).length;

    return (
        <div className="space-y-2">
            <h3 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">
                Made in {color.name} · {made} of {entries.length}
            </h3>
            {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
            {loading && entries.length === 0 && <p className="text-sm text-glaze-slate">Loading...</p>}
            <ul className="divide-y divide-glaze-shell rounded-xl border-2 border-glaze-shell bg-white">
                {entries.map((entry) => (
                    <li key={entry.variant_id}>
                        <label className={`flex items-center gap-3 px-3 py-2.5 text-sm ${entry.locked && entry.made ? '' : 'cursor-pointer hover:bg-glaze-sun/10'}`}>
                            <input
                                type="checkbox"
                                checked={entry.made}
                                disabled={Boolean(entry.locked) && entry.made}
                                onChange={() => toggle(entry)}
                                className="h-5 w-5 accent-glaze-lagoon"
                            />
                            <span className={entry.made ? 'font-bold' : 'text-glaze-slate'}>{entry.product.name}</span>
                            <span className="ml-auto text-xs text-glaze-slate">
                                {entry.locked ?? (entry.by_owner ? 'You said so' : '')}
                            </span>
                        </label>
                    </li>
                ))}
            </ul>
            <p className="text-xs text-glaze-slate">
                Checking a product marks that piece verified everywhere. A piece you own, or one a store listing shows, stays checked.
            </p>
        </div>
    );
}

/**
 * The admin's color editor: set the swatch, correct the years a color was
 * made, and check off the products it came in.
 */
export default function ColorAdmin() {
    const { get } = useApi();
    const [colors, setColors] = useState([]);
    const [filter, setFilter] = useState('');
    const [selected, setSelected] = useState(null);

    const load = useCallback(() => get('/api/colors').then((result) => setColors(result ?? [])), [get]);

    useEffect(() => {
        load();
    }, [load]);

    const onSaved = (updated) => {
        setSelected((current) => ({ ...current, ...updated }));
        load();
    };

    const visible = colors.filter((c) => `${c.name} ${c.line.name}`.toLowerCase().includes(filter.toLowerCase()));

    return (
        <div className="grid gap-6 lg:grid-cols-[18rem_1fr]">
            <section className={`space-y-2 ${selected ? 'hidden lg:block' : ''}`}>
                <input value={filter} onChange={(e) => setFilter(e.target.value)} placeholder="Find a color" className={INPUT} />
                <div className="overflow-y-auto rounded-2xl border-2 border-glaze-shell bg-white lg:max-h-[36rem]">
                    {visible.map((c) => (
                        <button
                            key={c.id}
                            onClick={() => setSelected(c)}
                            className={`flex w-full items-center gap-3 border-b border-glaze-shell/70 px-3 py-2.5 text-left text-sm last:border-0 ${
                                selected?.id === c.id ? 'bg-glaze-ink text-glaze-cream' : 'hover:bg-glaze-sun/15'
                            }`}
                        >
                            <Swatch hex={c.hex} size="sm" />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate font-bold">{c.name}</span>
                                <span className={`block text-xs ${selected?.id === c.id ? 'text-glaze-cream/60' : 'text-glaze-slate'}`}>
                                    {c.line.name} · {c.produced_label ?? 'years unknown'}
                                </span>
                            </span>
                        </button>
                    ))}
                </div>
            </section>

            {selected ? (
                <section className="space-y-5 rounded-2xl border-2 border-glaze-shell bg-white/70 p-4">
                    <button
                        onClick={() => setSelected(null)}
                        className="flex items-center gap-2 rounded-full bg-glaze-shell px-4 py-2 text-sm font-bold text-glaze-slate lg:hidden"
                    >
                        <span aria-hidden="true">&larr;</span>
                        All colors
                    </button>
                    <div className="flex items-center gap-3">
                        <Swatch hex={selected.hex} size="lg" />
                        <div>
                            <h2 className="text-xl font-black leading-tight">{selected.name}</h2>
                            <p className="text-sm text-glaze-slate">
                                {selected.line.name} · {selected.produced_label ?? 'years unknown'}
                                {selected.era && ` · ${selected.era.label}`}
                            </p>
                        </div>
                    </div>
                    <SwatchField color={selected} onSaved={onSaved} />
                    <Years color={selected} onSaved={onSaved} />
                    <Checklist color={selected} />
                </section>
            ) : (
                <p className="hidden rounded-2xl border-2 border-dashed border-glaze-shell px-4 py-8 text-center text-sm text-glaze-slate lg:block">
                    Pick a color to correct its years or check off what it was made in.
                </p>
            )}
        </div>
    );
}
