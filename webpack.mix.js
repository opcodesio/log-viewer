const mix = require('laravel-mix');
const webpack = require('webpack');
const path = require('path');

mix.options({
    terser: {
        terserOptions: {
            compress: {
                drop_console: true,
            },
        },
    },
})
    .setPublicPath('public')
    .js('resources/js/app.js', 'public')
    .vue()
    .sass('resources/css/app.scss', 'public')
    .version()
    // .copy('resources/img', 'public/img')
    .webpackConfig({
        resolve: {
            symlinks: false,
            alias: {
                '@': path.resolve(__dirname, 'resources/js/'),
            },
        },
        // plugins: [
        //     new webpack.IgnorePlugin({
        //         resourceRegExp: /^\.\/locale$/,
        //         contextRegExp: /moment$/,
        //     }),
        // ],
    });

mix.disableSuccessNotifications();
