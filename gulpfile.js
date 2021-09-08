'use strict';

const NAME = 'howmany';

var gulp = require('gulp');
var path = require('path');
var pug = require('gulp-pug');
var pug_compiler = require('pug');
var sass = require('gulp-sass')(require('node-sass'));
var webpackcompiler = require('webpack');
var webpack = require('webpack-stream');
var webserver = require('gulp-webserver');

var develop = process.env.prod != 'true';

gulp.task('scss', function() {
    return gulp.src('howmany_wordpress/src/scss/**/*.scss')
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    .pipe(gulp.dest('howmany_wordpress/css'));
});

gulp.task('pug', function() {
    return gulp.src(['howmany_wordpress/src/pug/**/*.pug', '!howmany_wordpress/src/pug/**/_*.pug'])
    .pipe(pug({pretty: true}))
    .pipe(gulp.dest('howmany_wordpress'));
});

gulp.task('js', function() {
    return gulp.src('howmany_wordpress/src/js/' + NAME + '.js')
    .pipe(webpack({
        resolve: {
            modules: [
                path.resolve('howmany_wordpress/src/js'),
                path.resolve('howmany_wordpress/src/pug'),
                path.resolve('node_modules'),
            ],
            alias: {
                vue: !develop ? 'vue/dist/vue.min' : 'vue/dist/vue',
            }
        },
        module: {
            rules: [
                {
                    test: /\.pug$/i,
                    loader: 'html-loader',
                    options: {
                        preprocessor: function(c) {
                            return pug_compiler.compile(c, {
                                pretty: false,
                            })({});
                        }
                    }
                }
            ],
        },
        mode: !develop ? 'production' : 'development',
        output: {filename: NAME + '.all.js'}
    }, webpackcompiler))
    .pipe(gulp.dest('howmany_wordpress/js'));
});

gulp.task('compile', gulp.parallel('pug', 'scss', 'js'));

gulp.task('compile_prod', gulp.series(async function() {
    develop = false;
}, 'compile'));

gulp.task('watch', gulp.series('compile', async function() {
    gulp.watch(['howmany_wordpress/src/scss/**/*.scss'], gulp.parallel('scss'));
    gulp.watch(['howmany_wordpress/src/pug/**/*.pug'], gulp.parallel('pug'));
    gulp.watch(['howmany_wordpress/src/js/**/*.js'], gulp.parallel('js'));

    gulp.src(['.'])
    .pipe(webserver({
        livereload: true,
        open: true,
        port: 8081,
    }));
}));

gulp.task('default', gulp.parallel('watch'));
