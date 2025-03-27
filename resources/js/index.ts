import './../css/app.css';
import type { App } from 'vue'
import Planerate from './App.vue';

interface PluginOptions {
    [key: string]: any
}

interface ComponentModule {
    default: any
}

const install = (app: App, options: PluginOptions = {}) => {
    const componentRegistry: string[] = [];
    app.component('Planerate', Planerate);
    app.component('v-planerate', Planerate); 
    //   Object.entries(import.meta.glob<ComponentModule>('./fields/**/*.vue', { eager: true }))
    //     .forEach(([path, definition]) => {
    //       const originalName = path.split('/').pop()?.replace(/\.\w+$/, '') || '';
    //       console.log(originalName);
    //       //Criar nome do componente em kebab-case
    //       const kebabName = originalName.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    //       console.log(('sc-').concat(kebabName));
    //       if (componentRegistry.indexOf(originalName) === -1) {
    //         app.component(('sc-').concat(kebabName), definition.default);
    //         app.component(originalName, definition.default);
    //       }
    //       componentRegistry.push(originalName);
    //     });
}

export default {
    install
}