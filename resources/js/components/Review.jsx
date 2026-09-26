import { useCallback, useEffect, useRef, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';

const SOURCE = 'fiesta_factory_direct';
const BASE = `/api/sources/${SOURCE}`;

const SECTIONS = [
    { key: 'color', label: 'Colors' },
    { key: 'product', label: 'Products' },
    { key: 'swatch', label: 'Swatches' },
];

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

function Picker({ kind, options, onPick }) {
    const [query, setQuery] = useState('');
    const matches = options
        .filter((o) => o.name.toLowerCase().includes(query.toLowerCase()))
        .slice(0, 8);

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
                            <span className="ml-auto text-xs tabular-nums text-glaze-slate">
                                {o.produced_label ?? 'years unknown'}
                            </span>
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
                    Create {kind}
                </button>
                <button type="button" onClick={onCancel} className={`${BUTTON} text-glaze-slate`}>
                    Cancel
                </button>
            </div>
        </form>
    );
}

function NameCard({ item, kind, options, onChanged, onSkip, selected, onSelect }) {
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
                        {item.retired > 0 && ` · ${item.retired} retired`}
                        {ruled && ` · ${item.resolved} evidencing a variant`}
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
                    <ul className="space-y-0.5 text-xs text-glaze-slate">
                        {item.examples.map((e) => (
                            <li key={e.id} className="truncate">
                                <a href={e.url} target="_blank" rel="noopener noreferrer" className="hover:underline">
                                    {e.title}
                                </a>
                            </li>
                        ))}
                    </ul>

                    {mode === 'map' && (
                        <Picker kind={kind} options={options} onPick={(o) => send('rulings', { decision: 'mapped', target_id: o.id })} />
                    )}
                    {mode === 'create' && (
                        <CreateForm
                            kind={kind}
                            defaultName={item.name}
                            onSubmit={(data) => send('rulings/create', data)}
                            onCancel={() => setMode(null)}
                        />
                    )}
                    {mode === null && (
                        <div className="flex flex-wrap gap-2">
                            <button onClick={() => setMode('map')} className={`${BUTTON} bg-glaze-ink text-glaze-cream`}>
                                One of mine
                            </button>
                            <button onClick={() => setMode('create')} className={`${BUTTON} bg-glaze-shell text-glaze-ink`}>
                                Add new
                            </button>
                            <button onClick={() => send('rulings', { decision: 'ignored' })} disabled={loading} className={`${BUTTON} text-glaze-slate`}>
                                Ignore
                            </button>
                            <button onClick={() => onSkip(item.key)} className={`${BUTTON} text-glaze-slate`}>
                                Skip
                            </button>
                        </div>
                    )}
                </>
            )}

            {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
        </div>
    );
}

function SwatchCard({ suggestion, onDone }) {
    const { post, error, loading } = useApi();
    const { color } = suggestion;

    const act = async (action) => {
        if (await post(`/api/swatch-suggestions/${suggestion.id}/${action}`)) onDone(suggestion.id);
    };

    return (
        <div className="space-y-3 rounded-2xl border-2 border-glaze-shell bg-white p-4">
            <div className="flex items-center gap-4">
                <span
                    className="rings h-16 w-16 shrink-0 rounded-full text-black/30 ring-1 ring-black/15"
                    style={{ backgroundColor: suggestion.hex }}
                />
                <div>
                    <p className="font-black">
                        {color.name} <span className="font-normal text-glaze-slate">{color.produced_label}</span>
                    </p>
                    <p className="font-mono text-sm">{suggestion.hex}</p>
                    <p className="text-xs text-glaze-slate">from {suggestion.photos_sampled} store photos</p>
                </div>
            </div>
            <p className="text-xs text-glaze-slate">{suggestion.method}</p>
            <div className="flex gap-2">
                <button onClick={() => act('accept')} disabled={loading} className={`${BUTTON} bg-glaze-ink text-glaze-cream`}>
                    Use this swatch
                </button>
                <button onClick={() => act('dismiss')} disabled={loading} className={`${BUTTON} text-glaze-slate`}>
                    Dismiss
                </button>
            </div>
            {error && <p className="text-sm font-bold text-glaze-flame">{error}</p>}
        </div>
    );
}

