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

export default function VariantDetail({ variantId }) {
    const { data: variant, loading, error, get } = useApi();
    const { patch } = useApi();
    const [holdings, setHoldings] = useState([]);

    useEffect(() => {
        if (variantId) {
            get(`/api/variants/${variantId}`).then((v) => setHoldings(v?.holdings ?? []));
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
        </div>
    );
}
