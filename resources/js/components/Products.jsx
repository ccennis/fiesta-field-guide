import { useCallback, useEffect, useMemo, useState } from 'react';
import { useApi } from '../hooks/useApi';

const INPUT =
    'rounded-lg border-2 border-glaze-shell bg-white px-3 py-2 text-sm focus:border-glaze-lagoon focus:outline-none';

/**
 * Products are seeded from the collection export, then owned here: renamed as
 * typos surface, merged as duplicates are spotted, added as new ones are
 * learned. Every product crosses against its line's colors, so a duplicate is
 * not a cosmetic problem — it is dozens of catalog entries that never existed.
 *
 * Merging is a selection action rather than a row action, because it is only
 * meaningful between two or more products.
 */
export default function Products() {
    const { data: lines, get: getLines } = useApi();
    const { data: products, loading, get: getProducts } = useApi();
    const { patch, post, destroy, error } = useApi();

    const [lineId, setLineId] = useState('');
    const [selected, setSelected] = useState([]);
    const [target, setTarget] = useState('');
    const [editing, setEditing] = useState(null);
    const [draftName, setDraftName] = useState('');
    const [newName, setNewName] = useState('');
    const [newLineId, setNewLineId] = useState('');
    const [notice, setNotice] = useState(null);
    const [busy, setBusy] = useState(false);

    const refresh = useCallback(() => {
        getProducts(lineId ? `/api/products?line_id=${lineId}` : '/api/products');
    }, [lineId, getProducts]);

    useEffect(() => {
        getLines('/api/lines');
    }, [getLines]);

    useEffect(() => {
        refresh();
    }, [refresh]);

    useEffect(() => {
        setSelected([]);
        setTarget('');
    }, [lineId]);

    useEffect(() => {
        if (lines?.length && !newLineId) setNewLineId(String(lines[0].id));
    }, [lines, newLineId]);

    const rows = products ?? [];
    const chosen = useMemo(() => rows.filter((p) => selected.includes(p.id)), [rows, selected]);
    const linesInSelection = new Set(chosen.map((p) => p.line.id));
    const sameLine = linesInSelection.size <= 1;
    const canMerge = chosen.length > 1 && sameLine && target !== '';

    const toggle = (id) => {
        setNotice(null);
        setSelected((current) =>
            current.includes(id) ? current.filter((x) => x !== id) : [...current, id]
        );
    };

    const clearSelection = () => {
        setSelected([]);
        setTarget('');
    };

    const saveName = async (product) => {
        const updated = await patch(`/api/products/${product.id}`, { name: draftName });
        if (updated) {
            setNotice(`Renamed to ${updated.name}.`);
            setEditing(null);
            refresh();
        }
    };

    /**
     * Everything selected folds into the chosen survivor, one call each, so a
     * failure part way through still leaves the earlier merges applied and
     * reported rather than silently half done.
     */
    const runMerge = async () => {
        const survivor = chosen.find((p) => String(p.id) === target);
        const sources = chosen.filter((p) => String(p.id) !== target);
        const totals = { holdings: 0, folded: 0, repointed: 0 };

        setBusy(true);

        for (const source of sources) {
            const result = await post(`/api/products/${source.id}/merge`, { into: Number(target) });

            if (!result) {
                setBusy(false);
                refresh();

                return;
            }

            totals.holdings += result.holdings;
            totals.folded += result.variants_folded;
            totals.repointed += result.variants_repointed;
        }

        setBusy(false);
        setNotice(
            `Merged ${sources.length} ${sources.length === 1 ? 'product' : 'products'} into ${survivor.name}. ` +
                `${totals.holdings} pieces moved, ${totals.folded} duplicate catalog rows removed.`
        );
        clearSelection();
        refresh();
    };

    const removeProduct = async (product) => {
        const gone = await destroy(`/api/products/${product.id}`);
        if (gone !== null) {
            setNotice(`Deleted ${product.name}.`);
            refresh();
        }
    };

    const addProduct = async () => {
        const created = await post('/api/products', { line_id: Number(newLineId), name: newName });
        if (created) {
            setNotice(`Added ${created.name}.`);
            setNewName('');
            refresh();
        }
    };

    const allChecked = rows.length > 0 && selected.length === rows.length;

    return (
        <div className="space-y-4">
            <div className="rounded-2xl border-2 border-glaze-shell bg-white p-4 shadow-sm">
                <div className="flex flex-wrap items-end gap-4">
                    <label className="flex flex-col gap-1">
                        <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">Line</span>
                        <select value={lineId} onChange={(e) => setLineId(e.target.value)} className={INPUT}>
                            <option value="">All lines</option>
                            {(lines ?? []).map((l) => (
                                <option key={l.id} value={l.id}>
                                    {l.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="ml-auto flex flex-col gap-1">
                        <span className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">
                            Add a product
                        </span>
                        <div className="flex gap-2">
                            <select
                                value={newLineId}
                                onChange={(e) => setNewLineId(e.target.value)}
                                className={INPUT}
                            >
                                {(lines ?? []).map((l) => (
                                    <option key={l.id} value={l.id}>
                                        {l.name}
                                    </option>
                                ))}
                            </select>
                            <input
                                value={newName}
                                onChange={(e) => setNewName(e.target.value)}
                                placeholder="e.g. Covered Onion Soup"
                                className={`${INPUT} w-56`}
                            />
                            <button
                                onClick={addProduct}
                                disabled={!newName.trim()}
                                className="rounded-lg bg-glaze-lagoon px-4 py-2 text-sm font-bold text-white disabled:opacity-40"
                            >
                                Add
                            </button>
                        </div>
                    </label>
                </div>

                {error && <p className="mt-3 text-sm font-bold text-glaze-flame">{error}</p>}
                {notice && !error && <p className="mt-3 text-sm font-bold text-glaze-fern">{notice}</p>}
            </div>

            {selected.length > 0 && (
                <div className="flex flex-wrap items-center gap-3 rounded-2xl border-2 border-glaze-sun bg-glaze-sun/15 px-4 py-3">
                    <span className="text-sm font-bold">
                        {selected.length} selected
                    </span>

                    {selected.length === 1 && (
                        <span className="text-sm text-glaze-slate">
                            Select another product to merge them together.
                        </span>
                    )}

                    {selected.length > 1 && !sameLine && (
                        <span className="text-sm font-bold text-glaze-flame">
                            Products can only be merged within one line.
                        </span>
                    )}

                    {selected.length > 1 && sameLine && (
                        <>
                            <span className="text-sm text-glaze-slate">Keep</span>
                            <select
                                value={target}
                                onChange={(e) => setTarget(e.target.value)}
                                className={`${INPUT} py-1.5`}
                            >
                                <option value="">Choose the one to keep</option>
                                {chosen.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                            <button
                                onClick={runMerge}
                                disabled={!canMerge || busy}
                                className="rounded-lg bg-glaze-flame px-4 py-2 text-sm font-bold text-white disabled:opacity-40"
                            >
                                {busy ? 'Merging...' : `Merge ${chosen.length - (target ? 1 : 0)} into it`}
                            </button>
                        </>
                    )}

                    <button
                        onClick={clearSelection}
                        className="ml-auto text-sm font-bold text-glaze-slate hover:underline"
                    >
                        Clear
                    </button>
                </div>
            )}

            <div className="overflow-hidden rounded-2xl border-2 border-glaze-shell bg-white shadow-sm">
                <table className="w-full border-collapse text-sm">
                    <thead>
                        <tr className="bg-glaze-ink text-left text-[11px] uppercase tracking-wide text-glaze-cream">
                            <th className="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    checked={allChecked}
                                    onChange={() => setSelected(allChecked ? [] : rows.map((p) => p.id))}
                                    aria-label="Select all products"
                                    className="h-4 w-4 cursor-pointer accent-glaze-sun"
                                />
                            </th>
                            <th className="px-4 py-3 font-bold">Product</th>
                            <th className="px-4 py-3 font-bold">Line</th>
                            <th className="px-4 py-3 text-right font-bold">Catalog rows</th>
                            <th className="px-4 py-3 text-right font-bold">Pieces owned</th>
                            <th className="px-4 py-3 font-bold">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((p) => (
                            <tr
                                key={p.id}
                                className={`border-b border-glaze-shell/70 last:border-0 ${
                                    selected.includes(p.id) ? 'bg-glaze-sun/20' : 'odd:bg-glaze-cream/40'
                                }`}
                            >
                                <td className="px-4 py-2.5">
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(p.id)}
                                        onChange={() => toggle(p.id)}
                                        aria-label={`Select ${p.name}`}
                                        className="h-4 w-4 cursor-pointer accent-glaze-flame"
                                    />
                                </td>
                                <td className="px-4 py-2.5">
                                    {editing === p.id ? (
                                        <div className="flex gap-2">
                                            <input
                                                value={draftName}
                                                onChange={(e) => setDraftName(e.target.value)}
                                                className={`${INPUT} w-64`}
                                                autoFocus
                                            />
                                            <button
                                                onClick={() => saveName(p)}
                                                className="rounded-lg bg-glaze-ink px-3 py-1 text-xs font-bold text-glaze-cream"
                                            >
                                                Save
                                            </button>
                                            <button
                                                onClick={() => setEditing(null)}
                                                className="text-xs font-bold text-glaze-slate"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    ) : (
                                        <span className="font-bold">{p.name}</span>
                                    )}
                                </td>
                                <td className="px-4 py-2.5 text-glaze-slate">{p.line.name}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums text-glaze-slate">
                                    {p.variants_count}
                                </td>
                                <td className="px-4 py-2.5 text-right tabular-nums font-bold">
                                    {p.pieces_count > 0 ? p.pieces_count : <span className="text-glaze-slate/50">—</span>}
                                </td>
                                <td className="px-4 py-2.5">
                                    <div className="flex gap-3">
                                        <button
                                            onClick={() => {
                                                setEditing(p.id);
                                                setDraftName(p.name);
                                                setNotice(null);
                                            }}
                                            className="text-xs font-bold text-glaze-lagoon hover:underline"
                                        >
                                            Rename
                                        </button>
                                        {p.pieces_count === 0 && (
                                            <button
                                                onClick={() => removeProduct(p)}
                                                className="text-xs font-bold text-glaze-slate hover:text-glaze-flame hover:underline"
                                            >
                                                Delete
                                            </button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {loading && <p className="px-4 py-6 text-center text-sm text-glaze-slate">Loading...</p>}
            </div>

            <p className="max-w-4xl text-xs text-glaze-slate">
                Tick two or more products to merge them. Every product crosses against its line's colors, so each
                row is worth its Catalog rows count in catalog entries. Merging keeps every piece: holdings move to
                the product you keep and only the duplicate catalog rows are removed. Delete is offered only when
                nothing is owned. These edits live in the database, so re-running the import resets them to the
                spreadsheet.
            </p>
        </div>
    );
}
