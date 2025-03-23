"use strict";

import $ from 'jquery';
import _ from 'lodash';


export default {
    template: require('components/_listtable.pug').default,
    props: ['values', 'label'],
    data: function() {
        return {
        }
    },
    computed: {
    },
    methods: {
        round: function(value) {
            return _.round(value, 1);
        },
    },
};
