import { useCallback, useEffect, useRef, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';

const BASE = '/api/sources/fiesta_factory_direct';

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-2 text-sm focus:border-glaze-lagoon focus:outline-none';
const BUTTON = 'rounded-full px-4 py-2 text-sm font-bold transition';

/**
 * Store photos are shown straight from the store's own links and never copied.
 */
function Thumbs({ examples }) {
    const withImages = examples.filter((e) => e.image_url);
    if (withImages.length === 0) return null;

    return (
        <div className="flex gap-2 overflow-x-auto">
            {withImages.map((e) => (
                <a key={e.id} href={e.url} target="_blank" rel="noopener noreferrer" className="shrink-0" title={e.title}>
                    <img
                        src={`${e.image_url}${e.image_url.includes('?') ? '&' : '?'}width=160`}
                        alt={e.title}
                        loading="lazy"
                        referrerPolicy="no-referrer"
                        className="h-20 w-20 rounded-xl border-2 border-glaze-shell bg-white object-contain"
                    />
                </a>
            ))}
        </div>
    );
}

/**
 * The colors a store product came in. A swatch shows once that store color is
 * tied to one of the catalog's; retired colors are muted.
 */
function ColorChips({ colors }) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {colors.map((c) => (
                <span
                    key={c.name}
                    title={c.retired ? `${c.name}, retired` : c.name}
                    className={`inline-flex items-center gap-1.5 rounded-full border-2 border-glaze-shell py-0.5 pl-0.5 pr-2.5 text-xs font-bold ${
                        c.retired ? 'text-glaze-slate/60' : 'text-glaze-ink'
                    }`}
                >
                    <span className={`flex ${c.retired ? 'opacity-50' : ''}`}>
                        <Swatch hex={c.hex} size="xs" />
                    </span>
                    {c.name}
                </span>
            ))}
        </div>
    );
}

function Picker({ kind, options, onPick }) {
    const [query, setQuery] = useState('');
    const matches = options.filter((o) => o.name.toLowerCase().includes(query.toLowerCase())).slice(0, 8);

    return (
        <div className="space-y-2">
            <input
                autoFocus
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder={kind === 'color' ? 'Search my colors' : 'Search my products'}
                className={INPUT}
            />
            <div className="max-h-64 overflow-y-auto rounded-xl border-2 border-glaze-shell bg-white">
                {matches.map((o) => (
                    <button
                        key={o.id}
                        onClick={() => onPick(o)}
                        className="flex w-full items-center gap-3 border-b border-glaze-shell/70 px-3 py-2.5 text-left text-sm last:border-0 hover:bg-glaze-sun/15"
                    >
                        {kind === 'color' && <Swatch hex={o.hex} size="sm" />}
                        <span className="font-bold">{o.name}</span>
                        {kind === 'color' && (
                            <span className="ml-auto text-xs tabular-nums text-glaze-slate">{o.produced_label ?? 'years unknown'}</span>
                        )}
                    </button>
                ))}
                {matches.length === 0 && <p className="px-3 py-3 text-sm text-glaze-slate">Nothing matches.</p>}
            </div>
        </div>
    );
}

