
// import { defineConfig } from 'vite';
const vite = require('vite');
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy'

export default vite.defineConfig({
    build: {
        manifest: true,
        // assetsDir: 'js',
        rtl: true,
        outDir: 'public/build/',
        cssCodeSplit: true,
        rollupOptions: {
            output: {
              assetFileNames: (css) => {
                if(css.name.split('.').pop() == 'css') {
                    return 'css/' + `[name]` + '.min.' + 'css';
                } else {
                    return 'icons/' + css.name;
                }
            },
                entryFileNames: 'js/' + `[name]` + `.js`,
            },
        },
      },
    plugins: [
        laravel(
            {
                input: [
                    'resources/scss/bootstrap.scss',
                    'resources/scss/icons.scss',
                    'resources/scss/app.scss',
                    'resources/scss/custom/views/datatables-filtros.scss',
                    'resources/scss/custom/components/select2-custom.scss',
                    'resources/js/components/yajra-datatable.js',
                    'resources/js/components/crud-modal.js',
                    'resources/scss/custom/views/public-reservas.scss',
                    'resources/js/pages/reservas-public.js'
                ],
                refresh: true,                
            }
        ),
         viteStaticCopy({
            targets: [
                {
                    src: 'resources/fonts',
                    dest: ''
                },
                {
                    src: 'resources/images',
                    dest: ''
                },
                {
                    src: 'resources/js',
                    dest: ''
                },
                {
                    src: 'resources/json',
                    dest: ''
                },
                {
                    src: 'resources/libs',
                    dest: ''
                },
            ]
         }),
    ],
});
