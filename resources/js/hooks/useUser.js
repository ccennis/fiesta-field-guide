import { createContext, useContext } from 'react';

/**
 * The person signed in, as /api/me returns them: name, role, whether they are
 * the admin, and the admin's name for invited friends, who may view the
 * admin's collection.
 */
export const UserContext = createContext(null);

export function useUser() {
    return useContext(UserContext);
}
