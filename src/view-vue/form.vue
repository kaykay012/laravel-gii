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
      >
        DummyRules
        
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
      inputForm: {
        id: 0,
        item_name: '',
        category: '',
        num: '',
        status: 1
      },
      dialog: {
        width: '400px',
        formLabelWidth: '120px'
      }
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
            adminsRequest.addCoinRule(this.inputForm)
              .then(res => {
                if (res.data.code === 200) {
                  this.closeDialog()
                  this.$message.success(res.data.msg)
                  this.$emit('getListData')
                } else {
                  this.$message.error(res.data.msg)
                }
              })
          } else {
            adminsRequest.editCoinRule(this.inputForm)
              .then(res => {
                if (res.data.code === 200) {
                  this.closeDialog()
                  this.$message.success(res.data.msg)
                  this.$emit('getListData')
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
