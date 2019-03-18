var gulp      = require( 'gulp' );
var wpPot     = require( 'gulp-wp-pot' );
var sort      = require( 'gulp-sort' );
var zip       = require( 'gulp-zip' );

var paths = {
    packageContents: [
        'admin/**/*',
        'assets/**/*',
        'classes/**/*',
        'lang/**/*',
        'lib/**/*',
        'templates/**/*',
        'woo-includes/**/*',
        'changelog.txt',
        'LICENSE',
        'README.md',
        'templates/**/*',
		'woothemes-sensei-certificates.php',
		'sensei-certificates-functions.php',
    ],
    packageDir: 'build/sensei-certificates',
    packageZip: 'build/sensei-certificates.zip'
};

gulp.task( 'pot', gulp.series( function() {
    return gulp.src( [ '**/**.php', '!node_modules/**', '!build/**' ] )
        .pipe( sort() )
        .pipe( wpPot( {
            domain: 'sensei-certificates'
        } ) )
        .pipe( gulp.dest( 'lang/sensei-certificates.pot' ) );
} ) );

gulp.task( 'copy-package', function() {
    return gulp.src( paths.packageContents, { base: '.' } )
        .pipe( gulp.dest( paths.packageDir ) );
} );

gulp.task( 'zip-package', function() {
    return gulp.src( paths.packageDir + '/**/*', { base: paths.packageDir + '/..' } )
        .pipe( zip( paths.packageZip ) )
        .pipe( gulp.dest( '.' ) );
} );

gulp.task( 'package', gulp.series( 'copy-package', 'zip-package' ) );
