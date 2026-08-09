/**
 * Color swatch, drawn as a plate rim.
 *
 * Hex values are reference data and are only present when the owner has
 * supplied them in database/seed-data/color-hex.csv. Without one the swatch is
 * deliberately an obvious placeholder rather than an invented color.
 */
export default function Swatch({ hex, size = 'md' }) {
    const dimension = { sm: 'h-7 w-7', md: 'h-9 w-9', lg: 'h-16 w-16' }[size];

    if (!hex) {
        return (
            <span
                title="No swatch data supplied"
                className={`${dimension} shrink-0 rounded-full border-2 border-dashed border-glaze-slate/40 bg-glaze-shell`}
            />
        );
    }

    return (
        <span
            title={hex}
            style={{ backgroundColor: hex }}
            className={`rings ${dimension} shrink-0 rounded-full text-black/40 shadow-inner ring-1 ring-black/15`}
        />
    );
}
