import './bootstrap';

import {createApp} from 'vue'
import Welcome from './components/Welcome.vue'
import BookContent from './components/BookContent.vue'


const app = createApp({})
app.component('welcome', Welcome)
app.component('book-content', BookContent)

const root = document.querySelector('#app')

if (root?.querySelector('welcome, book-content')) {
    app.mount(root)
}
