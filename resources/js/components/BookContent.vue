<template>
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">تنظیمات</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="text-center setting-table">
                        <tr>
                            <td>اندازه فونت</td>
                        </tr>
                        <tr>
                            <td>
                                <input type="range" class="form-range" min="1" max="3" step="0.1"
                                       v-model="setting.fontSize">
                            </td>
                        </tr>
                        <tr>
                            <td>فاصله خطوط</td>
                        </tr>
                        <tr>
                            <td>
                                <input type="range" class="form-range" min="1" max="5" step="0.5"
                                       v-model="setting.lineHeight">
                            </td>
                        </tr>
                        <tr>
                            <td>رنگ پس زمنیه</td>
                        </tr>
                        <tr>
                            <td>
                                <button class="btn-background-content" style="background: #FFFFFF"
                                        @click="changeColor('#FFFFFF')">متن
                                </button>
                                <button class="btn-background-content" style="background: #F7F0E6"
                                        @click="changeColor('#F7F0E6')">متن
                                </button>
                                <button class="btn-background-content" style="background: #333333;color: #FFFFFF;"
                                        @click="changeColor('#333333','#FFFFFF')">متن
                                </button>
                                <button class="btn-background-content" style="background: #F3F0E6"
                                        @click="changeColor('#F3F0E6')">متن
                                </button>
                                <button class="btn-background-content" style="background: #E0E0E0"
                                        @click="changeColor('#E0E0E0')">متن
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>پیش نمایش</td>
                        </tr>
                        <tr>
                            <td>
                                <div :style="showStyle">
                                    <p>الحمدللّه ربّ العالمین و صلّی اللّه علی سیّدنا محمّد و آله الطّیّبین
                                        الطّاهرین</p>
                                    <p>الحمدللّه ربّ العالمین و صلّی اللّه علی سیّدنا محمّد و آله الطّیّبین
                                        الطّاهرین</p>
                                    <p>الحمدللّه ربّ العالمین و صلّی اللّه علی سیّدنا محمّد و آله الطّیّبین
                                        الطّاهرین</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary" @click="applyStyle()">ذخیره</button>
                </div>
            </div>
        </div>
    </div>
    <SearchContent :writers="writers"></SearchContent>
    <div class="book-line-content" :style="applyStyleObj">
        <template v-for="item in contents">
            <div v-html="item.content" :id="'p_'+item.id" class="book_item" ref="items"></div>
        </template>
    </div>
    <div class="text-center mt-5" v-if="pageUrl!==null">
        <div class="d-flex justify-content-center" v-if="loading">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">در حال بارگذاری</span>
            </div>
        </div>

        <button @click="postRequest()" class="btn btn-outline-success mt-3" :disabled="loading">
            بارگذاری بیشتر
        </button>
    </div>

    <div class="tools" v-if="contents.length>0">
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exampleModal">
            تنظیمات
            <i class="fas fa-cog"></i>
        </button>
        <a v-if="download" :href="baseUrl+'/library/book/download/'+this.bookId" class="btn btn-secondary">
            دانلود کتاب
            <i class="fas fa-file-pdf"></i>
        </a>
        <button href="#" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#searchModal">
            جستجو
            <i class="fas fa-search"></i>
        </button>
        <!--        <span v-if="search.items.length>0">-->
        <!--            تعداد نتایج جستجو: {{ search.items.length }}-->
        <!--        </span>-->
    </div>
</template>
<script>
import SearchContent from './SearchContent.vue'
import {highlightSearchTarget} from '../searchTarget'

export default {
    components: {
        SearchContent
    },
    data() {
        return {
            loading: false,
            contents: [],
            baseUrl: window.location.origin,
            search: {
                items: [],
                keyword: null
            },
            currentPage: 1,
            total: null,
            setting: {
                lineHeight: 2,
                fontSize: 1,
                backgroundColor: '#ffffff',
                color: '#000000'
            },
            applyStyleObj: {},
            targetFocused: false,
            pageUrl: `${window.location.origin}/library/book/content/${this.bookId}?page=${this.initialPage}`
        }
    },
    methods: {
        applyStyle() {
            this.applyStyleObj = this.showStyle;
        },
        changeColor(bg, color = null) {
            this.setting.backgroundColor = bg;
            if (color !== null) {
                this.setting.color = color;
            } else {
                this.setting.color = '#000000';
            }
        },
        postRequest() {
            this.loading = true;
            axios.get(this.pageUrl)
                .then((response) => {
                    this.contents.push(...response.data.data);
                    this.total = response.data.total;
                    this.pageUrl = response.data['next_page_url'];
                    if (this.pageUrl !== null) {
                        this.postRequest();
                    }
                    this.focusSearchTarget();
                }).catch(err => {
                alert('متاسفانه خطایی رخ داده است');
                console.log(err);
            }).finally(() => {
                this.loading = false;
            });
        },
        focusSearchTarget() {
            if (this.targetFocused || !this.targetId) {
                return;
            }

            this.$nextTick(() => {
                const target = document.getElementById(`p_${this.targetId}`);

                if (!target) {
                    return;
                }

                highlightSearchTarget(target, this.targetQuery);
                target.classList.add('active_search');
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
                this.targetFocused = true;
            });
        },
    },
    mounted() {
        this.focusSearchTarget();
    },
    created() {
        this.applyStyle();
        if (this.bookContent.data?.length > 0) {
            this.contents = this.bookContent.data;
            this.total = this.bookContent.total;
            this.pageUrl = this.bookContent.next_page_url;

            if (this.pageUrl !== null) {
                this.postRequest();
            }
        } else {
            this.postRequest();
        }
    },
    computed: {
        showStyle() {
            const fontSize = this.setting.fontSize + 'rem';
            return {...this.setting, fontSize}
        }
    },
    props: {
        bookId: {
            type: Number,
            required: true,
        },
        download: {
            type: Number,
            default: 0,
        },
        bookContent: {
            type: Object,
            required: true,
        },
        writers: {
            type: Array,
            required: true,
        },
        targetId: {
            type: Number,
            default: null,
        },
        targetQuery: {
            type: String,
            default: null,
        },
        initialPage: {
            type: Number,
            default: 1,
        },
    },
}
</script>
