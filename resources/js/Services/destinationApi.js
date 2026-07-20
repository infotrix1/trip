import axios from 'axios';

export const destinationApi = {
    async list() {
        const { data } = await axios.get('/api/destinations');
        return data;
    },

    async create(payload) {
        const { data } = await axios.post('/api/destinations', payload);
        return data;
    },

    async remove(id) {
        await axios.delete(`/api/destinations/${id}`);
    },

    async reorder(destinationIds) {
        await axios.put('/api/destinations/reorder', {
            destination_ids: destinationIds,
        });
    },
};
