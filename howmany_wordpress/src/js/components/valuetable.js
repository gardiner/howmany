"use strict";

import $ from 'jquery';
import _ from 'lodash';


const LIMITED_LENGTH = 20;


export default {
    template: require('components/_valuetable.pug').default,
    props: ['values', 'label'],
    data: function() {
        return {
            is_expanded: false,
        };
    },
    computed: {
        is_expandable: function() {
            return _.size(this.values) > LIMITED_LENGTH;
        },
        limited: function() {
            return this.is_expanded ? this.values : _.slice(this.values, 0, LIMITED_LENGTH);
        },
    },
    methods: {
        round: function(value) {
            return _.round(value, 1);
        },
    },
};
