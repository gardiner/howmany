"use strict";

import _ from 'lodash';

import components from 'components';


function path2componentname(path) {
    var parts = _.split(path, '/'),
        name = _.join(parts, '_');

    //remove unnecessary name parts
    _.each(['.js', '.ts', 'components_', '_index'], function(removable) {
        name = _.replace(name, removable, '');
    });

    return name;
}


function init() {
    //auto-load all available components (utilizes webpack context which is evaluated at compile time)
    const component_context = require.context('components', true, /^components.*\.(js|ts)$/);

    _.each(component_context.keys(), function(key, index) {
        var name = path2componentname(key),
            component = component_context(key).default,
            alias = _.get(component, 'alias');

        _.each([name, alias], function(name) {
            if (name && _.has(components, name)) {
                throw "Component with name " + name + " already exists!";
            }
        });

        if (_.includes(['components/autoloader.js', 'components/index.js'], key)) {
            return;
        }

        if (alias) {
            components[alias] = component;
        }

        components[name] = component;
    });


    //make all components and filters available to each other
    _.forEach(components, function(component) {
        component.components = _.assign({}, components, component.components);
    });
}

export default {
    init,
};
