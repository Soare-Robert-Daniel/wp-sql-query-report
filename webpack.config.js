const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    entry: {
        'dashboard': './src/dashboard/index.tsx',
    },
};