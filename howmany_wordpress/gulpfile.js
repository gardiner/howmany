'use strict';

const NAME = 'howmany';

var gulp = require('gulp');
var path = require('path');
var pug = require('gulp-pug');
var pug_compiler = require('pug');
var sass = require('gulp-sass')(require('sass'));
var webpackcompiler = require('webpack');
var webpack = require('webpack-stream');

var develop = process.env.prod != 'true';

gulp.task('scss', function() {
    return gulp.src('src/scss/**/*.scss')
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    .pipe(gulp.dest('css'));
});

gulp.task('pug', function() {
    return gulp.src(['src/pug/**/*.pug', '!src/pug/**/_*.pug'])
    .pipe(pug({pretty: true}))
    .pipe(gulp.dest('.'));
});

gulp.task('js', function() {
    return gulp.src('src/js/index.js')
    .pipe(webpack({
        resolve: {
            modules: [
                path.resolve('src/js'),
                path.resolve('src/pug'),
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
    .pipe(gulp.dest('js'));
});

gulp.task('compile', gulp.parallel('pug', 'scss', 'js'));

gulp.task('compile_prod', gulp.series(async function() {
    develop = false;
}, 'compile'));

gulp.task('watch', gulp.series('compile', async function() {
    gulp.watch(['src/scss/**/*.scss'], gulp.parallel('scss'));
    gulp.watch(['src/pug/**/*.pug'], gulp.parallel('pug', 'js'));
    gulp.watch(['src/js/**/*.js'], gulp.parallel('js'));
}));

gulp.task('default', gulp.parallel('watch'));
