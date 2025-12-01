const mix = require('laravel-mix');
const path = require('path');
const webpack = require('webpack');

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
    .copy('resources/img', 'public/img')
    .webpackConfig({
        resolve: {
            symlinks: false,
            alias: {
                'vue$': 'vue/dist/vue.runtime.esm-bundler.js',
                '@': path.resolve(__dirname, 'resources/js/'),
            },
        },
        plugins: [
            new webpack.DefinePlugin({
                __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
                __VUE_OPTIONS_API__: 'true',
                __VUE_PROD_DEVTOOLS__: 'false',
            }),
        ],
        // stats: {
        //     children: true,
        // }
    });

mix.disableSuccessNotifications();
