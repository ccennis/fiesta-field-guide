import { useEffect, useState } from 'react';
import { useApi } from '../hooks/useApi';
import Identify from './Identify';
import Collection from './Collection';
import Products from './Products';

const TABS = [
    { key: 'identify', label: 'Identify a piece' },
    { key: 'collection', label: 'Collection' },
    { key: 'products', label: 'Products' },
];

function Stat({ value, label, tone }) {
    return (
        <div className="flex items-baseline gap-2">
            <span className={`text-2xl font-bold tabular-nums ${tone}`}>{value}</span>
            <span className="text-xs uppercase tracking-wide text-glaze-cream/70">{label}</span>
        </div>
    );
}

export default function App() {
    const [tab, setTab] = useState('collection');
    const { data: summary, get } = useApi();

    useEffect(() => {
        get('/api/collection/summary');
    }, [get]);

    return (
        <div className="min-h-screen">
            <header className="relative overflow-hidden bg-glaze-ink text-glaze-cream">
                <div
                    className="rings pointer-events-none absolute -right-24 -top-40 h-96 w-96 text-glaze-sun opacity-60"
                    aria-hidden="true"
                />
                <div
                    className="rings pointer-events-none absolute -bottom-56 left-1/3 h-80 w-80 text-glaze-lagoon opacity-40"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-[1600px] px-6 py-6">
                    <div className="flex flex-wrap items-end justify-between gap-6">
                        <div>
                            <h1 className="text-3xl font-black tracking-tight">
                                Fiesta<span className="text-glaze-sun"> Field Guide</span>
                            </h1>
                            <p className="mt-1 text-sm text-glaze-cream/70">
                                Homer Laughlin, 1936 to now. Fiesta, Riviera and Harlequin.
                            </p>
                        </div>

                        {summary && (
                            <div className="flex flex-wrap gap-x-8 gap-y-2">
                                <Stat value={summary.holdings} label="pieces" tone="text-glaze-sun" />
                                <Stat
                                    value={`$${summary.estimated_value.toLocaleString()}`}
                                    label="estimated"
                                    tone="text-glaze-lagoon"
                                />
                                <Stat
                                    value={summary.variants_confirmed}
                                    label={`of ${summary.variants_total.toLocaleString()} verified`}
                                    tone="text-glaze-flame"
                                />
                            </div>
                        )}
                    </div>

                    <nav className="mt-6 flex gap-2">
                        {TABS.map((t) => (
                            <button
                                key={t.key}
                                onClick={() => setTab(t.key)}
                                className={`rounded-full px-5 py-2 text-sm font-bold transition ${
                                    tab === t.key
                                        ? 'bg-glaze-sun text-glaze-ink'
                                        : 'bg-white/10 text-glaze-cream hover:bg-white/20'
                                }`}
                            >
                                {t.label}
                            </button>
                        ))}
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-[1600px] px-6 py-6">
                {tab === 'identify' && <Identify />}
                {tab === 'collection' && <Collection />}
                {tab === 'products' && <Products />}
            </main>
        </div>
    );
}
