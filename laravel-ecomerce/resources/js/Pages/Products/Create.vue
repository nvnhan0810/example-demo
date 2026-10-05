<template>
  <div class="form-container">
    <h2>Thêm Sản Phẩm Mới</h2>
    
    <form @submit.prevent="submit">
      <div class="form-group">
        <label for="name">Tên sản phẩm</label>
        <input 
          id="name" 
          v-model="form.name" 
          type="text" 
          :class="{ 'has-error': form.errors.name }"
        />
        <!-- Hiển thị lỗi validation từ Laravel nếu có -->
        <span v-if="form.errors.name" class="error-msg">{{ form.errors.name }}</span>
      </div>

      <div class="form-group">
        <label for="price">Giá (VNĐ)</label>
        <input 
          id="price" 
          v-model="form.price" 
          type="number" 
          :class="{ 'has-error': form.errors.price }"
        />
        <span v-if="form.errors.price" class="error-msg">{{ form.errors.price }}</span>
      </div>

      <button type="submit" :disabled="form.processing" class="submit-btn">
        {{ form.processing ? 'Đang lưu...' : 'Tạo mới' }}
      </button>
      
      <!-- Thông báo thành công (dựa vào trạng thái recent) -->
      <div v-if="form.recentlySuccessful" class="success-msg">
        Thêm sản phẩm thành công!
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

// Khởi tạo form với các trường trống
const form = useForm({
  name: '',
  price: 0,
})

const submit = () => {
  form.post('/products', {
    preserveScroll: true,
    onSuccess: () => form.reset(), // Reset form sau khi lưu thành công
  })
}
</script>

<style scoped>
/* Bạn có thể đưa CSS này ra dùng chung */
.form-container { max-width: 500px; margin: 40px auto; font-family: sans-serif; }
.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
input.has-error { border-color: #e53e3e; }
.error-msg { color: #e53e3e; font-size: 0.85rem; margin-top: 5px; display: block; }
.success-msg { color: #38a169; margin-top: 15px; text-align: center; font-weight: bold; }
.submit-btn { background: #3182ce; color: white; padding: 10px 20px; border: none; border-radius: 4px; width: 100%; cursor: pointer; }
.submit-btn:disabled { background: #a0aec0; cursor: not-allowed; }
</style>