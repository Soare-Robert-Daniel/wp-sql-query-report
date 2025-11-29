const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    entry: {
        'dashboard': './admin/js/src/dashboard/index.tsx',
    },
};