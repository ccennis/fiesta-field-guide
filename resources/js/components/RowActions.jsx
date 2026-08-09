import { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';

const CONDITIONS = ['mint', 'excellent', 'good', 'fair', 'damaged'];

const INPUT =
    'w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-2 text-sm focus:border-glaze-lagoon focus:outline-none';

/**
 * Per-row editing: the swatch for the color, and adding another physical piece
 * of this exact variant. One row per object, so adding is one click per piece.
 */
export default function RowActions({ variant, onClose, onChanged }) {
    const { patch, error: hexError } = useApi();
    const { post, error: addError } = useApi();
    const { data: products, get: getProducts } = useApi();
    const { get: findVariant } = useApi();

    const [hex, setHex] = useState(variant.color.hex ?? '#cccccc');
    const [productId, setProductId] = useState(String(variant.product.id));
    const [condition, setCondition] = useState('');
    const [price, setPrice] = useState('');
    const [saved, setSaved] = useState(null);
    const [addProblem, setAddProblem] = useState(null);

    // A decal exists only on the products the collection evidences, so the product
    // stays fixed for a decorated row rather than offering combinations that
    // are not in the catalog.
    const productLocked = Boolean(variant.decoration);

    useEffect(() => {
        if (!productLocked) {
            getProducts(`/api/products?line_id=${variant.product.line.id}`);
        }
    }, [productLocked, variant.product.line.id, getProducts]);

    const chosenProduct = (products ?? []).find((s) => String(s.id) === productId) ?? variant.product;

    const saveHex = async () => {
        const updated = await patch(`/api/colors/${variant.color.id}`, { hex });
        if (updated) {
            setSaved('Swatch saved.');
            onChanged();
        }
    };

    const clearHex = async () => {
        const updated = await patch(`/api/colors/${variant.color.id}`, { hex: null });
        if (updated) {
            setSaved('Swatch cleared.');
            onChanged();
        }
    };

    const addPiece = async () => {
        setAddProblem(null);
        setSaved(null);

        // Resolve the variant for the chosen product in this color, so a piece can
        // be added in any product rather than only the one the row happens to show.
        let targetId = variant.id;

        if (String(variant.product.id) !== productId) {
            const found = await findVariant(
                `/api/variants?product_id=${productId}&color_id=${variant.color.id}&decorated=0&per_page=1`
            );
            targetId = found?.items?.[0]?.id ?? null;

            if (!targetId) {
                setAddProblem(`${variant.color.name} ${chosenProduct.name} is not in the catalog.`);

                return;
            }
        }

        const created = await post('/api/holdings', {
            variant_id: targetId,
            condition: condition || null,
            purchase_price: price === '' ? null : Number(price),
        });

        if (created) {
            setSaved(`Added: ${variant.color.name} ${chosenProduct.name}.`);
            setCondition('');
            setPrice('');
            onChanged();
        }
    };

    return (
        <div className="fixed inset-0 z-30 flex items-center justify-center bg-glaze-ink/50 p-4" onClick={onClose}>
            <div
                className="w-full max-w-md space-y-5 rounded-2xl bg-glaze-cream p-6 shadow-2xl"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex items-start gap-3">
                    <Swatch hex={variant.color.hex} size="lg" />
                    <div className="flex-1">
                        <h2 className="text-lg font-black leading-tight">
                            {variant.color.name} {variant.product.name}
                        </h2>
                        <p className="text-sm text-glaze-slate">
                            {variant.product.line.name}
                            {variant.color.produced_label && <> · {variant.color.produced_label}</>}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-full bg-glaze-shell px-3 py-1 text-sm font-bold text-glaze-slate hover:text-glaze-ink"
                    >
                        Close
                    </button>
                </div>

                <section className="space-y-2">
                    <h3 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">
                        Swatch for {variant.color.name}
                    </h3>
                    <div className="flex gap-2">
                        <input
                            type="color"
                            value={/^#[0-9a-fA-F]{6}$/.test(hex) ? hex : '#cccccc'}
                            onChange={(e) => setHex(e.target.value)}
                            className="h-10 w-14 shrink-0 cursor-pointer rounded-lg border-2 border-glaze-shell bg-white"
                        />
                        <input
                            value={hex}
                            onChange={(e) => setHex(e.target.value)}
                            placeholder="#rrggbb"
                            className={INPUT}
                        />
                    </div>
                    {hexError && <p className="text-sm text-glaze-flame">{hexError}</p>}
                    <div className="flex gap-2">
                        <button
                            onClick={saveHex}
                            className="rounded-lg bg-glaze-ink px-4 py-2 text-sm font-bold text-glaze-cream"
                        >
                            Save swatch
                        </button>
                        {variant.color.hex && (
                            <button
                                onClick={clearHex}
                                className="rounded-lg px-3 py-2 text-sm font-bold text-glaze-slate hover:underline"
                            >
                                Clear
                            </button>
                        )}
                    </div>
                    <p className="text-xs text-glaze-slate">
                        Applies to {variant.color.name} everywhere, not just this product. Saved to the database
                        only — put it in color-hex.csv as well to survive a re-import.
                    </p>
                </section>

                <section className="space-y-2 border-t-2 border-glaze-shell pt-4">
                    <h3 className="text-[11px] font-bold uppercase tracking-wide text-glaze-slate">
                        Add a piece you own
                    </h3>
                    <label className="block">
                        <span className="mb-1 block text-xs text-glaze-slate">Product</span>
                        {productLocked ? (
                            <p className={`${INPUT} bg-glaze-shell/60 text-glaze-slate`}>
                                {variant.product.name} — fixed, this row is a decal
                            </p>
                        ) : (
                            <select
                                value={productId}
                                onChange={(e) => setProductId(e.target.value)}
                                className={INPUT}
                            >
                                {(products ?? [variant.product]).map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        )}
                    </label>

                    <div className="flex gap-2">
                        <select
                            value={condition}
                            onChange={(e) => setCondition(e.target.value)}
                            className={INPUT}
                        >
                            <option value="">Condition not recorded</option>
                            {CONDITIONS.map((c) => (
                                <option key={c} value={c}>
                                    {c[0].toUpperCase() + c.slice(1)}
                                </option>
                            ))}
                        </select>
                        <input
                            type="number"
                            value={price}
                            onChange={(e) => setPrice(e.target.value)}
                            placeholder="Paid"
                            min="0"
                            step="0.01"
                            className={`${INPUT} w-28`}
                        />
                    </div>
                    {(addError || addProblem) && (
                        <p className="text-sm text-glaze-flame">{addProblem ?? addError}</p>
                    )}
                    <button
                        onClick={addPiece}
                        className="rounded-lg bg-glaze-lagoon px-4 py-2 text-sm font-bold text-white"
                    >
                        Add one {variant.color.name} {chosenProduct.name}
                    </button>
                    <p className="text-xs text-glaze-slate">
                        One row per physical piece.
                        {String(variant.product.id) === productId
                            ? ` You currently have ${variant.owned_count ?? 0}.`
                            : ' Adding a different product in this color.'}
                    </p>
                </section>

                {saved && <p className="text-sm font-bold text-glaze-fern">{saved}</p>}
            </div>
        </div>
    );
}
