require('./bootstrap');

import { createApp, h } from 'vue'
import { App, createInertiaApp } from '@inertiajs/inertia-vue3'
import MainLayouts from './Pages/Layouts/MainLayouts'
import VueProgressBar from "@aacassandra/vue3-progressbar";
import { ZiggyVue } from 'ziggy';
import { Ziggy } from './ziggy';
import { InertiaProgress } from '@inertiajs/progress'

import Highcharts from 'highcharts';
import VueChartkick from 'vue-chartkick'
// import 'chartkick/chart.js'

import InfiniteLoading from "v3-infinite-loading";
import "v3-infinite-loading/lib/style.css";
// import Select2Component
import Select2 from 'vue3-select2-component';
import VueCurrencyInput from 'vue-currency-input';


Chartkick.options = {
  colors: ["#b00", "#666"]
}


const options = {
  color: "#FFD700",
  failedColor: "#C62828",
  thickness: "7px",
  transition: {
    speed: "0.2s",
    opacity: "0.6s",
    termination: 300,
  },
  autoRevert: true,
  location: "top",
  inverse: false,
};


InertiaProgress.init({
  // The delay after which the progress bar will
  // appear during navigation, in milliseconds.
  delay: 250,

  // The color of the progress bar.
  color: '#29d',

  // Whether to include the default NProgress styles.
  includeCSS: true,

  // Whether the NProgress spinner will be shown.
  showSpinner: false,
})

createInertiaApp({
  resolve: name => {
    let page = require(`./Pages/${name}`).default
    if(page.layout == null){
        page.layout = MainLayouts
    }

    return page
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(VueProgressBar, options)
      .use(ZiggyVue, Ziggy)
      .use(VueChartkick, { adapter: Highcharts })
      .component("infinite-loading", InfiniteLoading)
      .component('Select2', Select2)
      .mount(el)
  },
})
