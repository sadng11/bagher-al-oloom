<template>

    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">جستجو</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="row" @submit.prevent="search">
                        <div class="col-12">
                            <select class="form-control" v-model="writerId">
                                <option value="-1"> همه مولفین</option>
                                <option v-for="(item,index) in writers" :value="item.id" :key="'w_'+index">
                                    {{ item.name }} {{ item.nickname }}
                                </option>
                            </select>
                        </div>
                        <div class="col-9 mt-2">
                            <input class="form-control" type="text" v-model="keyword" placeholder="عبارت مورد نظر">
                        </div>
                        <div class="col-3 text-start mt-2">
                            <button :disabled="loading || keyword == null || keyword === ''" type="submit"
                                    class="btn btn-outline-success">
                                جستجو
                                <span v-if="loading" class="spinner-grow spinner-grow-sm" role="status"
                                      aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                    <div class="search-content" v-if="loading && Number(writerId) === -1">
                        با توجه به اینکه جستجو در تمامی کتب نویسندگان صورت می گیرد، ممکن است مدتی طولانی باشد.<br>
                        <div class="text-center">لطفا صبور باشید...</div>
                    </div>
                    <div v-if="validationMessage" class="alert alert-warning mt-3">
                        {{ validationMessage }}
                    </div>
                    <div v-if="errorMessage" class="alert alert-danger mt-3">
                        {{ errorMessage }}
                    </div>
                    <div
                        v-if="searched && !loading && !errorMessage && contents.length === 0"
                        class="alert alert-info mt-3"
                    >
                        نتیجه‌ای پیدا نشد.
                    </div>
                    <div class="search-content">
                        <div v-for="item in contents" :key="'book_' + item.book.id">
                            <div class="search-item">
                                <h3 v-html="item.book.title"></h3>
                                <ul class="search-result-ul">
                                    <template v-for="line in item.search" :key="'line_' + line.id">
                                        <li>
                                            <a :href="resultUrl(item.book.id, line)"
                                               v-html="line.content"></a>
                                            <hr>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>


    <!--    <select v-model="selectedBooks">-->
    <!--        <option :value="-1">همه مولفین</option>-->
    <!--        <option v-for="item in books" :value="item.id">{{ item.title }}</option>-->
    <!--    </select>-->


</template>
<script>
export default {
    data() {
        return {
            loading: false,
            contents: [],
            baseUrl: window.location.origin,
            keyword: null,
            selectedBooks: null,
            writerId: -1,
            searched: false,
            validationMessage: '',
            errorMessage: ''
        }
    },
    props: ['books', 'writers'],
    methods: {
        search() {
            const keyword = (this.keyword ?? '').trim()

            this.validationMessage = ''
            this.errorMessage = ''

            if (keyword.length < 3) {
                this.validationMessage = 'لطفاً عبارت دقیق‌تری با حداقل ۳ حرف وارد نمایید.'
                return
            }

            this.loading = true
            this.searched = true
            this.contents = []

            axios.post(`${window.location.origin}/book/search`, {
                keyword,
                writerId: Number(this.writerId)
            }).then((response) => {
                this.contents = Array.isArray(response.data) ? response.data : []
            }).catch(() => {
                this.errorMessage = 'متأسفانه هنگام جستجو خطایی رخ داده است.'
            }).finally(() => {
                this.loading = false
            })
        },
        resultUrl(bookId, line) {
            const params = new URLSearchParams({
                p: String(line.id),
                q: line.matched_query || (this.keyword ?? '').trim()
            })

            return `${this.baseUrl}/library/book/${bookId}?${params.toString()}`
        }
    }
}
</script>
<style>

</style>
