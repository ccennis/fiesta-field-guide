import { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';

const CONDITIONS = ['mint', 'excellent', 'good', 'fair', 'damaged'];

function Card({ title, children }) {
    return (
        <section>
            <h3 className="mb-2 text-[11px] font-bold uppercase tracking-wide text-glaze-slate">{title}</h3>
            {children}
        </section>
    );
}

function RarityFact({ label, value }) {
    return (
        <div className="rounded-xl border-2 border-glaze-shell bg-white px-3 py-2">
            <div className="text-[11px] uppercase tracking-wide text-glaze-slate">{label}</div>
            <div className={`text-sm font-bold ${value ? 'text-glaze-ink' : 'text-glaze-slate/60'}`}>
                {value ? value.label : 'No rarity data'}
            </div>
        </div>
    );
}

const PRIORITY_BUTTON = 'rounded-full px-3 py-1 text-xs font-bold transition';

/**
 * An item can name this exact piece, or the product in any plain color. When
 * both exist the exact one is what the server returns.
 */
function WishlistCard({ variant, item, onChange }) {
    const { post, patch, destroy, error } = useApi();
    const [maxPrice, setMaxPrice] = useState(item?.max_price ?? '');

    useEffect(() => {
        setMaxPrice(item?.max_price ?? '');
    }, [item]);

    const add = async (priority, anyColor) => {
        const created = await post('/api/wishlist', {
            product_id: variant.product.id,
            variant_id: anyColor ? null : variant.id,
            priority,
        });
        if (created) onChange(created);
    };

    const update = async (changes) => {
        const updated = await patch(`/api/wishlist/${item.id}`, changes);
        if (updated) onChange(updated);
    };

    const remove = async () => {
        const gone = await destroy(`/api/wishlist/${item.id}`);
        if (gone) onChange(null);
    };

    const saveMaxPrice = () => {
        const next = maxPrice === '' ? null : Number(maxPrice);
        if (next !== (item.max_price ?? null)) update({ max_price: next });
    };

    if (!item) {
        return (
            <div className="space-y-2">
                <p className="text-sm font-bold text-glaze-slate">Not on your wishlist.</p>
                <div className="flex flex-wrap gap-2">
                    <button onClick={() => add('want', false)} className={`${PRIORITY_BUTTON} bg-glaze-shell text-glaze-ink hover:bg-glaze-sun/40`}>
                        Add as want
                    </button>
                    <button onClick={() => add('grail', false)} className={`${PRIORITY_BUTTON} bg-glaze-sun text-glaze-ink hover:bg-glaze-sun/80`}>
                        Add as grail
                    </button>
                    {!variant.decoration && (
                        <button onClick={() => add('want', true)} className={`${PRIORITY_BUTTON} text-glaze-slate underline-offset-2 hover:underline`}>
                            Any color of {variant.product.name}
                        </button>
                    )}
                </div>
                {error && <p className="text-sm text-glaze-flame">{error}</p>}
            </div>
        );
    }

    const isGrail = item.priority.value === 'grail';

    return (
        <div className="space-y-3 rounded-2xl border-2 border-glaze-sun bg-white px-4 py-3">
            <p className="text-sm font-bold text-glaze-ink">
                Yes, as a {item.priority.label.toLowerCase()}
                {item.any_color && <span className="font-normal text-glaze-slate"> · any color of this product</span>}
            </p>

            <div className="flex flex-wrap items-center gap-3">
                <button
                    onClick={() => update({ priority: isGrail ? 'want' : 'grail' })}
                    className={`${PRIORITY_BUTTON} ${isGrail ? 'bg-glaze-sun text-glaze-ink' : 'bg-glaze-shell text-glaze-slate'}`}
                >
                    {isGrail ? 'Grail' : 'Make it a grail'}
                </button>

                <label className="flex items-center gap-2 text-sm text-glaze-slate">
                    I'd pay up to $
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        value={maxPrice}
                        onChange={(e) => setMaxPrice(e.target.value)}
                        onBlur={saveMaxPrice}
                        placeholder="—"
                        className="w-24 rounded-lg border-2 border-glaze-shell px-2 py-1 text-sm font-bold text-glaze-ink focus:border-glaze-lagoon focus:outline-none"
                    />
                </label>

                <button onClick={remove} className="ml-auto text-xs font-bold text-glaze-slate underline-offset-2 hover:underline">
                    Remove
                </button>
            </div>

            {error && <p className="text-sm text-glaze-flame">{error}</p>}
        </div>
    );
}

export default function VariantDetail({ variantId }) {
    const { data: variant, loading, error, get } = useApi();
    const { patch } = useApi();
    const [holdings, setHoldings] = useState([]);
    const [wishlistItem, setWishlistItem] = useState(null);

    useEffect(() => {
        if (variantId) {
            get(`/api/variants/${variantId}`).then((v) => {
                setHoldings(v?.holdings ?? []);
                setWishlistItem(v?.wishlist_item ?? null);
            });
        }
    }, [variantId, get]);

    if (!variantId) return null;
    if (loading) return <p className="text-sm text-glaze-slate">Loading...</p>;
    if (error) return <p className="text-sm text-glaze-flame">{error}</p>;
    if (!variant) return null;

    const { product, color, rarity, value, existence, owned_count: owned } = variant;

    const onCondition = async (holdingId, condition) => {
        const updated = await patch(`/api/holdings/${holdingId}`, { condition: condition || null });
        if (updated) {
            setHoldings((current) =>
                current.map((h) => (h.id === holdingId ? { ...h, condition: updated.condition } : h))
            );
        }
    };

    return (
        <div className="space-y-5">
            <div className="rounded-2xl border-2 border-glaze-shell bg-white p-4">
                <div className="flex items-start gap-4">
                    <Swatch hex={color.hex} size="lg" />
                    <div>
                        <h2 className="text-xl font-black leading-tight">
                            {color.name} {product.name}
                        </h2>
                        {variant.decoration && (
                            <p className="mt-1 inline-block rounded-full bg-glaze-plum/15 px-2 py-0.5 text-xs font-bold text-glaze-plum">
                                {variant.decoration.name} decal
                                {variant.decoration.category ? ` · ${variant.decoration.category.label}` : ''}
                                {variant.decoration.produced_label ? ` · ${variant.decoration.produced_label}` : ''}
                            </p>
                        )}
                        <p className="mt-1 text-sm text-glaze-slate">
                            {product.line.name}
                            {color.produced_label && <> · {color.produced_label}</>}
                            {color.era ? <> · {color.era.label}</> : <> · era unknown</>}
                        </p>
                    </div>
                </div>

                {!existence.confirmed && (
                    <p className="mt-3 rounded-xl border-2 border-glaze-flame/30 bg-glaze-flame/10 px-3 py-2 text-sm text-glaze-ink">
                        <strong>No known example.</strong> Generated from the color and product lists, not
                        verified. It may never have been made.
                    </p>
                )}
            </div>

            <Card title="Rarity">
                {rarity.override ? (
                    <div className="rounded-xl border-2 border-glaze-shell bg-white px-3 py-2">
                        <div className="text-[11px] uppercase tracking-wide text-glaze-slate">
                            This combination
                        </div>
                        <div className="text-sm font-bold">{rarity.override.label}</div>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-2">
                        <RarityFact label={color.name} value={rarity.color} />
                        <RarityFact label={product.name} value={rarity.product} />
                        {variant.decoration && (
                            <RarityFact label={variant.decoration.name} value={rarity.decoration} />
                        )}
                    </div>
                )}
            </Card>

            <Card title="Value">
                {value ? (
                    <div className="rounded-2xl border-2 border-glaze-shell bg-white px-4 py-3">
                        <div className="flex items-baseline gap-2">
                            <span className="text-3xl font-black text-glaze-lagoon">
                                ${value.amount.toFixed(2)}
                            </span>
                            <span className="text-sm text-glaze-slate">as of {value.observed_on}</span>
                        </div>
                        <div className="mt-1 text-sm text-glaze-slate">
                            {value.source.label}
                            {value.scope === 'product' && <> · applies to every color of this product</>}
                        </div>
                        {value.source.is_blanket && (
                            <p className="mt-2 rounded-lg bg-glaze-shell px-2 py-1 text-xs text-glaze-slate">
                                A blanket figure, not a considered price for this piece.
                            </p>
                        )}
                    </div>
                ) : (
                    <p className="text-sm text-glaze-slate/70">No value recorded.</p>
                )}

                {variant.value_history?.length > 1 && (
                    <ul className="mt-2 space-y-1 text-sm text-glaze-slate">
                        {variant.value_history.map((o) => (
                            <li key={o.id} className="flex justify-between border-b border-glaze-shell py-1">
                                <span>
                                    {o.observed_on} · {o.source.label}
                                    {o.scope === 'product' && ' (product level)'}
                                </span>
                                <span className="tabular-nums">${o.amount.toFixed(2)}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Card title="Do you own one?">
                {owned > 0 ? (
                    <div className="space-y-2">
                        <p className="text-sm font-bold text-glaze-fern">
                            Yes — you have {owned} {owned === 1 ? 'piece' : 'pieces'}.
                        </p>
                        {holdings.map((h, index) => (
                            <div
                                key={h.id}
                                className="flex items-center justify-between rounded-xl border-2 border-glaze-shell bg-white px-3 py-2"
                            >
                                <span className="text-sm font-medium text-glaze-slate">Piece {index + 1}</span>
                                <select
                                    value={h.condition?.value ?? ''}
                                    onChange={(e) => onCondition(h.id, e.target.value)}
                                    className="rounded-lg border-2 border-glaze-shell px-2 py-1 text-sm font-medium focus:border-glaze-lagoon focus:outline-none"
                                >
                                    <option value="">Condition not recorded</option>
                                    {CONDITIONS.map((c) => (
                                        <option key={c} value={c}>
                                            {c[0].toUpperCase() + c.slice(1)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm font-bold text-glaze-slate">No — you do not have this one.</p>
                )}
            </Card>

            <Card title="On your wishlist?">
                <WishlistCard variant={variant} item={wishlistItem} onChange={setWishlistItem} />
            </Card>
        </div>
    );
}
