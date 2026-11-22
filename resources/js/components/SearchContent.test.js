// @vitest-environment jsdom

import {afterEach, describe, expect, test, vi} from 'vitest'
import {createApp, nextTick} from 'vue'
import SearchContent from './SearchContent.vue'

async function mountAndSearch(responseData) {
    document.body.innerHTML = '<div id="app"></div>'
    globalThis.axios = {
        post: vi.fn().mockResolvedValue({data: responseData}),
    }

    const app = createApp(SearchContent, {books: [], writers: []})
    const root = app.mount('#app')
    const input = document.querySelector('input')
    input.value = 'بقیه الله'
    input.dispatchEvent(new Event('input'))
    document.querySelector('form').dispatchEvent(
        new Event('submit', {bubbles: true, cancelable: true})
    )

    await new Promise((resolve) => setTimeout(resolve, 0))
    await nextTick()

    return {app, root}
}

afterEach(() => {
    document.body.innerHTML = ''
    delete globalThis.axios
})

describe('SearchContent', () => {
    test('renders highlighted fields and links to the exact matched query', async () => {
        const {app} = await mountAndSearch([
            {
                book: {
                    id: 47,
                    title: 'کتاب <mark class="search-result">هدف</mark>',
                },
                search: [
                    {
                        id: 15353,
                        book_id: 47,
                        content: 'متن <mark class="search-result">الله</mark>',
                        matched_query: 'بقیه الله',
                    },
                ],
            },
        ])

        expect(document.querySelector('h3 mark')?.textContent).toBe('هدف')
        expect(document.querySelector('li mark')?.textContent).toBe('الله')

        const link = new URL(document.querySelector('li a').href)
        expect(link.pathname).toBe('/library/book/47')
        expect(link.searchParams.get('p')).toBe('15353')
        expect(link.searchParams.get('q')).toBe('بقیه الله')
        expect(link.searchParams.has('count')).toBe(false)

        app.unmount()
    })

    test('shows an explicit message when the search has no results', async () => {
        const {app} = await mountAndSearch([])

        expect(document.body.textContent).toContain('نتیجه‌ای پیدا نشد')

        app.unmount()
    })
})
