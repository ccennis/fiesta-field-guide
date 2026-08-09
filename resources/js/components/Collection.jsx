import { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';
import VariantDetail from './VariantDetail';
import RowActions from './RowActions';

const OWNERSHIP = [
    { key: 'all', label: 'Everything' },
    { key: 'owned', label: 'I own' },
    { key: 'missing', label: "I'm missing" },
];

const SELECT = 'rounded-lg border-2 border-glaze-shell bg-white px-3 py-2 text-sm font-medium focus:border-glaze-lagoon focus:outline-none';

function Field({ label, children }) {
    return (
        <label className="flex flex-col gap-1">
            <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">{label}</span>
            {children}
        </label>
    );
}

/**
 * Owned and missing are one query with the filter flipped. Unfiltered, missing
 * is most of the catalog, so the filters are what make the view usable.
 */
export default function Collection() {
    const { data: result, loading, get } = useApi();
    const { data: lines, get: getLines } = useApi();
    const { data: products, get: getProducts } = useApi();
    const { data: colors, get: getColors } = useApi();
    const { data: decorations, get: getDecorations } = useApi();

    const [ownership, setOwnership] = useState('owned');
    const [lineId, setLineId] = useState('');
    const [era, setEra] = useState('');
    const [year, setYear] = useState('');
    const [productId, setProductId] = useState('');
    const [colorId, setColorId] = useState('');
    const [decoration, setDecoration] = useState('');
    const [selected, setSelected] = useState(null);
    const [acting, setActing] = useState(null);

    useEffect(() => {
        getLines('/api/lines');
        getDecorations('/api/decorations');
    }, [getLines, getDecorations]);

    useEffect(() => {
        const suffix = lineId ? `?line_id=${lineId}` : '';
        getProducts(`/api/products${suffix}`);
        getColors(`/api/colors${suffix}`);
        setProductId('');
        setColorId('');
    }, [lineId, getProducts, getColors]);

    useEffect(() => {
        const params = new URLSearchParams({ per_page: '200' });
        if (ownership !== 'all') params.set('owned', ownership === 'owned' ? '1' : '0');
        if (lineId) params.set('line_id', lineId);
        if (era) params.set('era', era);
        if (year) params.set('year', year);
        if (productId) params.set('product_id', productId);
        if (colorId) params.set('color_id', colorId);
        if (decoration === 'plain') params.set('decorated', '0');
        if (decoration === 'any') params.set('decorated', '1');
        if (decoration.startsWith('id:')) params.set('decoration_id', decoration.slice(3));

        setSelected(null);
        get(`/api/variants?${params}`);
    }, [ownership, lineId, era, year, productId, colorId, decoration, get]);

    const refresh = () => {
        const params = new URLSearchParams({ per_page: '200' });
        if (ownership !== 'all') params.set('owned', ownership === 'owned' ? '1' : '0');
        if (lineId) params.set('line_id', lineId);
        if (era) params.set('era', era);
        if (year) params.set('year', year);
        if (productId) params.set('product_id', productId);
        if (colorId) params.set('color_id', colorId);
        if (decoration === 'plain') params.set('decorated', '0');
        if (decoration === 'any') params.set('decorated', '1');
        if (decoration.startsWith('id:')) params.set('decoration_id', decoration.slice(3));
        get(`/api/variants?${params}`);
    };

    const items = result?.items ?? [];
    const total = result?.meta?.total ?? 0;

    const reset = () => {
        setLineId('');
        setEra('');
        setYear('');
        setProductId('');
        setColorId('');
        setDecoration('');
    };

    return (
        <div className="space-y-4">
            <div className="rounded-2xl border-2 border-glaze-shell bg-white p-4 shadow-sm">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="flex gap-1 rounded-full bg-glaze-shell p-1">
                        {OWNERSHIP.map((o) => (
                            <button
                                key={o.key}
                                onClick={() => setOwnership(o.key)}
                                className={`rounded-full px-4 py-2 text-sm font-bold transition ${
                                    ownership === o.key
                                        ? 'bg-glaze-ink text-glaze-cream'
                                        : 'text-glaze-slate hover:text-glaze-ink'
                                }`}
                            >
                                {o.label}
                            </button>
                        ))}
                    </div>

                    <Field label="Line">
                        <select value={lineId} onChange={(e) => setLineId(e.target.value)} className={SELECT}>
                            <option value="">All lines</option>
                            {(lines ?? []).map((l) => (
                                <option key={l.id} value={l.id}>
                                    {l.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Era">
                        <select value={era} onChange={(e) => setEra(e.target.value)} className={SELECT}>
                            <option value="">All eras</option>
                            <option value="vintage">Vintage (1936-1973)</option>
                            <option value="post_86">Post-86 (1986-)</option>
                        </select>
                    </Field>

                    <Field label="In production in">
                        <input
                            type="number"
                            value={year}
                            onChange={(e) => setYear(e.target.value)}
                            placeholder="e.g. 1955"
                            min="1930"
                            max="2100"
                            className={`${SELECT} w-32`}
                        />
                    </Field>

                    <Field label="Product">
                        <select value={productId} onChange={(e) => setProductId(e.target.value)} className={SELECT}>
                            <option value="">All products</option>
                            {(products ?? []).map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Color">
                        <select value={colorId} onChange={(e) => setColorId(e.target.value)} className={SELECT}>
                            <option value="">All colors</option>
                            {(colors ?? []).map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                    {c.produced_label ? ` (${c.produced_label})` : ''}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Decoration">
                        <select
                            value={decoration}
                            onChange={(e) => setDecoration(e.target.value)}
                            className={SELECT}
                        >
                            <option value="">Any</option>
                            <option value="plain">Plain glaze only</option>
                            <option value="any">Decorated only</option>
                            {(decorations ?? []).map((d) => (
                                <option key={d.id} value={`id:${d.id}`}>
                                    {d.name}
                                    {d.category ? ` (${d.category.label})` : ''}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <button
                        onClick={reset}
                        className="rounded-lg px-3 py-2 text-sm font-bold text-glaze-slate underline-offset-2 hover:underline"
                    >
                        Clear
                    </button>

                    <p className="ml-auto text-sm font-bold text-glaze-slate">
                        {loading ? 'Loading...' : `${total.toLocaleString()} result${total === 1 ? '' : 's'}`}
                        {total > items.length && (
                            <span className="font-normal"> · showing {items.length}</span>
                        )}
                    </p>
                </div>
            </div>

            <div className="overflow-hidden rounded-2xl border-2 border-glaze-shell bg-white shadow-sm">
                <table className="w-full border-collapse text-sm">
                    <thead>
                        <tr className="bg-glaze-ink text-left text-[11px] uppercase tracking-wide text-glaze-cream">
                            <th className="px-4 py-3 font-bold">Color</th>
                            <th className="px-4 py-3 font-bold">Years</th>
                            <th className="px-4 py-3 font-bold">Era</th>
                            <th className="px-4 py-3 font-bold">Product</th>
                            <th className="px-4 py-3 font-bold">Line</th>
                            <th className="px-4 py-3 font-bold">Catalog</th>
                            <th className="px-4 py-3 text-right font-bold">Owned</th>
                            <th className="px-4 py-3 text-right font-bold">Value</th>
                            <th className="px-4 py-3 text-right font-bold">
                                <span className="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((v) => (
                            <tr
                                key={v.id}
                                onClick={() => setSelected(v.id)}
                                className="cursor-pointer border-b border-glaze-shell/70 transition last:border-0 odd:bg-glaze-cream/40 hover:bg-glaze-sun/15"
                            >
                                <td className="px-4 py-2.5">
                                    <div className="flex items-center gap-3">
                                        <Swatch hex={v.color.hex} size="sm" />
                                        <span className="font-bold">{v.color.name}</span>
                                        {v.decoration && (
                                            <span className="rounded-full bg-glaze-plum/15 px-2 py-0.5 text-xs font-bold text-glaze-plum">
                                                {v.decoration.name}
                                                {v.decoration.category ? ` · ${v.decoration.category.label}` : ''}
                                            </span>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-2.5 tabular-nums text-glaze-slate">
                                    {v.color.produced_label ?? '—'}
                                </td>
                                <td className="px-4 py-2.5 text-glaze-slate">{v.color.era?.label ?? '—'}</td>
                                <td className="px-4 py-2.5 font-medium">{v.product.name}</td>
                                <td className="px-4 py-2.5 text-glaze-slate">{v.product.line.name}</td>
                                <td className="px-4 py-2.5">
                                    {v.existence.confirmed ? (
                                        <span className="rounded-full bg-glaze-fern/15 px-2 py-1 text-xs font-bold text-glaze-fern">
                                            Known example
                                        </span>
                                    ) : (
                                        <span className="rounded-full bg-glaze-flame/15 px-2 py-1 text-xs font-bold text-glaze-flame">
                                            No known example
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-2.5 text-right tabular-nums font-bold">
                                    {v.owned_count > 0 ? v.owned_count : <span className="text-glaze-slate/50">—</span>}
                                </td>
                                <td className="px-4 py-2.5 text-right tabular-nums">
                                    {v.value ? (
                                        <span className={v.value.source.is_blanket ? 'text-glaze-slate' : 'font-bold'}>
                                            ${v.value.amount.toFixed(2)}
                                        </span>
                                    ) : (
                                        <span className="text-glaze-slate/50">—</span>
                                    )}
                                </td>
                                <td className="px-2 py-2.5 text-right">
                                    <button
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setActing(v);
                                        }}
                                        title={`Edit swatch or add a piece — ${v.color.name} ${v.product.name}`}
                                        aria-label={`Actions for ${v.color.name} ${v.product.name}`}
                                        className="rounded-lg px-2 py-1 text-lg leading-none font-bold text-glaze-slate hover:bg-glaze-shell hover:text-glaze-ink"
                                    >
                                        &#8943;
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {!loading && items.length === 0 && (
                    <p className="px-4 py-8 text-center text-sm text-glaze-slate">Nothing matches those filters.</p>
                )}
            </div>

            <p className="max-w-4xl text-xs text-glaze-slate">
                Values in gray come from a blanket per-product figure rather than a considered price. Swatches are
                community sourced approximations, not manufacturer values, and real glaze varies between pieces
                and firings — treat one as a strong hint, not a match. A dashed ring means no swatch was supplied.
            </p>

            {acting && (
                <RowActions
                    variant={acting}
                    onClose={() => setActing(null)}
                    onChanged={refresh}
                />
            )}

            {selected && (
                <div className="fixed inset-0 z-20 flex justify-end bg-glaze-ink/40" onClick={() => setSelected(null)}>
                    <div
                        className="h-full w-full max-w-lg overflow-y-auto bg-glaze-cream p-6 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            onClick={() => setSelected(null)}
                            className="mb-4 rounded-full bg-glaze-shell px-4 py-1.5 text-sm font-bold text-glaze-slate hover:text-glaze-ink"
                        >
                            Close
                        </button>
                        <VariantDetail variantId={selected} />
                    </div>
                </div>
            )}
        </div>
    );
}
