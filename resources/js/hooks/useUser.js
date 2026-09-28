import { createContext, useContext } from 'react';

/**
 * The person signed in, as /api/me returns them: name, role, whether they are
 * the owner, and the owner's name for testers viewing the owner's collection.
 */
export const UserContext = createContext(null);

export function useUser() {
    return useContext(UserContext);
}
