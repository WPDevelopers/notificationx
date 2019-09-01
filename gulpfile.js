var gulp = require('gulp'),
	sass = require('gulp-sass'),
	cleanCss = require('gulp-clean-css'),
	sourcemaps = require('gulp-sourcemaps');
var paths = {
	pAssets: 'public/assets/',
	pStyles: {
		src: 'public/assets/scss/*.scss',
		dest: 'public/assets/css/'
	}
};
gulp.task('pSass', function(){
	return gulp.src( paths.pStyles.src )
		.pipe(sourcemaps.init())
		.pipe( sass())
		.pipe(cleanCss())
		.pipe(sourcemaps.write(''))
		.pipe( gulp.dest(paths.pStyles.dest ));
});

gulp.task('watch', function() {
	gulp.watch( paths.pAssets + 'scss/**/*.scss',  gulp.series('pSass'));
});