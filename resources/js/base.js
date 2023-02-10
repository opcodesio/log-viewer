import moment from 'moment-timezone';

export default {
    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime) {
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60);
        },

        /**
         * Format the given date with respect to timezone.
         */
        formatDateIso(date) {
            return moment(date).add(new Date().getTimezoneOffset() / 60);
        },

        /**
         * Convert to human-readable timestamp.
         */
        readableTimestamp(timestamp) {
            return this.formatDate(timestamp).format('YYYY-MM-DD HH:mm:ss');
        },
    },
};
