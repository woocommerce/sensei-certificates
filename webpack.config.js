const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const files = {
	'js/admin': 'js/admin.js',
	'js/course': 'js/course.js',
	'css/admin': 'css/admin.scss',
	'css/frontend': 'css/frontend.scss',
	'css/blocks': 'blocks/view-certificate/editor.scss',
	'blocks/view-certificate/index': 'blocks/view-certificate/index.js',
};

const baseDist = 'assets/dist/';

Object.keys( files ).forEach( function ( key ) {
	files[ key ] = path.resolve( './assets', files[ key ] );
} );

module.exports = {
	...defaultConfig,
	entry: files,
	output: {
		path: path.resolve( '.', baseDist ),
	},
};
