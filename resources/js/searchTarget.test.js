// @vitest-environment jsdom

import {describe, expect, it} from 'vitest'
import {highlightSearchTarget, normalizeSearchText} from './searchTarget'

describe('search target highlighting', () => {
    it('normalizes Arabic and Persian variants and diacritics', () => {
        expect(normalizeSearchText('بَقِيَّةُ اللّه')).toBe(
            normalizeSearchText('بقیه الله')
        )
        expect(normalizeSearchText('كِتاب يكى')).toBe(
            normalizeSearchText('کتاب یکی')
        )
    })

    it('highlights a normalized match without replacing nested HTML', () => {
        const target = document.createElement('div')
        target.innerHTML = '<p>حضرت <strong>بَقِيَّةُ</strong> اللّه در زمین</p>'

        const count = highlightSearchTarget(target, 'بقیه الله')

        expect(count).toBe(1)
        expect(target.querySelector('strong')).not.toBeNull()
        expect(target.querySelectorAll('mark.search-target-match')).toHaveLength(2)
        expect(target.textContent).toContain('حضرت بَقِيَّةُ اللّه در زمین')
    })

    it('highlights every occurrence and leaves attributes unchanged', () => {
        const target = document.createElement('div')
        target.innerHTML = '<p title="بقیه الله">بقیة الله و بقیه الله</p>'

        const count = highlightSearchTarget(target, 'بقیه الله')

        expect(count).toBe(2)
        expect(target.querySelector('p').getAttribute('title')).toBe('بقیه الله')
        expect(target.querySelectorAll('mark.search-target-match')).toHaveLength(2)
    })

    it('does nothing for an empty query', () => {
        const target = document.createElement('div')
        target.textContent = 'بقیه الله'

        expect(highlightSearchTarget(target, '  ')).toBe(0)
        expect(target.querySelector('mark')).toBeNull()
    })
})
