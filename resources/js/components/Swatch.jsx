/**
 * Color swatch, drawn as a plate with rings like the logo's: a band on the rim
 * and one at the edge of the well.
 *
 * Hex values are reference data. Without one the swatch is deliberately an
 * obvious placeholder rather than an invented color.
 */
const DIMENSION = { xs: 'h-5 w-5', sm: 'h-7 w-7', md: 'h-9 w-9', lg: 'h-16 w-16' };

// Ring positions as a share of the plate's radius, and how thick each is.
// Small swatches get two bolder rings, since four thin ones smear together.
const RINGS = {
    small: { at: [0.82, 0.5], width: 0.07 },
    large: { at: [0.9, 0.8, 0.7, 0.5], width: 0.04 },
};

/**
 * White rings read on most glazes, but vanish on White, Ivory, Linen and the
 * pale yellows, so those get soft dark rings instead.
 */
function ringColor(hex) {
    const [r, g, b] = [1, 3, 5].map((i) => {
        const c = parseInt(hex.slice(i, i + 2), 16) / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;

    return luminance > 0.6 ? 'rgba(0, 0, 0, 0.14)' : 'rgba(255, 255, 255, 0.5)';
}

function plateRings(hex, size) {
    const { at, width } = size === 'xs' || size === 'sm' ? RINGS.small : RINGS.large;
    const color = ringColor(hex);
    const half = (width * 100) / 2;
    // A short fade on each edge, so a ring a pixel or two wide draws as a
    // smooth circle rather than a dashed one.
    const soft = 3;

    const stops = at
        .slice()
        .sort((a, b) => a - b)
        .flatMap((share) => {
            const middle = share * 100;
            return [
                `transparent ${middle - half - soft}%`,
                `${color} ${middle - half}%`,
                `${color} ${middle + half}%`,
                `transparent ${middle + half + soft}%`,
            ];
        });

    return `radial-gradient(circle closest-side, transparent 0%, ${stops.join(', ')})`;
}

export default function Swatch({ hex, size = 'md' }) {
    const dimension = DIMENSION[size];

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
            style={{ backgroundColor: hex, backgroundImage: plateRings(hex, size) }}
            className={`${dimension} shrink-0 rounded-full shadow-inner ring-1 ring-black/15`}
        />
    );
}