/**
 * Where store names are ruled on and suggested swatches reviewed. A ruling
 * re-resolves its listings straight away, so the counts here are always current.
 */
export default function Review() {
    const { get: getLines } = useApi();
    const { get: getOptions } = useApi();
    const { get, loading } = useApi();
    const { get: getCounts } = useApi();
    const { post: postBulk, error: bulkError } = useApi();

    const [section, setSection] = useState('color');
    const [ruled, setRuled] = useState(false);
    const [items, setItems] = useState([]);
    const [skipped, setSkipped] = useState([]);
    const [selected, setSelected] = useState([]);
    const [options, setOptions] = useState({ color: [], product: [] });
    const [counts, setCounts] = useState({ color: null, product: null, swatch: null });
    const [notice, setNotice] = useState(null);
    const [lineId, setLineId] = useState(null);

    const loadCounts = useCallback(async () => {
        const [colors, products, swatches] = await Promise.all([
            getCounts(`${BASE}/names?kind=color`),
            getCounts(`${BASE}/names?kind=product`),
            getCounts('/api/swatch-suggestions'),
        ]);
        setCounts({ color: colors?.length ?? 0, product: products?.length ?? 0, swatch: swatches?.length ?? 0 });
    }, [getCounts]);

    const loadOptions = useCallback(async (id) => {
        const [colors, products] = await Promise.all([
            getOptions(`/api/colors?line_id=${id}`),
            getOptions(`/api/products?line_id=${id}`),
        ]);
        setOptions({ color: colors ?? [], product: products ?? [] });
    }, [getOptions]);

    // Switching sections quickly leaves slower requests in flight. Only the most
    // recent one may fill the list, or an old section's answer can land last.
    const latestRequest = useRef(0);

    const load = useCallback(async () => {
        const request = ++latestRequest.current;
        setSelected([]);
        const url = section === 'swatch' ? '/api/swatch-suggestions' : `${BASE}/names?kind=${section}&ruled=${ruled ? 1 : 0}`;
        const result = await get(url);

        if (request === latestRequest.current) {
            setItems(result ?? []);
        }
    }, [get, section, ruled]);

    useEffect(() => {
        getLines('/api/lines').then((lines) => {
            const fiesta = (lines ?? []).find((l) => l.name === 'Fiesta');
            if (fiesta) {
                setLineId(fiesta.id);
                loadOptions(fiesta.id);
            }
        });
        loadCounts();
    }, [getLines, loadOptions, loadCounts]);

    useEffect(() => {
        load();
    }, [load]);

    const onChanged = (before, after) => {
        setItems((current) => current.filter((i) => i.key !== before.key));
        const target = after.ruling?.target;
        setNotice(
            after.ruling === null
                ? `"${after.name}" is back in the queue.`
                : target
                  ? `"${after.name}" → ${target.name}. ${after.confirmed_now} ${after.confirmed_now === 1 ? 'variant' : 'variants'} newly confirmed.`
                  : `"${after.name}" ignored.`
        );
        loadCounts();
        if (lineId) loadOptions(lineId);
    };

    const createSelected = async () => {
        const result = await postBulk(`${BASE}/rulings/create-products`, { keys: selected });
        if (result) {
            setNotice(`Created ${result.created} new ${result.created === 1 ? 'product' : 'products'}.`);
            load();
            loadCounts();
            if (lineId) loadOptions(lineId);
        }
    };

    const toggle = (key) =>
        setSelected((current) => (current.includes(key) ? current.filter((k) => k !== key) : [...current, key]));

    const visible = section === 'swatch' ? items : items.filter((i) => !skipped.includes(i.key));

    return (
        <div className="mx-auto max-w-3xl space-y-4">
            <div className="space-y-3 rounded-2xl border-2 border-glaze-shell bg-white p-4 shadow-sm">
                <div className="flex gap-1 rounded-full bg-glaze-shell p-1">
                    {SECTIONS.map((s) => (
                        <button
                            key={s.key}
                            onClick={() => {
                                // Clear in the same render, so the old section's cards never
                                // render as the new section's.
                                setItems([]);
                                setSection(s.key);
                                setRuled(false);
                                setNotice(null);
                            }}
                            className={`flex-1 rounded-full px-3 py-2 text-sm font-bold transition ${
                                section === s.key ? 'bg-glaze-ink text-glaze-cream' : 'text-glaze-slate hover:text-glaze-ink'
                            }`}
                        >
                            {s.label}
                            {counts[s.key] !== null && counts[s.key] > 0 && (
                                <span className="ml-1.5 rounded-full bg-glaze-sun px-1.5 text-xs text-glaze-ink">{counts[s.key]}</span>
                            )}
                        </button>
                    ))}
                </div>

                {section !== 'swatch' && (
                    <div className="flex items-center gap-4 text-sm font-bold">
                        <button onClick={() => setRuled(false)} className={ruled ? 'text-glaze-slate' : 'text-glaze-ink underline underline-offset-4'}>
                            To rule on
                        </button>
                        <button onClick={() => setRuled(true)} className={ruled ? 'text-glaze-ink underline underline-offset-4' : 'text-glaze-slate'}>
                            Already ruled
                        </button>
                        {skipped.length > 0 && !ruled && (
                            <button onClick={() => setSkipped([])} className="ml-auto text-xs text-glaze-slate">
                                Show {skipped.length} skipped
                            </button>
                        )}
                    </div>
                )}

                <p className="text-xs text-glaze-slate">
                    {section === 'swatch'
                        ? 'Swatches estimated from Fiesta Factory Direct photos for colors that have none. Nothing changes until you use one.'
                        : 'Names used by Fiesta Factory Direct listings, most listings first. A ruling applies to every listing that uses the name.'}
                </p>
            </div>

            {notice && (
                <p className="rounded-xl border-2 border-glaze-fern/30 bg-glaze-fern/10 px-3 py-2 text-sm font-bold text-glaze-ink">{notice}</p>
            )}

            {section === 'product' && !ruled && selected.length > 0 && (
                <div className="sticky top-2 z-10 flex items-center gap-3 rounded-2xl bg-glaze-ink px-4 py-3 text-glaze-cream shadow-lg">
                    <span className="text-sm font-bold">{selected.length} selected</span>
                    <button onClick={createSelected} className={`${BUTTON} ml-auto bg-glaze-sun text-glaze-ink`}>
                        Add as new products
                    </button>
                    {bulkError && <span className="text-sm text-glaze-flame">{bulkError}</span>}
                </div>
            )}

            {loading && <p className="text-sm text-glaze-slate">Loading...</p>}

            <div className="space-y-3">
                {section === 'swatch'
                    ? visible.map((s) => (
                          <SwatchCard
                              key={s.id}
                              suggestion={s}
                              onDone={(id) => {
                                  setItems((current) => current.filter((i) => i.id !== id));
                                  loadCounts();
                              }}
                          />
                      ))
                    : visible.map((item) => (
                          <NameCard
                              key={item.key}
                              item={item}
                              kind={section}
                              options={options[section]}
                              onChanged={onChanged}
                              onSkip={(key) => setSkipped((current) => [...current, key])}
                              selected={selected.includes(item.key)}
                              onSelect={section === 'product' ? toggle : null}
                          />
                      ))}
            </div>

            {!loading && visible.length === 0 && (
                <p className="rounded-2xl border-2 border-dashed border-glaze-shell px-4 py-8 text-center text-sm text-glaze-slate">
                    {section === 'swatch'
                        ? 'No swatch suggestions waiting. They are made weekly, after colors are ruled on.'
                        : ruled
                          ? 'Nothing ruled yet.'
                          : 'Nothing left to rule on.'}
                </p>
            )}
        </div>
    );
}
