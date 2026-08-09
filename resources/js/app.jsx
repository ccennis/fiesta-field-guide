import './bootstrap';
import { createRoot } from 'react-dom/client';
import PostList from './components/PostList';

const container = document.getElementById('app');
const root = createRoot(container);
root.render(<PostList />);
