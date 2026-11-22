const DIACRITICS = /[\u0610-\u061a\u064b-\u065f\u0670\u06d6-\u06ed\u0640]/u
const WHITESPACE = /\s/u
const CHARACTER_VARIANTS = {
    'ي': 'ی',
    'ى': 'ی',
    'ئ': 'ی',
    'ك': 'ک',
    'ة': 'ه',
    'ۀ': 'ه',
}

function normalizeCharacter(character) {
    if (DIACRITICS.test(character)) {
        return ''
    }

    if (WHITESPACE.test(character)) {
        return ' '
    }

    return CHARACTER_VARIANTS[character] ?? character
}

export function normalizeSearchText(value) {
    let normalized = ''

    for (const character of String(value ?? '')) {
        const next = normalizeCharacter(character)

        if (next === ' ' && (normalized === '' || normalized.endsWith(' '))) {
            continue
        }

        normalized += next
    }

    return normalized.trim()
}

function searchableTextMap(target) {
    const walker = document.createTreeWalker(target, NodeFilter.SHOW_TEXT)
    const map = []
    let normalized = ''
    let node

    while ((node = walker.nextNode())) {
        let offset = 0

        for (const character of node.nodeValue) {
            const next = normalizeCharacter(character)

            if (next === '') {
                offset += character.length
                continue
            }

            if (next === ' ' && (normalized === '' || normalized.endsWith(' '))) {
                offset += character.length
                continue
            }

            normalized += next
            map.push({
                node,
                offset,
                length: character.length,
            })
            offset += character.length
        }
    }

    return {normalized: normalized.trimEnd(), map}
}

function matchingSegments(map, start, length) {
    const characters = map.slice(start, start + length)
    const segments = []

    for (const character of characters) {
        const last = segments[segments.length - 1]

        if (last?.node === character.node) {
            last.end = character.offset + character.length
        } else {
            segments.push({
                node: character.node,
                start: character.offset,
                end: character.offset + character.length,
            })
        }
    }

    return segments
}

export function highlightSearchTarget(target, query) {
    const needle = normalizeSearchText(query)

    if (!target || needle === '') {
        return 0
    }

    const {normalized, map} = searchableTextMap(target)
    const matches = []
    let position = 0

    while ((position = normalized.indexOf(needle, position)) !== -1) {
        matches.push(matchingSegments(map, position, needle.length))
        position += needle.length
    }

    const segments = matches.flat().reverse()

    for (const segment of segments) {
        const range = document.createRange()
        range.setStart(segment.node, segment.start)
        range.setEnd(segment.node, segment.end)

        const mark = document.createElement('mark')
        mark.className = 'search-target-match'
        range.surroundContents(mark)
    }

    return matches.length
}
