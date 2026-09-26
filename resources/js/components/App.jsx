import { useEffect, useState } from 'react';
import { UNAUTHENTICATED, useApi } from '../hooks/useApi';
import Identify from './Identify';
import Collection from './Collection';
import Products from './Products';
import Wishlist from './Wishlist';
import Review from './Review';
import Login from './Login';
import InstallHint from './InstallHint';

const TABS = [
    { key: 'identify', label: 'Identify a piece', short: 'Identify', mobile: true },
    { key: 'collection', label: 'Collection', short: 'Collection', mobile: true },
    { key: 'wishlist', label: 'Wishlist', short: 'Wishlist', mobile: true },
    { key: 'review', label: 'Review', short: 'Review', mobile: true },
    { key: 'products', label: 'Products', short: 'Products', mobile: false },
];

const isPhone = () => window.matchMedia('(max-width: 767px)').matches;

function Stat({ value, label, tone }) {
    return (
        <div className="flex items-baseline gap-2">
            <span className={`text-lg font-bold tabular-nums md:text-2xl ${tone}`}>{value}</span>
            <span className="text-[10px] uppercase tracking-wide text-glaze-cream/70 md:text-xs">{label}</span>
        </div>
    );
}

export default function App() {
    const [user, setUser] = useState(undefined);
    const [tab, setTab] = useState(() => (isPhone() ? 'identify' : 'collection'));
    const { get: getMe } = useApi();
    const { data: summary, get } = useApi();
    const { post } = useApi();

    useEffect(() => {
        getMe('/api/me').then((me) => setUser(me ?? null));

        const signedOut = () => setUser(null);
        window.addEventListener(UNAUTHENTICATED, signedOut);
        return () => window.removeEventListener(UNAUTHENTICATED, signedOut);
    }, [getMe]);

    useEffect(() => {
        if (user) get('/api/collection/summary');
    }, [user, get]);

    const signOut = async () => {
        await post('/api/logout');
        setUser(null);
    };

    if (user === undefined) return null;
    if (user === null) return <Login onSignedIn={setUser} />;

    return (
        <div className="min-h-screen pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:pb-0">
            <header className="relative overflow-hidden bg-glaze-ink pt-[env(safe-area-inset-top)] text-glaze-cream">
                <div
                    className="rings pointer-events-none absolute -right-24 -top-40 h-96 w-96 text-glaze-sun opacity-60"
                    aria-hidden="true"
                />
                <div
                    className="rings pointer-events-none absolute -bottom-56 left-1/3 hidden h-80 w-80 text-glaze-lagoon opacity-40 md:block"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-[1600px] px-4 py-4 md:px-6 md:py-6">
                    <div className="flex flex-wrap items-end justify-between gap-4 md:gap-6">
                        <div>
                            <h1 className="flex items-center gap-3 text-2xl font-black tracking-tight md:text-3xl">
                                <img
                                    src="/icons/logo.png"
                                    alt=""
                                    className="h-10 w-10 rounded-xl bg-glaze-cream p-0.5 md:h-12 md:w-12"
                                />
                                <span>
                                    Fiesta<span className="text-glaze-sun"> Field Guide</span>
                                </span>
                            </h1>
                            <p className="mt-1 hidden text-sm text-glaze-cream/70 md:block">
                                Homer Laughlin, 1936 to now. Fiesta, Riviera and Harlequin.
                            </p>
                        </div>

                        <button
                            onClick={signOut}
                            className="order-last text-xs font-bold text-glaze-cream/60 hover:text-glaze-cream md:order-none"
                        >
                            Sign out
                        </button>

                        {summary && (
                            <div className="flex w-full flex-wrap gap-x-5 gap-y-1 md:w-auto md:gap-x-8 md:gap-y-2">
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
                                <Stat
                                    value={summary.wishlist_open}
                                    label={`wished for · ${summary.grails_open} grail${summary.grails_open === 1 ? '' : 's'}`}
                                    tone="text-glaze-cream"
                                />
                            </div>
                        )}
                    </div>

                    <nav className="mt-6 hidden gap-2 md:flex">
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

            <InstallHint />

            <main className="mx-auto max-w-[1600px] px-4 py-4 md:px-6 md:py-6">
                {tab === 'identify' && <Identify />}
                {tab === 'collection' && <Collection />}
                {tab === 'wishlist' && <Wishlist />}
                {tab === 'review' && <Review />}
                {tab === 'products' && <Products />}
            </main>

            <nav className="fixed inset-x-0 bottom-0 z-10 flex border-t-2 border-glaze-shell bg-glaze-cream pb-[env(safe-area-inset-bottom)] md:hidden">
                {TABS.filter((t) => t.mobile).map((t) => (
                    <button
                        key={t.key}
                        onClick={() => setTab(t.key)}
                        className={`flex-1 py-4 text-sm font-bold ${
                            tab === t.key ? 'text-glaze-ink' : 'text-glaze-slate/70'
                        }`}
                    >
                        <span
                            className={`mx-auto mb-1 block h-1 w-8 rounded-full ${tab === t.key ? 'bg-glaze-sun' : 'bg-transparent'}`}
                        />
                        {t.short}
                    </button>
                ))}
            </nav>
        </div>
    );
}
