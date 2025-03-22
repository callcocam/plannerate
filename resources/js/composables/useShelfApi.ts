import axios from 'axios';
import type { Section, Shelf } from '@/types/planogram';

export function useShelfApi() {
    
    const handleShelfDropAction = async (section: Section, shelf: Shelf, position: number, ordering: number) => {
        return await axios.put(route('sections.shelves.update', {
            section: section.id,
            shelf: shelf.id
        }), { position, ordering });
    };

    const moveShelf = async (oldSection: Section, shelfId: string, updatedShelf: Shelf, newSectionId: string) => {
        return await axios.put(route('sections.shelves.move', {
            shelf: shelfId
        }), {
            newSection: newSectionId,
            position: updatedShelf.position,
            ordering: updatedShelf.ordering,
            section_id: newSectionId
        });
    };

    const deleteShelf = async (section: Section, shelfId: string) => {
        return await axios.delete(route('sections.shelves.destroy', {
            section: section.id,
            shelf: shelfId
        }));
    };

    const copyShelf = async (section: Section, shelfId: string) => {
        return await axios.post(route('sections.shelves.copy', {
            section: section.id,
            shelf: shelfId
        }));
    };

    const createShelf = async (section: Section, shelfData: Partial<Shelf>) => {
        return await axios.post(`/sections/${section.id}/shelves`, shelfData);
    };

    return {
        handleShelfDropAction,
        moveShelf,
        deleteShelf,
        copyShelf,
        createShelf
    };
} 