function CreateForm({ kind, defaultName, onSubmit, onCancel }) {
    const [name, setName] = useState(defaultName);
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');

    const submit = (e) => {
        e.preventDefault();
        onSubmit({
            name,
            ...(kind === 'color' && {
                produced_from: from === '' ? null : Number(from),
                produced_to: to === '' ? null : Number(to),
            }),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-2">
            <input value={name} onChange={(e) => setName(e.target.value)} required className={INPUT} placeholder="Name" />
            {kind === 'color' && (
                <div className="grid grid-cols-2 gap-2">
                    <input value={from} onChange={(e) => setFrom(e.target.value)} inputMode="numeric" placeholder="First year" className={INPUT} />
                    <input value={to} onChange={(e) => setTo(e.target.value)} inputMode="numeric" placeholder="Last year, blank if current" className={INPUT} />
                </div>
            )}
            <div className="flex gap-2">
                <button type="submit" className={`${BUTTON} bg-glaze-ink text-glaze-cream`}>
                    Add as a new {kind}
                </button>
                <button type="button" onClick={onCancel} className={`${BUTTON} text-glaze-slate`}>
                    Cancel
                </button>
            </div>
        </form>
    );
}

function NameCard({ item, kind, options, onChanged, selected, onSelect }) {
    const { post, error, loading } = useApi();
    const [mode, setMode] = useState(null);
    const ruled = item.ruling !== null;

    const send = async (path, body) => {
        const updated = await post(`${BASE}/${path}`, { kind, key: item.key, ...body });
        if (updated) onChanged(item, updated);
    };

    return (
        <div className={`space-y-3 rounded-2xl border-2 bg-white p-4 ${selected ? 'border-glaze-lagoon' : 'border-glaze-shell'}`}>
            <div className="flex items-start gap-3">
                {onSelect && !ruled && (
                    <input
                        type="checkbox"
                        checked={selected}
                        onChange={() => onSelect(item.key)}
                        className="mt-1 h-5 w-5 accent-glaze-lagoon"
                        aria-label={`Select ${item.name}`}
                    />
                )}
                <div className="min-w-0 flex-1">
                    <p className="font-black leading-tight">&ldquo;{item.name}&rdquo;</p>
                    <p className="text-xs text-glaze-slate">
                        {item.listings} {item.listings === 1 ? 'listing' : 'listings'}
                        {kind === 'product'
                            ? ` · ${item.colors.length} ${item.colors.length === 1 ? 'color' : 'colors'}`
                            : ` · on ${item.products} ${item.products === 1 ? 'product' : 'products'}`}
                        {ruled && ` · ${item.resolved} confirming a piece`}
                    </p>
                </div>
            </div>

            {ruled ? (
                <div className="flex items-center gap-3 rounded-xl bg-glaze-cream px-3 py-2 text-sm">
                    {item.ruling.target ? (
                        <>
                            {kind === 'color' && <Swatch hex={item.ruling.target.hex} size="sm" />}
                            <span>
                                &rarr; <strong>{item.ruling.target.name}</strong>
                                {item.ruling.target.label && ` (${item.ruling.target.label})`}
                            </span>
                        </>
                    ) : (
                        <span className="font-bold text-glaze-slate">Ignored</span>
                    )}
                    <button onClick={() => send('rulings/undo', {})} disabled={loading} className="ml-auto text-xs font-bold text-glaze-slate underline-offset-2 hover:underline">
                        Undo
                    </button>
                </div>
            ) : (
                <>
                    <Thumbs examples={item.examples} />
                    {kind === 'product' && <ColorChips colors={item.colors} />}

                    {mode === 'map' && (
                        <Picker kind={kind} options={options} onPick={(o) => send('rulings', { decision: 'mapped', target_id: o.id })} />
                    )}
                    {mode === 'create' && (
                        <CreateForm kind={kind} defaultName={item.name} onSubmit={(data) => send('rulings/create', data)} onCancel={() => setMode(null)} />
                    )}
                    {mode === null && (
                        <div className="flex flex-wrap gap-2">
                            <button onClick={() => setMode('map')} className={`${BUTTON} bg-glaze-ink text-glaze-cream`}>
                                It's one of mine
                            </button>
                            <button onClick={() => setMode('create')} className={`${BUTTON} bg-glaze-shell text-glaze-ink`}>
                                Add as new
                            </button>
                            <button onClick={() => send('rulings', { decision: 'ignored' })} disabled={loading} className={`${BUTTON} text-glaze-slate`}>
                                Ignore
                            </button>
                        </div>
                    )}
                </>
            )}

            {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
        </div>
    );
}

/**
 * Fiesta Factory Direct names that did not match one of the catalog's exactly.
 * Exact matches are tied on import without asking; these are what is left.
 * Once a name is tied, every store listing using it confirms its piece.
 * `onChanged` lets the tab refresh its own list after a new color or product
 * is added.
 */
export default function StoreNames({ kind, onChanged }) {
    const { get, loading } = useApi();
    const { get: getOptions } = useApi();
    const { post: postBulk, error: bulkError } = useApi();

    const [open, setOpen] = useState(false);
    const [ruled, setRuled] = useState(false);
    const [items, setItems] = useState([]);
    const [waiting, setWaiting] = useState(null);
    const [options, setOptions] = useState([]);
    const [selected, setSelected] = useState([]);
    const [notice, setNotice] = useState(null);
    const latestRequest = useRef(0);

    const plural = kind === 'color' ? 'colors' : 'products';

    const load = useCallback(async () => {
        const request = ++latestRequest.current;
        const [pending, shown, choices] = await Promise.all([
            get(`${BASE}/names?kind=${kind}&ruled=0`),
            ruled ? get(`${BASE}/names?kind=${kind}&ruled=1`) : null,
            getOptions(`/api/${plural}`),
        ]);

        if (request !== latestRequest.current) return;

        setWaiting(pending?.length ?? 0);
        setItems((ruled ? shown : pending) ?? []);
        setOptions((choices ?? []).filter((o) => o.line?.name === 'Fiesta'));
        setSelected([]);
    }, [get, getOptions, kind, plural, ruled]);

    useEffect(() => {
        load();
    }, [load]);

    const changed = (before, after) => {
        const target = after.ruling?.target;
        const renamed = after.rename?.to ? ` Renamed "${after.rename.from}" to "${after.rename.to}".` : '';
        setNotice(
            after.ruling === null
                ? `"${after.name}" is back on the list.`
                : target
                  ? `"${after.name}" → ${target.name}. ${after.confirmed_now} ${after.confirmed_now === 1 ? 'piece' : 'pieces'} newly verified.${renamed}`
                  : `"${after.name}" ignored.`
        );
        load();
        onChanged?.();
    };

    const createSelected = async () => {
        const result = await postBulk(`${BASE}/rulings/create-products`, { keys: selected });
        if (result) {
            setNotice(`Added ${result.created} new ${result.created === 1 ? 'product' : 'products'}.`);
            load();
            onChanged?.();
        }
    };

    const toggle = (key) => setSelected((current) => (current.includes(key) ? current.filter((k) => k !== key) : [...current, key]));

    return (
        <section className="space-y-3 rounded-2xl border-2 border-glaze-sun/60 bg-glaze-sun/10 p-4">
            <button onClick={() => setOpen((o) => !o)} className="flex w-full items-center gap-3 text-left">
                <span className="flex-1">
                    <span className="block font-black">
                        {waiting === null ? 'Store names' : waiting === 0 ? `Every store ${kind} is matched` : `${waiting} store ${waiting === 1 ? kind : plural} to match`}
                    </span>
                    <span className="block text-xs text-glaze-slate">
                        Fiesta Factory Direct names that are not exactly one of yours. Matching one lets its listings verify which{' '}
                        {kind === 'color' ? 'products came in that color' : 'colors that product came in'}.
                    </span>
                </span>
                <span className="text-sm font-bold text-glaze-slate">{open ? 'Hide' : 'Show'}</span>
            </button>

            {open && (
                <>
                    <div className="flex items-center gap-4 text-sm font-bold">
                        <button onClick={() => setRuled(false)} className={ruled ? 'text-glaze-slate' : 'text-glaze-ink underline underline-offset-4'}>
                            To match
                        </button>
                        <button onClick={() => setRuled(true)} className={ruled ? 'text-glaze-ink underline underline-offset-4' : 'text-glaze-slate'}>
                            Already matched
                        </button>
                    </div>

                    {notice && <p className="rounded-xl border-2 border-glaze-fern/30 bg-glaze-fern/10 px-3 py-2 text-sm font-bold">{notice}</p>}

                    {kind === 'product' && !ruled && selected.length > 0 && (
                        <div className="sticky top-2 z-10 flex items-center gap-3 rounded-2xl bg-glaze-ink px-4 py-3 text-glaze-cream shadow-lg">
                            <span className="text-sm font-bold">{selected.length} selected</span>
                            <button onClick={createSelected} className={`${BUTTON} ml-auto bg-glaze-sun text-glaze-ink`}>
                                Add as new products
                            </button>
                            {bulkError && <span className="text-sm text-glaze-flame">{bulkError}</span>}
                        </div>
                    )}

                    {kind === 'product' && !ruled && items.length > 1 && (
                        <button
                            onClick={() => setSelected(selected.length === items.length ? [] : items.map((i) => i.key))}
                            className="text-xs font-bold text-glaze-slate underline-offset-2 hover:underline"
                        >
                            {selected.length === items.length ? 'Select none' : `Select all ${items.length}`}
                        </button>
                    )}

                    {loading && items.length === 0 && <p className="text-sm text-glaze-slate">Loading...</p>}

                    <div className="space-y-3">
                        {items.map((item) => (
                            <NameCard
                                key={item.key}
                                item={item}
                                kind={kind}
                                options={options}
                                onChanged={changed}
                                selected={selected.includes(item.key)}
                                onSelect={kind === 'product' ? toggle : null}
                            />
                        ))}
                    </div>

                    {!loading && items.length === 0 && (
                        <p className="text-sm text-glaze-slate">{ruled ? 'Nothing matched yet.' : 'Nothing left to match.'}</p>
                    )}
                </>
            )}
        </section>
    );
}
