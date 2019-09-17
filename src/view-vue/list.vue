<template>
  <div class="fillcontain">
    <div class="search_container searchArea">
      <el-form
        :inline="true"
        :model="searchData"
        ref="searchData"
        class="demo-form-inline search-form"
      >
        DummyAttributes
        <el-form-item>
          <el-button type="primary" size="mini" icon="search" @click="getDummyPathNameTitleCaseListData()">查询</el-button>
        </el-form-item>

        <el-form-item class="btnRight">
          <el-button type="primary" size="mini" icon="view" @click="showDialogDummyPathNameTitleCase('add',{})">添加</el-button>
        </el-form-item>
      </el-form>
    </div>
    <div class="table_container">
      <el-table :data="listDataDummyPathNameTitleCase" v-loading="loading" style="width: 100%" align="center">
        DummyList
        <el-table-column fixed="right" label="操作" align="center" width="240">
          <template slot-scope="scope">
            <el-button
              @click="showDialogDummyPathNameTitleCase('edit', scope.row)"
              type="warning"
              icon="edit"
              size="mini"
            >编辑</el-button>
          </template>
        </el-table-column>
      </el-table>
      <pagination
        v-show="last_page > 1"
        :total="total"
        :perPage="perPage"
        :currentPage="currentPage"
        @changeCurrentPage="getCurrentPage"
      ></pagination>
      <inputDummyPathNameTitleCase
        v-if="showDialogDummyPathNameTitleCase.show"
        :isShow="showDialogDummyPathNameTitleCase.show"
        :dialogTitle="showDialogDummyPathNameTitleCase.title"
        :dialogRow="showDialogDummyPathNameTitleCase.dialogRow"
        :dialogType="showDialogDummyPathNameTitleCase.type"
        @closeDialog="hideAddAdminDialog"
        @getDummyPathNameTitleCaseListData="getDummyPathNameTitleCaseListData"
      ></inputDummyPathNameTitleCase>
    </div>
  </div>
</template>
<script>
import adminsRequest from '@/apis/admins'
import pagination from '@/components/pagination'
import inputDummyPathNameTitleCase from './form.vue'
export default {
  components: {
    pagination,
    inputDummyPathNameTitleCase
  },
  data () {
    return {
      loading: true,
      total: undefined, // 总条数
      perPage: undefined, // 每页多少条
      currentPage: undefined, // 当前页
      last_page: 1, // 总页数
      searchData: {
        item_name: '',
        status: '',
        page: 1
      },
      listDataDummyPathNameTitleCase: [],
      showDialogDummyPathNameTitleCase: {
        show: false,
        title: '添加',
        dialogRow: {},
        type: 'add'
      }
    }
  },
  methods: {
    getDummyPathNameTitleCaseListData () {
      adminsRequest.DummyPathNameTitleCaseList(this.searchData).then(res => {
        if (res.data.code === 200) {
          this.loading = false
          this.listDataDummyPathNameTitleCase = res.data.data.data
          this.total = res.data.data.total
          this.perPage = res.data.data.per_page
          this.currentPage = res.data.data.current_page
          this.last_page = res.data.data.last_page
        } else {
          this.loading = false
          this.listDataDummyPathNameTitleCase = []
        }
      })
    },
    getCurrentPage (val) {
      this.searchData.page = val
      this.currentPage = val
      this.getDummyPathNameTitleCaseListData()
    },
    showDialogDummyPathNameTitleCase (action, data) {
      if (action === 'add') {
        this.showDialogDummyPathNameTitleCase.show = true
        this.showDialogDummyPathNameTitleCase.dialogRow = {}
        this.showDialogDummyPathNameTitleCase.title = '添加'
      } else if (action === 'edit') {
        this.showDialogDummyPathNameTitleCase.show = true
        this.showDialogDummyPathNameTitleCase.dialogRow = data
        this.showDialogDummyPathNameTitleCase.title = '编辑'
      }
      this.showDialogDummyPathNameTitleCase.type = action
    },
    hideAddAdminDialog (val) {
      this.showDialogDummyPathNameTitleCase.show = val
    }
  },
  created () {
    this.getDummyPathNameTitleCaseListData()
  }
}
</script>
<style lang="less" scoped>
.table_container {
  padding: 10px;
  background: #fff;
  border-radius: 2px;
}
.el-dialog--small {
  width: 600px !important;
}
.pagination {
  text-align: left;
  margin-top: 10px;
}
.search_container {
  margin-bottom: 20px;
}
.btnRight {
  float: right;
  margin-right: 0px !important;
}
.searchArea {
  background: rgba(255, 255, 255, 1);
  border-radius: 2px;
  padding: 18px 18px 0;
}
</style>
