import { useEffect } from 'react';
import { useApi } from '../hooks/useApi';

export default function PostList() {
    const { data: posts, loading, error, get } = useApi();

    useEffect(() => {
        get('/api/posts');
    }, []);

    if (loading) return <p>Loading...</p>;
    if (error) return <p>{error}</p>;

    if (!posts || posts.length === 0) return <p>No posts yet.</p>;

    return (
        <div>
            <h2>Posts</h2>
            <ul>
                {posts.map((post) => (
                    <li key={post.id}>
                        <strong>{post.title}</strong>
                        <p>{post.body}</p>
                    </li>
                ))}
            </ul>
        </div>
    );
}
