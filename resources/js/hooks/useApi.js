import { useState, useCallback } from 'react';

export function useApi() {
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const request = useCallback(async (url, options = {}) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                ...options,
            });

            const json = await response.json();

            if (!response.ok || !json.success) {
                setError(json.message || 'Something went wrong');
                return null;
            }

            setData(json.data);
            return json.data;
        } catch (e) {
            setError('Network error');
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    const get = useCallback((url) => request(url), [request]);

    const post = useCallback((url, body) => {
        return request(url, { method: 'POST', body: JSON.stringify(body) });
    }, [request]);

    const put = useCallback((url, body) => {
        return request(url, { method: 'PUT', body: JSON.stringify(body) });
    }, [request]);

    const destroy = useCallback((url) => {
        return request(url, { method: 'DELETE' });
    }, [request]);

    return { data, error, loading, get, post, put, destroy };
}
