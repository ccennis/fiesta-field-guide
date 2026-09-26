import { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Swatch from './Swatch';
import VariantDetail from './VariantDetail';

/**
 * Product first, color second. Both are visible without tools while holding the
 * piece. There is no free text search on color, because the color name is the
 * thing being worked out.
 */
export default function Identify() {
    const { data: products, get: getProducts } = useApi();
    const { data: colors, get: getColors } = useApi();
    const { get: getVariants } = useApi();

    const [product, setShape] = useState(null);
    const [filter, setFilter] = useState('');
    const [variantId, setVariantId] = useState(null);

    useEffect(() => {
        getProducts('/api/products');
    }, [getProducts]);

    useEffect(() => {
        setVariantId(null);
        if (product) {
            getColors(`/api/colors?product_id=${product.id}&line_id=${product.line.id}`);
        }
    }, [product, getColors]);

    const selectColor = async (color) => {
        const result = await getVariants(`/api/variants?product_id=${product.id}&color_id=${color.id}&per_page=1`);
        setVariantId(result?.items?.[0]?.id ?? null);
    };

    const visible = (products ?? []).filter((s) =>
        `${s.name} ${s.line.name}`.toLowerCase().includes(filter.toLowerCase())
    );

    // On a phone one step shows at a time; on a wide screen all three sit side by side.
    const step = variantId ? 'answer' : product ? 'color' : 'product';
    const shown = (name) => (step === name ? '' : 'hidden lg:block');

    useEffect(() => {
        if (window.matchMedia('(max-width: 1023px)').matches) window.scrollTo(0, 0);
    }, [step]);

    const back = () => {
        if (variantId) {
            setVariantId(null);
            return;
        }
        setShape(null);
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_1fr_1.1fr]">
            {step !== 'product' && (
                <button
                    onClick={back}
                    className="flex items-center gap-2 justify-self-start rounded-full bg-glaze-shell px-4 py-2 text-sm font-bold text-glaze-slate lg:hidden"
                >
                    <span aria-hidden="true">&larr;</span>
                    {step === 'answer' ? `Colors for ${product.name}` : 'All products'}
                </button>
            )}

            <section className={shown('product')}>
                <h2 className="mb-2 flex items-center gap-2 text-sm font-black uppercase tracking-wide">
                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-glaze-flame text-xs text-white">
                        1
                    </span>
                    What product is it?
                </h2>
                <input
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    placeholder="Narrow the list"
                    className="mb-2 w-full rounded-lg border-2 border-glaze-shell bg-white px-3 py-3 text-base focus:border-glaze-lagoon focus:outline-none lg:py-2 lg:text-sm"
                />
                <div className="overflow-y-auto rounded-2xl border-2 border-glaze-shell bg-white lg:max-h-[30rem]">
                    {visible.map((s) => (
                        <button
                            key={s.id}
                            onClick={() => setShape(s)}
                            className={`block w-full border-b border-glaze-shell/70 px-4 py-3.5 text-left text-base last:border-0 lg:py-2.5 lg:text-sm ${
                                product?.id === s.id
                                    ? 'bg-glaze-ink font-bold text-glaze-cream'
                                    : 'hover:bg-glaze-sun/15'
                            }`}
                        >
                            {s.name}
                            <span
                                className={`ml-2 text-xs ${
                                    product?.id === s.id ? 'text-glaze-cream/60' : 'text-glaze-slate'
                                }`}
                            >
                                {s.line.name}
                            </span>
                        </button>
                    ))}
                </div>
            </section>

            <section className={shown('color')}>
                <h2 className="mb-2 flex items-center gap-2 text-sm font-black uppercase tracking-wide">
                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-glaze-lagoon text-xs text-white">
                        2
                    </span>
                    What color is it?
                    {product && <span className="font-bold normal-case text-glaze-slate lg:hidden">{product.name}</span>}
                </h2>

                {product ? (
                    <>
                        <div className="overflow-y-auto rounded-2xl border-2 border-glaze-shell bg-white lg:max-h-[30rem]">
                            {(colors ?? []).map((c) => (
                                <button
                                    key={c.id}
                                    onClick={() => selectColor(c)}
                                    className="flex w-full items-center gap-3 border-b border-glaze-shell/70 px-4 py-3.5 text-left text-base last:border-0 hover:bg-glaze-sun/15 lg:py-2.5 lg:text-sm"
                                >
                                    <Swatch hex={c.hex} size="sm" />
                                    <span className="font-bold">{c.name}</span>
                                    <span className="ml-auto text-xs tabular-nums text-glaze-slate">
                                        {c.produced_label ?? 'years unknown'}
                                    </span>
                                </button>
                            ))}
                        </div>
                        <p className="mt-2 text-xs text-glaze-slate">
                            Colors repeat across eras. Where a name appears twice the production years tell them
                            apart; the backstamp settles it in the hand. Swatches are community approximations,
                            so match on the piece rather than the screen.
                        </p>
                    </>
                ) : (
                    <p className="rounded-2xl border-2 border-dashed border-glaze-shell px-4 py-8 text-center text-sm text-glaze-slate">
                        Pick a product first.
                    </p>
                )}
            </section>

            <section className={shown('answer')}>
                {variantId ? (
                    <VariantDetail variantId={variantId} />
                ) : (
                    <p className="rounded-2xl border-2 border-dashed border-glaze-shell px-4 py-8 text-center text-sm text-glaze-slate">
                        Rarity, value and whether you already own one will appear here.
                    </p>
                )}
            </section>
        </div>
    );
}
