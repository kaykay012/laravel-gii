<template>
  <el-dialog
    :visible.sync="dialogStatus"
    :title="dialogTitle"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :modal-append-to-body="false"
    @close="closeDialog"
  >
    <div class="form">
      <el-form
        ref="inputForm"
        :model="inputForm"
        :label-width="dialog.formLabelWidth"
        style="margin:10px;width:auto"
      >DummyRules
        <el-form-item class="text_right">
          <el-button @click="dialogStatus = false">取 消</el-button>
          <el-button type="primary" @click="onSubmit('inputForm')">提 交</el-button>
        </el-form-item>
      </el-form>
    </div>
  </el-dialog>
</template>
<script>
import adminsRequest from '@/apis/admins'
export default {
  data () {
    return {
      dialogStatus: this.isShow,
      actionType: this.dialogType,
      inputForm: {DummySearchParams
      },
      dialog: {
        width: '400px',
        formLabelWidth: '120px'
      },
      options: [{
        value: '选项1',
        label: '黄金糕'
      }, {
        value: '选项2',
        label: '双皮奶'
      }, {
        value: '选项3',
        label: '蚵仔煎'
      }]
    }
  },
  methods: {
    closeDialog () {
      this.$emit('closeDialog', false)
    },
    onSubmit (formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          if (this.actionType === 'add') {
            adminsRequest.addDummyPathNameTitleCaseList(this.inputForm)
              .then(res => {
                if (res.data.code === 200) {
                  this.closeDialog()
                  this.$message.success(res.data.msg)
                  this.$emit('getDummyPathNameTitleCaseListData')
                } else {
                  this.$message.error(res.data.msg)
                }
              })
          } else {
            adminsRequest.editDummyPathNameTitleCaseList(this.inputForm)
              .then(res => {
                if (res.data.code === 200) {
                  this.closeDialog()
                  this.$message.success(res.data.msg)
                  this.$emit('getDummyPathNameTitleCaseListData')
                } else {
                  this.$message.error(res.data.msg)
                }
              })
          }
        } else {
          return false
        }
      })
    }
  },
  created () {
    if (this.actionType === 'edit') {
      this.inputForm = this.dialogRow
    }
  },
  props: {
    isShow: Boolean,
    dialogRow: Object,
    dialogTitle: String,
    dialogType: String
  },
  watch: {
    isShow (value) {
      this.dialogStatus = value
    }
  }

}
</script>
<style lang="less" scoped>
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
