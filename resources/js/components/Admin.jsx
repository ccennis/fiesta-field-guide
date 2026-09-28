import { useState } from 'react';
import ColorAdmin from './ColorAdmin';
import Review from './Review';
import Products from './Products';
import Members from './Members';

const SECTIONS = [
    { key: 'colors', label: 'Colors' },
    { key: 'store', label: 'Store listings' },
    { key: 'products', label: 'Products' },
    { key: 'members', label: 'Members' },
];

/**
 * Everything only the admin can change, in one place.
 */
export default function Admin() {
    const [section, setSection] = useState('colors');

    return (
        <div className="space-y-4">
            <div className="flex gap-1 overflow-x-auto rounded-full bg-glaze-shell p-1">
                {SECTIONS.map((s) => (
                    <button
                        key={s.key}
                        onClick={() => setSection(s.key)}
                        className={`flex-1 whitespace-nowrap rounded-full px-3 py-2 text-sm font-bold transition ${
                            section === s.key ? 'bg-glaze-ink text-glaze-cream' : 'text-glaze-slate hover:text-glaze-ink'
                        }`}
                    >
                        {s.label}
                    </button>
                ))}
            </div>

            {section === 'colors' && <ColorAdmin />}
            {section === 'store' && <Review />}
            {section === 'products' && <Products />}
            {section === 'members' && <Members />}
        </div>
    );
}
