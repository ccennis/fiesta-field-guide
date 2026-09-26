import { useState } from 'react';

const DISMISSED = 'fiesta:install-hint-dismissed';

function shouldShow() {
    const iOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const installed = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;

    try {
        return iOS && !installed && localStorage.getItem(DISMISSED) !== '1';
    } catch {
        return iOS && !installed;
    }
}

/**
 * iOS never offers to install a web app, so this says how. It also matters for
 * later offline work: iOS keeps a home screen app's stored data far longer
 * than a browser tab's.
 */
export default function InstallHint() {
    const [visible, setVisible] = useState(shouldShow);

    if (!visible) return null;

    const dismiss = () => {
        try {
            localStorage.setItem(DISMISSED, '1');
        } catch {
            // Private browsing can refuse storage; hiding for this visit is enough.
        }
        setVisible(false);
    };

    return (
        <div className="mx-4 mt-4 flex items-start gap-3 rounded-2xl border-2 border-glaze-sun bg-white px-4 py-3 text-sm md:hidden">
            <p className="text-glaze-ink">
                <strong>Put this on your home screen.</strong> Tap the Share button, then <em>Add to Home Screen</em>.
            </p>
            <button onClick={dismiss} className="ml-auto shrink-0 font-bold text-glaze-slate" aria-label="Dismiss">
                Got it
            </button>
        </div>
    );
}
