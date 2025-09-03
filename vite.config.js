import { defineConfig } from 'vite'
import { resolve } from 'path'
import legacy from '@vitejs/plugin-legacy'

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11']
    })
  ],
  
  build: {
    outDir: 'assets/build',
    emptyOutDir: true,
    
    rollupOptions: {
      input: {
        'frontend': resolve(__dirname, 'assets/src/js/frontend.js'),
        'admin': resolve(__dirname, 'assets/src/js/admin.js'),
        'elementor-widgets': resolve(__dirname, 'assets/src/js/elementor-widgets.js'),
        'frontend.css': resolve(__dirname, 'assets/src/css/frontend.css'),
        'admin.css': resolve(__dirname, 'assets/src/css/admin.css'),
        'elementor-widgets.css': resolve(__dirname, 'assets/src/css/elementor-widgets.css'),
        'animations.css': resolve(__dirname, 'assets/src/css/animations.css')
      },
      
      output: {
        entryFileNames: '[name].[hash].js',
        chunkFileNames: '[name].[hash].js',
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.')
          const extType = info[info.length - 1]
          
          if (/\.(css)$/.test(assetInfo.name)) {
            return `[name].[hash].css`
          }
          
          if (/\.(png|jpe?g|gif|svg|webp|ico)$/.test(assetInfo.name)) {
            return `images/[name].[hash].[ext]`
          }
          
          if (/\.(woff2?|eot|ttf|otf)$/.test(assetInfo.name)) {
            return `fonts/[name].[hash].[ext]`
          }
          
          return `[name].[hash].[ext]`
        }
      },
      
      external: [
        'jquery',
        'wp',
        'elementor'
      ]
    },
    
    manifest: true,
    
    // Generate source maps in development
    sourcemap: process.env.NODE_ENV === 'development'
  },
  
  css: {
    postcss: {
      plugins: [
        require('autoprefixer')
      ]
    },
    preprocessorOptions: {
      scss: {
        additionalData: `@import "assets/src/css/_variables.scss";`
      }
    }
  },
  
  server: {
    host: 'localhost',
    port: 3000,
    open: false,
    cors: true
  },
  
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development')
  }
})