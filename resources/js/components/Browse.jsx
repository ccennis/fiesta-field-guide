import { useCallback, useEffect, useRef, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';
import VariantDetail from './VariantDetail';

const SEARCH_DELAY_MS = 250;

/**
 * One piece in a result card. Unverified pieces are shown, since most of the
 * catalog has not been verified yet, but drawn dashed and labeled so they are
 * never mistaken for a known example.
 */
function Piece({ variant, label, hex, detail, selected, onSelect }) {
    const verified = variant.existence.confirmed;

    return (
        <button
            onClick={() => onSelect(variant.id)}
            className={`flex items-center gap-2.5 rounded-xl border-2 px-3 py-2 text-left transition ${
                selected
                    ? 'border-glaze-lagoon bg-glaze-lagoon/10'
                    : verified
                      ? 'border-glaze-shell bg-white hover:border-glaze-lagoon'
                      : 'border-dashed border-glaze-shell bg-white/60 hover:border-glaze-lagoon'
            }`}
        >
            {hex !== undefined && <Swatch hex={hex} size="sm" />}
            <span className="min-w-0 flex-1">
                <span className={`block truncate text-sm font-bold ${verified ? 'text-glaze-ink' : 'text-glaze-slate'}`}>{label}</span>
                <span className="block truncate text-xs text-glaze-slate">
                    {verified ? 'Verified' : 'Not verified'}
                    {detail && ` · ${detail}`}
                </span>
            </span>
            {variant.owned_count > 0 && (
                <span className="shrink-0 rounded-full bg-glaze-fern/15 px-2 py-0.5 text-xs font-bold text-glaze-fern">
                    have {variant.owned_count}
                </span>
            )}
            {variant.wishlisted && (
                <span className="shrink-0 rounded-full bg-glaze-sun/40 px-2 py-0.5 text-xs font-bold text-glaze-ink">wished</span>
            )}
        </button>
    );
}

function Card({ title, subtitle, swatch, children }) {
    return (
        <section className="space-y-3 rounded-2xl border-2 border-glaze-shell bg-white/70 p-4">
            <div className="flex items-center gap-3">
                {swatch}
                <div>
                    <h3 className="text-lg font-black leading-tight">{title}</h3>
                    <p className="text-sm text-glaze-slate">{subtitle}</p>
                </div>
            </div>
            {children}
        </section>
    );
}

const GRID = 'grid gap-2 sm:grid-cols-2';

function ColorCard({ color, selectedId, onSelect }) {
    const verified = color.variants.filter((v) => v.existence.confirmed).length;

    return (
        <Card
            title={color.name}
            subtitle={`${color.line.name} · ${color.produced_label ?? 'years unknown'} · ${verified} of ${color.variants.length} verified`}
            swatch={<Swatch hex={color.hex} size="lg" />}
        >
            <div className={GRID}>
                {color.variants.map((v) => (
                    <Piece key={v.id} variant={v} label={v.product.name} selected={selectedId === v.id} onSelect={onSelect} />
                ))}
            </div>
        </Card>
    );
}

function ProductCard({ product, selectedId, onSelect }) {
    return (
        <Card title={product.name} subtitle={product.line.name}>
            {product.eras.map((group) => (
                <div key={group.era?.value ?? 'unknown'} className="space-y-2">
                    <h4 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">
                        {group.era ? group.era.label : 'Years unknown'}
                    </h4>
                    <div className={GRID}>
                        {group.variants.map((v) => (
                            <Piece
                                key={v.id}
                                variant={v}
                                label={v.color.name}
                                hex={v.color.hex}
                                detail={v.color.produced_label}
                                selected={selectedId === v.id}
                                onSelect={onSelect}
                            />
                        ))}
                    </div>
                </div>
            ))}
        </Card>
    );
}

/**
 * Before anything is typed, every color is laid out as a palette, grouped by
 * line, so a color can be picked without knowing its name.
 */
function Palette({ onPick }) {
    const { data: colors, get } = useApi();

    useEffect(() => {
        get('/api/colors');
    }, [get]);

    const byLine = (colors ?? []).reduce((groups, color) => {
        (groups[color.line.name] ??= []).push(color);
        return groups;
    }, {});

    return (
        <div className="space-y-4">
            {Object.entries(byLine).map(([line, lineColors]) => (
                <section key={line} className="space-y-2">
                    <h3 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">{line}</h3>
                    <div className="flex flex-wrap gap-1.5">
                        {lineColors.map((c) => (
                            <button
                                key={c.id}
                                onClick={() => onPick(c)}
                                title={c.produced_label ?? 'years unknown'}
                                className="inline-flex items-center gap-1.5 rounded-full border-2 border-glaze-shell bg-white py-0.5 pl-0.5 pr-2.5 text-xs font-bold hover:border-glaze-lagoon"
                            >
                                <Swatch hex={c.hex} size="xs" />
                                {c.name}
                                {c.produced_from && <span className="font-normal text-glaze-slate">{c.produced_from}</span>}
                            </button>
                        ))}
                    </div>
                </section>
            ))}
        </div>
    );
}

/**
 * Search by color or by product. A color lists every product it pairs with; a
 * product lists every color, split by era so a vintage piece and its reissue
 * sit apart. Picking a piece opens it, where it can be added to the collection
 * or the wishlist.
 */
export default function Browse({ onChanged }) {
    const { get, loading, error } = useApi();
    const [query, setQuery] = useState('');
    const [picked, setPicked] = useState(null);
    const [results, setResults] = useState(null);
    const [variantId, setVariantId] = useState(null);
    const latestRequest = useRef(0);

    const search = useCallback(async () => {
        const params = picked ? `color_id=${picked.id}` : query.trim().length >= 2 ? `q=${encodeURIComponent(query.trim())}` : null;

        if (!params) {
            setResults(null);
            return;
        }

        const request = ++latestRequest.current;
        const found = await get(`/api/browse?${params}`);

        if (request === latestRequest.current) setResults(found);
    }, [get, query, picked]);

    useEffect(() => {
        const timer = setTimeout(search, picked ? 0 : SEARCH_DELAY_MS);
        return () => clearTimeout(timer);
    }, [search, picked]);

    const type = (value) => {
        setPicked(null);
        setVariantId(null);
        setQuery(value);
    };

    const pickColor = (color) => {
        setPicked(color);
        setVariantId(null);
        setQuery(color.name);
    };

    useEffect(() => {
        if (variantId && window.matchMedia('(max-width: 1023px)').matches) window.scrollTo(0, 0);
    }, [variantId]);

    const empty = results && results.colors.length === 0 && results.products.length === 0;

    return (
        <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div className={`space-y-4 ${variantId ? 'hidden lg:block' : ''}`}>
                <div className="relative">
                    <input
                        type="search"
                        value={query}
                        onChange={(e) => type(e.target.value)}
                        placeholder="Search a color or a product, like Heather or Carafe"
                        autoFocus
                        className="w-full rounded-2xl border-2 border-glaze-shell bg-white px-4 py-3.5 text-base shadow-sm focus:border-glaze-lagoon focus:outline-none"
                    />
                    {loading && <span className="absolute right-4 top-4 text-xs text-glaze-slate">Searching...</span>}
                </div>

                {error && query.trim().length >= 2 && <p className="text-sm text-glaze-flame">{error}</p>}

                {!results && <Palette onPick={pickColor} />}

                {empty && (
                    <p className="rounded-2xl border-2 border-dashed border-glaze-shell px-4 py-8 text-center text-sm text-glaze-slate">
                        Nothing matches &ldquo;{query}&rdquo;.
                    </p>
                )}

                {results?.colors.map((c) => (
                    <ColorCard key={`c${c.id}`} color={c} selectedId={variantId} onSelect={setVariantId} />
                ))}
                {results?.products.map((p) => (
                    <ProductCard key={`p${p.id}`} product={p} selectedId={variantId} onSelect={setVariantId} />
                ))}
            </div>

            <div className={variantId ? '' : 'hidden lg:block'}>
                {variantId ? (
                    <div className="space-y-3 lg:sticky lg:top-4">
                        <button
                            onClick={() => setVariantId(null)}
                            className="flex items-center gap-2 rounded-full bg-glaze-shell px-4 py-2 text-sm font-bold text-glaze-slate lg:hidden"
                        >
                            <span aria-hidden="true">&larr;</span>
                            Back to results
                        </button>
                                                <VariantDetail
                            variantId={variantId}
                            onChanged={() => {
                                search();
                                onChanged?.();
                            }}
                        />
                    </div>
                ) : (
                    <p className="rounded-2xl border-2 border-dashed border-glaze-shell px-4 py-8 text-center text-sm text-glaze-slate">
                        Pick a piece to see its rarity and value, and to add it to your collection or wishlist.
                    </p>
                )}
            </div>
        </div>
    );
}
