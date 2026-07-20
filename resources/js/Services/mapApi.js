import axios from 'axios';

export const mapApi = {
    async search(query, countryCode = 'ng') {
        const { data } = await axios.get('/api/map/search', {
            params: { query, country_code: countryCode },
        });
        return data;
    },

    async reverse(latitude, longitude) {
        const { data } = await axios.get('/api/map/reverse', {
            params: { latitude, longitude },
        });
        return data;
    },

    async pointsOfInterest(latitude, longitude) {
        const { data } = await axios.get('/api/map/points-of-interest', {
            params: { latitude, longitude },
        });
        return data;
    },
};
