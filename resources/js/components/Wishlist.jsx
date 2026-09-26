import { useCallback, useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';
import VariantDetail from './VariantDetail';

const STATUS = [
    { key: 'open', label: 'Still hunting' },
    { key: 'found', label: 'Found' },
];

function AnyColorMark() {
    return (
        <span
            title="Any color"
            className="h-7 w-7 shrink-0 rounded-full ring-1 ring-black/15"
            style={{ background: 'conic-gradient(#e0533d, #f5c518, #4a9b5a, #2a7ab0, #7a4f9a, #e0533d)' }}
        />
    );
}

/**
 * Grails sort first. Found items stay on the list, pointing at the piece that
 * fulfilled them.
 */
export default function Wishlist() {
    const { data: items, loading, get } = useApi();
    const { patch, destroy } = useApi();
    const [status, setStatus] = useState('open');
    const [selected, setSelected] = useState(null);

    const load = useCallback(() => {
        get(`/api/wishlist?fulfilled=${status === 'found' ? '1' : '0'}`);
    }, [get, status]);

    useEffect(() => {
        load();
    }, [load]);

    const update = async (item, changes) => {
        if (await patch(`/api/wishlist/${item.id}`, changes)) load();
    };

    const remove = async (item) => {
        if (await destroy(`/api/wishlist/${item.id}`)) load();
    };

    const rows = items ?? [];

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-4 rounded-2xl border-2 border-glaze-shell bg-white p-4 shadow-sm">
                <div className="flex gap-1 rounded-full bg-glaze-shell p-1">
                    {STATUS.map((s) => (
                        <button
                            key={s.key}
                            onClick={() => setStatus(s.key)}
                            className={`rounded-full px-4 py-2 text-sm font-bold transition ${
                                status === s.key ? 'bg-glaze-ink text-glaze-cream' : 'text-glaze-slate hover:text-glaze-ink'
                            }`}
                        >
                            {s.label}
                        </button>
                    ))}
                </div>
                <p className="ml-auto text-sm font-bold text-glaze-slate">
                    {loading ? 'Loading...' : `${rows.length} item${rows.length === 1 ? '' : 's'}`}
                </p>
            </div>

            <ul className="space-y-2 md:hidden">
                {rows.map((item) => {
                    const isGrail = item.priority.value === 'grail';

                    return (
                        <li
                            key={item.id}
                            onClick={() => item.variant && setSelected(item.variant.id)}
                            className={`flex items-center gap-3 rounded-2xl border-2 bg-white px-4 py-3 ${
                                isGrail ? 'border-glaze-sun' : 'border-glaze-shell'
                            } ${item.variant ? 'cursor-pointer' : ''}`}
                        >
                            {item.any_color ? <AnyColorMark /> : <Swatch hex={item.variant.color.hex} size="md" />}
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-bold">
                                    {item.any_color ? 'Any color' : item.variant.color.name} {item.product.name}
                                </p>
                                <p className="truncate text-xs text-glaze-slate">
                                    {item.product.line.name}
                                    {item.variant?.decoration && ` · ${item.variant.decoration.name}`}
                                    {item.value && ` · worth $${item.value.amount.toFixed(2)}`}
                                    {status === 'found' && item.fulfilled_at && ` · found ${item.fulfilled_at}`}
                                </p>
                            </div>
                            <div className="flex flex-col items-end gap-1">
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        update(item, { priority: isGrail ? 'want' : 'grail' });
                                    }}
                                    className={`rounded-full px-3 py-1.5 text-xs font-bold ${
                                        isGrail ? 'bg-glaze-sun text-glaze-ink' : 'bg-glaze-shell text-glaze-slate'
                                    }`}
                                >
                                    {item.priority.label}
                                </button>
                                {item.max_price !== null && (
                                    <span className="text-xs font-bold tabular-nums">up to ${item.max_price.toFixed(2)}</span>
                                )}
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        status === 'found' ? update(item, { fulfilled: false }) : remove(item);
                                    }}
                                    className="py-1 text-xs font-bold text-glaze-slate"
                                >
                                    {status === 'found' ? 'Reopen' : 'Remove'}
                                </button>
                            </div>
                        </li>
                    );
                })}
                {!loading && rows.length === 0 && (
                    <li className="px-4 py-8 text-center text-sm text-glaze-slate">
                        {status === 'found' ? 'Nothing found yet.' : 'Your wishlist is empty.'}
                    </li>
                )}
            </ul>

            <div className="hidden overflow-hidden rounded-2xl border-2 border-glaze-shell bg-white shadow-sm md:block">
                <table className="w-full border-collapse text-sm">
                    <thead>
                        <tr className="bg-glaze-ink text-left text-[11px] uppercase tracking-wide text-glaze-cream">
                            <th className="px-4 py-3 font-bold">Piece</th>
                            <th className="px-4 py-3 font-bold">Line</th>
                            <th className="px-4 py-3 font-bold">Priority</th>
                            <th className="px-4 py-3 text-right font-bold">Value</th>
                            <th className="px-4 py-3 text-right font-bold">I'd pay up to</th>
                            {status === 'found' && <th className="px-4 py-3 font-bold">Found</th>}
                            <th className="px-4 py-3">
                                <span className="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((item) => {
                            const isGrail = item.priority.value === 'grail';

                            return (
                                <tr
                                    key={item.id}
                                    onClick={() => item.variant && setSelected(item.variant.id)}
                                    className={`border-b border-glaze-shell/70 transition last:border-0 odd:bg-glaze-cream/40 ${
                                        item.variant ? 'cursor-pointer hover:bg-glaze-sun/15' : ''
                                    }`}
                                >
                                    <td className="px-4 py-2.5">
                                        <div className="flex items-center gap-3">
                                            {item.any_color ? <AnyColorMark /> : <Swatch hex={item.variant.color.hex} size="sm" />}
                                            <span className="font-bold">
                                                {item.any_color ? 'Any color' : item.variant.color.name} {item.product.name}
                                            </span>
                                            {item.variant?.decoration && (
                                                <span className="rounded-full bg-glaze-plum/15 px-2 py-0.5 text-xs font-bold text-glaze-plum">
                                                    {item.variant.decoration.name}
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-2.5 text-glaze-slate">{item.product.line.name}</td>
                                    <td className="px-4 py-2.5">
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                update(item, { priority: isGrail ? 'want' : 'grail' });
                                            }}
                                            title="Toggle grail"
                                            className={`rounded-full px-3 py-1 text-xs font-bold ${
                                                isGrail ? 'bg-glaze-sun text-glaze-ink' : 'bg-glaze-shell text-glaze-slate'
                                            }`}
                                        >
                                            {item.priority.label}
                                        </button>
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums">
                                        {item.value ? (
                                            <span className={item.value.source.is_blanket ? 'text-glaze-slate' : 'font-bold'}>
                                                ${item.value.amount.toFixed(2)}
                                            </span>
                                        ) : (
                                            <span className="text-glaze-slate/50">—</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5 text-right font-bold tabular-nums">
                                        {item.max_price !== null ? (
                                            `$${item.max_price.toFixed(2)}`
                                        ) : (
                                            <span className="font-normal text-glaze-slate/50">—</span>
                                        )}
                                    </td>
                                    {status === 'found' && (
                                        <td className="px-4 py-2.5 text-glaze-slate">{item.fulfilled_at}</td>
                                    )}
                                    <td className="px-4 py-2.5 text-right">
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                status === 'found' ? update(item, { fulfilled: false }) : remove(item);
                                            }}
                                            className="text-xs font-bold text-glaze-slate underline-offset-2 hover:underline"
                                        >
                                            {status === 'found' ? 'Reopen' : 'Remove'}
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>

                {!loading && rows.length === 0 && (
                    <p className="px-4 py-8 text-center text-sm text-glaze-slate">
                        {status === 'found' ? 'Nothing found yet.' : 'Your wishlist is empty.'}
                    </p>
                )}
            </div>

            <p className="max-w-4xl text-xs text-glaze-slate">
                Recording a piece crosses off the matching item, the exact piece first and then an any-color item
                for that product. Any-color items never match a decorated piece. Values in gray are blanket
                per-product figures.
            </p>

            {selected && (
                <div
                    className="fixed inset-0 z-20 flex justify-end bg-glaze-ink/40"
                    onClick={() => {
                        setSelected(null);
                        load();
                    }}
                >
                    <div
                        className="h-full w-full max-w-lg overflow-y-auto bg-glaze-cream p-6 pt-[calc(1.5rem+env(safe-area-inset-top))] pb-[calc(1.5rem+env(safe-area-inset-bottom))] shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            onClick={() => {
                                setSelected(null);
                                load();
                            }}
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
