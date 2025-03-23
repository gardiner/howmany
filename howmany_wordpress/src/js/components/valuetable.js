"use strict";

import $ from 'jquery';
import _ from 'lodash';


export default {
    template: require('components/_valuetable.pug').default,
    props: ['values', 'label'],
    data: function() {
        return {
        }
    },
    computed: {
        limited: function() {
            return _.slice(this.values, 0, 25);
        },
    },
    methods: {
        round: function(value) {
            return _.round(value, 1);
        },
    },
};
